<?php
// ============================================================
// Carrito de Compras
// ============================================================
// [PEDAGÓGICO] Maneja el carrito de compras en dos modalidades:
// - INVITADOS: los items se guardan en $_SESSION['carrito']
//   como un array asociativo [producto_id => cantidad]
// - USUARIOS LOGUEADOS: los items se guardan en la BD
//   (tabla items_carrito) asociados al usuario.
//
// GET  → Muestra el contenido del carrito
// POST → Procesa agregar/actualizar/eliminar items
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$mensaje = '';
$error   = '';

// ============================================================
// Procesar acciones POST
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar token CSRF
    $token = $_POST['_csrf_token'] ?? '';
    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Intenta de nuevo.';
    } else {
        $accion      = $_POST['accion'] ?? '';
        $producto_id = isset($_POST['producto_id']) ? (int) $_POST['producto_id'] : 0;
        $cantidad    = isset($_POST['cantidad']) ? max(1, (int) $_POST['cantidad']) : 1;

        switch ($accion) {
            // ============================================================
            // AGREGAR producto al carrito
            // ============================================================
            case 'agregar':
                $stmt = $pdo->prepare("
                    SELECT p.id, p.precio, p.precio_descuento,
                           inv.cantidad as stock
                    FROM productos p
                    LEFT JOIN inventario inv ON inv.producto_id = p.id
                    WHERE p.id = :id AND p.activo = 1
                ");
                $stmt->execute([':id' => $producto_id]);
                $producto = $stmt->fetch();

                if (!$producto) {
                    $error = 'Producto no encontrado o inactivo.';
                } else {
                    // Calcular cuántas unidades ya tiene el cliente en su carrito actual
                    $unidades_en_carrito = 0;
                    if (esta_logueado()) {
                        $stmt_check = $pdo->prepare("SELECT cantidad FROM items_carrito WHERE usuario_id = :uid AND producto_id = :pid");
                        $stmt_check->execute([':uid' => $_SESSION['usuario_id'], ':pid' => $producto_id]);
                        $unidades_en_carrito = (int) ($stmt_check->fetchColumn() ?: 0);
                    } else {
                        $unidades_en_carrito = $_SESSION['carrito'][$producto_id]['cantidad'] ?? 0;
                    }

                    $total_solicitado = $unidades_en_carrito + $cantidad;

                    // [REQUERIMIENTO OBJ-04] Control de stock estricto
                    if (($producto['stock'] ?? 0) < $total_solicitado) {
                        $error = '⚠️ No puedes añadir esa cantidad. Stock disponible: ' . $producto['stock'] . ' unidades. (Ya posees ' . $unidades_en_carrito . ' en tu carro).';
                    } else {
                        $precio = !empty($producto['precio_descuento']) ? $producto['precio_descuento'] : $producto['precio'];

                        if (esta_logueado()) {
                            $stmt = $pdo->prepare("
                                SELECT id, cantidad FROM items_carrito
                                WHERE usuario_id = :uid AND producto_id = :pid
                            ");
                            $stmt->execute([':uid' => $_SESSION['usuario_id'], ':pid' => $producto_id]);
                            $existente = $stmt->fetch();

                            if ($existente) {
                                $stmt = $pdo->prepare("
                                    UPDATE items_carrito
                                    SET cantidad = :cant, precio_unitario = :precio
                                    WHERE id = :id
                                ");
                                $stmt->execute([
                                    ':cant'   => $total_solicitado,
                                    ':precio' => $precio,
                                    ':id'     => $existente['id'],
                                ]);
                            } else {
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
                            if (!isset($_SESSION['carrito'])) {
                                $_SESSION['carrito'] = [];
                            }
                            if (isset($_SESSION['carrito'][$producto_id])) {
                                $_SESSION['carrito'][$producto_id]['cantidad'] += $cantidad;
                            } else {
                                $_SESSION['carrito'][$producto_id] = [
                                    'cantidad' => $cantidad,
                                    'precio'   => $precio,
                                ];
                            }
                        }
                        $mensaje = '✅ Producto agregado al carrito.';
                    }
                }
                break;

            // ============================================================
            // ACTUALIZAR cantidad de un producto en el carrito
            // ============================================================
            case 'actualizar':
                // [REQUERIMIENTO OBJ-04] Obtener stock antes de actualizar numéricamente
                $stmt_stock = $pdo->prepare("
                    SELECT inv.cantidad as stock 
                    FROM productos p
                    LEFT JOIN inventario inv ON inv.producto_id = p.id
                    WHERE p.id = :pid AND p.activo = 1
                ");
                $stmt_stock->execute([':pid' => $producto_id]);
                $disponibilidad = $stmt_stock->fetch();

                if (!$disponibilidad) {
                    $error = 'El producto ya no está disponible.';
                } elseif ($cantidad > ($disponibilidad['stock'] ?? 0)) {
                    $error = '⚠️ Error: El stock disponible para este producto es de ' . $disponibilidad['stock'] . ' unidades.';
                } else {
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
                    } else {
                        if (isset($_SESSION['carrito'][$producto_id])) {
                            $_SESSION['carrito'][$producto_id]['cantidad'] = $cantidad;
                        }
                    }
                    $mensaje = '✅ Cantidad actualizada.';
                }
                break;

            // ============================================================
            // ELIMINAR producto del carrito
            // ============================================================
            case 'eliminar':
                if (esta_logueado()) {
                    $stmt = $pdo->prepare("
                        DELETE FROM items_carrito
                        WHERE usuario_id = :uid AND producto_id = :pid
                    ");
                    $stmt->execute([
                        ':uid' => $_SESSION['usuario_id'],
                        ':pid' => $producto_id,
                    ]);
                } else {
                    unset($_SESSION['carrito'][$producto_id]);
                }
                $mensaje = '🗑️ Producto eliminado del carrito.';
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
                $mensaje = '🗑️ Carrito vaciado.';
                break;
        }
    }
}

// ============================================================
// Obtener items del carrito para mostrar
// ============================================================
$items_carrito = [];

if (esta_logueado()) {
    $stmt = $pdo->prepare("
        SELECT ic.*, p.nombre, p.sku,
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
} else {
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
                $precio = !empty($prod['precio_descuento']) ? $prod['precio_descuento'] : $prod['precio'];

                $items_carrito[] = [
                    'producto_id'    => $prod['id'],
                    'nombre'         => $prod['nombre'],
                    'sku'            => $prod['sku'],
                    'precio_unitario'=> $precio,
                    'cantidad'       => $sesion_item['cantidad'],
                    'imagen_url'     => $prod['imagen_url'],
                    'alt_text'       => $prod['alt_text'],
                ];
            }
        }
    }
}

// Calcular totales del carrito
$items_totales = [];
foreach ($items_carrito as $item) {
    $items_totales[] = [
        'precio'   => $item['precio_unitario'],
        'cantidad' => $item['cantidad'],
    ];
}
$totales = calcular_totales($items_totales);
$total_unidades = array_sum(array_column($items_totales, 'cantidad') ?: [0]);
?>

<h1 class="mb-4">🛍️ Mi Carrito</h1>

<?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= escapar($mensaje) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= escapar($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if (empty($items_carrito)): ?>
    <div class="alert alert-info text-center py-5">
        <div style="font-size: 4rem;">🛒</div>
        <h4>Tu carrito está vacío</h4>
        <p class="mb-3">Agrega productos desde el catálogo para empezar a comprar.</p>
        <a href="index.php" class="btn btn-primary">🛍️ Ir al catálogo</a>
    </div>
<?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle">
            <thead class="table-light">
                <tr>
                    <th scope="col" style="width: 80px;"></th>
                    <th scope="col">Producto</th>
                    <th scope="col" class="text-center">Precio</th>
                    <th scope="col" class="text-center">Cantidad</th>
                    <th scope="col" class="text-end">Subtotal</th>
                    <th scope="col" class="text-center"></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($items_carrito as $item): ?>
                <tr>
                    <td>
                        <?php if (!empty($item['imagen_url'])): ?>
                            <img src="<?= escapar($item['imagen_url']) ?>"
                                 alt="<?= escapar($item['alt_text'] ?? $item['nombre']) ?>"
                                 class="img-thumbnail"
                                 style="width: 60px; height: 60px; object-fit: cover;">
                        <?php else: ?>
                            <div class="bg-light text-center p-1 rounded" style="width: 60px; height: 60px;">
                                <span style="font-size: 1.5rem;">📦</span>
                            </div>
                        <?php endif; ?>
                    </td>

                    <td>
                        <a href="producto.php?id=<?= (int) $item['producto_id'] ?>" class="text-decoration-none fw-semibold">
                            <?= escapar($item['nombre']) ?>
                        </a>
                        <br>
                        <small class="text-muted">SKU: <?= escapar($item['sku'] ?? '') ?></small>
                    </td>

                    <td class="text-center fw-semibold">
                        <?= formato_precio($item['precio_unitario']) ?>
                    </td>

                    <td class="text-center">
                        <form method="POST" action="carrito.php" class="d-inline">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="actualizar">
                            <input type="hidden" name="producto_id" value="<?= (int) $item['producto_id'] ?>">
                            <div class="input-group input-group-sm" style="max-width: 120px; margin: 0 auto;">
                                <input type="number"
                                       name="cantidad"
                                       class="form-control text-center"
                                       value="<?= (int) $item['cantidad'] ?>"
                                       min="1"
                                       max="999">
                                <button type="submit" class="btn btn-outline-primary" title="Actualizar">
                                    🔄
                                </button>
                            </div>
                        </form>
                    </td>

                    <td class="text-end fw-bold">
                        <?= formato_precio($item['precio_unitario'] * $item['cantidad']) ?>
                    </td>

                    <td class="text-center">
                        <form method="POST" action="carrito.php" onsubmit="return confirm('¿Eliminar este producto del carrito?');">
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" value="eliminar">
                            <input type="hidden" name="producto_id" value="<?= (int) $item['producto_id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm" title="Eliminar producto">
                                🗑️
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="row mt-4">
        <div class="col-md-6 mb-3">
            <div class="d-flex gap-2">
                <a href="index.php" class="btn btn-outline-primary">
                    🛍️ Seguir comprando
                </a>
                <form method="POST" action="carrito.php" onsubmit="return confirm('¿Vaciar todo el carrito?');">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="vaciar">
                    <button type="submit" class="btn btn-outline-danger">
                        🗑️ Vaciar carrito
                    </button>
                </form>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title mb-3">📊 Resumen de compra</h5>

                    <div class="d-flex justify-content-between mb-2">
                        <span>Subtotal (<?= $total_unidades ?> productos):</span>
                        <span class="fw-semibold"><?= formato_precio($totales['subtotal']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span>IVA (<?= IVA ?>%):</span>
                        <span class="fw-semibold"><?= formato_precio($totales['iva']) ?></span>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span>Envío:</span>
                        <span class="fw-semibold"><?= formato_precio($totales['envio']) ?></span>
                    </div>

                    <hr>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="fs-5 fw-bold">TOTAL:</span>
                        <span class="fs-5 fw-bold text-primary">
                            <?= formato_precio($totales['total']) ?>
                        </span>
                    </div>

                    <?php if (esta_logueado()): ?>
                        <a href="checkout.php" class="btn btn-success btn-lg w-100">
                            💳 Ir a pagar
                        </a>
                    <?php else: ?>
                        <a href="login.php?redirect=checkout.php" class="btn btn-warning btn-lg w-100">
                            🔑 Inicia sesión para pagar
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>