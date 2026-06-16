<?php
// ============================================================
// Página de Éxito - Compra Confirmada
// ============================================================
// [PEDAGÓGICO] Se muestra después de completar una compra.
// Recibe el número de orden vía GET (?orden=ORD-2026-00001)
// y muestra el resumen de la compra al usuario.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// ============================================================
// Obtener número de orden desde la URL
// ============================================================
$numero_orden = isset($_GET['orden']) ? trim($_GET['orden']) : '';

$pedido    = null;
$detalles  = [];
$error_msg = '';

if (empty($numero_orden)) {
    $error_msg = 'No se especificó un número de orden.';
} else {
    // ============================================================
    // Buscar el pedido en la base de datos
    // ============================================================
    // [PEDAGÓGICO] Si el usuario está logueado, solo puede ver
    // sus propias órdenes. Si es admin, puede ver cualquier orden.
    $sql  = "SELECT * FROM pedidos WHERE numero = :numero";
    $params = [':numero' => $numero_orden];

    // Si no es admin, filtrar por usuario actual
    if (!es_admin() && esta_logueado()) {
        $sql .= " AND usuario_id = :uid";
        $params[':uid'] = $_SESSION['usuario_id'];
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        $error_msg = 'Orden no encontrada. Verifica el número e intenta de nuevo.';
    } else {
        // ============================================================
        // Obtener detalles de la orden (productos comprados)
        // ============================================================
        $stmt = $pdo->prepare("
            SELECT dp.*, i.url as imagen_url
            FROM detalles_pedido dp
            LEFT JOIN (
                SELECT producto_id, url
                FROM imagenes
                WHERE es_principal = 1
            ) i ON i.producto_id = dp.producto_id
            WHERE dp.pedido_id = :pedido_id
            ORDER BY dp.id ASC
        ");
        $stmt->execute([':pedido_id' => $pedido['id']]);
        $detalles = $stmt->fetchAll();
    }
}
?>

<!-- ============================================================
     Mensaje de éxito y detalle de la orden
     ============================================================ -->
<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <?php if ($error_msg): ?>
            <!-- ============================================================
                 Error: orden no encontrada
                 ============================================================ -->
            <div class="text-center py-5">
                <div style="font-size: 4rem;">❌</div>
                <h3 class="mt-3">Orden no encontrada</h3>
                <p class="text-muted"><?= escapar($error_msg) ?></p>
                <a href="index.php" class="btn btn-primary mt-3">🏠 Volver al inicio</a>
            </div>
        <?php else: ?>
            <!-- ============================================================
                 Éxito: mostrar confirmación de la orden
                 ============================================================ -->
            <div class="text-center mb-5">
                <!-- Icono de éxito -->
                <div style="font-size: 5rem;">✅</div>
                <h2 class="mt-3 text-success">¡Compra realizada con éxito!</h2>
                <p class="text-muted fs-5">
                    Gracias por tu compra, <strong><?= escapar($_SESSION['usuario_nombre'] ?? '') ?></strong>.
                </p>
            </div>

            <!-- Card con resumen de la orden -->
            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <!-- Número de orden destacado -->
                    <div class="text-center mb-4 p-3 bg-light rounded">
                        <small class="text-muted text-uppercase">Número de orden</small>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= escapar($pedido['numero']) ?>
                        </h3>
                    </div>

                    <!-- Estado del pedido -->
                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Estado:</span>
                        <span class="badge bg-info fs-6">
                            <?= escapar(ucfirst($pedido['estado'])) ?>
                        </span>
                    </div>

                    <hr>

                    <!-- Productos comprados -->
                    <h5 class="mb-3">🛍️ Productos</h5>
                    <?php foreach ($detalles as $det): ?>
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <!-- Mini imagen -->
                        <div class="me-3">
                            <?php if (!empty($det['imagen_url'])): ?>
                                <img src="<?= escapar($det['imagen_url']) ?>"
                                     alt="<?= escapar($det['nombre_producto']) ?>"
                                     class="rounded"
                                     style="width: 45px; height: 45px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width: 45px; height: 45px;">
                                    📦
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <small class="fw-semibold d-block">
                                <?= escapar($det['nombre_producto']) ?>
                            </small>
                            <small class="text-muted">
                                <?= (int) $det['cantidad'] ?> x <?= formato_precio($det['precio_unitario']) ?>
                            </small>
                        </div>
                        <div class="fw-semibold">
                            <?= formato_precio($det['subtotal']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <hr>

                    <!-- Resumen de totales -->
                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span><?= formato_precio($pedido['subtotal']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">IVA (<?= IVA ?>%):</span>
                            <span><?= formato_precio($pedido['iva']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Envío:</span>
                            <span><?= formato_precio($pedido['costo_envio']) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fs-5 fw-bold">TOTAL:</span>
                            <span class="fs-5 fw-bold text-success">
                                <?= formato_precio($pedido['total']) ?>
                            </span>
                        </div>
                    </div>

                    <!-- Dirección de envío -->
                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted d-block fw-semibold mb-1">📦 Dirección de envío</small>
                        <small><?= nl2br(escapar($pedido['direccion_envio'])) ?></small>
                    </div>

                    <!-- Fecha de creación -->
                    <div class="mt-3 text-center text-muted small">
                        Fecha: <?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?>
                    </div>
                </div>
            </div>

            <!-- Botón para volver al inicio -->
            <div class="text-center mb-5">
                <a href="index.php" class="btn btn-primary btn-lg">
                    🏠 Volver al inicio
                </a>
                <?php if (esta_logueado()): ?>
                <a href="cuenta.php" class="btn btn-outline-primary btn-lg ms-2">
                    📋 Mis compras
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
