<?php
// ============================================================
// inventario.php - Gestión de Inventario (Panel Admin)
// ============================================================
// [PEDAGÓGICO] Panel para administrar el inventario de productos:
// - Tabla con stock total, reservado, disponible, umbral y alerta
// - Formulario de ajuste manual (entrada/salida/ajuste)
// - Ver movimientos recientes de un producto
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
$producto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ============================================================
// Manejar POST: Ajuste manual de inventario
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajustar_stock'])) {

    // Validar CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_validar($csrf)) {
        $_SESSION['mensaje_error'] = 'Error de seguridad: token CSRF inválido.';
        redireccionar(SITE_URL . '/admin/inventario.php');
    }

    $prod_id   = (int) ($_POST['producto_id'] ?? 0);
    $tipo      = $_POST['tipo_movimiento'] ?? '';
    $cantidad  = (int) ($_POST['cantidad'] ?? 0);
    $motivo    = trim($_POST['motivo'] ?? '');

    // Validaciones
    $errores = [];
    if ($prod_id <= 0) {
        $errores[] = 'Debe seleccionar un producto.';
    }
    if (!in_array($tipo, ['entrada', 'salida', 'ajuste'])) {
        $errores[] = 'Tipo de movimiento inválido.';
    }
    if ($cantidad <= 0) {
        $errores[] = 'La cantidad debe ser mayor a 0.';
    }
    if ($motivo === '') {
        $errores[] = 'Debe ingresar un motivo para el movimiento.';
    }

    // Verificar que el producto existe y tiene inventario
    $stmt = $pdo->prepare("
        SELECT inv.*, pr.nombre, pr.activo
        FROM inventario inv
        JOIN productos pr ON pr.id = inv.producto_id
        WHERE inv.producto_id = :id
    ");
    $stmt->execute([':id' => $prod_id]);
    $inv_actual = $stmt->fetch();

    if (!$inv_actual) {
        $errores[] = 'El producto no tiene registro de inventario.';
    }

    // [PEDAGÓGICO] Verificar que no se pueda sacar más stock del disponible
    $disponible_actual = (int) ($inv_actual['cantidad'] ?? 0) - (int) ($inv_actual['cantidad_reservada'] ?? 0);
    if ($tipo === 'salida' && $cantidad > $disponible_actual) {
        $errores[] = "No hay suficiente stock disponible. Disponible: {$disponible_actual}";
    }

    if (empty($errores)) {
        // Ajustar cantidad según tipo
        $diferencia = match ($tipo) {
            'entrada' => $cantidad,           // Sumar
            'salida'  => -$cantidad,           // Restar
            'ajuste'  => $cantidad - $disponible_actual, // Diferencia exacta
        };

        // Actualizar inventario
        $nueva_cantidad = (int) $inv_actual['cantidad'] + $diferencia;
        $stmt = $pdo->prepare("
            UPDATE inventario
            SET cantidad = :cantidad
            WHERE producto_id = :producto_id
        ");
        $stmt->execute([
            ':cantidad'     => max(0, $nueva_cantidad),
            ':producto_id'  => $prod_id,
        ]);

        // Registrar movimiento en movimientos_inventario
        $stmt = $pdo->prepare("
            INSERT INTO movimientos_inventario
                (producto_id, tipo_movimiento, cantidad, referencia)
            VALUES
                (:producto_id, :tipo, :cantidad, :referencia)
        ");
        $stmt->execute([
            ':producto_id' => $prod_id,
            ':tipo'        => $tipo,
            ':cantidad'    => $cantidad,
            ':referencia'  => $motivo,
        ]);

        // Actualizar umbral si se envía
        if (isset($_POST['umbral_alerta']) && $_POST['umbral_alerta'] !== '') {
            $nuevo_umbral = (int) $_POST['umbral_alerta'];
            if ($nuevo_umbral >= 0) {
                $stmt = $pdo->prepare("
                    UPDATE inventario SET umbral_alerta = :umbral WHERE producto_id = :producto_id
                ");
                $stmt->execute([':umbral' => $nuevo_umbral, ':producto_id' => $prod_id]);
            }
        }

        $_SESSION['mensaje_exito'] = "Inventario de «{$inv_actual['nombre']}» actualizado.";
        redireccionar(SITE_URL . '/admin/inventario.php');
    } else {
        $_SESSION['mensaje_error'] = implode('<br>', $errores);
        redireccionar(SITE_URL . '/admin/inventario.php');
    }
}

// ============================================================
// Obtener datos del inventario
// ============================================================

// Producto específico para ver movimientos
$producto_info = null;
$movimientos = [];
if ($accion === 'movimientos' && $producto_id > 0) {
    $stmt = $pdo->prepare("
        SELECT pr.*, inv.cantidad, inv.cantidad_reservada,
               (inv.cantidad - inv.cantidad_reservada) as disponible,
               inv.umbral_alerta
        FROM productos pr
        JOIN inventario inv ON inv.producto_id = pr.id
        WHERE pr.id = :id
    ");
    $stmt->execute([':id' => $producto_id]);
    $producto_info = $stmt->fetch();

    if ($producto_info) {
        // Obtener movimientos del producto
        $stmt = $pdo->prepare("
            SELECT *
            FROM movimientos_inventario
            WHERE producto_id = :producto_id
            ORDER BY fecha DESC
            LIMIT 50
        ");
        $stmt->execute([':producto_id' => $producto_id]);
        $movimientos = $stmt->fetchAll();
    }
}

// Listado completo de inventario
$stmt = $pdo->query("
    SELECT pr.id, pr.sku, pr.nombre, pr.activo,
           inv.cantidad, inv.cantidad_reservada,
           (inv.cantidad - inv.cantidad_reservada) as disponible,
           inv.umbral_alerta,
           CASE WHEN (inv.cantidad - inv.cantidad_reservada) < inv.umbral_alerta THEN 1 ELSE 0 END as alerta
    FROM productos pr
    JOIN inventario inv ON inv.producto_id = pr.id
    ORDER BY alerta DESC, pr.nombre ASC
");
$inventario = $stmt->fetchAll();

// Productos para el selector del formulario de ajuste
$stmt = $pdo->query("
    SELECT id, sku, nombre
    FROM productos
    WHERE activo = 1
    ORDER BY nombre ASC
");
$productos_activos = $stmt->fetchAll();

// Incluir header del admin
require_once __DIR__ . '/admin_header.php';
?>

<!-- ============================================================
     Título
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-boxes me-2"></i>
        <?php if ($accion === 'movimientos'): ?>
            Movimientos: <?= escapar($producto_info['nombre'] ?? '') ?>
        <?php else: ?>
            Inventario
        <?php endif; ?>
    </h2>
    <?php if ($accion === 'movimientos'): ?>
        <a href="<?= SITE_URL ?>/admin/inventario.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al inventario
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

<?php if ($accion === 'movimientos' && $producto_info): ?>

<!-- ============================================================
     MOVIMIENTOS DE UN PRODUCTO ESPECÍFICO
     ============================================================ -->

<!-- Resumen del producto -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body">
                <div class="text-muted small">Stock Total</div>
                <div class="fs-3 fw-bold"><?= (int) $producto_info['cantidad'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body">
                <div class="text-muted small">Reservado</div>
                <div class="fs-3 fw-bold text-warning"><?= (int) $producto_info['cantidad_reservada'] ?></div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body">
                <div class="text-muted small">Disponible</div>
                <div class="fs-3 fw-bold <?= (int) $producto_info['disponible'] < (int) $producto_info['umbral_alerta'] ? 'text-danger' : 'text-success' ?>">
                    <?= (int) $producto_info['disponible'] ?>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm text-center h-100">
            <div class="card-body">
                <div class="text-muted small">Umbral Alerta</div>
                <div class="fs-3 fw-bold"><?= (int) $producto_info['umbral_alerta'] ?></div>
            </div>
        </div>
    </div>
</div>

<!-- Tabla de movimientos -->
<div class="card shadow-sm border-0">
    <div class="card-header bg-white">
        <h5 class="mb-0">
            <i class="bi bi-arrow-left-right me-2"></i>
            Últimos 50 movimientos
        </h5>
    </div>
    <div class="card-body p-0">
        <?php if (empty($movimientos)): ?>
            <div class="text-center py-4 text-muted">
                <i class="bi bi-inbox" style="font-size: 2rem;"></i>
                <p class="mt-2 mb-0">No hay movimientos registrados para este producto.</p>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Referencia / Motivo</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($movimientos as $mov): ?>
                        <tr>
                            <td class="text-muted"><?= (int) $mov['id'] ?></td>
                            <td>
                                <?php
                                $badge_tipo = match ($mov['tipo_movimiento']) {
                                    'entrada'    => 'bg-success',
                                    'salida'     => 'bg-danger',
                                    'reserva'    => 'bg-warning text-dark',
                                    'liberacion' => 'bg-info',
                                    'ajuste'     => 'bg-secondary',
                                    default      => 'bg-light text-dark',
                                };
                                ?>
                                <span class="badge <?= $badge_tipo ?>">
                                    <?= escapar($mov['tipo_movimiento']) ?>
                                </span>
                            </td>
                            <td class="fw-bold">
                                <?= (int) $mov['cantidad'] ?>
                            </td>
                            <td>
                                <small><?= escapar($mov['referencia'] ?? '—') ?></small>
                            </td>
                            <td class="small text-muted">
                                <?= date('d/m/Y H:i', strtotime($mov['fecha'])) ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php else: ?>

<!-- ============================================================
     PANEL PRINCIPAL DE INVENTARIO
     ============================================================ -->

<div class="row g-4">
    <!-- Columna izquierda: Tabla de inventario -->
    <div class="col-lg-8">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="bi bi-list-check me-2"></i>
                    Estado del Inventario
                </h5>
                <span class="badge bg-primary"><?= count($inventario) ?> productos</span>
            </div>
            <div class="card-body p-0">
                <?php if (empty($inventario)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                        <p class="mt-2 mb-0">No hay productos con inventario registrado.</p>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th>SKU</th>
                                    <th class="text-center">Stock Total</th>
                                    <th class="text-center">Reservado</th>
                                    <th class="text-center">Disponible</th>
                                    <th class="text-center">Umbral</th>
                                    <th class="text-center">Alerta</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($inventario as $item): ?>
                                <tr class="<?= $item['alerta'] ? 'table-warning' : '' ?>">
                                    <td>
                                        <strong><?= escapar($item['nombre']) ?></strong>
                                        <?php if (!$item['activo']): ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php endif; ?>
                                    </td>
                                    <td><code><?= escapar($item['sku']) ?></code></td>
                                    <td class="text-center fw-bold">
                                        <?= (int) $item['cantidad'] ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ((int) $item['cantidad_reservada'] > 0): ?>
                                            <span class="text-warning fw-bold">
                                                <?= (int) $item['cantidad_reservada'] ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">0</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center fw-bold
                                        <?= $item['alerta'] ? 'stock-bajo' : 'stock-normal' ?>">
                                        <?= (int) $item['disponible'] ?>
                                        <?php if ($item['alerta']): ?>
                                            <i class="bi bi-exclamation-circle"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><?= (int) $item['umbral_alerta'] ?></td>
                                    <td class="text-center">
                                        <?php if ($item['alerta']): ?>
                                            <span class="badge bg-danger">⚠</span>
                                        <?php else: ?>
                                            <span class="badge bg-success">✓</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="?accion=movimientos&id=<?= (int) $item['id'] ?>"
                                           class="btn btn-sm btn-outline-info"
                                           title="Ver movimientos">
                                            <i class="bi bi-clock-history"></i>
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

    <!-- Columna derecha: Formulario de ajuste manual -->
    <div class="col-lg-4">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white">
                <h5 class="mb-0">
                    <i class="bi bi-sliders me-2"></i>
                    Ajuste Manual
                </h5>
            </div>
            <div class="card-body">
                <p class="text-muted small mb-3">
                    Registra una entrada, salida o ajuste de stock para un producto.
                    Todos los movimientos quedan registrados para auditoría.
                </p>

                <form method="POST" action="inventario.php">
                    <!-- CSRF Token -->
                    <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="ajustar_stock" value="1">

                    <!-- Producto -->
                    <div class="mb-3">
                        <label for="producto_id" class="form-label fw-semibold">Producto *</label>
                        <select id="producto_id" name="producto_id" class="form-select" required>
                            <option value="">Seleccionar producto...</option>
                            <?php foreach ($productos_activos as $prod): ?>
                                <option value="<?= (int) $prod['id'] ?>"
                                    <?= isset($_POST['producto_id']) && (int) $_POST['producto_id'] === (int) $prod['id'] ? 'selected' : '' ?>>
                                    <?= escapar($prod['sku']) ?> — <?= escapar($prod['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Tipo de movimiento -->
                    <div class="mb-3">
                        <label for="tipo_movimiento" class="form-label fw-semibold">Tipo *</label>
                        <select id="tipo_movimiento" name="tipo_movimiento" class="form-select" required>
                            <option value="entrada">Entrada (aumentar stock)</option>
                            <option value="salida">Salida (reducir stock)</option>
                            <option value="ajuste">Ajuste (fijar cantidad exacta)</option>
                        </select>
                        <div class="form-text">
                            <strong>Entrada:</strong> suma stock.<br>
                            <strong>Salida:</strong> resta stock.<br>
                            <strong>Ajuste:</strong> fija el stock al valor ingresado.
                        </div>
                    </div>

                    <!-- Cantidad -->
                    <div class="mb-3">
                        <label for="cantidad" class="form-label fw-semibold">Cantidad *</label>
                        <input type="number"
                               id="cantidad"
                               name="cantidad"
                               class="form-control"
                               required
                               min="1"
                               value="1">
                    </div>

                    <!-- Nuevo umbral (opcional) -->
                    <div class="mb-3">
                        <label for="umbral_alerta" class="form-label fw-semibold">Nuevo umbral de alerta</label>
                        <input type="number"
                               id="umbral_alerta"
                               name="umbral_alerta"
                               class="form-control"
                               min="0"
                               placeholder="Dejar vacío para mantener el actual">
                        <div class="form-text">Cantidad mínima antes de mostrar alerta de stock bajo.</div>
                    </div>

                    <!-- Motivo -->
                    <div class="mb-3">
                        <label for="motivo" class="form-label fw-semibold">Motivo *</label>
                        <textarea id="motivo"
                                  name="motivo"
                                  class="form-control"
                                  rows="2"
                                  required
                                  placeholder="Ej: Recepción de proveedor, ajuste por conteo físico, devolución..."><?= escapar($_POST['motivo'] ?? '') ?></textarea>
                        <div class="form-text">Describa la razón del movimiento (visible en la auditoría).</div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-check-lg"></i> Registrar movimiento
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php endif; ?>

<?php
// ============================================================
// Incluir footer del admin
// ============================================================
require_once __DIR__ . '/admin_footer.php';
?>
