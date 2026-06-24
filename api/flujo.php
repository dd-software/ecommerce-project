<?php
// ============================================================
// API JSON: Orquestador del Flujo Completo de Compra (OBJ-08)
// ============================================================
// [PEDAGÓGICO] Este endpoint NO ejecuta lógica de negocio nueva:
// orquesta y consulta el estado E2E de una orden combinando los
// resultados de los módulos de Checkout, Inventario y Pago.
//
// Sirve a dos propósitos:
//   1. Página de éxito / cuenta del cliente: mostrar un resumen
//      consolidado del flujo (¿pagué?, ¿tengo reserva?, ¿stock
//      ya descontado?).
//   2. Pruebas E2E e integración: verificar de un solo vistazo
//      que cada etapa del flujo dejó la BD en el estado esperado.
//
// Acciones (GET):
//   estado  -> Devuelve el estado consolidado de una orden por
//              numero (ej: ORD-2026-00001) o por id.
//   resumen -> Resumen agregado de órdenes del usuario logueado
//              (cantidad por estado del pedido y del pago).
//
// Acciones (POST):
//   cancelar -> Cancela una orden pendiente del propio usuario,
//               liberando sus reservas activas.
//
// Respuesta: {success: bool, data: array, message: string}
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = getDB();
$respuesta = [
    'success' => false,
    'data'    => [],
    'message' => '',
];

// [PEDAGÓGICO - OBJ-06] Auto-limpieza de reservas vencidas en
// cada consulta del orquestador: lo que reportemos al usuario
// debe reflejar el estado más actualizado posible.
liberar_reservas_expiradas($pdo);

try {
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'GET') {
        $accion = trim($_GET['action'] ?? 'estado');
    } elseif ($method === 'POST') {
        $content_type = $_SERVER['CONTENT_TYPE'] ?? '';
        if (strpos($content_type, 'application/json') !== false) {
            $input = json_decode(file_get_contents('php://input'), true);
            if ($input === null && json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('JSON inválido en el cuerpo de la petición.');
            }
            $_POST = array_merge($_POST, $input ?? []);
        }
        $accion = trim($_POST['action'] ?? '');
    } else {
        throw new Exception('Método HTTP no permitido.');
    }

    if (empty($accion)) {
        throw new Exception('Parámetro "action" es requerido.');
    }

    switch ($accion) {

        // ============================================================
        // ESTADO: Snapshot E2E de una orden (GET)
        // ============================================================
        // [PEDAGÓGICO] Junta en una sola respuesta lo que pasó en
        // cada módulo del flujo de compra para esa orden:
        //   - Pedido (estado, totales, dirección)
        //   - Detalles del pedido (productos congelados)
        //   - Reservas de inventario (activas/expiradas/confirmadas)
        //   - Pago (estado PayPal, referencia, fecha)
        //   - Movimientos de inventario asociados a la orden
        //   - Etapa del flujo: carrito_creado, pendiente_pago,
        //     pagado_confirmado, expirado, cancelado.
        // ============================================================
        case 'estado':
            $numero   = trim($_GET['numero'] ?? '');
            $orden_id = isset($_GET['orden_id']) ? (int) $_GET['orden_id'] : 0;

            if ($numero === '' && $orden_id <= 0) {
                throw new Exception('Debes indicar "numero" o "orden_id".');
            }

            // 1. Pedido
            $sql = "SELECT * FROM pedidos WHERE ";
            $params = [];
            if ($numero !== '') {
                $sql .= 'numero = :numero';
                $params[':numero'] = $numero;
            } else {
                $sql .= 'id = :id';
                $params[':id'] = $orden_id;
            }

            // [PEDAGÓGICO] Si hay usuario logueado y no es admin,
            // limitamos la consulta a sus propias órdenes — un
            // cliente no debe ver el estado de pedidos ajenos.
            if (esta_logueado() && !es_admin()) {
                $sql .= ' AND usuario_id = :uid';
                $params[':uid'] = (int) $_SESSION['usuario_id'];
            }

            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $pedido = $stmt->fetch();

            if (!$pedido) {
                throw new Exception('Orden no encontrada o no tienes permiso para verla.');
            }

            $pedido_id = (int) $pedido['id'];

            // 2. Detalles
            $stmt = $pdo->prepare("
                SELECT producto_id, nombre_producto, cantidad,
                       precio_unitario, subtotal
                FROM detalles_pedido
                WHERE pedido_id = :pid
                ORDER BY id ASC
            ");
            $stmt->execute([':pid' => $pedido_id]);
            $detalles = $stmt->fetchAll();

            // 3. Reservas (todos los estados)
            $stmt = $pdo->prepare("
                SELECT id, producto_id, cantidad, estado,
                       fecha_creacion, fecha_expiracion
                FROM reservas_inventario
                WHERE orden_id = :oid
                ORDER BY id ASC
            ");
            $stmt->execute([':oid' => $pedido_id]);
            $reservas = $stmt->fetchAll();

            $resumen_reservas = [
                'activa' => 0, 'liberada' => 0,
                'confirmada' => 0, 'expirada' => 0,
            ];
            foreach ($reservas as $r) {
                $estado = $r['estado'];
                if (isset($resumen_reservas[$estado])) {
                    $resumen_reservas[$estado]++;
                }
            }

            // 4. Pago
            $stmt = $pdo->prepare("
                SELECT id, metodo, estado, monto,
                       referencia_pasarela, fecha_creacion, fecha_pago
                FROM pagos
                WHERE pedido_id = :pid
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([':pid' => $pedido_id]);
            $pago = $stmt->fetch() ?: null;

            // 5. Movimientos de inventario referenciando esta orden
            $stmt = $pdo->prepare("
                SELECT producto_id, tipo_movimiento, cantidad,
                       referencia, fecha
                FROM movimientos_inventario
                WHERE referencia = :numero
                   OR referencia LIKE :patron_orden
                ORDER BY fecha ASC, id ASC
            ");
            $stmt->execute([
                ':numero'        => $pedido['numero'],
                ':patron_orden'  => '%orden #' . $pedido_id . '%',
            ]);
            $movimientos = $stmt->fetchAll();

            // 6. Determinar la etapa actual del flujo E2E
            // [PEDAGÓGICO] Esta es la "vista de pájaro" que ayuda
            // a entender en qué punto del flujo está la orden sin
            // tener que inspeccionar cada tabla manualmente.
            $etapa = 'pendiente_pago';
            $estado_pedido = $pedido['estado'];
            $estado_pago   = $pago['estado'] ?? null;

            if ($estado_pedido === 'cancelado') {
                $etapa = 'cancelado';
            } elseif ($estado_pedido === 'reembolsado') {
                $etapa = 'reembolsado';
            } elseif ($estado_pago === 'completado'
                   && in_array($estado_pedido, ['confirmado', 'en_proceso', 'enviado', 'entregado'], true)) {
                $etapa = 'pagado_confirmado';
            } elseif ($resumen_reservas['activa'] === 0
                   && $resumen_reservas['confirmada'] === 0
                   && $resumen_reservas['expirada'] > 0) {
                $etapa = 'expirado';
            } elseif ($resumen_reservas['activa'] > 0) {
                $etapa = 'reserva_activa';
            } elseif ($estado_pedido === 'pendiente' && empty($pago)) {
                $etapa = 'pendiente_pago';
            }

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'pedido' => [
                    'id'              => $pedido_id,
                    'numero'          => $pedido['numero'],
                    'estado'          => $estado_pedido,
                    'subtotal'        => (float) $pedido['subtotal'],
                    'iva'             => (float) $pedido['iva'],
                    'costo_envio'     => (float) $pedido['costo_envio'],
                    'total'           => (float) $pedido['total'],
                    'direccion_envio' => $pedido['direccion_envio'],
                    'fecha_creacion'  => $pedido['fecha_creacion'],
                ],
                'detalles'         => $detalles,
                'reservas'         => $reservas,
                'resumen_reservas' => $resumen_reservas,
                'pago'             => $pago,
                'movimientos'      => $movimientos,
                'etapa_flujo'      => $etapa,
            ];
            $respuesta['message'] = 'Estado del flujo obtenido correctamente.';
            break;

        // ============================================================
        // RESUMEN: Conteo agregado de órdenes del usuario (GET)
        // ============================================================
        // [PEDAGÓGICO] Útil para mostrar al cliente en su cuenta
        // ("tienes 2 órdenes pendientes de pago") o al admin como
        // métrica rápida del estado del flujo en la plataforma.
        // ============================================================
        case 'resumen':
            if (!esta_logueado()) {
                throw new Exception('Debes iniciar sesión.');
            }

            $filtro_uid = '';
            $params = [];
            if (!es_admin()) {
                $filtro_uid = 'WHERE p.usuario_id = :uid';
                $params[':uid'] = (int) $_SESSION['usuario_id'];
            }

            $stmt = $pdo->prepare("
                SELECT p.estado AS estado_pedido,
                       COALESCE(pa.estado, 'sin_pago') AS estado_pago,
                       COUNT(*) AS total
                FROM pedidos p
                LEFT JOIN pagos pa ON pa.pedido_id = p.id
                {$filtro_uid}
                GROUP BY p.estado, COALESCE(pa.estado, 'sin_pago')
                ORDER BY p.estado ASC
            ");
            $stmt->execute($params);
            $filas = $stmt->fetchAll();

            $por_pedido = [];
            $por_pago   = [];
            $total      = 0;
            foreach ($filas as $f) {
                $por_pedido[$f['estado_pedido']] = ($por_pedido[$f['estado_pedido']] ?? 0) + (int) $f['total'];
                $por_pago[$f['estado_pago']]     = ($por_pago[$f['estado_pago']]     ?? 0) + (int) $f['total'];
                $total += (int) $f['total'];
            }

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'total_ordenes'   => $total,
                'por_estado_pedido' => $por_pedido,
                'por_estado_pago'   => $por_pago,
                'detalle'           => $filas,
            ];
            $respuesta['message'] = 'Resumen obtenido correctamente.';
            break;

        // ============================================================
        // CANCELAR: Cancelar orden pendiente y liberar reservas (POST)
        // ============================================================
        // [PEDAGÓGICO] El usuario puede arrepentirse mientras la
        // orden esté pendiente (sin pago capturado). En ese caso:
        //   - Marcamos el pedido como 'cancelado'
        //   - Liberamos cualquier reserva activa (devolviendo stock)
        //   - Si hay un pago en estado 'pendiente', lo marcamos
        //     como 'cancelado' también.
        // Si ya fue pagado, hay que pasar por reembolso (no aquí).
        // ============================================================
        case 'cancelar':
            if (!esta_logueado()) {
                throw new Exception('Debes iniciar sesión.');
            }
            if ($method !== 'POST') {
                throw new Exception('Usa POST para esta acción.');
            }

            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Token CSRF inválido.');
            }

            $orden_id = isset($_POST['orden_id']) ? (int) $_POST['orden_id'] : 0;
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido.');
            }

            $pdo->beginTransaction();

            try {
                // Verificar que la orden es del usuario y aún cancelable.
                $stmt = $pdo->prepare("
                    SELECT id, numero, estado, usuario_id
                    FROM pedidos
                    WHERE id = :id
                    FOR UPDATE
                ");
                $stmt->execute([':id' => $orden_id]);
                $pedido = $stmt->fetch();

                if (!$pedido) {
                    throw new Exception('Orden no encontrada.');
                }
                if (!es_admin() && (int) $pedido['usuario_id'] !== (int) $_SESSION['usuario_id']) {
                    throw new Exception('No tienes permiso sobre esta orden.');
                }
                if (!in_array($pedido['estado'], ['pendiente'], true)) {
                    throw new Exception("La orden no se puede cancelar (estado actual: {$pedido['estado']}).");
                }

                // Verificar que no haya pago capturado.
                $stmt = $pdo->prepare("SELECT id, estado FROM pagos WHERE pedido_id = :pid");
                $stmt->execute([':pid' => $orden_id]);
                $pago = $stmt->fetch();
                if ($pago && $pago['estado'] === 'completado') {
                    throw new Exception('La orden ya fue pagada. Solicita reembolso al administrador.');
                }

                // Liberar reservas activas asociadas.
                $stmt = $pdo->prepare("
                    SELECT id, producto_id, cantidad
                    FROM reservas_inventario
                    WHERE orden_id = :oid AND estado = 'activa'
                    FOR UPDATE
                ");
                $stmt->execute([':oid' => $orden_id]);
                $reservas = $stmt->fetchAll();

                $stmt_inv = $pdo->prepare("
                    UPDATE inventario
                    SET cantidad_reservada = GREATEST(0, cantidad_reservada - :cant)
                    WHERE producto_id = :pid
                ");
                $stmt_reserva = $pdo->prepare("
                    UPDATE reservas_inventario
                    SET estado = 'liberada'
                    WHERE id = :id AND estado = 'activa'
                ");
                $stmt_mov = $pdo->prepare("
                    INSERT INTO movimientos_inventario
                        (producto_id, tipo_movimiento, cantidad, referencia, fecha)
                    VALUES (:pid, 'liberacion', :cant, :ref, NOW())
                ");

                foreach ($reservas as $r) {
                    $stmt_inv->execute([
                        ':cant' => (int) $r['cantidad'],
                        ':pid'  => (int) $r['producto_id'],
                    ]);
                    $stmt_reserva->execute([':id' => (int) $r['id']]);
                    $stmt_mov->execute([
                        ':pid'  => (int) $r['producto_id'],
                        ':cant' => (int) $r['cantidad'],
                        ':ref'  => 'Cancelación ' . $pedido['numero'],
                    ]);
                }

                // Cancelar pago pendiente si existía.
                if ($pago && $pago['estado'] === 'pendiente') {
                    $stmt = $pdo->prepare("
                        UPDATE pagos SET estado = 'cancelado' WHERE id = :id
                    ");
                    $stmt->execute([':id' => (int) $pago['id']]);
                }

                // Marcar pedido como cancelado.
                $stmt = $pdo->prepare("
                    UPDATE pedidos SET estado = 'cancelado' WHERE id = :id
                ");
                $stmt->execute([':id' => $orden_id]);

                $pdo->commit();

                $respuesta['success'] = true;
                $respuesta['data'] = [
                    'orden_id'           => $orden_id,
                    'numero_orden'       => $pedido['numero'],
                    'reservas_liberadas' => count($reservas),
                ];
                $respuesta['message'] = "✅ Orden {$pedido['numero']} cancelada. Stock liberado.";
            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        default:
            throw new Exception("Acción desconocida: {$accion}");
    }
} catch (Exception $e) {
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
