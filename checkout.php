<?php
// ============================================================
// Checkout (Paso final de compra)
// ============================================================
// [PEDAGÓGICO] Esta página permite pagar como invitado o
// como usuario autenticado. Muestra el resumen del carrito y
// un formulario para la dirección de envío. El botón
// "Confirmar compra" enviará los datos a api/checkout.php.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

$pdo = getDB();

// ============================================================
// Cargar items del carrito del usuario o invitado
// ============================================================
// [PEDAGÓGICO] Permitimos el checkout como invitado si el
// usuario no ha iniciado sesión. El carrito de invitados se
// guarda en sesión y el carrito de usuarios en la base de datos.
$items_carrito = [];

if (esta_logueado()) {
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
} else {
    if (!empty($_SESSION['carrito'])) {
        $ids = array_keys($_SESSION['carrito']);
        if (!empty($ids)) {
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $stmt = $pdo->prepare("
                SELECT p.id, p.nombre, p.sku, p.precio, p.precio_descuento,
                       i.url as imagen_url, i.alt_text
                FROM productos p
                LEFT JOIN (
                    SELECT producto_id, url, alt_text
                    FROM imagenes
                    WHERE es_principal = 1
                ) i ON i.producto_id = p.id
                WHERE p.id IN ($placeholders) AND p.activo = 1
            ");
            $stmt->execute($ids);
            $productos_db = $stmt->fetchAll();

            foreach ($productos_db as $prod) {
                $sesion_item = $_SESSION['carrito'][$prod['id']];
                $precio = !empty($prod['precio_descuento'])
                    ? $prod['precio_descuento']
                    : $prod['precio'];

                $items_carrito[] = [
                    'producto_id'     => $prod['id'],
                    'nombre'          => $prod['nombre'],
                    'sku'             => $prod['sku'],
                    'precio_unitario' => $precio,
                    'cantidad'        => $sesion_item['cantidad'],
                    'imagen_url'      => $prod['imagen_url'],
                    'alt_text'        => $prod['alt_text'],
                ];
            }
        }
    }
}

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
// AHORA incluimos el header (después de las verificaciones)
// ============================================================
require_once __DIR__ . '/includes/header.php';

// Los datos del carrito y totales ya fueron obtenidos antes del header
// No es necesario volver a consultarlos
?>

<!-- ============================================================
     Encabezado de la página
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="mb-0">💳 Checkout</h1>
    <a href="carrito.php" class="btn btn-outline-secondary btn-sm">
        ← Volver al carrito
    </a>
</div>

<!-- Mostrar errores desde sesión -->
<?php if (!empty($_SESSION['error'])): ?>
    <div class="alert alert-danger"><?= escapar($_SESSION['error']) ?></div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<?php if (!esta_logueado()): ?>
    <div class="alert alert-info">
        Puedes pagar como invitado. Completa la dirección de envío y confirma tu compra sin iniciar sesión.
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- ============================================================
         Columna izquierda: Formulario de dirección de envío
         ============================================================ -->
    <div class="col-lg-7">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title mb-4">📦 Dirección de Envío</h4>

                <!-- [PEDAGÓGICO] El formulario envía los datos a api/checkout.php
                     mediante POST. Este archivo se encargará de crear la orden
                     en la base de datos y redirigir a exito.php. -->
                <form id="form-checkout" method="POST" action="api/checkout.php" novalidate>
                    <!-- Token CSRF -->
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">

                    <!-- Calle y número -->
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
                        <!-- Ciudad -->
                        <div class="col-md-6 mb-3">
                            <label for="ciudad" class="form-label">🏙️ Ciudad</label>
                            <input type="text"
                                   id="ciudad"
                                   name="ciudad"
                                   class="form-control"
                                   placeholder="Ej: Temuco"
                                   required>
                        </div>

                        <!-- Región -->
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

                    <!-- Código Postal -->
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

                    <!-- Notas adicionales -->
                    <div class="mb-3">
                        <label for="notas" class="form-label">📝 Notas (opcional)</label>
                        <textarea id="notas"
                                  name="notas"
                                  class="form-control"
                                  rows="2"
                                  placeholder="Indica cualquier instrucción especial para el envío..."></textarea>
                    </div>

                    <hr>

                    <!-- Botón de confirmación -->
                    <button type="button" id="btn-crear-orden" class="btn btn-primary btn-lg w-100" onclick="crearOrden(event)">
                        ✅ Continuar a PayPal
                    </button>

                    <p class="text-muted small text-center mt-2 mb-0" id="loading-mensaje" style="display:none;">
                        ⏳ Creando tu orden...
                    </p>
                    <p class="text-muted small text-center mt-2 mb-0">
                        Al hacer clic, se creará tu orden y serás redirigido a PayPal para completar el pago.
                    </p>
                </form>

                <!-- Contenedor para el botón de PayPal (mostrado después de crear la orden) -->
                <div id="paypal-button-container" style="display:none; margin-top: 20px;"></div>
            </div>
        </div>
    </div>

    <!-- ============================================================
         Columna derecha: Resumen de la compra
         ============================================================ -->
    <div class="col-lg-5">
        <div class="card shadow-sm">
            <div class="card-body p-4">
                <h4 class="card-title mb-3">📋 Resumen del Pedido</h4>

                <!-- Lista de productos -->
                <div class="mb-3">
                    <?php foreach ($items_carrito as $item): ?>
                    <div class="d-flex align-items-center mb-2 pb-2 border-bottom">
                        <!-- Mini imagen -->
                        <div class="me-3">
                            <?php if (!empty($item['imagen_url'])): ?>
                                <img src="<?= escapar(ruta_imagen_producto($item['imagen_url'])) ?>"
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
                        <!-- Info del producto -->
                        <div class="flex-grow-1">
                            <small class="fw-semibold d-block"><?= escapar($item['nombre']) ?></small>
                            <small class="text-muted">
                                <?= (int) $item['cantidad'] ?> x <?= formato_precio($item['precio_unitario']) ?>
                            </small>
                        </div>
                        <!-- Subtotal -->
                        <div class="fw-semibold">
                            <?= formato_precio($item['precio_unitario'] * $item['cantidad']) ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Totales -->
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

                <!-- Información del usuario -->
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

<!-- ============================================================
     PayPal SDK & JavaScript para manejar el pago
     ============================================================ -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= htmlspecialchars(PAYPAL_CLIENT_ID) ?>&currency=USD&disable-funding=credit,card"></script>

<script>
// Estado global para la orden
let ordenActual = {
    id: null,
    numero: null,
    total: null,
};
let tokenCSRF = null;

/**
 * Valida el formulario y crea la orden en el servidor
 */
function crearOrden(event) {
    event.preventDefault();
    
    const form = document.getElementById('form-checkout');
    
    // Validar que el formulario sea válido
    if (!form.checkValidity()) {
        form.reportValidity();
        return;
    }
    
    const calle = document.getElementById('calle').value.trim();
    const ciudad = document.getElementById('ciudad').value.trim();
    const region = document.getElementById('region').value.trim();
    const codigo_postal = document.getElementById('codigo_postal').value.trim();
    const notas = document.getElementById('notas').value.trim();
    tokenCSRF = form.querySelector('input[name="_csrf_token"]').value;
    
    // Mostrar loading
    document.getElementById('btn-crear-orden').disabled = true;
    document.getElementById('loading-mensaje').style.display = 'block';
    
    // Crear FormData para enviar
    const formData = new FormData();
    formData.append('calle', calle);
    formData.append('ciudad', ciudad);
    formData.append('region', region);
    formData.append('codigo_postal', codigo_postal);
    formData.append('notas', notas);
    formData.append('_csrf_token', tokenCSRF);
    
    // Enviar a api/checkout.php
    fetch('api/checkout.php', {
        method: 'POST',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Guardar datos de la orden
            ordenActual.id = data.data.orden_id;
            ordenActual.numero = data.data.numero_orden;
            ordenActual.total = data.data.total;
            
            // Ocultar formulario y mostrar PayPal
            form.style.display = 'none';
            document.getElementById('btn-crear-orden').style.display = 'none';
            document.getElementById('loading-mensaje').style.display = 'none';
            document.getElementById('paypal-button-container').style.display = 'block';
            
            // Renderizar botón de PayPal
            renderizarPayPal();
        } else {
            alert('Error: ' + (data.message || 'No se pudo crear la orden'));
            document.getElementById('btn-crear-orden').disabled = false;
            document.getElementById('loading-mensaje').style.display = 'none';
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión: ' + error.message);
        document.getElementById('btn-crear-orden').disabled = false;
        document.getElementById('loading-mensaje').style.display = 'none';
    });
}

/**
 * Renderiza el botón de PayPal
 */
function renderizarPayPal() {
    if (typeof paypal === 'undefined') {
        console.error('PayPal SDK no cargó correctamente');
        return;
    }
    
    paypal.Buttons({
        // Crear orden en PayPal
        createOrder: function(data, actions) {
            return fetch('api/pago.php?action=crear', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'crear',
                    'orden_id': ordenActual.id,
                    '_csrf_token': tokenCSRF
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    return data.data.paypal_order_id;
                } else {
                    throw new Error(data.message || 'Error al crear orden en PayPal');
                }
            });
        },
        
        // Cuando el usuario aprueba el pago en PayPal
        onApprove: function(data, actions) {
            return fetch('api/pago.php?action=capturar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams({
                    'action': 'capturar',
                    'orden_id': ordenActual.id,
                    'paypal_order_id': data.orderID,
                    '_csrf_token': tokenCSRF
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Pago completado exitosamente
                    window.location.href = 'exito.php?orden=' + encodeURIComponent(ordenActual.numero);
                } else {
                    alert('Error al confirmar pago: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error de conexión: ' + error.message);
            });
        },
        
        // Si el usuario cancela en PayPal
        onCancel: function(data) {
            alert('Pago cancelado. Tu orden se mantuvo en el sistema. Puedes intentar nuevamente.');
            // Recargar para permitir otro intento
            location.reload();
        },
        
        // Manejo de errores
        onError: function(err) {
            console.error('PayPal error:', err);
            alert('Error con PayPal: ' + err);
        }
    }).render('#paypal-button-container');
}
</script>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
