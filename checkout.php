<?php
// ============================================================
// Checkout (Paso final de compra)
// ============================================================
// [PEDAGÓGICO] Esta página requiere que el usuario esté
// logueado. Muestra el resumen del carrito y un formulario
// para la dirección de envío. El botón de PayPal enviará 
// los datos a api/checkout.php para procesar la orden.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// ============================================================
// Verificar autenticación
// ============================================================
if (!esta_logueado()) {
    $_SESSION['error'] = 'Debes iniciar sesión para continuar con la compra.';
    redireccionar('login.php?redirect=' . urlencode('checkout.php'));
}

// ============================================================
// Obtener items del carrito desde la BD
// ============================================================
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

// ============================================================
// Verificar que el carrito no esté vacío
// ============================================================
if (empty($items_carrito)) {
    $_SESSION['error'] = 'Tu carrito está vacío. Agrega productos antes de pagar.';
    redireccionar('carrito.php');
}

// ============================================================
// Calcular totales
// ============================================================
$items_totales = [];
foreach ($items_carrito as $item) {
    $items_totales[] = [
        'precio'   => $item['precio_unitario'],
        'cantidad' => $item['cantidad'],
    ];
}
$totales = calcular_totales($items_totales);
$total_unidades = array_sum(array_column($items_totales, 'cantidad') ?: [0]);

// ============================================================
// Countdown persistente por sesión (OBJ-06)
// ============================================================
// [PEDAGÓGICO] El timestamp vive en $_SESSION. Reglas:
//   - Al entrar a checkout.php SIEMPRE hay timer: si no existe,
//     se inicializa a now + RESERVA_MINUTOS·60.
//   - Si ya existe (porque venimos de un reload o de otra pestaña):
//     se mantiene → el contador no salta.
//   - Reset: carrito.php y api/carrito.php#agregar borran la clave
//     al modificar el carrito o volver atrás, así el próximo
//     ingreso a checkout.php arranca limpio en 10:00.
//   - api/checkout.php#commit también la borra al terminar la
//     compra exitosa.
if (empty($_SESSION['checkout_expira_at'])) {
    $_SESSION['checkout_expira_at'] = time() + (RESERVA_MINUTOS * 60);
}
$checkout_expira_at_ms = $_SESSION['checkout_expira_at'] * 1000;
?>

<script src="https://www.paypal.com/sdk/js?client-id=AS0Vpwu2uML769uxxby0p2XLqbcWhRYHcAqiyVcM48VztuLCjKdBRetSKdSPLAzmlLxgEzesfwrU8PkO&currency=USD"></script>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">💳 Checkout</h1>
    <a href="carrito.php" class="btn btn-outline-secondary btn-sm">
        ← Volver al carrito
    </a>
</div>

<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= escapar($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- ============================================================
     Countdown de la sesión de checkout (OBJ-06)
     ============================================================
     [PEDAGÓGICO] Banner SIEMPRE visible en checkout. El timestamp
     viene del servidor (data-expira-at-ms) y persiste por sesión
     PHP, por lo que sobrevive a reloads y se reinicia automáticamente
     cuando el usuario modifica el carrito (carrito.php / api/carrito.php
     borran la clave de la sesión).
     position: sticky lo mantiene visible al hacer scroll. -->
<div id="checkoutCountdown"
     class="alert alert-warning d-flex align-items-center mb-4 shadow"
     role="status"
     data-expira-at-ms="<?= $checkout_expira_at_ms ?>"
     style="position: sticky; top: 1rem; z-index: 1000;">
    <span style="font-size: 1.5rem;" class="me-2">⏱️</span>
    <div class="flex-grow-1">
        <strong>Tu sesión de pago expira en
            <span id="checkoutCountdownTiempo">--:--</span>
        </strong>
        <div class="small text-muted">
            Completa el pago antes de que termine el tiempo o tu
            reserva de stock se liberará.
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title mb-4">📦 Dirección de Envío</h4>

                <form id="form-checkout" method="POST" action="api/checkout.php" novalidate>
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

                    <div class="mb-3">
                        <label for="calle" class="form-label">🏠 Calle y número</label>
                        <input type="text"
                               id="calle"
                               name="calle"
                               class="form-control"
                               placeholder="Av. Ejemplo 1234, Depto. 5"
                               required>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="ciudad" class="form-label">🏙️ Ciudad</label>
                            <input type="text"
                                   id="ciudad"
                                   name="ciudad"
                                   class="form-control"
                                   placeholder="Ej: Temuco"
                                   required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label for="region" class="form-label">🗺️ Región</label>
                            <select id="region" name="region" class="form-select" required>
                                <option value="">Selecciona una región</option>
                                <option value="Arica y Parinacota">Arica y Parinacota</option>
                                <option value="Tarapacá">Tarapacá</option>
                                <option value="Antofagasta">Antofagasta</option>
                                <option value="Atacama">Atacama</option>
                                <option value="Coquimbo">Coquimbo</option>
                                <option value="Valparaíso">Valparaíso</option>
                                <option value="Metropolitana">Metropolitana</option>
                                <option value="O'Higgins">O'Higgins</option>
                                <option value="Maule">Maule</option>
                                <option value="Ñuble">Ñuble</option>
                                <option value="Biobío">Biobío</option>
                                <option value="La Araucanía" selected>La Araucanía</option>
                                <option value="Los Ríos">Los Ríos</option>
                                <option value="Los Lagos">Los Lagos</option>
                                <option value="Aysén">Aysén</option>
                                <option value="Magallanes">Magallanes</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="codigo_postal" class="form-label">📮 Código Postal</label>
                        <input type="text"
                               id="codigo_postal"
                               name="codigo_postal"
                               class="form-control"
                               placeholder="Ej: 4780000"
                               pattern="[0-9]{7}"
                               title="El código postal chileno tiene 7 dígitos">
                    </div>

                    <div class="mb-3">
                        <label for="notas" class="form-label">📝 Notas (opcional)</label>
                        <textarea id="notas"
                                  name="notas"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Indica cualquier instrucción especial para el envío..."></textarea>
                    </div>

                    <hr>

                    <div id="paypal-button-container" class="mt-4 mb-3"></div>
                </form> 
            </div>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title mb-3">📋 Resumen del Pedido</h4>

                <div class="mb-3">
                    <?php foreach ($items_carrito as $item): ?>
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <div class="me-3">
                            <?php if (!empty($item['imagen_url'])): ?>
                                <img src="<?= escapar($item['imagen_url']) ?>"
                                     alt="<?= escapar($item['alt_text'] ?? $item['nombre']) ?>"
                                     class="rounded"
                                     style="width: 50px; height: 50px; object-fit: cover;">
                            <?php else: ?>
                                <div class="bg-light rounded d-flex align-items-center justify-content-center"
                                     style="width: 50px; height: 50px;">
                                    📦
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="flex-grow-1">
                            <small class="fw-semibold d-block"><?= escapar($item['nombre']) ?></small>
                            <small class="text-muted">
                                <?= (int) $item['cantidad'] ?> x <?= formato_precio($item['precio_unitario']) ?>
                            </small>
                        </div>
                        <div class="fw-semibold">
                            <?= formato_precio($item['precio_unitario'] * $item['cantidad']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <div class="mt-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Subtotal (<?= $total_unidades ?> productos):</span>
                        <span><?= formato_precio($totales['subtotal']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">IVA (<?= IVA ?>%):</span>
                        <span><?= formato_precio($totales['iva']) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Envío:</span>
                        <span><?= formato_precio($totales['envio']) ?></span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between">
                        <span class="fs-5 fw-bold">TOTAL:</span>
                        <span class="fs-5 fw-bold text-success">
                            <?= formato_precio($totales['total']) ?>
                        </span>
                    </div>
                </div>
                <div class="mt-4 p-3 bg-light rounded">
                    <small class="text-muted d-block">
                        👤 <strong>Cliente:</strong> <?= escapar($_SESSION['usuario_nombre'] ?? '') ?>
                    </small>
                    <small class="text-muted d-block">
                        📧 <strong>Email:</strong> <?= escapar($_SESSION['usuario_email'] ?? '') ?>
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    paypal.Buttons({
        // 1. Validamos que la dirección no esté vacía antes de abrir la interfaz de PayPal
        onClick: function(data, actions) {
            const calle = document.getElementById('calle').value.trim();
            const ciudad = document.getElementById('ciudad').value.trim();
            const region = document.getElementById('region').value;

            if (!calle || !ciudad || !region) {
                alert('⚠️ Por favor, completa los campos obligatorios de la dirección (Calle, Ciudad y Región) antes de pagar.');
                return actions.reject();
            }
            return actions.resolve();
        },

        // 2. Configuramos el monto convertido (Pasando el total de CLP a USD dividiendo por 900)
        createOrder: function(data, actions) {
            return actions.order.create({
                purchase_units: [{
                    amount: {
                        value: '<?= number_format($totales['total'] / 900, 2, '.', '') ?>'
                    }
                }]
            });
        },

        // 3. Cuando el pago es aprobado por el cliente en PayPal
        onApprove: function(data, actions) {
            return actions.order.capture().then(function(orderData) {
                const form = document.getElementById('form-checkout');
                
                const existeInput = document.querySelector('input[name="paypal_order_id"]');
                if (existeInput) existeInput.remove();
                
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'paypal_order_id';
                input.value = orderData.id;
                form.appendChild(input);
                
                form.submit();
            });
        },

        onCancel: function(data) {
            alert('Pago cancelado. Tu orden no ha sido procesada.');
        },

        onError: function(err) {
            alert('Hubo un problema de conexión con la pasarela de PayPal.');
            console.error(err);
        }
    }).render('#paypal-button-container');
</script>

<!-- ============================================================
     Lógica del countdown de checkout (OBJ-06)
     ============================================================
     [PEDAGÓGICO] El timestamp viene del servidor en data-expira-at-ms
     (renderizado por PHP desde $_SESSION). El JS sólo descuenta y
     bloquea el formulario al llegar a 0. Como el servidor garantiza
     que $_SESSION['checkout_expira_at'] existe en cada carga de
     checkout.php, el banner siempre tiene un tiempo válido. -->
<script>
(function () {
    var $banner = $('#checkoutCountdown');
    if (!$banner.length) return;

    var $tiempo   = $('#checkoutCountdownTiempo');
    var intervalo = null;
    var yaExpirado = false;

    function marcarExpirado() {
        if (yaExpirado) return;
        yaExpirado = true;
        $tiempo.text('00:00');
        $banner
            .removeClass('alert-warning')
            .addClass('alert-danger')
            .find('strong').text('La sesión de checkout expiró.');
        $banner.find('.small').html(
            'Vuelve al <a href="carrito.php" class="alert-link">carrito</a> ' +
            'para empezar de nuevo.'
        );
        $('#form-checkout :input').prop('disabled', true);
        $('#paypal-button-container').css({
            'pointer-events': 'none',
            'opacity': '0.4'
        });
    }

    function arrancar(expiraMs) {
        if (!expiraMs || isNaN(expiraMs)) return;

        // Limpia un tick previo si re-iniciamos el contador.
        if (intervalo) clearInterval(intervalo);
        yaExpirado = false;

        // Asegura que el banner sea visible.
        $banner.removeClass('d-none').addClass('d-flex');

        function tick() {
            var msRestantes = expiraMs - Date.now();
            if (msRestantes <= 0) {
                marcarExpirado();
                clearInterval(intervalo);
                return;
            }
            var seg = Math.floor(msRestantes / 1000);
            var mm = String(Math.floor(seg / 60)).padStart(2, '0');
            var ss = String(seg % 60).padStart(2, '0');
            $tiempo.text(mm + ':' + ss);
        }

        tick();
        intervalo = setInterval(tick, 1000);
    }

    // Exposición global para que el onClick de PayPal lo invoque.
    window.iniciarCheckoutCountdown = arrancar;

    // Si la página cargó con un timer ya iniciado en $_SESSION
    // (porque el usuario refrescó después de clickear PayPal),
    // lo retomamos automáticamente.
    var preexistente = parseInt($banner.data('expira-at-ms'), 10);
    if (preexistente > 0) arrancar(preexistente);
})();
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>