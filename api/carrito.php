<?php
// ============================================================
// API JSON: Manejo del Carrito de Compras
// ============================================================
// [PEDAGÓGICO] Este endpoint recibe peticiones AJAX con POST/JSON
// para manejar el carrito de compras en dos modalidades:
//   - INVITADOS: $_SESSION['carrito'] como array asociativo
//   - USUARIOS LOGUEADOS: BD tabla items_carrito
//
// Acciones via POST (action=XXX):
//   agregar   -> Añadir producto al carrito
//   actualizar-> Cambiar cantidad de un producto
//   eliminar  -> Quitar producto del carrito
//   vaciar    -> Vaciar todo el carrito
//   obtener   -> Devolver contenido actual del carrito
//
// Cada acción valida: producto existe, activo, stock suficiente.
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
    // Validar método HTTP y parámetros
    // ============================================================
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método no permitido. Usa POST.');
    }

    $accion = trim($_POST['action'] ?? '');
    if (empty($accion)) {
        throw new Exception('Parámetro action es requerido.');
    }

    // ============================================================
    // Validar CSRF para acciones que modifican datos
    // ============================================================
    // [PEDAGÓGICO] Solo 'obtener' es lectura, el resto muta datos
    // y debe validar el token CSRF.
    $acciones_mutacion = ['agregar', 'actualizar', 'eliminar', 'vaciar'];
    if (in_array($accion, $acciones_mutacion, true)) {
        $token = $_POST['_csrf_token'] ?? '';
        // También aceptar cabecera X-CSRF-Token (común en fetch/axios)
        if (empty($token)) {
            $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        }
        if (!csrf_validar($token)) {
            throw new Exception('Error de seguridad. Token CSRF inválido. Recarga la página.');
        }
    }

    // Obtener parámetros comunes
    $producto_id = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;
    $cantidad    = isset($_POST['cantidad']) ? max(1, (int) $_POST['cantidad']) : 1;

    // ============================================================
    // Switcher de acciones
    // ============================================================
    switch ($accion) {

        // ============================================================
        // AGREGAR producto al carrito
        // ============================================================
        case 'agregar':
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido.');
            }

            // [PEDAGÓGICO] Verificamos que el producto exista, esté activo y tenga stock
            $stmt = $pdo->prepare("
                SELECT p.id, p.precio, p.precio_descuento,
                       p.nombre,
                       inv.cantidad as stock_disponible,
                       (inv.cantidad - inv.cantidad_reservada) as stock_efectivo
                FROM productos p
                LEFT JOIN inventario inv ON inv.producto_id = p.id
                WHERE p.id = :id AND p.activo = 1
            ");
            $stmt->execute([':id' => $producto_id]);
            $producto = $stmt->fetch();

            if (!$producto) {
                throw new Exception('Producto no encontrado o desactivado.');
            }

            // Calcular stock efectivo (disponible - reservado)
            $stock_efectivo = (int) ($producto['stock_efectivo'] ?? 0);
            if ($stock_efectivo < 1) {
                throw new Exception('Producto agotado.');
            }

            // Verificar que la cantidad solicitada no supere el stock
            if ($cantidad > $stock_efectivo) {
                throw new Exception("Stock insuficiente. Solo hay {$stock_efectivo} unidades disponibles.");
            }

            // Precio a cobrar: precio con descuento si existe, sino precio normal
            $precio = !empty($producto['precio_descuento'])
                ? (float) $producto['precio_descuento']
                : (float) $producto['precio'];

            if (esta_logueado()) {
                // ============================================================
                // Usuario logueado: guardar en BD (tabla items_carrito)
                // ============================================================
                // Verificar si ya existe el producto en su carrito
                $stmt = $pdo->prepare("
                    SELECT id, cantidad
                    FROM items_carrito
                    WHERE usuario_id = :uid AND producto_id = :pid
                ");
                $stmt->execute([
                    ':uid' => $_SESSION['usuario_id'],
                    ':pid' => $producto_id,
                ]);
                $existente = $stmt->fetch();

                if ($existente) {
                    // Actualizar cantidad (sumar la nueva cantidad a la existente)
                    $nueva_cantidad = $existente['cantidad'] + $cantidad;

                    // Verificar que la nueva cantidad no supere stock
                    if ($nueva_cantidad > $stock_efectivo) {
                        throw new Exception("Stock insuficiente. Ya tienes {$existente['cantidad']} unidades en tu carrito y solo hay {$stock_efectivo} disponibles.");
                    }

                    $stmt = $pdo->prepare("
                        UPDATE items_carrito
                        SET cantidad = :cant, precio_unitario = :precio
                        WHERE id = :id
                    ");
                    $stmt->execute([
                        ':cant'   => $nueva_cantidad,
                        ':precio' => $precio,
                        ':id'     => $existente['id'],
                    ]);
                } else {
                    // Insertar nuevo item
                    $stmt = $pdo->prepare("
                        INSERT INTO items_carrito (sesion_id, usuario_id, producto_id, cantidad, precio_unitario)
                        VALUES (:sesion, :uid, :pid, :cant, :precio)
                    ");
                    $stmt->execute([
                        ':sesion' => session_id(),
                        ':uid'    => $_SESSION['usuario_id'],
                        ':pid'    => $producto_id,
                        ':cant'   => $cantidad,
                        ':precio' => $precio,
                    ]);
                }
            } else {
                // ============================================================
                // Invitado: guardar en sesión
                // ============================================================
                if (!isset($_SESSION['carrito'])) {
                    $_SESSION['carrito'] = [];
                }

                // [PEDAGÓGICO] La sesión guarda un array asociativo
                // con producto_id => ['cantidad' => N, 'precio' => X]
                if (isset($_SESSION['carrito'][$producto_id])) {
                    $nueva_cantidad = $_SESSION['carrito'][$producto_id]['cantidad'] + $cantidad;

                    if ($nueva_cantidad > $stock_efectivo) {
                        throw new Exception("Stock insuficiente. Ya tienes {$_SESSION['carrito'][$producto_id]['cantidad']} unidades en tu carrito.");
                    }

                    $_SESSION['carrito'][$producto_id]['cantidad'] = $nueva_cantidad;
                } else {
                    $_SESSION['carrito'][$producto_id] = [
                        'cantidad' => $cantidad,
                        'precio'   => $precio,
                    ];
                }
            }

            $respuesta['success'] = true;
            $respuesta['message'] = '✅ Producto agregado al carrito.';
            // [OBJ-06] Recién aquí — después de validar stock e insertar
            // con éxito — invalidamos el countdown del checkout. Si la
            // validación de stock fallara más arriba, salimos por
            // Exception y el timer NO se toca.
            unset($_SESSION['checkout_expira_at']);
            break;

        // ============================================================
        // ACTUALIZAR cantidad de un producto en el carrito
        // ============================================================
        case 'actualizar':
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido.');
            }

            // Validar stock disponible
            $stmt = $pdo->prepare("
                SELECT (inv.cantidad - inv.cantidad_reservada) as stock_efectivo
                FROM inventario inv
                WHERE inv.producto_id = :pid
            ");
            $stmt->execute([':pid' => $producto_id]);
            $stock_row = $stmt->fetch();

            $stock_efectivo = (int) ($stock_row['stock_efectivo'] ?? 0);
            if ($cantidad > $stock_efectivo) {
                throw new Exception("Stock insuficiente. Solo hay {$stock_efectivo} unidades disponibles.");
            }

            if (esta_logueado()) {
                $stmt = $pdo->prepare("
                    UPDATE items_carrito
                    SET cantidad = :cant
                    WHERE usuario_id = :uid AND producto_id = :pid
                ");
                $stmt->execute([
                    ':cant' => $cantidad,
                    ':uid'  => $_SESSION['usuario_id'],
                    ':pid'  => $producto_id,
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('El producto no está en tu carrito.');
                }
            } else {
                if (!isset($_SESSION['carrito'][$producto_id])) {
                    throw new Exception('El producto no está en tu carrito.');
                }
                $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
            }

            $respuesta['success'] = true;
            $respuesta['message'] = '✅ Cantidad actualizada.';
            // [OBJ-06] El carrito cambió: reset del countdown.
            unset($_SESSION['checkout_expira_at']);
            break;

        // ============================================================
        // ELIMINAR producto del carrito
        // ============================================================
        case 'eliminar':
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido.');
            }

            if (esta_logueado()) {
                $stmt = $pdo->prepare("
                    DELETE FROM items_carrito
                    WHERE usuario_id = :uid AND producto_id = :pid
                ");
                $stmt->execute([
                    ':uid' => $_SESSION['usuario_id'],
                    ':pid' => $producto_id,
                ]);

                if ($stmt->rowCount() === 0) {
                    throw new Exception('El producto no está en tu carrito.');
                }
            } else {
                if (!isset($_SESSION['carrito'][$producto_id])) {
                    throw new Exception('El producto no está en tu carrito.');
                }
                unset($_SESSION['carrito'][$producto_id]);
            }

            $respuesta['success'] = true;
            $respuesta['message'] = '🗑️ Producto eliminado del carrito.';
            // [OBJ-06] Item eliminado: reset del countdown.
            unset($_SESSION['checkout_expira_at']);
            break;

        // ============================================================
        // VACIAR todo el carrito
        // ============================================================
        case 'vaciar':
            if (esta_logueado()) {
                $stmt = $pdo->prepare("
                    DELETE FROM items_carrito
                    WHERE usuario_id = :uid
                ");
                $stmt->execute([':uid' => $_SESSION['usuario_id']]);
            } else {
                $_SESSION['carrito'] = [];
            }

            $respuesta['success'] = true;
            $respuesta['message'] = '🗑️ Carrito vaciado.';
            // [OBJ-06] Carrito vaciado: reset del countdown.
            unset($_SESSION['checkout_expira_at']);
            break;

        // ============================================================
        // OBTENER contenido actual del carrito
        // ============================================================
        case 'obtener':
            // [PEDAGÓGICO] Esta acción es de solo lectura, no necesita CSRF.
            // Devuelve todos los items del carrito con datos del producto,
            // imágenes y totales calculados.
            $items_carrito = [];
            $total_items   = 0;

            if (esta_logueado()) {
                // Cargar desde BD
                $stmt = $pdo->prepare("
                    SELECT ic.*, p.nombre, p.sku, p.precio, p.precio_descuento,
                           i.url as imagen_url, i.alt_text
                    FROM items_carrito ic
                    JOIN productos p ON p.id = ic.producto_id
                    LEFT JOIN (
                        SELECT producto_id, url, alt_text
                        FROM imagenes
                        WHERE es_principal = 1
                    ) i ON i.producto_id = ic.producto_id
                    WHERE ic.usuario_id = :uid
                    ORDER BY ic.fecha_agregado ASC
                ");
                $stmt->execute([':uid' => $_SESSION['usuario_id']]);
                $items_carrito = $stmt->fetchAll();

                foreach ($items_carrito as &$item) {
                    $total_items += (int) $item['cantidad'];
                }
                unset($item);
            } else {
                // Cargar desde sesión
                if (!empty($_SESSION['carrito'])) {
                    $ids = array_keys($_SESSION['carrito']);
                    if (!empty($ids)) {
                        $placeholders = implode(',', array_fill(0, count($ids), '?'));
                        $stmt = $pdo->prepare("
                            SELECT p.id, p.nombre, p.sku, p.precio, p.precio_descuento,
                                   i.url as imagen_url, i.alt_text
                            FROM productos p
                            LEFT JOIN (
                                SELECT producto_id, url, alt_text
                                FROM imagenes
                                WHERE es_principal = 1
                            ) i ON i.producto_id = p.id
                            WHERE p.id IN ($placeholders) AND p.activo = 1
                        ");
                        $stmt->execute($ids);
                        $productos_db = $stmt->fetchAll();

                        foreach ($productos_db as $prod) {
                            $sesion_item = $_SESSION['carrito'][$prod['id']];
                            $precio = !empty($prod['precio_descuento'])
                                ? (float) $prod['precio_descuento']
                                : (float) $prod['precio'];

                            $items_carrito[] = [
                                'producto_id'     => (int) $prod['id'],
                                'nombre'          => $prod['nombre'],
                                'sku'             => $prod['sku'],
                                'precio_unitario' => $precio,
                                'cantidad'        => (int) $sesion_item['cantidad'],
                                'imagen_url'      => $prod['imagen_url'],
                                'alt_text'        => $prod['alt_text'],
                            ];
                            $total_items += (int) $sesion_item['cantidad'];
                        }
                    }
                }
            }

            // Calcular totales
            $items_totales = [];
            foreach ($items_carrito as $item) {
                $items_totales[] = [
                    'precio'   => $item['precio_unitario'],
                    'cantidad' => $item['cantidad'],
                ];
            }
            $totales = calcular_totales($items_totales);

            $respuesta['success'] = true;
            $respuesta['data'] = [
                'items'         => $items_carrito,
                'totales'       => $totales,
                'total_items'   => $total_items,
                'esta_logueado' => esta_logueado(),
            ];
            $respuesta['message'] = 'Carrito obtenido correctamente.';
            break;

        // ============================================================
        // Acción no reconocida
        // ============================================================
        default:
            throw new Exception("Acción desconocida: {$accion}");
    }

} catch (Exception $e) {
    // [PEDAGÓGICO] Capturamos excepciones y devolvemos el error como JSON
    $respuesta['success'] = false;
    $respuesta['message'] = $e->getMessage();
}

die(json_encode($respuesta, JSON_UNESCAPED_UNICODE));
