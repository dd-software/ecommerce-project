<?php
// ============================================================
// pago.php - Página intermedia que redirige a PayPal
// y permite confirmar el pago manualmente.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

// Verificar autenticación
if (!esta_logueado()) {
    $_SESSION['error'] = 'Debes iniciar sesión para continuar.';
    redireccionar('login.php?redirect=pago.php');
}

$pdo = getDB();
$usuario_id = (int) $_SESSION['usuario_id'];

// Obtener orden_id desde GET, POST o sesión
$orden_id = isset($_GET['orden_id']) ? (int) $_GET['orden_id'] : 0;
if ($orden_id <= 0) {
    $orden_id = (int) ($_SESSION['orden_pendiente_id'] ?? 0);
    unset($_SESSION['orden_pendiente_id']);
}

if ($orden_id <= 0) {
    $_SESSION['error'] = 'No se encontró la orden pendiente.';
    redireccionar('checkout.php');
}

// Verificar que la orden exista y esté pendiente
$stmt = $pdo->prepare("SELECT id, numero, total, estado FROM pedidos WHERE id = :id AND usuario_id = :uid");
$stmt->execute([':id' => $orden_id, ':uid' => $usuario_id]);
$orden = $stmt->fetch();

if (!$orden) {
    $_SESSION['error'] = 'Orden no encontrada.';
    redireccionar('cuenta.php');
}

if ($orden['estado'] !== 'pendiente') {
    $_SESSION['error'] = 'Esta orden ya fue procesada. Estado actual: ' . $orden['estado'];
    redireccionar('cuenta.php');
}

// Parámetros de retorno de PayPal
$paypal_aprobado  = isset($_GET['paypal_aprobado']) ? (int) $_GET['paypal_aprobado'] : 0;
$paypal_cancelado = isset($_GET['paypal_cancelado']) ? (int) $_GET['paypal_cancelado'] : 0;
$paypal_token     = $_GET['token'] ?? '';

// Si el usuario canceló en PayPal, reintentar (redirect puro, antes de imprimir nada)
if ($paypal_cancelado === 1) {
    $_SESSION['error'] = 'Cancelaste el pago en PayPal. Puedes intentar de nuevo.';
    redireccionar('pago.php?orden_id=' . $orden_id);
}

// A partir de aquí ya no hay más redirecciones: recién ahora imprimimos HTML.
require_once __DIR__ . '/includes/header.php';

// Si PayPal ya redirigió aprobado, mostrar botón de confirmación manual
if ($paypal_aprobado === 1):
?>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body text-center p-5">
                <h2 class="mb-4">✅ Pago Aprobado en PayPal</h2>
                <div class="mb-4" style="font-size: 4rem;">✅</div>
                <p class="lead">
                    Has aprobado el pago en PayPal.<br>
                    Ahora debes <strong>confirmar</strong> la compra para finalizar.
                </p>
                <form method="POST" action="api/pago.php" id="form-confirmar-pago">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="action" value="capturar">
                    <input type="hidden" name="paypal_order_id" value="<?= escapar($paypal_token) ?>">
                    <input type="hidden" name="orden_id" value="<?= (int) $orden_id ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100" id="btn-confirmar-pago">
                        💳 Confirmar Pago
                    </button>
                </form>
                <div id="error-confirmar" class="alert alert-danger d-none mt-3"></div>
                <p class="small text-muted mt-3">
                    Si no haces clic en confirmar, el pago quedará pendiente.<br>
                    Puedes volver a <a href="cuenta.php">Mis Compras</a> y pagar después.
                </p>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function () {
    $('#form-confirmar-pago').on('submit', function (e) {
        e.preventDefault();
        var $btn = $('#btn-confirmar-pago');
        var $errorBox = $('#error-confirmar');
        $btn.prop('disabled', true).text('Confirmando...');
        $errorBox.addClass('d-none').empty();

        $.ajax({
            url: 'api/pago.php',
            method: 'POST',
            data: $(this).serialize(),
            dataType: 'json',
            success: function (response) {
                if (response.success && response.data && response.data.numero_orden) {
                    window.location.href = 'exito.php?orden=' + encodeURIComponent(response.data.numero_orden);
                } else {
                    $errorBox.removeClass('d-none').html(response.message || 'No se pudo confirmar el pago.');
                    $btn.prop('disabled', false).text('💳 Confirmar Pago');
                }
            },
            error: function () {
                $errorBox.removeClass('d-none').text('Error de conexión al confirmar el pago. Intenta de nuevo.');
                $btn.prop('disabled', false).text('💳 Confirmar Pago');
            }
        });
    });
});
</script>
<?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
endif;
?>

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card shadow-sm">
            <div class="card-body text-center p-5">
                <h2 class="mb-4">⏳ Procesando Pago</h2>
                <div class="mb-4">
                    <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Conectando con PayPal...</span>
                    </div>
                </div>
                <p class="lead">Conectando con <strong>PayPal</strong>...</p>
                <p class="text-muted">
                    Orden: <strong>#<?= escapar($orden['numero']) ?></strong><br>
                    Total: <strong><?= formato_precio($orden['total']) ?></strong>
                </p>
                <p class="small text-muted">
                    Si no eres redirigido automáticamente,
                    <a href="#" id="btn-manual">haz clic aquí</a>.
                </p>
                <div id="error-mensaje" class="alert alert-danger d-none mt-3"></div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    var csrfToken = $('meta[name="csrf-token"]').attr('content');
    var ordenId = <?= (int) $orden_id ?>;
    
    function crearPagoPayPal() {
        $.ajax({
            url: 'api/pago.php',
            method: 'POST',
            data: {
                action: 'crear',
                orden_id: ordenId,
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data.approval_url) {
                    window.location.href = response.data.approval_url;
                } else {
                    mostrarError(response.message || 'Error al crear el pago en PayPal.');
                }
            },
            error: function(xhr, status, error) {
                mostrarError('Error de conexión: ' + error);
            }
        });
    }
    
    function mostrarError(mensaje) {
        $('#error-mensaje').removeClass('d-none').html(mensaje);
        $('.spinner-border').hide();
        $('#btn-manual').attr('href', 'checkout.php').text('Volver al checkout');
    }
    
    crearPagoPayPal();
    
    $('#btn-manual').on('click', function(e) {
        e.preventDefault();
        crearPagoPayPal();
    });
});
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
