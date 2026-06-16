<?php
// ============================================================
// API JSON: Procesar Pago con PayPal
// ============================================================
// [PEDAGÓGICO] Endpoint para integración con PayPal REST API.
// Usa cURL para comunicarse con los endpoints de PayPal.
//
// Acciones via POST (action=XXX):
//   crear    -> Crear orden de pago en PayPal y devolver approval_url
//   capturar -> Capturar orden PayPal después de aprobación del usuario
//
// Flujo completo:
// 1. Frontend solicita "crear" -> se crea orden en PayPal
// 2. PayPal devuelve approval_url -> frontend redirige al usuario
// 3. Usuario aprueba en PayPal.com -> PayPal redirige de vuelta
// 4. Frontend solicita "capturar" -> se captura el pago
// 5. Si éxito (COMPLETED): se actualiza pago y confirma descuento inventario
// 6. Si falla: se liberan las reservas de inventario
//
// Configuración en config.php:
//   PAYPAL_MODE      = 'sandbox' (o 'live')
//   PAYPAL_CLIENT_ID = ID del cliente (App REST API)
//   PAYPAL_SECRET    = Secret de la App
//
// Todas responden JSON: {success: bool, data: array, message: string}
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// Forzar respuesta JSON
header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$respuesta = [
    'success' => false,
    'data'    => [],
    'message' => '',
];

try {
    // ============================================================
    // Validar autenticación y método
    // ============================================================
    if (!esta_logueado()) {
        throw new Exception('Debes iniciar sesión para procesar el pago.');
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Usa POST.');
    }

    // Validar CSRF
    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_validar($token)) {
        throw new Exception('Error de seguridad. Token CSRF inválido.');
    }

    $accion = trim($_POST['action'] ?? '');
    if (empty($accion)) {
        throw new Exception('Parámetro action es requerido.');
    }

    // ============================================================
    // Verificar configuración de PayPal
    // ============================================================
    // [PEDAGÓGICO] Si las credenciales de PayPal están vacías,
    // mostramos un error claro en lugar de fallar silenciosamente.
    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_SECRET)) {
        throw new Exception('PayPal no está configurado. Contacta al administrador.');
    }

    // ============================================================
    // Función auxiliar: Obtener Access Token de PayPal
    // ============================================================
    /**
     * Obtiene un access token de OAuth 2.0 desde PayPal.
     *
     * [PEDAGÓGICO] PayPal REST API usa OAuth 2.0 para autenticar
     * las peticiones. El token se obtiene intercambiando Client ID
     * y Secret por un token de acceso temporal (válido ~9 horas).
     *
     * @return string Access token de PayPal
     */
    function obtener_paypal_token(): string
    {
        $url = PAYPAL_MODE === 'live'
            ? 'https://api-m.paypal.com/v1/oauth2/token'
            : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_USERPWD        => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
            CURLOPT_POSTFIELDS     => 'grant_type=client_credentials',
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_TIMEOUT        => 30,
        ]);

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new Exception('Error de conexión con PayPal: ' . $error);
        }

        if ($http_code !== 200) {
            throw new Exception('PayPal devolvió código HTTP ' . $http_code);
        }

        $data = json_decode($response, true);

        if (empty($data['access_token'])) {
            throw new Exception('No se pudo obtener token de PayPal. Verifica tus credenciales.');
        }

        return $data['access_token'];
    }

    // ============================================================
    // Switcher de acciones
    // ============================================================
    switch ($accion) {

        // ============================================================
        // CREAR: Crear una orden de pago en PayPal
        // ============================================================
        case 'crear':
            // [PEDAGÓGICO] Esta acción crea una orden en PayPal asociada
            // al pedido. La orden tiene un monto y descripción. PayPal
            // devuelve un ID de orden (order_id) y una URL de aprobación
            // (approval_url) a la que redirigimos al usuario.

            $orden_id = isset($_POST['orden_id']) ? (int) $_POST['orden_id'] : 0;
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido.');
            }

            // Verificar que la orden pertenezca al usuario logueado
            $stmt = $pdo->prepare("
                SELECT id, numero, total, estado
                FROM pedidos
                WHERE id = :id AND usuario_id = :uid
            ");
            $stmt->execute([
                ':id'  => $orden_id,
                ':uid' => $_SESSION['usuario_id'],
            ]);
            $orden = $stmt->fetch();

            if (!$orden) {
                throw new Exception('Orden no encontrada o no te pertenece.');
            }

            if ($orden['estado'] !== 'pendiente') {
                throw new Exception('La orden ya fue procesada. Estado actual: ' . $orden['estado']);
            }

            // Verificar que no exista ya un pago para esta orden
            $stmt = $pdo->prepare("SELECT id, estado FROM pagos WHERE pedido_id = :pid");
            $stmt->execute([':pid' => $orden_id]);
            $pago_existente = $stmt->fetch();

            if ($pago_existente && $pago_existente['estado'] === 'completado') {
                throw new Exception('Esta orden ya fue pagada.');
            }

            // Obtener token de PayPal
            $access_token = obtener_paypal_token();

            // URL base de PayPal según modo
            $api_base = PAYPAL_MODE === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // ============================================================
            // Crear orden en PayPal
            // ============================================================
            // [PEDAGÓGICO] El cuerpo de la petición incluye:
            // - intent: CAPTURE (captura directa, no autorización)
            // - purchase_units: los items/ montos a cobrar
            // - application_context: URLs de retorno (éxito/cancelación)
            $body = json_encode([
                'intent' => 'CAPTURE',
                'purchase_units' => [
                    [
                        'reference_id' => $orden['numero'],
                        'description'  => 'Compra en ' . SITE_NAME . ' - Orden ' . $orden['numero'],
                        'amount' => [
                            'currency_code' => 'CLP',
                            'value'         => number_format((float) $orden['total'], 0, '.', ''),
                        ],
                    ],
                ],
                'application_context' => [
                    'brand_name'          => SITE_NAME,
                    'landing_page'        => 'NO_PREFERENCE',
                    'user_action'         => 'PAY_NOW',
                    'return_url'          => SITE_URL . '/api/pago.php?action=capturar',
                    'cancel_url'          => SITE_URL . '/checkout.php?cancelado=1',
                ],
            ]);

            $ch = curl_init($api_base . '/v2/checkout/orders');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => $body,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 30,
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception('Error al conectar con PayPal: ' . $curl_error);
            }

            $paypal_data = json_decode($response, true);

            if ($http_code !== 201 || empty($paypal_data['id'])) {
                $detalle_error = $paypal_data['message'] ?? ($paypal_data['error_description'] ?? 'Error desconocido');
                error_log('PayPal Create Order Error: ' . print_r($paypal_data, true));
                throw new Exception('Error al crear orden en PayPal: ' . $detalle_error);
            }

            $paypal_order_id = $paypal_data['id'];

            // Extraer approval_url del array de links
            $approval_url = '';
            foreach ($paypal_data['links'] as $link) {
                if ($link['rel'] === 'approve') {
                    $approval_url = $link['href'];
                    break;
                }
            }

            if (empty($approval_url)) {
                throw new Exception('No se encontró URL de aprobación en la respuesta de PayPal.');
            }

            // ============================================================
            // Guardar referencia de pago en la BD
            // ============================================================
            // [PEDAGÓGICO] Guardamos el ID de la orden de PayPal
            // como referencia_pasarela para usarlo al capturar.
            if ($pago_existente) {
                // Actualizar pago existente (ej: reintento)
                $stmt = $pdo->prepare("
                    UPDATE pagos
                    SET referencia_pasarela = :ref, fecha_creacion = NOW(), estado = 'pendiente'
                    WHERE pedido_id = :pid
                ");
                $stmt->execute([
                    ':ref' => $paypal_order_id,
                    ':pid' => $orden_id,
                ]);
            } else {
                // Crear nuevo registro de pago
                $stmt = $pdo->prepare("
                    INSERT INTO pagos (pedido_id, metodo, estado, monto, referencia_pasarela, fecha_creacion)
                    VALUES (:pedido_id, 'paypal', 'pendiente', :monto, :ref, NOW())
                ");
                $stmt->execute([
                    ':pedido_id' => $orden_id,
                    ':monto'     => $orden['total'],
                    ':ref'       => $paypal_order_id,
                ]);
            }

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'paypal_order_id' => $paypal_order_id,
                'approval_url'    => $approval_url,
                'orden_id'        => $orden_id,
                'numero_orden'    => $orden['numero'],
            ];
            $respuesta['message'] = '✅ Orden de pago creada en PayPal. Redirigiendo...';
            break;

        // ============================================================
        // CAPTURAR: Capturar orden PayPal después de aprobación
        // ============================================================
        case 'capturar':
            // [PEDAGÓGICO] Esta acción se llama cuando PayPal redirige
            // al usuario de vuelta a nuestra tienda después de aprobar
            // el pago. Capturamos (completamos) la orden en PayPal.

            $paypal_order_id = $_POST['paypal_order_id'] ?? $_GET['token'] ?? '';
            $orden_id        = isset($_POST['orden_id']) ? (int) $_POST['orden_id'] : (isset($_GET['orden_id']) ? (int) $_GET['orden_id'] : 0);

            if (empty($paypal_order_id)) {
                throw new Exception('ID de orden de PayPal no proporcionado.');
            }

            if ($orden_id <= 0) {
                throw new Exception('ID de orden interna no proporcionado.');
            }

            // Verificar que la orden pertenezca al usuario
            $stmt = $pdo->prepare("
                SELECT id, numero, total, estado
                FROM pedidos
                WHERE id = :id AND usuario_id = :uid
            ");
            $stmt->execute([
                ':id'  => $orden_id,
                ':uid' => $_SESSION['usuario_id'],
            ]);
            $orden = $stmt->fetch();

            if (!$orden) {
                throw new Exception('Orden no encontrada o no te pertenece.');
            }

            // Obtener token de PayPal
            $access_token = obtener_paypal_token();

            $api_base = PAYPAL_MODE === 'live'
                ? 'https://api-m.paypal.com'
                : 'https://api-m.sandbox.paypal.com';

            // ============================================================
            // Capturar la orden en PayPal
            // ============================================================
            // [PEDAGÓGICO] POST /v2/checkout/orders/{id}/capture
            // confirma la captura del pago. PayPal devuelve el estado
            // de la transacción.
            $ch = curl_init($api_base . '/v2/checkout/orders/' . urlencode($paypal_order_id) . '/capture');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => '{}',
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    'Authorization: Bearer ' . $access_token,
                    'Accept: application/json',
                ],
                CURLOPT_TIMEOUT        => 30,
            ]);

            $response = curl_exec($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curl_error = curl_error($ch);
            curl_close($ch);

            if ($curl_error) {
                throw new Exception('Error al capturar pago en PayPal: ' . $curl_error);
            }

            $paypal_data = json_decode($response, true);

            // ============================================================
            // Procesar resultado de la captura
            // ============================================================
            // [PEDAGÓGICO] PayPal puede devolver COMPLETED (pago exitoso)
            // u otros estados. Solo COMPLETED confirma el pago.
            $paypal_estado = $paypal_data['status'] ?? '';

            if ($http_code !== 201 && $http_code !== 200) {
                error_log('PayPal Capture Error: ' . print_r($paypal_data, true));
                throw new Exception('Error al capturar el pago. Código: ' . $http_code);
            }

            if ($paypal_estado === 'COMPLETED') {
                // ============================================================
                // PAGO EXITOSO
                // ============================================================
                // Obtener ID de transacción de PayPal
                $transaction_id = '';
                if (!empty($paypal_data['purchase_units'][0]['payments']['captures'][0]['id'])) {
                    $transaction_id = $paypal_data['purchase_units'][0]['payments']['captures'][0]['id'];
                }

                // Iniciar transacción para actualizar BD
                $pdo->beginTransaction();

                try {
                    // Actualizar pago a completado
                    $stmt = $pdo->prepare("
                        UPDATE pagos
                        SET estado = 'completado', fecha_pago = NOW(), referencia_pasarela = :ref
                        WHERE pedido_id = :pid AND metodo = 'paypal'
                    ");
                    $stmt->execute([
                        ':ref' => $transaction_id ?: $paypal_order_id,
                        ':pid' => $orden_id,
                    ]);

                    // Insertar en historial_pagos
                    $stmt_historial = $pdo->prepare("
                        INSERT INTO historial_pagos (pago_id, estado_anterior, estado_nuevo, observacion, fecha)
                        VALUES (
                            (SELECT id FROM pagos WHERE pedido_id = :pid),
                            'pendiente',
                            'completado',
                            :observacion,
                            NOW()
                        )
                    ");
                    $stmt_historial->execute([
                        ':pid'         => $orden_id,
                        ':observacion' => 'Pago confirmado vía PayPal. Transacción: ' . ($transaction_id ?: $paypal_order_id),
                    ]);

                    // ============================================================
                    // Confirmar descuento de inventario
                    // ============================================================
                    // [PEDAGÓGICO] Al confirmar el pago, transformamos las reservas
                    // en descuentos reales de stock:
                    // 1. Reducimos cantidad (stock disponible)
                    // 2. Reducimos cantidad_reservada (libera la reserva)
                    // 3. Actualizamos estado de reservas a 'confirmada'
                    // 4. Registramos movimiento de salida
                    $stmt_reservas = $pdo->prepare("
                        SELECT producto_id, cantidad
                        FROM reservas_inventario
                        WHERE orden_id = :oid AND estado = 'activa'
                    ");
                    $stmt_reservas->execute([':oid' => $orden_id]);
                    $reservas = $stmt_reservas->fetchAll();

                    $stmt_descuento = $pdo->prepare("
                        UPDATE inventario
                        SET cantidad = cantidad - :cant,
                            cantidad_reservada = cantidad_reservada - :cant
                        WHERE producto_id = :pid
                    ");

                    $stmt_confirmar_reserva = $pdo->prepare("
                        UPDATE reservas_inventario
                        SET estado = 'confirmada'
                        WHERE orden_id = :oid AND estado = 'activa'
                    ");

                    $stmt_mov_salida = $pdo->prepare("
                        INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, referencia, fecha)
                        VALUES (:producto_id, 'salida', :cantidad, :referencia, NOW())
                    ");

                    foreach ($reservas as $reserva) {
                        // Descontar stock real y reserva
                        $stmt_descuento->execute([
                            ':cant' => (int) $reserva['cantidad'],
                            ':pid'  => $reserva['producto_id'],
                        ]);

                        // Registrar movimiento de salida
                        $stmt_mov_salida->execute([
                            ':producto_id' => $reserva['producto_id'],
                            ':cantidad'    => (int) $reserva['cantidad'],
                            ':referencia'  => $orden['numero'],
                        ]);
                    }

                    // Confirmar reservas
                    $stmt_confirmar_reserva->execute([':oid' => $orden_id]);

                    // Actualizar estado del pedido a 'confirmado'
                    $stmt = $pdo->prepare("UPDATE pedidos SET estado = 'confirmado' WHERE id = :id");
                    $stmt->execute([':id' => $orden_id]);

                    $pdo->commit();

                    $respuesta['success'] = true;
                    $respuesta['data'] = [
                        'orden_id'        => $orden_id,
                        'numero_orden'    => $orden['numero'],
                        'estado_pedido'   => 'confirmado',
                        'transaction_id'  => $transaction_id,
                    ];
                    $respuesta['message'] = '✅ ¡Pago recibido exitosamente! Tu orden ' . $orden['numero'] . ' está confirmada.';
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }

            } else {
                // ============================================================
                // PAGO NO COMPLETADO: Liberar reservas
                // ============================================================
                // [PEDAGÓGICO] Si PayPal devuelve un estado diferente a
                // COMPLETED (ej: VOIDED, FAILED), liberamos las reservas
                // para que el stock vuelva a estar disponible.
                $pdo->beginTransaction();

                try {
                    // Liberar reservas
                    $stmt_reservas = $pdo->prepare("
                        SELECT producto_id, cantidad
                        FROM reservas_inventario
                        WHERE orden_id = :oid AND estado = 'activa'
                    ");
                    $stmt_reservas->execute([':oid' => $orden_id]);
                    $reservas = $stmt_reservas->fetchAll();

                    $stmt_liberar = $pdo->prepare("
                        UPDATE inventario
                        SET cantidad_reservada = cantidad_reservada - :cant
                        WHERE producto_id = :pid
                    ");

                    $stmt_mov_liberacion = $pdo->prepare("
                        INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, referencia, fecha)
                        VALUES (:producto_id, 'liberacion', :cantidad, :referencia, NOW())
                    ");

                    $stmt_update_reserva = $pdo->prepare("
                        UPDATE reservas_inventario
                        SET estado = 'liberada'
                        WHERE orden_id = :oid AND estado = 'activa'
                    ");

                    foreach ($reservas as $reserva) {
                        $stmt_liberar->execute([
                            ':cant' => (int) $reserva['cantidad'],
                            ':pid'  => $reserva['producto_id'],
                        ]);

                        $stmt_mov_liberacion->execute([
                            ':producto_id' => $reserva['producto_id'],
                            ':cantidad'    => (int) $reserva['cantidad'],
                            ':referencia'  => $orden['numero'] . '-payment-failed',
                        ]);
                    }

                    $stmt_update_reserva->execute([':oid' => $orden_id]);

                    // Actualizar pago a rechazado
                    $stmt = $pdo->prepare("
                        UPDATE pagos
                        SET estado = 'rechazado', referencia_pasarela = :ref
                        WHERE pedido_id = :pid AND metodo = 'paypal'
                    ");
                    $stmt->execute([
                        ':ref' => $paypal_order_id,
                        ':pid' => $orden_id,
                    ]);

                    $pdo->commit();

                    $respuesta['success'] = false;
                    $respuesta['data'] = [
                        'paypal_status' => $paypal_estado,
                    ];
                    $respuesta['message'] = "❌ El pago no fue completado. PayPal respondió: {$paypal_estado}. Las reservas han sido liberadas.";
                } catch (Exception $e) {
                    $pdo->rollBack();
                    throw $e;
                }
            }
            break;

        // ============================================================
        // Acción no reconocida
        // ============================================================
        default:
            throw new Exception("Acción desconocida: {$accion}");
    }

} catch (Exception $e) {
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
