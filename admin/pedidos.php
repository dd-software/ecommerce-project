<?php
// ============================================================
// pedidos.php - Gestión de Pedidos (Panel Admin)
// ============================================================
// [PEDAGÓGICO] Panel para administrar pedidos:
// - Listado con filtros por estado y fecha
// - Cambiar estado con botones (solo transiciones válidas)
// - Ver detalle completo del pedido (items + pago + dirección)
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// ============================================================
// Verificar permisos de administrador
// ============================================================
if (!esta_logueado() || !es_admin()) {
    redireccionar(SITE_URL . '/login.php');
}

$pdo = getDB();

// ============================================================
// Mensajes flash
// ============================================================
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// ============================================================
// Determinar acción
// ============================================================
$accion = $_GET['accion'] ?? 'listar';
$pedido_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ============================================================
// Mapa de transiciones válidas de estado
// ============================================================
// [PEDAGÓGICO] No todos los cambios de estado son válidos.
// Un pedido 'pendiente' puede ir a 'confirmado' o 'cancelado',
// pero no puede ir directamente a 'entregado'. Este mapa
// controla las transiciones permitidas.
$transiciones_validas = [
    'pendiente'   => ['confirmado', 'cancelado'],
    'confirmado'  => ['en_proceso', 'cancelado', 'reembolsado'],
    'en_proceso'  => ['enviado', 'cancelado'],
    'enviado'     => ['entregado', 'cancelado'],
    'entregado'   => ['reembolsado'],
    'cancelado'   => ['reembolsado'],
    'reembolsado' => [],
];

// ============================================================
// Manejar POST: cambiar estado del pedido
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cambiar_estado'])) {

    // Validar CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_validar($csrf)) {
        $_SESSION['mensaje_error'] = 'Error de seguridad: token CSRF inválido.';
        redireccionar(SITE_URL . '/admin/pedidos.php');
    }

    $pedido_id_post = (int) ($_POST['pedido_id'] ?? 0);
    $nuevo_estado   = $_POST['nuevo_estado'] ?? '';

    // Obtener estado actual del pedido
    $stmt = $pdo->prepare("SELECT id, estado, numero FROM pedidos WHERE id = :id");
    $stmt->execute([':id' => $pedido_id_post]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        $_SESSION['mensaje_error'] = 'Pedido no encontrado.';
        redireccionar(SITE_URL . '/admin/pedidos.php');
    }

    $estado_actual = $pedido['estado'];

    // Validar que la transición sea permitida
    $permitidos = $transiciones_validas[$estado_actual] ?? [];
    if (!in_array($nuevo_estado, $permitidos)) {
        $_SESSION['mensaje_error'] = "Transición inválida: de «{$estado_actual}» a «{$nuevo_estado}».";
        redireccionar(SITE_URL . '/admin/pedidos.php');
    }

    // Actualizar estado
    $stmt = $pdo->prepare("UPDATE pedidos SET estado = :estado WHERE id = :id");
    $stmt->execute([':estado' => $nuevo_estado, ':id' => $pedido_id_post]);

    // Si el estado es 'cancelado' o 'reembolsado', liberar reservas de inventario
    if (in_array($nuevo_estado, ['cancelado', 'reembolsado'])) {
        $stmt_reservas = $pdo->prepare("
            UPDATE reservas_inventario
            SET estado = 'liberada'
            WHERE orden_id = :orden_id AND estado = 'activa'
        ");
        $stmt_reservas->execute([':orden_id' => $pedido_id_post]);
    }

    $_SESSION['mensaje_exito'] = "Pedido {$pedido['numero']} actualizado a «{$nuevo_estado}».";
    redireccionar(SITE_URL . '/admin/pedidos.php');
}

// ============================================================
// Obtener detalle completo del pedido (si se solicita)
// ============================================================
$detalle_pedido = null;
if ($accion === 'ver' && $pedido_id > 0) {
    // Cabecera del pedido con datos del usuario
    $stmt = $pdo->prepare("
        SELECT p.*, u.nombre, u.apellido, u.email
        FROM pedidos p
        JOIN usuarios u ON u.id = p.usuario_id
        WHERE p.id = :id
    ");
    $stmt->execute([':id' => $pedido_id]);
    $detalle_pedido = $stmt->fetch();

    if (!$detalle_pedido) {
        $_SESSION['mensaje_error'] = 'Pedido no encontrado.';
        redireccionar(SITE_URL . '/admin/pedidos.php');
    }

    // Items del pedido (detalles)
    $stmt = $pdo->prepare("
        SELECT dp.*, pr.sku
        FROM detalles_pedido dp
        LEFT JOIN productos pr ON pr.id = dp.producto_id
        WHERE dp.pedido_id = :pedido_id
        ORDER BY dp.id ASC
    ");
    $stmt->execute([':pedido_id' => $pedido_id]);
    $detalle_items = $stmt->fetchAll();

    // Pago asociado
    $stmt = $pdo->prepare("SELECT * FROM pagos WHERE pedido_id = :pedido_id");
    $stmt->execute([':pedido_id' => $pedido_id]);
    $detalle_pago = $stmt->fetch() ?: null;
}

// ============================================================
// Parámetros de filtro
// ============================================================
$filtro_estado = $_GET['estado'] ?? '';
$filtro_fecha_desde = $_GET['fecha_desde'] ?? '';
$filtro_fecha_hasta = $_GET['fecha_hasta'] ?? '';

// ============================================================
// Construir consulta con filtros
// ============================================================
$where   = ['1=1'];
$params  = [];

if ($filtro_estado !== '') {
    $where[]            = "p.estado = :estado";
    $params[':estado']  = $filtro_estado;
}
if ($filtro_fecha_desde !== '') {
    $where[]                    = "p.fecha_creacion >= :fecha_desde";
    $params[':fecha_desde']     = $filtro_fecha_desde . ' 00:00:00';
}
if ($filtro_fecha_hasta !== '') {
    $where[]                    = "p.fecha_creacion <= :fecha_hasta";
    $params[':fecha_hasta']     = $filtro_fecha_hasta . ' 23:59:59';
}

$where_sql = implode(' AND ', $where);

// Total de pedidos (para paginación)
$stmt_count = $pdo->prepare("SELECT COUNT(*) as total FROM pedidos p WHERE {$where_sql}");
$stmt_count->execute($params);
$total_pedidos = (int) $stmt_count->fetch()['total'];

// Paginación
$pagina      = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$por_pagina  = 20;
$offset      = ($pagina - 1) * $por_pagina;
$total_paginas = max(1, ceil($total_pedidos / $por_pagina));

// Obtener pedidos
$stmt = $pdo->prepare("
    SELECT p.id, p.numero, p.estado, p.total, p.fecha_creacion,
           u.nombre, u.apellido, u.email
    FROM pedidos p
    JOIN usuarios u ON u.id = p.usuario_id
    WHERE {$where_sql}
    ORDER BY p.fecha_creacion DESC
    LIMIT :limite OFFSET :offset
");
foreach ($params as $clave => $valor) {
    $stmt->bindValue($clave, $valor);
}
$stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$pedidos = $stmt->fetchAll();

// Incluir header del admin
require_once __DIR__ . '/admin_header.php';
?>

<!-- ============================================================
     Título
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-cart-check me-2"></i>
        <?php if ($accion === 'ver'): ?>
            Pedido #<?= escapar($detalle_pedido['numero'] ?? '') ?>
        <?php else: ?>
            Pedidos
        <?php endif; ?>
    </h2>
    <?php if ($accion === 'ver'): ?>
        <a href="<?= SITE_URL ?>/admin/pedidos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver a pedidos
        </a>
    <?php endif; ?>
</div>

<!-- ============================================================
     Mensajes flash
     ============================================================ -->
<?php if ($mensaje_exito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?= $mensaje_exito ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($mensaje_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= $mensaje_error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($accion === 'ver' && $detalle_pedido): ?>

<!-- ============================================================
     DETALLE DEL PEDIDO
     ============================================================ -->

<!-- Información General -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i>Información del Pedido</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th class="text-muted">Número:</th>
                        <td><strong><?= escapar($detalle_pedido['numero']) ?></strong></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Estado:</th>
                        <td>
                            <?php
                            $badge = match ($detalle_pedido['estado']) {
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
                            <span class="badge <?= $badge ?>"><?= escapar($detalle_pedido['estado']) ?></span>
                        </td>
                    </tr>
                    <tr>
                        <th class="text-muted">Fecha:</th>
                        <td><?= date('d/m/Y H:i', strtotime($detalle_pedido['fecha_creacion'])) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Notas:</th>
                        <td><?= escapar($detalle_pedido['notas'] ?? 'Sin notas') ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <div class="col-md-6">
        <div class="card shadow-sm border-0 h-100">
            <div class="card-header bg-white">
                <h5 class="mb-0"><i class="bi bi-person me-2"></i>Cliente</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <tr>
                        <th class="text-muted">Nombre:</th>
                        <td><?= escapar($detalle_pedido['nombre'] . ' ' . $detalle_pedido['apellido']) ?></td>
                    </tr>
                    <tr>
                        <th class="text-muted">Email:</th>
                        <td><?= escapar($detalle_pedido['email']) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Dirección de envío -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-geo-alt me-2"></i>Dirección de Envío</h5>
    </div>
    <div class="card-body">
        <p class="mb-0"><?= nl2br(escapar($detalle_pedido['direccion_envio'])) ?></p>
    </div>
</div>

<!-- Items del pedido -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Items del Pedido</h5>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Producto</th>
                        <th>SKU</th>
                        <th class="text-center">Cant.</th>
                        <th class="text-end">Precio Unit.</th>
                        <th class="text-end">Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($detalle_items as $item): ?>
                    <tr>
                        <td><?= escapar($item['nombre_producto']) ?></td>
                        <td><code><?= escapar($item['sku'] ?? '—') ?></code></td>
                        <td class="text-center"><?= (int) $item['cantidad'] ?></td>
                        <td class="text-end"><?= formato_precio($item['precio_unitario']) ?></td>
                        <td class="text-end fw-bold"><?= formato_precio($item['subtotal']) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-group-divider">
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Subtotal:</td>
                        <td class="text-end"><?= formato_precio($detalle_pedido['subtotal']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">IVA (<?= IVA ?>%):</td>
                        <td class="text-end"><?= formato_precio($detalle_pedido['iva']) ?></td>
                    </tr>
                    <tr>
                        <td colspan="4" class="text-end fw-semibold">Costo de envío:</td>
                        <td class="text-end"><?= formato_precio($detalle_pedido['costo_envio']) ?></td>
                    </tr>
                    <tr class="table-active">
                        <td colspan="4" class="text-end fw-bold fs-5">Total:</td>
                        <td class="text-end fw-bold fs-5"><?= formato_precio($detalle_pedido['total']) ?></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
</div>

<!-- Información de pago -->
<?php if ($detalle_pago): ?>
<div class="card shadow-sm border-0 mb-4">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-credit-card me-2"></i>Pago</h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-3">
                <strong>Método:</strong>
                <span class="badge bg-info">
                    <?= escapar($detalle_pago['metodo']) ?>
                </span>
            </div>
            <div class="col-md-3">
                <strong>Estado:</strong>
                <?php
                $pago_badge = match ($detalle_pago['estado']) {
                    'completado' => 'bg-success',
                    'pendiente'  => 'bg-warning text-dark',
                    'rechazado'  => 'bg-danger',
                    'reembolsado'=> 'bg-dark',
                    default      => 'bg-secondary',
                };
                ?>
                <span class="badge <?= $pago_badge ?>">
                    <?= escapar($detalle_pago['estado']) ?>
                </span>
            </div>
            <div class="col-md-3">
                <strong>Monto:</strong>
                <?= formato_precio($detalle_pago['monto']) ?>
            </div>
            <div class="col-md-3">
                <strong>Referencia:</strong>
                <?= escapar($detalle_pago['referencia_pasarela'] ?? '—') ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Acciones: cambiar estado -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="mb-0"><i class="bi bi-arrow-repeat me-2"></i>Cambiar Estado</h5>
    </div>
    <div class="card-body">
        <?php
        $estado_actual = $detalle_pedido['estado'];
        $transiciones = $transiciones_validas[$estado_actual] ?? [];
        ?>
        <?php if (empty($transiciones)): ?>
            <p class="text-muted mb-0">
                <i class="bi bi-info-circle me-1"></i>
                El estado «<?= $estado_actual ?>» no permite más transiciones.
            </p>
        <?php else: ?>
            <p class="mb-2">Estado actual: <strong><?= $estado_actual ?></strong></p>
            <div class="d-flex flex-wrap gap-2">
                <?php foreach ($transiciones as $transicion): ?>
                <form method="POST" action="pedidos.php"
                      style="display:inline;"
                      onsubmit="return confirm('¿Cambiar estado a «<?= $transicion ?>»?');">
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="cambiar_estado" value="1">
                    <input type="hidden" name="pedido_id" value="<?= (int) $detalle_pedido['id'] ?>">
                    <input type="hidden" name="nuevo_estado" value="<?= $transicion ?>">
                    <button type="submit" class="btn btn-outline-primary">
                        <i class="bi bi-arrow-right"></i>
                        Marcar como «<?= $transicion ?>»
                    </button>
                </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- ============================================================
     LISTADO DE PEDIDOS CON FILTROS
     ============================================================ -->

<!-- Filtros -->
<div class="card shadow-sm border-0 mb-4">
    <div class="card-body">
        <form method="GET" action="pedidos.php" class="row g-3 align-items-end">
            <div class="col-md-3">
                <label for="estado" class="form-label fw-semibold">Estado</label>
                <select id="estado" name="estado" class="form-select">
                    <option value="">Todos los estados</option>
                    <?php
                    $estados = ['pendiente', 'confirmado', 'en_proceso', 'enviado', 'entregado', 'cancelado', 'reembolsado'];
                    foreach ($estados as $est):
                    ?>
                        <option value="<?= $est ?>" <?= $filtro_estado === $est ? 'selected' : '' ?>>
                            <?= ucfirst($est) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label for="fecha_desde" class="form-label fw-semibold">Desde</label>
                <input type="date" id="fecha_desde" name="fecha_desde"
                       class="form-control" value="<?= escapar($filtro_fecha_desde) ?>">
            </div>
            <div class="col-md-3">
                <label for="fecha_hasta" class="form-label fw-semibold">Hasta</label>
                <input type="date" id="fecha_hasta" name="fecha_hasta"
                       class="form-control" value="<?= escapar($filtro_fecha_hasta) ?>">
            </div>
            <div class="col-md-3 d-flex gap-2">
                <button type="submit" class="btn btn-primary flex-fill">
                    <i class="bi bi-funnel"></i> Filtrar
                </button>
                <a href="pedidos.php" class="btn btn-outline-secondary flex-fill">
                    <i class="bi bi-x-circle"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabla de pedidos -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($pedidos)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-2 mb-0">
                    <?= $filtro_estado ? 'No hay pedidos con los filtros seleccionados.' : 'No hay pedidos registrados.' ?>
                </p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th># Pedido</th>
                            <th>Cliente</th>
                            <th>Email</th>
                            <th>Total</th>
                            <th>Estado</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pedidos as $pedido): ?>
                        <tr>
                            <td>
                                <strong><?= escapar($pedido['numero']) ?></strong>
                            </td>
                            <td><?= escapar($pedido['nombre'] . ' ' . $pedido['apellido']) ?></td>
                            <td><small><?= escapar($pedido['email']) ?></small></td>
                            <td><?= formato_precio($pedido['total']) ?></td>
                            <td>
                                <?php
                                $badge = match ($pedido['estado']) {
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
                                    <?= escapar($pedido['estado']) ?>
                                </span>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?>
                            </td>
                            <td>
                                <a href="?accion=ver&id=<?= (int) $pedido['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> Ver
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de pedidos" class="p-3">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                            &laquo; Anterior
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                            Siguiente &raquo;
                        </a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php
// ============================================================
// Incluir footer del admin
// ============================================================
require_once __DIR__ . '/admin_footer.php';
?>
