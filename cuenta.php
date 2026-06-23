<?php
// ============================================================
// cuenta.php - Perfil y Estado de Compras del Usuario
// ============================================================
// [PEDAGÓGICO] Página donde el usuario logueado puede ver
// el historial de sus pedidos, filtrar por nombre de producto
// o número de pedido, y consultar el estado de cada compra.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

// Verificar que el usuario esté logueado
if (!esta_logueado()) {
    $_SESSION['error'] = 'Debes iniciar sesión para ver tu cuenta.';
    redireccionar('login.php?redirect=cuenta.php');
}

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$usuario_id = (int) $_SESSION['usuario_id'];

// ============================================================
// Parámetros de búsqueda y filtrado
// ============================================================
$buscar_numero = isset($_GET['buscar_numero']) ? trim($_GET['buscar_numero']) : '';
$buscar_producto = isset($_GET['buscar_producto']) ? trim($_GET['buscar_producto']) : '';
$filtro_estado = isset($_GET['estado']) ? trim($_GET['estado']) : '';

// ============================================================
// Consultar pedidos del usuario con filtros
// ============================================================
$where = ["p.usuario_id = :uid"];
$params = [':uid' => $usuario_id];

if ($buscar_numero !== '') {
    $where[] = "p.numero LIKE :numero";
    $params[':numero'] = '%' . $buscar_numero . '%';
}

if ($buscar_producto !== '') {
    $where[] = "dp.nombre_producto LIKE :producto OR p.id IN (
        SELECT pedido_id FROM detalles_pedido WHERE nombre_producto LIKE :producto2
    )";
    $params[':producto'] = '%' . $buscar_producto . '%';
    $params[':producto2'] = '%' . $buscar_producto . '%';
}

if ($filtro_estado !== '') {
    $where[] = "p.estado = :estado";
    $params[':estado'] = $filtro_estado;
}

$where_sql = implode(' AND ', $where);

// Obtener pedidos
$stmt = $pdo->prepare("
    SELECT p.id, p.numero, p.estado, p.subtotal, p.iva, p.costo_envio, p.total,
           p.direccion_envio, p.fecha_creacion
    FROM pedidos p
    WHERE $where_sql
    ORDER BY p.fecha_creacion DESC
");
$stmt->execute($params);
$pedidos = $stmt->fetchAll();

// Obtener lista de estados para el filtro
$estados = ['pendiente', 'confirmado', 'en_proceso', 'enviado', 'entregado', 'cancelado', 'reembolsado'];
?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">👤 Mis Compras</h1>
    <a href="index.php" class="btn btn-outline-primary btn-sm">
        ← Volver al inicio
    </a>
</div>

<!-- Mensajes -->
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= escapar($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!empty($_SESSION['exito'])): ?>
    <div class="alert alert-success"><?= escapar($_SESSION['exito']) ?></div>
    <?php unset($_SESSION['exito']); ?>
<?php endif; ?>

<!-- Información del usuario -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <div class="row align-items-center">
            <div class="col-md-6">
                <h5 class="mb-1"><?= escapar($_SESSION['usuario_nombre'] ?? 'Usuario') ?></h5>
                <p class="text-muted mb-0"><?= escapar($_SESSION['usuario_email'] ?? '') ?></p>
            </div>
            <div class="col-md-6 text-md-end">
                <small class="text-muted">Rol: <span class="badge bg-info"><?= escapar($_SESSION['usuario_rol'] ?? 'cliente') ?></span></small>
            </div>
        </div>
    </div>
</div>

<!-- Filtros de búsqueda -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" action="cuenta.php" class="row g-3">
            <div class="col-md-4">
                <label for="buscar_numero" class="form-label fw-semibold">🔍 N° de Pedido</label>
                <input type="text"
                       id="buscar_numero"
                       name="buscar_numero"
                       class="form-control"
                       placeholder="Ej: ORD-2026-00001"
                       value="<?= escapar($buscar_numero) ?>">
            </div>
            <div class="col-md-4">
                <label for="buscar_producto" class="form-label fw-semibold">📦 Producto</label>
                <input type="text"
                       id="buscar_producto"
                       name="buscar_producto"
                       class="form-control"
                       placeholder="Buscar por nombre de producto"
                       value="<?= escapar($buscar_producto) ?>">
            </div>
            <div class="col-md-3">
                <label for="estado" class="form-label fw-semibold">📊 Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php foreach ($estados as $est): ?>
                        <option value="<?= $est ?>" <?= $filtro_estado === $est ? 'selected' : '' ?>>
                            <?= ucfirst($est) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-1 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">🔍</button>
            </div>
        </form>
        <?php if ($buscar_numero !== '' || $buscar_producto !== '' || $filtro_estado !== ''): ?>
            <div class="mt-2">
                <a href="cuenta.php" class="btn btn-outline-secondary btn-sm">✕ Limpiar filtros</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Listado de pedidos -->
<?php if (empty($pedidos)): ?>
    <div class="alert alert-info text-center py-5">
        <div style="font-size: 3rem;">📭</div>
        <h5>No se encontraron compras</h5>
        <p class="mb-0">
            <?php if ($buscar_numero !== '' || $buscar_producto !== '' || $filtro_estado !== ''): ?>
                No hay pedidos que coincidan con los filtros aplicados.
                <br><a href="cuenta.php" class="btn btn-outline-primary btn-sm mt-2">Mostrar todos</a>
            <?php else: ?>
                Aún no has realizado ninguna compra.
                <br><a href="index.php" class="btn btn-primary btn-sm mt-2">Ir al catálogo</a>
            <?php endif; ?>
        </p>
    </div>
<?php else: ?>
    <div class="row g-4">
        <?php foreach ($pedidos as $pedido): ?>
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <div>
                        <strong># <?= escapar($pedido['numero']) ?></strong>
                        <small class="text-muted ms-2"><?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?></small>
                    </div>
                    <div>
                        <?php
                        $badge_class = match ($pedido['estado']) {
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
                        <span class="badge <?= $badge_class ?> fs-6"><?= escapar($pedido['estado']) ?></span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8">
                            <!-- Detalles del pedido -->
                            <?php
                            $stmt_det = $pdo->prepare("SELECT nombre_producto, cantidad, precio_unitario, subtotal FROM detalles_pedido WHERE pedido_id = :pid");
                            $stmt_det->execute([':pid' => $pedido['id']]);
                            $detalles = $stmt_det->fetchAll();
                            ?>
                            <h6 class="fw-semibold mb-2">📦 Productos:</h6>
                            <ul class="list-group list-group-flush mb-3">
                                <?php foreach ($detalles as $det): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center py-1 px-0 border-0">
                                    <span><?= escapar($det['nombre_producto']) ?> <small class="text-muted">× <?= (int) $det['cantidad'] ?></small></span>
                                    <span class="fw-semibold"><?= formato_precio($det['subtotal']) ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                        <div class="col-md-4 border-start">
                            <div class="ps-3">
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Subtotal:</small>
                                    <small><?= formato_precio($pedido['subtotal']) ?></small>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">IVA (<?= IVA ?>%):</small>
                                    <small><?= formato_precio($pedido['iva']) ?></small>
                                </div>
                                <div class="d-flex justify-content-between mb-1">
                                    <small class="text-muted">Envío:</small>
                                    <small><?= formato_precio($pedido['costo_envio']) ?></small>
                                </div>
                                <hr class="my-1">
                                <div class="d-flex justify-content-between">
                                    <strong>TOTAL:</strong>
                                    <strong class="text-success"><?= formato_precio($pedido['total']) ?></strong>
                                </div>
                                <hr>
                                <small class="text-muted d-block">
                                    📍 <?= escapar($pedido['direccion_envio']) ?>
                                </small>
                                <!-- Estado del pago -->
                                <?php
                                $stmt_pago = $pdo->prepare("SELECT metodo, estado FROM pagos WHERE pedido_id = :pid");
                                $stmt_pago->execute([':pid' => $pedido['id']]);
                                $pago = $stmt_pago->fetch();
                                if ($pago):
                                ?>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        💳 Pago: <?= escapar($pago['metodo']) ?> -
                                        <span class="badge bg-<?= $pago['estado'] === 'completado' ? 'success' : ($pago['estado'] === 'rechazado' ? 'danger' : 'warning') ?>">
                                            <?= escapar($pago['estado']) ?>
                                        </span>
                                    </small>
                                </div>
                                <?php endif; ?>

                                <!-- Botón Pagar ahora si está pendiente -->
                                <?php if ($pedido['estado'] === 'pendiente'): ?>
                                <div class="mt-3">
                                    <a href="pago.php?orden_id=<?= (int) $pedido['id'] ?>" class="btn btn-success w-100">
                                        💳 Pagar ahora
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>