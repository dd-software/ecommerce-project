<?php
// ============================================================
// API JSON: Procesar Checkout (Confirmar Compra)
// ============================================================
// [PEDAGÓGICO] Este endpoint procesa la compra via AJAX.
// Requiere que el usuario esté logueado.
//
// Flujo transaccional:
// 1. Validar sesión del usuario
// 2. Validar token CSRF
// 3. Validar datos de dirección de envío
// 4. Obtener items del carrito del usuario
// 5. Re-validar stock de todos los items
// 6. BEGIN TRANSACTION
// 7.   Generar número de orden
// 8.   Insertar pedido (estado='pendiente')
// 9.   Insertar detalles_pedido
// 10.  Reservar inventario con expiración (10 min desde config)
// 11.  Registrar movimientos_inventario
// 12.  Limpiar carrito (o marcar como procesado)
// 13. COMMIT
// 14. Si algo falla: ROLLBACK + mensaje de error
// 15. Devolver JSON con orden_id + total
//
// Respuesta: {success: bool, data: {orden_id, numero_orden, total}, message: string}
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
    // 1. Verificar autenticación
    // ============================================================
    if (!esta_logueado()) {
        throw new Exception('Debes iniciar sesión para completar la compra.');
    }

    $usuario_id = (int) $_SESSION['usuario_id'];

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
    // 3.5 Validar pago de PayPal (Nuevo paso obligatorio)
    // ============================================================
    $paypal_order_id = $_POST['paypal_order_id'] ?? null;

    if (!$paypal_order_id) {
     throw new Exception('El pago no ha sido confirmado por PayPal.');
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
    // 4. Obtener items del carrito del usuario
    // ============================================================
    // [PEDAGÓGICO] JOIN con productos para obtener el nombre actual
    // del producto (se congela en detalles_pedido).
    $stmt = $pdo->prepare("
        SELECT ic.*, p.nombre, p.precio, p.precio_descuento
        FROM items_carrito ic
        JOIN productos p ON p.id = ic.producto_id
        WHERE ic.usuario_id = :uid
        ORDER BY ic.fecha_agregado ASC
    ");
    $stmt->execute([':uid' => $usuario_id]);
    $items = $stmt->fetchAll();

    if (empty($items)) {
        throw new Exception('Tu carrito está vacío.');
    }

    // ============================================================
    // 5. Re-validar stock de todos los items
    // ============================================================
    // [PEDAGÓGICO] Antes de crear la orden, verificamos que cada
    // producto tenga stock suficiente. Esto evita que un producto
    // se agote entre que el usuario lo agregó al carrito y ahora.
    //
    // [PEDAGÓGICO - OBJ-06] Primero liberamos reservas vencidas
    // (>10 min sin confirmar) para que el stock disponible refleje
    // lo que realmente se puede vender en este instante.
    liberar_reservas_expiradas($pdo);

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
        // 12. Limpiar carrito del usuario
        // ============================================================
        // [PEDAGÓGICO] El carrito se vacía SOLO si todo salió bien.
        // Si hay ROLLBACK, los items del carrito se conservan.
        $stmt = $pdo->prepare("DELETE FROM items_carrito WHERE usuario_id = :uid");
        $stmt->execute([':uid' => $usuario_id]);

        // ============================================================
        // 13. COMMIT: Confirmar la transacción
        // ============================================================
        $pdo->commit();

        // ============================================================
        // Respuesta exitosa
        // ============================================================
        $respuesta['success'] = true;
        $respuesta['data'] = [
            'orden_id'     => $pedido_id,
            'numero_orden' => $numero_orden,
            'total'        => (float) $totales['total'],
            'subtotal'     => (float) $totales['subtotal'],
            'iva'          => (float) $totales['iva'],
            'envio'        => (float) $totales['envio'],
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

// ============================================================
// [PEDAGÓGICO] Detectar si la petición espera JSON (AJAX) o es
// un formulario tradicional. Si es formulario, redirigimos con
// mensajes en sesión para mejor experiencia de usuario.
// ============================================================
$es_ajax = (
    strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false
    || ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest'
);

if ($es_ajax) {
    // AJAX: devolver JSON como siempre
    die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
} else {
    // Formulario tradicional: redirigir con mensaje en sesión
    if ($respuesta['success']) {
        $_SESSION['exito'] = $respuesta['message'];
        redireccionar('../exito.php?orden=' . urlencode($respuesta['data']['numero_orden']));
    } else {
        $_SESSION['error'] = $respuesta['message'];
        redireccionar('../checkout.php');
    }
}