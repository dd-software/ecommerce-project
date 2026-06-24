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
    $sql  = "SELECT id, numero, estado, subtotal, iva, costo_envio, total, direccion_envio, fecha_creacion FROM pedidos WHERE numero = :numero";
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

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">

        <?php if ($error_msg): ?>
            <div class="text-center py-5">
                <div style="font-size: 4rem;">❌</div>
                <h3 class="mt-3">Orden no encontrada</h3>
                <p class="text-muted"><?= escapar($error_msg) ?></p>
                <a href="index.php" class="btn btn-primary mt-3">🏠 Volver al inicio</a>
            </div>
        <?php else: ?>
            <!-- ============================================================
                 Banner de reserva activa (countdown OBJ-06 + OBJ-08)
                 ============================================================
                 [PEDAGÓGICO] Si la orden todavía tiene reservas activas
                 (etapa_flujo === 'reserva_activa' en api/flujo.php),
                 mostramos un contador regresivo hasta que la reserva
                 expire. Usa el orquestador del módulo H para no consultar
                 reservas_inventario directamente desde la vista. -->
            <div id="reservaCountdown"
                 class="alert alert-warning d-none align-items-center"
                 role="status">
                <span style="font-size: 1.5rem;" class="me-2">⏱️</span>
                <div class="flex-grow-1">
                    <strong>Tu reserva expira en
                        <span id="reservaCountdownTiempo">--:--</span>
                    </strong>
                    <div class="small text-muted">
                        Completa el pago antes de que termine el tiempo
                        o el stock se liberará.
                    </div>
                </div>
            </div>

            <div class="text-center mb-5">
                <div style="font-size: 5rem;">✅</div>
                <h2 class="mt-3 text-success">¡Compra realizada con éxito!</h2>
                <p class="text-muted fs-5">
                    Gracias por tu compra, <strong><?= escapar($_SESSION['usuario_nombre'] ?? '') ?></strong>.
                </p>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-body p-4">
                    <div class="text-center mb-4 p-3 bg-light rounded">
                        <small class="text-muted text-uppercase">Número de orden</small>
                        <h3 class="fw-bold text-primary mb-0">
                            <?= escapar($pedido['numero']) ?>
                        </h3>
                    </div>

                    <div class="d-flex justify-content-between mb-3">
                        <span class="text-muted">Estado:</span>
                        <span class="badge bg-info fs-6">
                            <?= escapar(ucfirst($pedido['estado'])) ?>
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

                    <div class="mt-4 p-3 bg-light rounded">
                        <small class="text-muted d-block fw-semibold mb-1">📦 Dirección de envío</small>
                        <small><?= nl2br(escapar($pedido['direccion_envio'])) ?></small>
                    </div>

                    <div class="mt-3 text-center text-muted small">
                        Fecha: <?= date('d/m/Y H:i', strtotime($pedido['fecha_creacion'])) ?>
                    </div>
                </div>
            </div>

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

<?php if (!$error_msg && !empty($pedido)): ?>
<!-- ============================================================
     Countdown de reserva — consume api/flujo.php (OBJ-08)
     ============================================================
     [PEDAGÓGICO] Demuestra el orquestador en acción:
     1. Pide el estado E2E de la orden
     2. Si etapa_flujo es 'reserva_activa', renderiza el banner
        y actualiza el contador cada segundo
     3. Si el tiempo llega a 0, marca la reserva como expirada
        visualmente sin recargar la página. -->
<script>
(function () {
    var numero = <?= json_encode($pedido['numero']) ?>;
    var $banner = $('#reservaCountdown');
    var $tiempo = $('#reservaCountdownTiempo');

    fetch('api/flujo.php?action=estado&numero=' + encodeURIComponent(numero), {
        headers: { 'Accept': 'application/json' }
    })
    .then(function (r) { return r.json(); })
    .then(function (data) {
        if (!data || !data.success) return;

        // Sólo mostramos el countdown si hay reserva activa.
        if (data.data.etapa_flujo !== 'reserva_activa') return;

        // Buscamos la reserva activa con menor fecha_expiracion.
        var reservas = (data.data.reservas || []).filter(function (r) {
            return r.estado === 'activa';
        });
        if (!reservas.length) return;

        reservas.sort(function (a, b) {
            return a.fecha_expiracion.localeCompare(b.fecha_expiracion);
        });
        var expira = new Date(reservas[0].fecha_expiracion.replace(' ', 'T'));

        $banner.removeClass('d-none').addClass('d-flex');

        function tick() {
            var msRestantes = expira.getTime() - Date.now();
            if (msRestantes <= 0) {
                $tiempo.text('00:00');
                $banner
                    .removeClass('alert-warning')
                    .addClass('alert-danger')
                    .find('strong').text('La reserva expiró.');
                clearInterval(intervalo);
                return;
            }
            var seg = Math.floor(msRestantes / 1000);
            var mm = String(Math.floor(seg / 60)).padStart(2, '0');
            var ss = String(seg % 60).padStart(2, '0');
            $tiempo.text(mm + ':' + ss);
        }

        tick(); // pintar inmediatamente
        var intervalo = setInterval(tick, 1000);
    })
    .catch(function (e) { console.error('Countdown reserva:', e); });
})();
</script>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>