<?php
// ============================================================
// API JSON: Procesar Checkout (Confirmar Compra)
// ============================================================
// Forzar respuesta JSON inmediatamente
@header('Content-Type: application/json; charset=utf-8');
@header('Cache-Control: no-cache, no-store, must-revalidate');
@header('Pragma: no-cache');
@header('Expires: 0');

// Iniciar output buffer para capturar errores
ob_start();

// Configurar reporte de errores
error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// Limpiar cualquier output accidental
ob_end_clean();

// Volver a configurar JSON header
@header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$respuesta = [
    'success' => false,
    'data'    => [],
    'message' => '',
];

function crear_orden_paypal_desde_pedido(PDO $pdo, int $pedido_id, string $numero_orden, float $total): array
{
    if (empty(PAYPAL_CLIENT_ID) || empty(PAYPAL_SECRET)) {
        throw new Exception('PayPal no está configurado.');
    }

    $token_url = PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com/v1/oauth2/token'
        : 'https://api-m.sandbox.paypal.com/v1/oauth2/token';

    $ch = curl_init($token_url);
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
    $curl_error = curl_error($ch);
    curl_close($ch);

    if ($curl_error) {
        throw new Exception('Error al conectar con PayPal: ' . $curl_error);
    }

    if ($http_code !== 200) {
        throw new Exception('PayPal devolvió código HTTP ' . $http_code);
    }

    $token_data = json_decode($response, true);
    if (empty($token_data['access_token'])) {
        throw new Exception('No se pudo obtener el token de PayPal.');
    }

    $access_token = $token_data['access_token'];
    $api_base = PAYPAL_MODE === 'live'
        ? 'https://api-m.paypal.com'
        : 'https://api-m.sandbox.paypal.com';

    $body = json_encode([
        'intent' => 'CAPTURE',
        'purchase_units' => [[
            'reference_id' => $numero_orden,
            'description'  => 'Compra en ' . SITE_NAME . ' - Orden ' . $numero_orden,
            'amount' => [
                'currency_code' => PAYPAL_CURRENCY,
                'value'         => number_format((float) $total, 2, '.', ''),
            ],
        ]],
        'application_context' => [
            'brand_name'   => SITE_NAME,
            'landing_page' => 'NO_PREFERENCE',
            'user_action'  => 'PAY_NOW',
            'return_url'   => SITE_URL . '/api/pago.php?action=capturar&orden_id=' . $pedido_id,
            'cancel_url'   => SITE_URL . '/checkout.php?cancelado=1',
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
        throw new Exception('Error al crear la orden en PayPal: ' . $curl_error);
    }

    $paypal_data = json_decode($response, true);
    if ($http_code !== 201 || empty($paypal_data['id'])) {
        $detalle_error = $paypal_data['message'] ?? ($paypal_data['error_description'] ?? 'Error desconocido');
        throw new Exception('Error al crear orden en PayPal: ' . $detalle_error);
    }

    $paypal_order_id = $paypal_data['id'];
    $approval_url = '';
    foreach ($paypal_data['links'] ?? [] as $link) {
        if (($link['rel'] ?? '') === 'approve') {
            $approval_url = $link['href'];
            break;
        }
    }

    if (empty($approval_url)) {
        throw new Exception('No se encontró la URL de aprobación de PayPal.');
    }

    $stmt = $pdo->prepare("SELECT id FROM pagos WHERE pedido_id = :pid");
    $stmt->execute([':pid' => $pedido_id]);
    $pago_existente = $stmt->fetch();

    if ($pago_existente) {
        $stmt = $pdo->prepare("UPDATE pagos SET referencia_pasarela = :ref, estado = 'pendiente', fecha_creacion = NOW() WHERE pedido_id = :pid");
        $stmt->execute([':ref' => $paypal_order_id, ':pid' => $pedido_id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO pagos (pedido_id, metodo, estado, monto, referencia_pasarela, fecha_creacion) VALUES (:pedido_id, 'paypal', 'pendiente', :monto, :ref, NOW())");
        $stmt->execute([
            ':pedido_id' => $pedido_id,
            ':monto'     => $total,
            ':ref'       => $paypal_order_id,
        ]);
    }

    return [
        'paypal_order_id' => $paypal_order_id,
        'approval_url'    => $approval_url,
    ];
}

try {
    // ============================================================
    // 1. Verificar si hay usuario logueado o pago como invitado
    // ============================================================
    $usuario_id = null;
    if (esta_logueado()) {
        $usuario_id = (int) $_SESSION['usuario_id'];
    }

    // ============================================================
    // 2. Validar método POST y CSRF
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Usa POST.');
    }

    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_validar($token)) {
        throw new Exception('Error de seguridad. Token CSRF inválido.');
    }

    // ============================================================
    // 3. Validar datos de dirección de envío
    // ============================================================
    $calle        = trim($_POST['calle'] ?? '');
    $ciudad       = trim($_POST['ciudad'] ?? '');
    $region       = trim($_POST['region'] ?? '');
    $codigo_postal = trim($_POST['codigo_postal'] ?? '');
    $notas        = trim($_POST['notas'] ?? '');

    if (empty($calle) || empty($ciudad) || empty($region)) {
        throw new Exception('Completa todos los campos obligatorios de la dirección (calle, ciudad, región).');
    }

    // Construir dirección completa
    $direccion_envio = "{$calle}, {$ciudad}, {$region}";
    if (!empty($codigo_postal)) {
        $direccion_envio .= ", CP: {$codigo_postal}";
    }

    // ============================================================
    // 4. Obtener items del carrito del usuario o invitado
    // ============================================================
    if ($usuario_id !== null) {
        // Usuario autenticado: carrito persistente en BD
        $stmt = $pdo->prepare("
            SELECT ic.*, p.nombre, p.precio, p.precio_descuento
            FROM items_carrito ic
            JOIN productos p ON p.id = ic.producto_id
            WHERE ic.usuario_id = :uid
            ORDER BY ic.fecha_agregado ASC
        ");
        $stmt->execute([':uid' => $usuario_id]);
        $items = $stmt->fetchAll();
    } else {
        // Invitado: carrito en sesión
        if (empty($_SESSION['carrito'])) {
            throw new Exception('Tu carrito está vacío.');
        }

        $ids = array_keys($_SESSION['carrito']);
        if (empty($ids)) {
            throw new Exception('Tu carrito está vacío.');
        }

        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $pdo->prepare("
            SELECT p.id as producto_id, p.nombre, p.precio, p.precio_descuento
            FROM productos p
            WHERE p.id IN ($placeholders) AND p.activo = 1
        ");
        $stmt->execute($ids);
        $productos_db = $stmt->fetchAll();

        $items = [];
        foreach ($productos_db as $prod) {
            $sesion_item = $_SESSION['carrito'][$prod['producto_id']];
            $precio = !empty($prod['precio_descuento'])
                ? $prod['precio_descuento']
                : $prod['precio'];

            $items[] = [
                'producto_id'     => $prod['producto_id'],
                'cantidad'        => $sesion_item['cantidad'],
                'precio_unitario' => $precio,
                'nombre'          => $prod['nombre'],
            ];
        }
    }

    if (empty($items)) {
        throw new Exception('Tu carrito está vacío.');
    }

    // ============================================================
    // 5. Re-validar stock de todos los items
    // ============================================================
    // [PEDAGÓGICO] Antes de crear la orden, verificamos que cada
    // producto tenga stock suficiente. Esto evita que un producto
    // se agote entre que el usuario lo agregó al carrito y ahora.
    $errores_stock = [];

    foreach ($items as $item) {
        $stmt = $pdo->prepare("
            SELECT cantidad, cantidad_reservada,
                   (cantidad - cantidad_reservada) as stock_efectivo
            FROM inventario
            WHERE producto_id = :pid
        ");
        $stmt->execute([':pid' => $item['producto_id']]);
        $inventario = $stmt->fetch();

        $stock_efectivo = (int) ($inventario['stock_efectivo'] ?? 0);

        if ($stock_efectivo < (int) $item['cantidad']) {
            $errores_stock[] = "{$item['nombre']}: solicitaste {$item['cantidad']}, disponible {$stock_efectivo}";
        }
    }

    if (!empty($errores_stock)) {
        $mensaje = 'Stock insuficiente para los siguientes productos:<br>';
        $mensaje .= implode('<br>', $errores_stock);
        throw new Exception($mensaje);
    }

    // ============================================================
    // 6. Calcular totales
    // ============================================================
    // [PEDAGÓGICO] El precio_unitario en items_carrito es el precio
    // que se congeló al agregar (con descuento si aplicaba).
    $items_totales = [];
    foreach ($items as $item) {
        $items_totales[] = [
            'precio'   => $item['precio_unitario'],
            'cantidad' => $item['cantidad'],
        ];
    }
    $totales = calcular_totales($items_totales);

    // ============================================================
    // 7 - 13: TRANSACCIÓN: Crear orden, detalles, reservas
    // ============================================================
    // [PEDAGÓGICO] Usamos BEGIN TRANSACTION / COMMIT / ROLLBACK
    // para asegurar que TODAS las operaciones se ejecuten o NINGUNA.
    // Si algo falla a medio camino, hacemos ROLLBACK y la BD
    // queda en el estado anterior (sin datos parciales).

    $pdo->beginTransaction();

    try {
        // ============================================================
        // 7. Generar número de orden
        // ============================================================
        $numero_orden = generar_numero_orden($pdo);

        // ============================================================
        // 8. Insertar pedido
        // ============================================================
        // [PEDAGÓGICO] Al crear una orden desde checkout sin pasarela,
        // la orden queda en estado 'pendiente' y se procesa luego.
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (numero, usuario_id, estado, subtotal, iva, costo_envio, total, direccion_envio, notas, fecha_creacion)
            VALUES (:numero, :usuario_id, 'pendiente', :subtotal, :iva, :envio, :total, :direccion, :notas, NOW())
        ");
        $stmt->execute([
            ':numero'       => $numero_orden,
            ':usuario_id'   => $usuario_id,
            ':subtotal'     => $totales['subtotal'],
            ':iva'          => $totales['iva'],
            ':envio'        => $totales['envio'],
            ':total'        => $totales['total'],
            ':direccion'    => $direccion_envio,
            ':notas'        => $notas,
        ]);

        $pedido_id = (int) $pdo->lastInsertId();

        // ============================================================
        // 9. Insertar detalles del pedido
        // ============================================================
        // [PEDAGÓGICO] Congelamos nombre_producto y precio_unitario
        // al momento de la compra. Si el producto cambia de precio
        // o nombre después, el pedido conserva los valores originales.
        $stmt_detalle = $pdo->prepare("
            INSERT INTO detalles_pedido (pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal)
            VALUES (:pedido_id, :producto_id, :nombre, :cantidad, :precio, :subtotal)
        ");

        foreach ($items as $item) {
            $subtotal_item = (float) $item['precio_unitario'] * (int) $item['cantidad'];
            $stmt_detalle->execute([
                ':pedido_id'   => $pedido_id,
                ':producto_id' => $item['producto_id'],
                ':nombre'      => $item['nombre'],
                ':cantidad'    => (int) $item['cantidad'],
                ':precio'      => $item['precio_unitario'],
                ':subtotal'    => $subtotal_item,
            ]);
        }

        // ============================================================
        // 10. Reservar inventario con expiración
        // ============================================================
        // [PEDAGÓGICO] Reservar stock significa incrementar
        // cantidad_reservada en inventario y crear un registro en
        // reservas_inventario con fecha de expiración. Si el pago
        // no se confirma en 10 minutos, la reserva expira y el
        // stock vuelve a estar disponible.
        $fecha_expiracion = date('Y-m-d H:i:s', time() + (RESERVA_MINUTOS * 60));

        $stmt_inv = $pdo->prepare("
            UPDATE inventario
            SET cantidad_reservada = cantidad_reservada + :cant
            WHERE producto_id = :pid
        ");

        $stmt_reserva = $pdo->prepare("
            INSERT INTO reservas_inventario (orden_id, producto_id, cantidad, estado, fecha_creacion, fecha_expiracion)
            VALUES (:orden_id, :producto_id, :cantidad, 'activa', NOW(), :fecha_expiracion)
        ");

        foreach ($items as $item) {
            // Actualizar cantidad_reservada en inventario
            $stmt_inv->execute([
                ':cant' => (int) $item['cantidad'],
                ':pid'  => $item['producto_id'],
            ]);

            // Crear registro de reserva
            $stmt_reserva->execute([
                ':orden_id'         => $pedido_id,
                ':producto_id'      => $item['producto_id'],
                ':cantidad'         => (int) $item['cantidad'],
                ':fecha_expiracion' => $fecha_expiracion,
            ]);
        }

        // ============================================================
        // 11. Registrar movimientos_inventario (tipo 'reserva')
        // ============================================================
        // [PEDAGÓGICO] Cada cambio en el inventario debe quedar
        // registrado para auditoría. Los movimientos tipo 'reserva'
        // documentan que se apartó stock para una orden pendiente.
        $stmt_mov = $pdo->prepare("
            INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, referencia, fecha)
            VALUES (:producto_id, 'reserva', :cantidad, :referencia, NOW())
        ");

        foreach ($items as $item) {
            $stmt_mov->execute([
                ':producto_id' => $item['producto_id'],
                ':cantidad'    => (int) $item['cantidad'],
                ':referencia'  => $numero_orden,
            ]);
        }

        // ============================================================
        // 12. Limpiar carrito después de crear la orden
        // ============================================================
        // [PEDAGÓGICO] El carrito se vacía SOLO si todo salió bien.
        // Si hay ROLLBACK, los items del carrito se conservan.
        if ($usuario_id !== null) {
            $stmt = $pdo->prepare("DELETE FROM items_carrito WHERE usuario_id = :uid");
            $stmt->execute([':uid' => $usuario_id]);
        } else {
            unset($_SESSION['carrito']);
        }

        // ============================================================
        // 13. COMMIT: Confirmar la transacción
        // ============================================================
        $pdo->commit();

        // ============================================================
        // Respuesta exitosa
        // ============================================================
        $_SESSION['ultima_orden'] = $numero_orden;
        $_SESSION['pending_order_id'] = $pedido_id;

        $paypal_approval_url = '';
        $paypal_order_id = '';

        if (!empty(PAYPAL_CLIENT_ID) && !empty(PAYPAL_SECRET)) {
            try {
                $paypal_data = crear_orden_paypal_desde_pedido($pdo, $pedido_id, $numero_orden, (float) $totales['total']);
                $paypal_approval_url = $paypal_data['approval_url'] ?? '';
                $paypal_order_id = $paypal_data['paypal_order_id'] ?? '';
            } catch (Exception $e) {
                error_log('PayPal init failed: ' . $e->getMessage());
            }
        }

        $respuesta['success'] = true;
        $respuesta['data'] = [
            'orden_id'        => $pedido_id,
            'numero_orden'    => $numero_orden,
            'total'           => (float) $totales['total'],
            'subtotal'        => (float) $totales['subtotal'],
            'iva'             => (float) $totales['iva'],
            'envio'           => (float) $totales['envio'],
            'paypal_order_id' => $paypal_order_id,
            'approval_url'    => $paypal_approval_url,
        ];
        $respuesta['message'] = '✅ ¡Compra realizada con éxito! Número de orden: ' . $numero_orden;

    } catch (Exception $e) {
        // ============================================================
        // ROLLBACK: Revertir todos los cambios
        // ============================================================
        // [PEDAGÓGICO] Si algo falla a medio camino, deshacemos todo
        // para no dejar datos huérfanos.
        $pdo->rollBack();
        throw $e; // Relanzar para que el catch exterior lo maneje
    }

} catch (Exception $e) {
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

// Asegurar que respondemos JSON siempre
@header('Content-Type: application/json; charset=utf-8');
ob_end_clean();
echo json_encode($respuesta, JSON_UNESCAPED_UNICODE);
exit;