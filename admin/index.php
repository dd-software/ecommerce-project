<?php
// ============================================================
// Dashboard - Panel de Administración
// ============================================================
// [PEDAGÓGICO] El dashboard es la página de inicio del panel
// admin. Muestra métricas clave del negocio: total de productos,
// pedidos del mes, usuarios registrados, ingresos del mes.
// También incluye una tabla de pedidos recientes y alertas de
// stock bajo.
// ============================================================

// Incluir configuraciones y conexión BD
require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// ============================================================
// Verificar permisos de administrador
// ============================================================
// [PEDAGÓGICO] Cada página del panel DEBE verificar que el
// usuario sea administrador. Si no lo es, redirige al login
// o a la página principal.
if (!esta_logueado() || !es_admin()) {
    redireccionar(SITE_URL . 'login.php');
}

$pdo = getDB();

// ============================================================
// Obtener métricas del dashboard
// ============================================================

// 1. Total de productos activos
$stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$total_productos = (int) $stmt->fetch()['total'];

// 2. Pedidos del mes actual
$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW())
      AND YEAR(fecha_creacion) = YEAR(NOW())
");
$stmt->execute();
$pedidos_mes = (int) $stmt->fetch()['total'];

// 3. Total de usuarios registrados (activos)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$total_usuarios = (int) $stmt->fetch()['total'];

// 4. Ingresos del mes actual (pedidos confirmados en adelante)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) as total
    FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW())
      AND YEAR(fecha_creacion) = YEAR(NOW())
      AND estado NOT IN ('cancelado', 'reembolsado')
");
$stmt->execute();
$ingresos_mes = (float) $stmt->fetch()['total'];

// 5. Pedidos recientes (últimos 10)
$stmt = $pdo->query("
    SELECT p.id, p.numero, p.estado, p.total, p.fecha_creacion,
           u.nombre, u.apellido
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.fecha_creacion DESC
    LIMIT 10
");
$pedidos_recientes = $stmt->fetchAll();

// 6. Alertas de stock bajo (productos con cantidad < umbral_alerta)
$stmt = $pdo->query("
    SELECT pr.id, pr.sku, pr.nombre, inv.cantidad, inv.cantidad_reservada,
           (inv.cantidad - inv.cantidad_reservada) as disponible,
           inv.umbral_alerta
    FROM inventario inv
    JOIN productos pr ON pr.id = inv.producto_id
    WHERE (inv.cantidad - inv.cantidad_reservada) < inv.umbral_alerta
      AND pr.activo = 1
    ORDER BY (inv.cantidad - inv.cantidad_reservada) ASC
");
$stock_bajo = $stmt->fetchAll();

// Incluir header del admin
require_once __DIR__ . '/admin_header.php';
?>

<!-- ============================================================
     Título de la página
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-speedometer2 me-2"></i>
        Dashboard
    </h2>
    <small class="text-muted">
        Última actualización: <?= date('d/m/Y H:i') ?>
    </small>
</div>

<!-- ============================================================
     Tarjetas de Métricas (4 columnas en escritorio)
     ============================================================
     [PEDAGÓGICO] Las tarjetas muestran indicadores clave de
     rendimiento (KPI) del negocio. Cada una tiene un color
     de borde izquierdo diferente para identificar visualmente
     la categoría. -->
<div class="row g-4 mb-4">

    <!-- Tarjeta: Total Productos -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100"
             style="border-left-color: #0d6efd !important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="metric-value"><?= $total_productos ?></div>
                <div class="metric-label">Productos activos</div>
            </div>
        </div>
    </div>

    <!-- Tarjeta: Pedidos del Mes -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100"
             style="border-left-color: #198754 !important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="metric-value"><?= $pedidos_mes ?></div>
                <div class="metric-label">Pedidos este mes</div>
            </div>
        </div>
    </div>

    <!-- Tarjeta: Total Usuarios -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100"
             style="border-left-color: #ffc107 !important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-people"></i>
                </div>
                <div class="metric-value"><?= $total_usuarios ?></div>
                <div class="metric-label">Usuarios registrados</div>
            </div>
        </div>
    </div>

    <!-- Tarjeta: Ingresos del Mes -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100"
             style="border-left-color: #dc3545 !important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="metric-value"><?= formato_precio($ingresos_mes) ?></div>
                <div class="metric-label">Ingresos este mes</div>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     Fila de dos columnas: Pedidos Recientes + Alertas Stock
     ============================================================ -->
<div class="row g-4">

    <!-- ============================================================
         Columna izquierda: Pedidos Recientes
         ============================================================ -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-clock-history me-2"></i>
                    Pedidos Recientes
                </h5>
                <a href="<?= SITE_URL ?>/admin/pedidos.php" class="btn btn-sm btn-outline-primary">
                    Ver todos
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pedidos_recientes)): ?>
                    <!-- Mensaje cuando no hay pedidos -->
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">No hay pedidos registrados aún.</p>
                    </div>
                <?php else: ?>
                    <!-- Tabla de pedidos recientes -->
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th># Pedido</th>
                                    <th>Cliente</th>
                                    <th>Total</th>
                                    <th>Estado</th>
                                    <th>Fecha</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($pedidos_recientes as $pedido): ?>
                                <tr>
                                    <td>
                                        <strong><?= escapar($pedido['numero']) ?></strong>
                                    </td>
                                    <td>
                                        <?= escapar($pedido['nombre'] . ' ' . $pedido['apellido']) ?>
                                    </td>
                                    <td><?= formato_precio($pedido['total']) ?></td>
                                    <td>
                                        <?php
                                        // [PEDAGÓGICO] Badge de color según el estado del pedido
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
                                        <span class="badge <?= $badge_class ?>">
                                            <?= escapar($pedido['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-muted small">
                                        <?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?>
                                    </td>
                                    <td>
                                        <a href="<?= SITE_URL ?>/admin/pedidos.php?accion=ver&id=<?= (int) $pedido['id'] ?>"
                                           class="btn btn-sm btn-outline-secondary">
                                            <i class="bi bi-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ============================================================
         Columna derecha: Alertas de Stock Bajo
         ============================================================ -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-exclamation-triangle me-2 text-warning"></i>
                    Stock Bajo
                </h5>
                <a href="<?= SITE_URL ?>/admin/inventario.php" class="btn btn-sm btn-outline-primary">
                    Ir a inventario
                </a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stock_bajo)): ?>
                    <!-- Sin alertas de stock -->
                    <div class="text-center py-5 text-success">
                        <i class="bi bi-check-circle" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Todo en orden. No hay stock bajo.</p>
                    </div>
                <?php else: ?>
                    <!-- Lista de productos con stock bajo -->
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stock_bajo as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="stock-bajo">
                                    <?= escapar($item['nombre']) ?>
                                </strong>
                                <br>
                                <small class="text-muted">
                                    SKU: <?= escapar($item['sku']) ?>
                                </small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger rounded-pill">
                                    <?= (int) $item['disponible'] ?> disp.
                                </span>
                                <br>
                                <small class="text-muted">
                                    Umbral: <?= (int) $item['umbral_alerta'] ?>
                                </small>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     Enlaces rápidos a secciones principales
     ============================================================ -->
<div class="row g-4 mt-2">
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/productos.php"
           class="card text-decoration-none border-0 shadow-sm hover-shadow">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-box-seam fs-1 text-primary"></i>
                <div>
                    <h6 class="mb-1">Gestionar Productos</h6>
                    <small class="text-muted">Crear, editar, activar/desactivar productos</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/pedidos.php"
           class="card text-decoration-none border-0 shadow-sm hover-shadow">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-cart-check fs-1 text-success"></i>
                <div>
                    <h6 class="mb-1">Gestionar Pedidos</h6>
                    <small class="text-muted">Ver, filtrar y cambiar estado de pedidos</small>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-4">
        <a href="<?= SITE_URL ?>/admin/inventario.php"
           class="card text-decoration-none border-0 shadow-sm hover-shadow">
            <div class="card-body d-flex align-items-center gap-3">
                <i class="bi bi-boxes fs-1 text-warning"></i>
                <div>
                    <h6 class="mb-1">Gestionar Inventario</h6>
                    <small class="text-muted">Ajustar stock y ver movimientos</small>
                </div>
            </div>
        </a>
    </div>
</div>

<?php
// ============================================================
// Incluir footer del admin
// ============================================================
require_once __DIR__ . '/admin_footer.php';
?>
