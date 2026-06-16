<?php
// ============================================================
// API JSON: Gestión de Inventario (Reservas y Consultas)
// ============================================================
// [PEDAGÓGICO] Este endpoint expone operaciones de inventario
// para ser consumidas por el proceso de checkout y el panel
// de administración vía AJAX/JSON.
//
// Acciones disponibles:
//   consultar (GET)  -> Stock de un producto
//   validar   (POST) -> Validar disponibilidad de múltiples items
//   reservar  (POST) -> Reservar stock con expiración (transacción)
//   liberar   (POST) -> Liberar reservas activas de una orden
//   confirmar (POST) -> Descontar stock definitivo (transacción)
//
// Cada acción devuelve JSON: {success: bool, data?: mixed, message: string}
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
    // Determinar acción según método HTTP y parámetros
    // ============================================================
    $method = $_SERVER['REQUEST_METHOD'];
    $accion = '';

    if ($method === 'GET') {
        // GET: la acción se pasa como query string
        $accion = trim($_GET['action'] ?? '');
    } elseif ($method === 'POST') {
        // POST: la acción se pasa en el body (JSON o form-urlencoded)
        // [PEDAGÓGICO] Detectamos si el content-type es JSON para
        // parsear correctamente el body. Las APIs modernas suelen
        // usar JSON, pero también aceptamos form-data para compatibilidad.
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

    // ============================================================
    // Switcher de acciones
    // ============================================================
    switch ($accion) {

        // ============================================================
        // CONSULTAR: Stock de un producto (GET)
        // ============================================================
        // [PEDAGÓGICO] Endpoint de solo lectura. Devuelve el estado
        // actual del inventario: stock total, reservado y disponible.
        // No requiere autenticación porque es visible en producto.php.
        // ============================================================
        case 'consultar':
            if ($method !== 'GET') {
                throw new Exception('Usa GET para esta acción.');
            }

            $producto_id = isset($_GET['producto_id']) ? (int) $_GET['producto_id'] : 0;
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido.');
            }

            // [PEDAGÓGICO] Consultamos el inventario con JOIN a productos
            // para verificar que exista y esté activo.
            $stmt = $pdo->prepare("
                SELECT p.id, p.nombre, p.sku, p.activo,
                       inv.cantidad            AS stock_total,
                       inv.cantidad_reservada  AS stock_reservado,
                       (inv.cantidad - inv.cantidad_reservada) AS stock_disponible
                FROM productos p
                JOIN inventario inv ON inv.producto_id = p.id
                WHERE p.id = :id
            ");
            $stmt->execute([':id' => $producto_id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                throw new Exception('Producto no encontrado o sin inventario.');
            }

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'producto_id'     => (int) $producto['id'],
                'nombre'          => $producto['nombre'],
                'sku'             => $producto['sku'],
                'activo'          => (bool) $producto['activo'],
                'stock_total'     => (int) $producto['stock_total'],
                'stock_reservado' => (int) $producto['stock_reservado'],
                'stock_disponible'=> (int) $producto['stock_disponible'],
            ];
            $respuesta['message'] = 'Stock consultado correctamente.';
            break;

        // ============================================================
        // VALIDAR: Verificar disponibilidad de múltiples items (POST)
        // ============================================================
        // [PEDAGÓGICO] Utilizado antes de confirmar una orden para
        // verificar que todos los productos tengan stock suficiente.
        // Recibe un array de {producto_id, cantidad} y devuelve
        // disponibilidad (true/false) para cada uno.
        // ============================================================
        case 'validar':
            if ($method !== 'POST') {
                throw new Exception('Usa POST para esta acción.');
            }

            // [PEDAGÓGICO] Los items pueden venir como array en POST
            // o como JSON en el body. Ejemplo:
            //   items[0][producto_id]=1&items[0][cantidad]=2
            //   o {"items": [{"producto_id":1,"cantidad":2}]}
            $items = $_POST['items'] ?? [];
            if (empty($items) || !is_array($items)) {
                throw new Exception('Array "items" es requerido con {producto_id, cantidad}.');
            }

            $resultados = [];
            $todo_disponible = true;

            foreach ($items as $idx => $item) {
                $pid = (int) ($item['producto_id'] ?? 0);
                $cant = (int) ($item['cantidad'] ?? 0);

                if ($pid <= 0 || $cant <= 0) {
                    $resultados[] = [
                        'producto_id' => $pid,
                        'cantidad'    => $cant,
                        'disponible'  => false,
                        'razon'       => 'Datos de producto inválidos.',
                    ];
                    $todo_disponible = false;
                    continue;
                }

                // Consultar stock disponible
                $stmt = $pdo->prepare("
                    SELECT p.nombre,
                           inv.cantidad,
                           inv.cantidad_reservada,
                           (inv.cantidad - inv.cantidad_reservada) AS stock_disponible
                    FROM inventario inv
                    JOIN productos p ON p.id = inv.producto_id
                    WHERE inv.producto_id = :pid AND p.activo = 1
                ");
                $stmt->execute([':pid' => $pid]);
                $inv = $stmt->fetch();

                if (!$inv) {
                    $resultados[] = [
                        'producto_id' => $pid,
                        'cantidad'    => $cant,
                        'disponible'  => false,
                        'razon'       => 'Producto no encontrado o inactivo.',
                    ];
                    $todo_disponible = false;
                    continue;
                }

                $disponible = (int) $inv['stock_disponible'];
                $suficiente = $cant <= $disponible;

                if (!$suficiente) {
                    $todo_disponible = false;
                }

                $resultados[] = [
                    'producto_id'      => $pid,
                    'nombre'           => $inv['nombre'],
                    'cantidad_solicitada' => $cant,
                    'stock_disponible' => $disponible,
                    'disponible'       => $suficiente,
                    'razon'            => $suficiente ? '' : "Stock insuficiente. Disponible: {$disponible}, solicitado: {$cant}.",
                ];
            }

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'items'           => $resultados,
                'todo_disponible' => $todo_disponible,
            ];
            $respuesta['message'] = $todo_disponible
                ? '✅ Todos los productos tienen stock suficiente.'
                : '⚠️ Algunos productos no tienen stock suficiente.';
            break;

        // ============================================================
        // RESERVAR: Apartar stock con expiración (POST, transacción)
        // ============================================================
        // [PEDAGÓGICO] Cuando el usuario inicia el checkout, se
        // reservan las unidades para que no sean compradas por otro
        // mientras paga. La reserva expira en RESERVA_MINUTOS (10 min).
        //
        // Usamos una TRANSACCIÓN PDO para asegurar que todas las
        // operaciones (insert en reservas, update en inventario,
        // insert en movimientos) ocurren juntas o no ocurren nada.
        // ============================================================
        case 'reservar':
            if ($method !== 'POST') {
                throw new Exception('Usa POST para esta acción.');
            }

            // CSRF: las reservas modifican datos — validar token
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Token CSRF inválido.');
            }

            $orden_id = isset($_POST['orden_id']) ? (int) $_POST['orden_id'] : 0;
            $items = $_POST['items'] ?? [];

            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido.');
            }
            if (empty($items) || !is_array($items)) {
                throw new Exception('Array "items" es requerido con {producto_id, cantidad}.');
            }

            // Iniciar transacción
            // [PEDAGÓGICO] BEGIN TRANSACTION asegura que todas las
            // consultas siguientes se ejecuten como una unidad atómica.
            // Si algo falla, hacemos ROLLBACK y nada se modifica.
            $pdo->beginTransaction();

            try {
                $reservas_creadas = [];
                $fecha_expiracion = date('Y-m-d H:i:s', time() + (RESERVA_MINUTOS * 60));

                foreach ($items as $item) {
                    $producto_id = (int) ($item['producto_id'] ?? 0);
                    $cantidad    = (int) ($item['cantidad'] ?? 0);

                    if ($producto_id <= 0 || $cantidad <= 0) {
                        throw new Exception("Item inválido: producto_id={$producto_id}, cantidad={$cantidad}");
                    }

                    // [PEDAGÓGICO] Bloqueamos la fila del inventario con
                    // FOR UPDATE para evitar race conditions. Esto previene
                    // que dos peticiones simultáneas reserven el mismo stock.
                    $stmt = $pdo->prepare("
                        SELECT cantidad, cantidad_reservada
                        FROM inventario
                        WHERE producto_id = :pid
                        FOR UPDATE
                    ");
                    $stmt->execute([':pid' => $producto_id]);
                    $inv_row = $stmt->fetch();

                    if (!$inv_row) {
                        throw new Exception("Inventario no encontrado para producto_id={$producto_id}");
                    }

                    $stock_total    = (int) $inv_row['cantidad'];
                    $stock_reservado = (int) $inv_row['cantidad_reservada'];
                    $disponible     = $stock_total - $stock_reservado;

                    if ($cantidad > $disponible) {
                        throw new Exception(
                            "Stock insuficiente para producto_id={$producto_id}. " .
                            "Disponible: {$disponible}, solicitado: {$cantidad}."
                        );
                    }

                    // 1. Insertar reserva en reservas_inventario
                    $stmt = $pdo->prepare("
                        INSERT INTO reservas_inventario
                            (orden_id, producto_id, cantidad, estado, fecha_creacion, fecha_expiracion)
                        VALUES
                            (:orden_id, :producto_id, :cantidad, 'activa', NOW(), :fecha_expiracion)
                    ");
                    $stmt->execute([
                        ':orden_id'         => $orden_id,
                        ':producto_id'      => $producto_id,
                        ':cantidad'         => $cantidad,
                        ':fecha_expiracion' => $fecha_expiracion,
                    ]);
                    $reserva_id = $pdo->lastInsertId();

                    // 2. Actualizar cantidad_reservada en inventario
                    $stmt = $pdo->prepare("
                        UPDATE inventario
                        SET cantidad_reservada = cantidad_reservada + :cantidad
                        WHERE producto_id = :producto_id
                    ");
                    $stmt->execute([
                        ':cantidad'    => $cantidad,
                        ':producto_id' => $producto_id,
                    ]);

                    // 3. Registrar movimiento en movimientos_inventario
                    $stmt = $pdo->prepare("
                        INSERT INTO movimientos_inventario
                            (producto_id, tipo_movimiento, cantidad, referencia)
                        VALUES
                            (:producto_id, 'reserva', :cantidad, :referencia)
                    ");
                    $stmt->execute([
                        ':producto_id' => $producto_id,
                        ':cantidad'    => $cantidad,
                        ':referencia'  => "Reserva orden #{$orden_id}",
                    ]);

                    $reservas_creadas[] = [
                        'reserva_id'  => (int) $reserva_id,
                        'producto_id' => $producto_id,
                        'cantidad'    => $cantidad,
                    ];
                }

                // Si todo salió bien, confirmamos la transacción
                $pdo->commit();

                $respuesta['success'] = true;
                $respuesta['data'] = [
                    'orden_id'         => $orden_id,
                    'reservas'         => $reservas_creadas,
                    'fecha_expiracion' => $fecha_expiracion,
                ];
                $respuesta['message'] = '✅ Stock reservado correctamente. La reserva expira en ' . RESERVA_MINUTOS . ' minutos.';

            } catch (Exception $e) {
                // [PEDAGÓGICO] Si algo falla, hacemos ROLLBACK para
                // deshacer cualquier cambio parcial realizado en la
                // transacción. Así evitamos datos inconsistentes.
                $pdo->rollBack();
                throw $e;
            }
            break;

        // ============================================================
        // LIBERAR: Liberar reservas activas de una orden (POST)
        // ============================================================
        // [PEDAGÓGICO] Si el usuario cancela el pago o la reserva
        // expira, debemos liberar las unidades reservadas para que
        // vuelvan a estar disponibles para otros clientes.
        // ============================================================
        case 'liberar':
            if ($method !== 'POST') {
                throw new Exception('Usa POST para esta acción.');
            }

            // Validar CSRF
            $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
            if (!csrf_validar($token)) {
                throw new Exception('Error de seguridad. Token CSRF inválido.');
            }

            $orden_id = isset($_POST['orden_id']) ? (int) $_POST['orden_id'] : 0;
            if ($orden_id <= 0) {
                throw new Exception('ID de orden inválido.');
            }

            // Iniciar transacción
            $pdo->beginTransaction();

            try {
                // Obtener todas las reservas activas de la orden
                $stmt = $pdo->prepare("
                    SELECT id, producto_id, cantidad
                    FROM reservas_inventario
                    WHERE orden_id = :orden_id AND estado = 'activa'
                    FOR UPDATE
                ");
                $stmt->execute([':orden_id' => $orden_id]);
                $reservas = $stmt->fetchAll();

                if (empty($reservas)) {
                    // [PEDAGÓGICO] No es error — simplemente no hay
                    // reservas activas que liberar. La orden pudo haber
                    // sido confirmada previamente o nunca reservada.
                    $pdo->rollBack();
                    $respuesta['success'] = true;
                    $respuesta['data'] = ['orden_id' => $orden_id, 'liberadas' => 0];
                    $respuesta['message'] = 'No hay reservas activas para liberar.';
                    break;
                }

                $total_liberadas = 0;

                foreach ($reservas as $reserva) {
                    $producto_id = (int) $reserva['producto_id'];
                    $cantidad    = (int) $reserva['cantidad'];

                    // 1. Liberar (reducir) cantidad_reservada en inventario
                    $stmt = $pdo->prepare("
                        UPDATE inventario
                        SET cantidad_reservada = GREATEST(0, cantidad_reservada - :cantidad)
                        WHERE producto_id = :producto_id
                    ");
                    $stmt->execute([
                        ':cantidad'    => $cantidad,
                        ':producto_id' => $producto_id,
                    ]);

                    // 2. Cambiar estado de la reserva a 'liberada'
                    $stmt = $pdo->prepare("
                        UPDATE reservas_inventario
                        SET estado = 'liberada'
                        WHERE id = :id AND estado = 'activa'
                    ");
                    $stmt->execute([':id' => $reserva['id']]);

                    // 3. Registrar movimiento de liberación
                    $stmt = $pdo->prepare("
                        INSERT INTO movimientos_inventario
                            (producto_id, tipo_movimiento, cantidad, referencia)
                        VALUES
                            (:producto_id, 'liberacion', :cantidad, :referencia)
                    ");
                    $stmt->execute([
                        ':producto_id' => $producto_id,
                        ':cantidad'    => $cantidad,
                        ':referencia'  => "Liberación orden #{$orden_id}",
                    ]);

                    $total_liberadas++;
                }

                $pdo->commit();

                $respuesta['success'] = true;
                $respuesta['data'] = [
                    'orden_id'    => $orden_id,
                    'liberadas'   => $total_liberadas,
                ];
                $respuesta['message'] = "✅ Se liberaron {$total_liberadas} reserva(s) de la orden #{$orden_id}.";

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // ============================================================
        // CONFIRMAR: Descontar stock definitivo (POST, transacción)
        // ============================================================
        // [PEDAGÓGICO] Cuando el pago se confirma exitosamente, las
        // reservas se convierten en salidas definitivas:
        //   - Se descuenta del stock total (cantidad)
        //   - Se descuenta de la reserva (cantidad_reservada)
        //   - La reserva pasa a estado 'confirmada'
        //   - Se registra movimiento tipo 'salida'
        //
        // Esto asegura que el stock se reduce de forma permanente
        // y queda auditado en movimientos_inventario.
        // ============================================================
        case 'confirmar':
            if ($method !== 'POST') {
                throw new Exception('Usa POST para esta acción.');
            }

            // Validar CSRF
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
                // Obtener reservas activas de la orden
                $stmt = $pdo->prepare("
                    SELECT id, producto_id, cantidad
                    FROM reservas_inventario
                    WHERE orden_id = :orden_id AND estado = 'activa'
                    FOR UPDATE
                ");
                $stmt->execute([':orden_id' => $orden_id]);
                $reservas = $stmt->fetchAll();

                if (empty($reservas)) {
                    $pdo->rollBack();
                    throw new Exception("No hay reservas activas para la orden #{$orden_id}. Debes reservar stock antes de confirmar.");
                }

                $total_confirmadas = 0;

                foreach ($reservas as $reserva) {
                    $producto_id = (int) $reserva['producto_id'];
                    $cantidad    = (int) $reserva['cantidad'];

                    // 1. Descontar del stock total y reducir reserva
                    // [PEDAGÓGICO] La salida definitiva resta de cantidad
                    // y también reduce cantidad_reservada, porque esas
                    // unidades ya no están "apartadas" sino "vendidas".
                    $stmt = $pdo->prepare("
                        UPDATE inventario
                        SET cantidad           = GREATEST(0, cantidad - :cantidad),
                            cantidad_reservada = GREATEST(0, cantidad_reservada - :cantidad)
                        WHERE producto_id = :producto_id
                    ");
                    $stmt->execute([
                        ':cantidad'    => $cantidad,
                        ':producto_id' => $producto_id,
                    ]);

                    // 2. Cambiar estado de la reserva a 'confirmada'
                    $stmt = $pdo->prepare("
                        UPDATE reservas_inventario
                        SET estado = 'confirmada'
                        WHERE id = :id AND estado = 'activa'
                    ");
                    $stmt->execute([':id' => $reserva['id']]);

                    // 3. Registrar movimiento de salida definitiva
                    $stmt = $pdo->prepare("
                        INSERT INTO movimientos_inventario
                            (producto_id, tipo_movimiento, cantidad, referencia)
                        VALUES
                            (:producto_id, 'salida', :cantidad, :referencia)
                    ");
                    $stmt->execute([
                        ':producto_id' => $producto_id,
                        ':cantidad'    => $cantidad,
                        ':referencia'  => "Confirmación orden #{$orden_id}",
                    ]);

                    $total_confirmadas++;
                }

                $pdo->commit();

                $respuesta['success'] = true;
                $respuesta['data'] = [
                    'orden_id'     => $orden_id,
                    'confirmadas'  => $total_confirmadas,
                ];
                $respuesta['message'] = "✅ Stock confirmado para orden #{$orden_id}. {$total_confirmadas} ítem(s) descontados.";

            } catch (Exception $e) {
                $pdo->rollBack();
                throw $e;
            }
            break;

        // ============================================================
        // Acción no reconocida
        // ============================================================
        default:
            throw new Exception("Acción desconocida: {$accion}");
    }

} catch (Exception $e) {
    // [PEDAGÓGICO] Capturamos cualquier excepción y la devolvemos
    // como JSON para que el frontend pueda mostrar el error.
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
