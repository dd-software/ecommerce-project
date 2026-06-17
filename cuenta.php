<?php
// ============================================================
// Mi Cuenta - Historial de Compras
// ============================================================
// [PEDAGÓGICO] Esta página muestra el historial de pedidos del
// usuario logueado. Es el destino del botón "📋 Mis compras"
// que aparece en exito.php tras completar una compra.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

$pdo = getDB();

// ============================================================
// Verificar autenticación
// ============================================================
// [PEDAGÓGICO] Igual que en checkout.php, si el usuario no ha
// iniciado sesión lo mandamos a login.php y luego de vuelta aquí.
if (!esta_logueado()) {
    $_SESSION['error'] = 'Debes iniciar sesión para ver tus compras.';
    redireccionar('login.php?redirect=' . urlencode('cuenta.php'));
}

// ============================================================
// Obtener los pedidos del usuario logueado
// ============================================================
$stmt = $pdo->prepare("
    SELECT id, numero, estado, total, fecha_creacion
    FROM pedidos
    WHERE usuario_id = :uid
    ORDER BY fecha_creacion DESC
");
$stmt->execute([':uid' => $_SESSION['usuario_id']]);
$pedidos = $stmt->fetchAll();

// ============================================================
// Si se pide el detalle de un pedido específico (?orden=ORD-...)
// ============================================================
$pedido_detalle = null;
$detalles        = [];
$numero_solicitado = $_GET['orden'] ?? '';

if (!empty($numero_solicitado)) {
    $stmt = $pdo->prepare("
        SELECT * FROM pedidos
        WHERE numero = :numero AND usuario_id = :uid
    ");
    $stmt->execute([
        ':numero' => $numero_solicitado,
        ':uid'    => $_SESSION['usuario_id'],
    ]);
    $pedido_detalle = $stmt->fetch();

    if ($pedido_detalle) {
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
        $stmt->execute([':pedido_id' => $pedido_detalle['id']]);
        $detalles = $stmt->fetchAll();
    }
}
?>
<?php
// ============================================================
// Incluir Header
// ============================================================
require_once __DIR__ . '/includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">📋 Mis Compras</h1>
    <a href="index.php" class="btn btn-outline-secondary btn-sm">
        ← Volver al catálogo
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= escapar($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if ($pedido_detalle): ?>
    <!-- ============================================================
         Vista de detalle de un pedido
         ============================================================ -->
    <div class="row justify-content-center">
        <div class="col-md-8 col-lg-6">
            <a href="cuenta.php" class="btn btn-link mb-3 px-0">← Volver a mis compras</a>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4 p-3 bg-light rounded">
                        <small class="text-muted text-uppercase">Número de orden</small>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= escapar($pedido_detalle['numero']) ?>
                        </h3>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Estado:</span>
                        <?php
                        $badge = match ($pedido_detalle['estado']) {
                            'pendiente'   => 'bg-warning text-dark',
                            'confirmado'  => 'bg-info',
                            'en_proceso'  => 'bg-primary',
                            'enviado'     => 'bg-secondary',
                            'entregado'   => 'bg-success',
                            'cancelado'   => 'bg-danger',
                            'reembolsado' => 'bg-dark',
                            default       => 'bg-light text-dark',
                        };
                        ?>
                        <span class="badge <?= $badge ?> fs-6">
                            <?= escapar(ucfirst($pedido_detalle['estado'])) ?>
                        </span>
                    </div>

                    <hr>

                    <h5 class="mb-3">🛍️ Productos</h5>
                    <?php foreach ($detalles as $det): ?>
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
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

                    <div class="mt-3">
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Subtotal:</span>
                            <span><?= formato_precio($pedido_detalle['subtotal']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">IVA (<?= IVA ?>%):</span>
                            <span><?= formato_precio($pedido_detalle['iva']) ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Envío:</span>
                            <span><?= formato_precio($pedido_detalle['costo_envio']) ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between">
                            <span class="fs-5 fw-bold">TOTAL:</span>
                            <span class="fs-5 fw-bold text-success">
                                <?= formato_precio($pedido_detalle['total']) ?>
                            </span>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted d-block fw-semibold mb-1">📦 Dirección de envío</small>
                        <small><?= nl2br(escapar($pedido_detalle['direccion_envio'])) ?></small>
                    </div>

                    <div class="mt-3 text-center text-muted small">
                        Fecha: <?= date('d/m/Y H:i', strtotime($pedido_detalle['fecha_creacion'])) ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

<?php else: ?>
    <!-- ============================================================
         Listado de pedidos del usuario
         ============================================================ -->
    <?php if (empty($pedidos)): ?>
        <div class="text-center py-5">
            <div style="font-size: 4rem;">🛒</div>
            <h4 class="mt-3">Aún no tienes compras</h4>
            <p class="text-muted">Cuando realices tu primera compra, aparecerá aquí.</p>
            <a href="index.php" class="btn btn-primary mt-2">📋 Ver catálogo</a>
        </div>
    <?php else: ?>
        <div class="card shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Número de orden</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Detalle</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $p): ?>
                        <tr>
                            <td><strong><?= escapar($p['numero']) ?></strong></td>
                            <td><?= date('d/m/Y H:i', strtotime($p['fecha_creacion'])) ?></td>
                            <td>
                                <?php
                                $badge = match ($p['estado']) {
                                    'pendiente'   => 'bg-warning text-dark',
                                    'confirmado'  => 'bg-info',
                                    'en_proceso'  => 'bg-primary',
                                    'enviado'     => 'bg-secondary',
                                    'entregado'   => 'bg-success',
                                    'cancelado'   => 'bg-danger',
                                    'reembolsado' => 'bg-dark',
                                    default       => 'bg-light text-dark',
                                };
                                ?>
                                <span class="badge <?= $badge ?>">
                                    <?= escapar(ucfirst($p['estado'])) ?>
                                </span>
                            </td>
                            <td class="text-end fw-semibold"><?= formato_precio($p['total']) ?></td>
                            <td class="text-end">
                                <a href="cuenta.php?orden=<?= urlencode($p['numero']) ?>"
                                   class="btn btn-outline-primary btn-sm">
                                    Ver detalle
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
