<?php
// ============================================================
// Dashboard - Panel de Administración
// ============================================================
// [PEDAGÓGICO] El dashboard es la página de inicio del panel
// admin. Muestra métricas clave del negocio: total de productos,
// pedidos del mes, usuarios registrados, ingresos del mes.
// También incluye gráficos de ventas, distribución por categoría
// y métricas ampliadas.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

if (!esta_logueado() || !es_admin()) {
    redireccionar(SITE_URL . '/login.php');
}

$pdo = getDB();

// ============================================================
// 1. Métricas principales
// ============================================================

$stmt = $pdo->query("SELECT COUNT(*) as total FROM productos WHERE activo = 1");
$total_productos = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total
    FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW())
      AND YEAR(fecha_creacion) = YEAR(NOW())
");
$stmt->execute();
$pedidos_mes = (int) $stmt->fetch()['total'];

$stmt = $pdo->query("SELECT COUNT(*) as total FROM usuarios WHERE activo = 1");
$total_usuarios = (int) $stmt->fetch()['total'];

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) as total
    FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW())
      AND YEAR(fecha_creacion) = YEAR(NOW())
      AND estado NOT IN ('cancelado', 'reembolsado')
");
$stmt->execute();
$ingresos_mes = (float) $stmt->fetch()['total'];

// ============================================================
// 2. Variación vs mes anterior
// ============================================================

$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(total), 0) as total
    FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW() - INTERVAL 1 MONTH)
      AND YEAR(fecha_creacion) = YEAR(NOW() - INTERVAL 1 MONTH)
      AND estado NOT IN ('cancelado', 'reembolsado')
");
$stmt->execute();
$ingresos_mes_anterior = (float) $stmt->fetch()['total'];
$variacion_ingresos = $ingresos_mes_anterior > 0
    ? round((($ingresos_mes - $ingresos_mes_anterior) / $ingresos_mes_anterior) * 100, 1)
    : null;

$stmt = $pdo->prepare("
    SELECT COUNT(*) as total FROM pedidos
    WHERE MONTH(fecha_creacion) = MONTH(NOW() - INTERVAL 1 MONTH)
      AND YEAR(fecha_creacion) = YEAR(NOW() - INTERVAL 1 MONTH)
");
$stmt->execute();
$pedidos_mes_anterior = (int) $stmt->fetch()['total'];
$variacion_pedidos = $pedidos_mes_anterior > 0
    ? round((($pedidos_mes - $pedidos_mes_anterior) / $pedidos_mes_anterior) * 100, 1)
    : null;

// ============================================================
// 3. Pedidos recientes (últimos 10)
// ============================================================

$stmt = $pdo->query("
    SELECT p.id, p.numero, p.estado, p.total, p.fecha_creacion,
           u.nombre, u.apellido
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    ORDER BY p.fecha_creacion DESC
    LIMIT 10
");
$pedidos_recientes = $stmt->fetchAll();

// ============================================================
// 4. Alertas de stock bajo
// ============================================================

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

// ============================================================
// 5. Ventas de los últimos 6 meses (para gráfico de línea)
// ============================================================

$stmt = $pdo->query("
    SELECT
        DATE_FORMAT(fecha_creacion, '%Y-%m') AS mes,
        DATE_FORMAT(fecha_creacion, '%b %Y') AS mes_label,
        COUNT(*) AS num_pedidos,
        COALESCE(SUM(total), 0) AS ingresos
    FROM pedidos
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
      AND estado NOT IN ('cancelado', 'reembolsado')
    GROUP BY mes, mes_label
    ORDER BY mes ASC
");
$ventas_meses = $stmt->fetchAll();

$chart_labels   = json_encode(array_column($ventas_meses, 'mes_label'));
$chart_ingresos = json_encode(array_map('floatval', array_column($ventas_meses, 'ingresos')));
$chart_pedidos  = json_encode(array_map('intval', array_column($ventas_meses, 'num_pedidos')));

// ============================================================
// 6. Ventas por categoría (para gráfico de dona)
// ============================================================

$stmt = $pdo->query("
    SELECT
        c.nombre AS categoria,
        COUNT(DISTINCT p.id) AS num_pedidos,
        COALESCE(SUM(dp.subtotal), 0) AS total_ventas
    FROM detalle_pedidos dp
    JOIN productos pr ON pr.id = dp.producto_id
    JOIN categorias c ON c.id = pr.categoria_id
    JOIN pedidos p ON p.id = dp.pedido_id
    WHERE p.estado NOT IN ('cancelado', 'reembolsado')
    GROUP BY c.id, c.nombre
    ORDER BY total_ventas DESC
    LIMIT 6
");
$ventas_categorias = $stmt->fetchAll();

$cat_labels = json_encode(array_column($ventas_categorias, 'categoria'));
$cat_totales = json_encode(array_map('floatval', array_column($ventas_categorias, 'total_ventas')));

// ============================================================
// 7. Distribución de pedidos por estado
// ============================================================

$stmt = $pdo->query("
    SELECT estado, COUNT(*) AS total
    FROM pedidos
    WHERE fecha_creacion >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
    GROUP BY estado
    ORDER BY total DESC
");
$pedidos_por_estado = $stmt->fetchAll();

$estado_labels = json_encode(array_column($pedidos_por_estado, 'estado'));
$estado_totales = json_encode(array_map('intval', array_column($pedidos_por_estado, 'total')));

// ============================================================
// 8. Top 5 productos más vendidos
// ============================================================

$stmt = $pdo->query("
    SELECT pr.nombre, pr.sku,
           SUM(dp.cantidad) AS unidades_vendidas,
           SUM(dp.subtotal) AS total_generado
    FROM detalle_pedidos dp
    JOIN productos pr ON pr.id = dp.producto_id
    JOIN pedidos p ON p.id = dp.pedido_id
    WHERE p.estado NOT IN ('cancelado', 'reembolsado')
    GROUP BY pr.id, pr.nombre, pr.sku
    ORDER BY unidades_vendidas DESC
    LIMIT 5
");
$top_productos = $stmt->fetchAll();

require_once __DIR__ . '/admin_header.php';
?>

<!-- Título -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-speedometer2 me-2"></i>Dashboard
    </h2>
    <small class="text-muted">Última actualización: <?= date('d/m/Y H:i') ?></small>
</div>

<!-- ============================================================
     Tarjetas KPI
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Productos activos -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100" style="border-left-color:#0d6efd!important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-box-seam"></i>
                </div>
                <div class="metric-value"><?= $total_productos ?></div>
                <div class="metric-label">Productos activos</div>
            </div>
        </div>
    </div>

    <!-- Pedidos del mes -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100" style="border-left-color:#198754!important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-cart-check"></i>
                </div>
                <div class="metric-value"><?= $pedidos_mes ?></div>
                <div class="metric-label">Pedidos este mes</div>
                <?php if ($variacion_pedidos !== null): ?>
                    <div class="mt-1">
                        <span class="badge <?= $variacion_pedidos >= 0 ? 'bg-success' : 'bg-danger' ?> fs-small">
                            <i class="bi bi-arrow-<?= $variacion_pedidos >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($variacion_pedidos) ?>% vs mes anterior
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Usuarios -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100" style="border-left-color:#ffc107!important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-people"></i>
                </div>
                <div class="metric-value"><?= $total_usuarios ?></div>
                <div class="metric-label">Usuarios registrados</div>
            </div>
        </div>
    </div>

    <!-- Ingresos del mes -->
    <div class="col-sm-6 col-xl-3">
        <div class="card metric-card border-0 shadow-sm h-100" style="border-left-color:#dc3545!important;">
            <div class="card-body position-relative">
                <div class="metric-icon position-absolute top-0 end-0 me-3 mt-2">
                    <i class="bi bi-currency-dollar"></i>
                </div>
                <div class="metric-value"><?= formato_precio($ingresos_mes) ?></div>
                <div class="metric-label">Ingresos este mes</div>
                <?php if ($variacion_ingresos !== null): ?>
                    <div class="mt-1">
                        <span class="badge <?= $variacion_ingresos >= 0 ? 'bg-success' : 'bg-danger' ?> fs-small">
                            <i class="bi bi-arrow-<?= $variacion_ingresos >= 0 ? 'up' : 'down' ?>"></i>
                            <?= abs($variacion_ingresos) ?>% vs mes anterior
                        </span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     Fila de gráficos: Ventas + Dona categorías
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Gráfico de línea: ventas últimos 6 meses -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-graph-up me-2 text-primary"></i>Ventas — últimos 6 meses</h5>
                <div class="btn-group btn-group-sm" role="group">
                    <button type="button" class="btn btn-outline-secondary active" onclick="toggleChart('ingresos')">Ingresos</button>
                    <button type="button" class="btn btn-outline-secondary" onclick="toggleChart('pedidos')">Pedidos</button>
                </div>
            </div>
            <div class="card-body">
                <canvas id="chartVentas" height="100"></canvas>
            </div>
        </div>
    </div>

    <!-- Gráfico de dona: ventas por categoría -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-pie-chart me-2 text-warning"></i>Ventas por categoría</h5>
            </div>
            <div class="card-body d-flex flex-column align-items-center justify-content-center">
                <?php if (empty($ventas_categorias)): ?>
                    <div class="text-center text-muted py-4">
                        <i class="bi bi-bar-chart" style="font-size:2rem;"></i>
                        <p class="mt-2">Sin datos de ventas aún.</p>
                    </div>
                <?php else: ?>
                    <canvas id="chartCategorias" style="max-height:220px;"></canvas>
                <?php endif; ?>
            </div>
        </div>
    </div>

</div>

<!-- ============================================================
     Fila: Pedidos recientes + Estado de pedidos + Stock bajo
     ============================================================ -->
<div class="row g-4 mb-4">

    <!-- Pedidos recientes -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Pedidos Recientes</h5>
                <a href="<?= SITE_URL ?>/admin/pedidos.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($pedidos_recientes)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">No hay pedidos registrados aún.</p>
                    </div>
                <?php else: ?>
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
                                    <td><strong><?= escapar($pedido['numero']) ?></strong></td>
                                    <td><?= escapar($pedido['nombre'] . ' ' . $pedido['apellido']) ?></td>
                                    <td><?= formato_precio($pedido['total']) ?></td>
                                    <td>
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
                                        <span class="badge <?= $badge_class ?>"><?= escapar($pedido['estado']) ?></span>
                                    </td>
                                    <td class="text-muted small"><?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?></td>
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

    <!-- Columna derecha: Estado de pedidos + Stock bajo -->
    <div class="col-lg-4 d-flex flex-column gap-4">

        <!-- Distribución por estado (mini gráfico de barras) -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-bar-chart-steps me-2 text-info"></i>Estado de pedidos (3 meses)</h5>
            </div>
            <div class="card-body">
                <?php if (empty($pedidos_por_estado)): ?>
                    <p class="text-muted text-center mb-0">Sin datos.</p>
                <?php else: ?>
                    <canvas id="chartEstados" height="160"></canvas>
                <?php endif; ?>
            </div>
        </div>

        <!-- Stock bajo -->
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-exclamation-triangle me-2 text-warning"></i>Stock Bajo</h5>
                <a href="<?= SITE_URL ?>/admin/inventario.php" class="btn btn-sm btn-outline-primary">Inventario</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($stock_bajo)): ?>
                    <div class="text-center py-4 text-success">
                        <i class="bi bi-check-circle" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">Sin alertas de stock.</p>
                    </div>
                <?php else: ?>
                    <ul class="list-group list-group-flush">
                        <?php foreach ($stock_bajo as $item): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="stock-bajo"><?= escapar($item['nombre']) ?></strong><br>
                                <small class="text-muted">SKU: <?= escapar($item['sku']) ?></small>
                            </div>
                            <div class="text-end">
                                <span class="badge bg-danger rounded-pill"><?= (int) $item['disponible'] ?> disp.</span><br>
                                <small class="text-muted">Umbral: <?= (int) $item['umbral_alerta'] ?></small>
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
     Top 5 productos más vendidos
     ============================================================ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="bi bi-trophy me-2 text-warning"></i>Top 5 productos más vendidos</h5>
                <a href="<?= SITE_URL ?>/admin/productos.php" class="btn btn-sm btn-outline-primary">Ver productos</a>
            </div>
            <div class="card-body p-0">
                <?php if (empty($top_productos)): ?>
                    <div class="text-center py-4 text-muted">
                        <i class="bi bi-bag" style="font-size:2rem;"></i>
                        <p class="mt-2 mb-0">Sin ventas registradas aún.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th>Unidades vendidas</th>
                                    <th>Total generado</th>
                                    <th>Popularidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $max_unidades = !empty($top_productos) ? (int)$top_productos[0]['unidades_vendidas'] : 1;
                                foreach ($top_productos as $i => $prod):
                                    $pct = $max_unidades > 0 ? round(($prod['unidades_vendidas'] / $max_unidades) * 100) : 0;
                                ?>
                                <tr>
                                    <td>
                                        <?php if ($i === 0): ?>
                                            <i class="bi bi-trophy-fill text-warning fs-5"></i>
                                        <?php else: ?>
                                            <span class="text-muted fw-bold"><?= $i + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><strong><?= escapar($prod['nombre']) ?></strong></td>
                                    <td><code><?= escapar($prod['sku']) ?></code></td>
                                    <td><?= number_format((int)$prod['unidades_vendidas']) ?> uds.</td>
                                    <td><?= formato_precio((float)$prod['total_generado']) ?></td>
                                    <td style="min-width:140px;">
                                        <div class="progress" style="height:8px;">
                                            <div class="progress-bar bg-primary" style="width:<?= $pct ?>%"></div>
                                        </div>
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
</div>

<!-- ============================================================
     Accesos rápidos
     ============================================================ -->
<div class="row g-4 mt-0">
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
// Scripts de Chart.js — se inyectan antes del </body> mediante
// la variable $scripts_adicionales que lee admin_footer.php
// ============================================================
$scripts_adicionales = <<<HTML
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
// ── Datos desde PHP ─────────────────────────────────────────
const labelsVentas   = {$chart_labels};
const dataIngresos   = {$chart_ingresos};
const dataPedidos    = {$chart_pedidos};
const labelsCats     = {$cat_labels};
const dataCats       = {$cat_totales};
const labelsEstados  = {$estado_labels};
const dataEstados    = {$estado_totales};

// ── Paleta de colores ────────────────────────────────────────
const COLORES = [
    'rgba(13,110,253,0.8)', 'rgba(25,135,84,0.8)', 'rgba(255,193,7,0.8)',
    'rgba(220,53,69,0.8)',  'rgba(13,202,240,0.8)', 'rgba(111,66,193,0.8)'
];
const COLORES_BORDE = COLORES.map(c => c.replace('0.8','1'));

// ── 1. Gráfico de línea: ventas ──────────────────────────────
let modoActual = 'ingresos';
const ctxVentas = document.getElementById('chartVentas').getContext('2d');
const chartVentas = new Chart(ctxVentas, {
    type: 'line',
    data: {
        labels: labelsVentas,
        datasets: [{
            label: 'Ingresos (CLP)',
            data: dataIngresos,
            borderColor: 'rgba(13,110,253,1)',
            backgroundColor: 'rgba(13,110,253,0.1)',
            borderWidth: 2.5,
            pointRadius: 4,
            pointHoverRadius: 6,
            fill: true,
            tension: 0.3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            tooltip: {
                callbacks: {
                    label: ctx => {
                        const v = ctx.parsed.y;
                        return modoActual === 'ingresos'
                            ? '\$ ' + v.toLocaleString('es-CL')
                            : v + ' pedidos';
                    }
                }
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: v => modoActual === 'ingresos'
                        ? '\$' + (v/1000).toFixed(0) + 'k'
                        : v
                }
            }
        }
    }
});

window.toggleChart = function(modo) {
    modoActual = modo;
    chartVentas.data.datasets[0].data  = modo === 'ingresos' ? dataIngresos : dataPedidos;
    chartVentas.data.datasets[0].label = modo === 'ingresos' ? 'Ingresos (CLP)' : 'Pedidos';
    chartVentas.data.datasets[0].borderColor     = modo === 'ingresos' ? 'rgba(13,110,253,1)' : 'rgba(25,135,84,1)';
    chartVentas.data.datasets[0].backgroundColor = modo === 'ingresos' ? 'rgba(13,110,253,0.1)' : 'rgba(25,135,84,0.1)';
    chartVentas.update();

    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    event.target.classList.add('active');
};

// ── 2. Gráfico de dona: categorías ──────────────────────────
if (labelsCats.length > 0) {
    const ctxCats = document.getElementById('chartCategorias').getContext('2d');
    new Chart(ctxCats, {
        type: 'doughnut',
        data: {
            labels: labelsCats,
            datasets: [{
                data: dataCats,
                backgroundColor: COLORES,
                borderColor: COLORES_BORDE,
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { position: 'bottom', labels: { font: { size: 11 } } },
                tooltip: {
                    callbacks: {
                        label: ctx => {
                            const total = ctx.dataset.data.reduce((a,b) => a+b, 0);
                            const pct   = total > 0 ? ((ctx.parsed / total) * 100).toFixed(1) : 0;
                            return ctx.label + ': \$' + ctx.parsed.toLocaleString('es-CL') + ' (' + pct + '%)';
                        }
                    }
                }
            }
        }
    });
}

// ── 3. Gráfico de barras horizontales: estados ───────────────
if (labelsEstados.length > 0) {
    const ctxEstados = document.getElementById('chartEstados').getContext('2d');
    new Chart(ctxEstados, {
        type: 'bar',
        data: {
            labels: labelsEstados,
            datasets: [{
                label: 'Pedidos',
                data: dataEstados,
                backgroundColor: COLORES,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                x: { beginAtZero: true, ticks: { stepSize: 1 } }
            }
        }
    });
}
</script>
HTML;

require_once __DIR__ . '/admin_footer.php';
?>
