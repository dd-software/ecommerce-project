<?php
// ============================================================
// Checkout (Paso final de compra)
// ============================================================
// [PEDAGÓGICO] Esta página requiere que el usuario esté
// logueado. Muestra el resumen del carrito y un formulario
// para la dirección de envío. El botón "Confirmar compra"
// enviará los datos a api/checkout.php para procesar.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// ============================================================
// Verificar autenticación
// ============================================================
// [PEDAGÓGICO] Si el usuario no ha iniciado sesión, lo
// redirigimos al login. Le pasamos la URL actual como
// 'redirect' para que vuelva aquí después de loguearse.
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
                    <button type="submit" class="btn btn-success btn-lg w-100">
                        ✅ Confirmar Compra
                    </button>

                    <p class="text-muted small text-center mt-2 mb-0">
                        Al confirmar, aceptas nuestros términos y condiciones.
                    </p>
                </form>
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

<?php
require_once __DIR__ . '/includes/footer.php';
?>
