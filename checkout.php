<?php
// ============================================================
// Checkout (Paso final de compra) - CON PAYPAL
// ============================================================
// [PEDAGÓGICO] Esta página requiere que el usuario esté
// logueado. Muestra el resumen del carrito y ofrece dos métodos
// de pago: PayPal (principal) y Transferencia Bancaria.
// ============================================================

// ============================================================
// 1. Incluir archivos necesarios
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

$pdo = getDB();

// ============================================================
// 2. Verificar autenticación
// ============================================================
if (!esta_logueado()) {
    $_SESSION['error'] = 'Debes iniciar sesión para continuar con la compra.';
    redireccionar('login.php?redirect=' . urlencode('checkout.php'));
}

// ============================================================
// 3. Obtener items del carrito desde la BD
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
// 4. Verificar que el carrito no esté vacío
// ============================================================
if (empty($items_carrito)) {
    $_SESSION['error'] = 'Tu carrito está vacío. Agrega productos antes de pagar.';
    redireccionar('carrito.php');
}

// ============================================================
// 5. Calcular totales
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
// 6. Configuración de PayPal (desde config.php)
// ============================================================
if (!defined('PAYPAL_CLIENT_ID')) {
    define('PAYPAL_CLIENT_ID', 'sb'); // Sandbox por defecto
}
if (!defined('PAYPAL_MODE')) {
    define('PAYPAL_MODE', 'sandbox');
}
if (!defined('PAYPAL_CURRENCY')) {
    define('PAYPAL_CURRENCY', 'CLP');
}

// ============================================================
// 7. Incluir header DESPUÉS de toda la lógica
// ============================================================
require_once __DIR__ . '/includes/header.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - <?= SITE_NAME ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <!-- jQuery -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous">
    </script>

    <style>
        .producto-imagen-mini {
            width: 50px;
            height: 50px;
            object-fit: cover;
        }
        .paypal-button-container {
            min-height: 45px;
        }
        .metodo-pago-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        .metodo-pago-card:hover {
            border-color: #dee2e6;
        }
        .metodo-pago-card.seleccionado {
            border-color: #0d6efd;
            background-color: #f0f7ff;
        }
        .metodo-pago-card .form-check-input {
            cursor: pointer;
        }
        .loading-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 9999;
            justify-content: center;
            align-items: center;
        }
        .loading-overlay.active {
            display: flex;
        }
        .loading-box {
            background: white;
            padding: 2rem 3rem;
            border-radius: 1rem;
            text-align: center;
        }
        .spinner {
            width: 50px;
            height: 50px;
            border: 5px solid #f3f3f3;
            border-top: 5px solid #3498db;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin: 0 auto 1rem;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
        .badge-paypal {
            background-color: #0070ba;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .badge-transferencia {
            background-color: #28a745;
            color: white;
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.75rem;
        }
        .card-pago {
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        .card-pago .card-body {
            padding: 1.5rem;
        }
        .resumen-item {
            padding: 0.5rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .resumen-item:last-child {
            border-bottom: none;
        }
        .total-final {
            font-size: 1.25rem;
            font-weight: bold;
            color: #198754;
        }
        .form-control:focus, .form-select:focus {
            border-color: #0d6efd;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
        }
    </style>
</head>
<body>

    <!-- ============================================================
         Overlay de carga
         ============================================================ -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="loading-box">
            <div class="spinner"></div>
            <h5>Procesando tu pago...</h5>
            <p class="text-muted mb-0">Por favor espera, esto puede tomar unos segundos.</p>
        </div>
    </div>

    <!-- ============================================================
         Navbar
         ============================================================ -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
                🛒 <?= SITE_NAME ?>
            </a>
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarPrincipal"
                    aria-controls="navbarPrincipal"
                    aria-expanded="false"
                    aria-label="Menú de navegación">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarPrincipal">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/index.php">
                            📋 Catálogo
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="<?= SITE_URL ?>/carrito.php">
                            🛍️ Carrito
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (esta_logueado()): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#"
                               id="usuarioDropdown"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">
                                👤 <?= escapar($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/carrito.php">🛍️ Mi Carrito</a></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/cuenta.php">👤 Mi Cuenta</a></li>
                                <?php if (es_admin()): ?>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/admin/">⚙️ Panel Admin</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="<?= SITE_URL ?>/logout.php">🚪 Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/login.php">🔑 Iniciar Sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/registro.php">📝 Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ============================================================
         Contenido Principal
         ============================================================ -->
    <main class="container my-4">

        <!-- Mostrar errores desde sesión -->
        <?php if (!empty($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle"></i> <?= escapar($_SESSION['error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <!-- Mostrar mensajes de éxito -->
        <?php if (!empty($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle"></i> <?= escapar($_SESSION['success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
            <?php unset($_SESSION['success']); ?>
        <?php endif; ?>

        <!-- ============================================================
             Encabezado
             ============================================================ -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="mb-0"><i class="fas fa-credit-card"></i> Checkout</h1>
            <a href="carrito.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-arrow-left"></i> Volver al carrito
            </a>
        </div>

        <!-- ============================================================
             Barra de progreso
             ============================================================ -->
        <div class="mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-shopping-cart"></i>
                    </div>
                    <div><small>Carrito</small></div>
                </div>
                <div class="flex-fill"><hr class="border-success"></div>
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-success text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-check"></i>
                    </div>
                    <div><small>Dirección</small></div>
                </div>
                <div class="flex-fill"><hr class="border-success"></div>
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-primary text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-credit-card"></i>
                    </div>
                    <div><small>Pago</small></div>
                </div>
                <div class="flex-fill"><hr></div>
                <div class="text-center flex-fill">
                    <div class="rounded-circle bg-secondary text-white d-inline-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div><small>Confirmar</small></div>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <!-- ============================================================
                 Columna izquierda: Formulario de dirección + Métodos de pago
                 ============================================================ -->
            <div class="col-lg-7">
                <!-- Formulario de dirección -->
                <div class="card card-pago mb-4">
                    <div class="card-body">
                        <h4 class="card-title mb-4"><i class="fas fa-map-pin"></i> 📦 Dirección de Envío</h4>

                        <form id="form-checkout" method="POST" action="api/checkout.php" novalidate>
                            <!-- Token CSRF -->
                            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                            <input type="hidden" name="accion" id="accion" value="transferencia">

                            <!-- Calle y número -->
                            <div class="mb-3">
                                <label for="calle" class="form-label fw-semibold">🏠 Calle y número</label>
                                <input type="text"
                                       id="calle"
                                       name="calle"
                                       class="form-control"
                                       placeholder="Av. Ejemplo 1234, Depto. 5"
                                       required>
                                <div class="invalid-feedback">Por favor, ingresa tu dirección completa.</div>
                            </div>

                            <div class="row">
                                <!-- Ciudad -->
                                <div class="col-md-6 mb-3">
                                    <label for="ciudad" class="form-label fw-semibold">🏙️ Ciudad</label>
                                    <input type="text"
                                           id="ciudad"
                                           name="ciudad"
                                           class="form-control"
                                           placeholder="Ej: Temuco"
                                           required>
                                    <div class="invalid-feedback">Ingresa tu ciudad.</div>
                                </div>

                                <!-- Región -->
                                <div class="col-md-6 mb-3">
                                    <label for="region" class="form-label fw-semibold">🗺️ Región</label>
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
                                    <div class="invalid-feedback">Selecciona tu región.</div>
                                </div>
                            </div>

                            <!-- Código Postal -->
                            <div class="mb-3">
                                <label for="codigo_postal" class="form-label fw-semibold">📮 Código Postal</label>
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
                                <label for="notas" class="form-label fw-semibold">📝 Notas (opcional)</label>
                                <textarea id="notas"
                                          name="notas"
                                          class="form-control"
                                          rows="2"
                                          placeholder="Indica cualquier instrucción especial para el envío..."></textarea>
                            </div>

                            <!-- ============================================================
                                 MÉTODOS DE PAGO
                                 ============================================================ -->
                            <hr>
                            <h5 class="mb-3"><i class="fas fa-wallet"></i> 💳 Selecciona tu método de pago</h5>

                            <div class="row g-3">
                                <!-- PayPal -->
                                <div class="col-md-6">
                                    <div class="card metodo-pago-card selected" id="metodo-paypal" onclick="seleccionarMetodo('paypal')">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="metodo_pago" id="paypal" value="paypal" checked>
                                                <label class="form-check-label" for="paypal">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fab fa-paypal fa-2x me-2" style="color: #0070ba;"></i>
                                                        <span class="badge-paypal">PayPal</span>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Pago seguro con PayPal</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Transferencia Bancaria -->
                                <div class="col-md-6">
                                    <div class="card metodo-pago-card" id="metodo-transferencia" onclick="seleccionarMetodo('transferencia')">
                                        <div class="card-body">
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" 
                                                       name="metodo_pago" id="transferencia" value="transferencia">
                                                <label class="form-check-label" for="transferencia">
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-university fa-2x me-2" style="color: #28a745;"></i>
                                                        <span class="badge-transferencia">Transferencia</span>
                                                    </div>
                                                    <small class="text-muted d-block mt-1">Pago por transferencia bancaria</small>
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Contenedor del botón de PayPal -->
                            <div id="paypal-button-container" class="mt-3 paypal-button-container"></div>

                            <!-- Botón de confirmación (para transferencia) -->
                            <button type="submit" id="btn-confirmar-transferencia" 
                                    class="btn btn-success btn-lg w-100 mt-3" style="display:none;">
                                <i class="fas fa-check"></i> Confirmar Compra
                            </button>

                            <p class="text-muted small text-center mt-2 mb-0">
                                <i class="fas fa-lock"></i> Tus datos están seguros. Al confirmar, aceptas nuestros términos y condiciones.
                            </p>
                        </form>
                    </div>
                </div>
            </div>

            <!-- ============================================================
                 Columna derecha: Resumen de la compra
                 ============================================================ -->
            <div class="col-lg-5">
                <div class="card card-pago">
                    <div class="card-body">
                        <h4 class="card-title mb-3"><i class="fas fa-receipt"></i> 📋 Resumen del Pedido</h4>

                        <!-- Lista de productos -->
                        <div class="mb-3">
                            <?php foreach ($items_carrito as $item): ?>
                            <div class="d-flex align-items-center resumen-item">
                                <!-- Mini imagen -->
                                <div class="me-3">
                                    <?php if (!empty($item['imagen_url'])): ?>
                                        <img src="<?= escapar($item['imagen_url']) ?>"
                                             alt="<?= escapar($item['alt_text'] ?? $item['nombre']) ?>"
                                             class="rounded producto-imagen-mini">
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
                                <i class="fas fa-user"></i> <strong>Cliente:</strong> <?= escapar($_SESSION['usuario_nombre'] ?? '') ?>
                            </small>
                            <small class="text-muted d-block">
                                <i class="fas fa-envelope"></i> <strong>Email:</strong> <?= escapar($_SESSION['usuario_email'] ?? '') ?>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- ============================================================
         Footer
         ============================================================ -->
    <footer class="footer mt-5 py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?= date('Y') ?> <?= SITE_NAME ?> - Todos los derechos reservados.</span>
        </div>
    </footer>

    <!-- ============================================================
         PayPal JavaScript SDK
         ============================================================ -->
    <script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=<?= PAYPAL_CURRENCY ?>&intent=capture"
            data-namespace="paypal_sdk"
            defer>
    </script>

    <!-- ============================================================
         JavaScript Personalizado
         ============================================================ -->
    <script>
    // ============================================================
    // Variables globales
    // ============================================================
    let metodoSeleccionado = 'paypal';
    let ordenCreada = false;
    let montoTotal = <?= $totales['total'] ?>;

    // ============================================================
    // Seleccionar método de pago
    // ============================================================
    function seleccionarMetodo(metodo) {
        metodoSeleccionado = metodo;
        
        // Actualizar UI
        document.querySelectorAll('.metodo-pago-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        if (metodo === 'paypal') {
            document.getElementById('metodo-paypal').classList.add('selected');
            document.getElementById('paypal-button-container').style.display = 'block';
            document.getElementById('btn-confirmar-transferencia').style.display = 'none';
            document.getElementById('accion').value = 'crear_orden';
            document.getElementById('paypal').checked = true;
        } else {
            document.getElementById('metodo-transferencia').classList.add('selected');
            document.getElementById('paypal-button-container').style.display = 'none';
            document.getElementById('btn-confirmar-transferencia').style.display = 'block';
            document.getElementById('accion').value = 'transferencia';
            document.getElementById('transferencia').checked = true;
        }
    }

    // ============================================================
    // Mostrar/Ocultar overlay de carga
    // ============================================================
    function mostrarCarga() {
        document.getElementById('loadingOverlay').classList.add('active');
    }

    function ocultarCarga() {
        document.getElementById('loadingOverlay').classList.remove('active');
    }

    // ============================================================
    // Validar dirección
    // ============================================================
    function validarDireccion() {
        const calle = document.getElementById('calle').value.trim();
        const ciudad = document.getElementById('ciudad').value.trim();
        const region = document.getElementById('region').value;
        let valid = true;

        if (!calle) {
            document.getElementById('calle').classList.add('is-invalid');
            valid = false;
        } else {
            document.getElementById('calle').classList.remove('is-invalid');
        }

        if (!ciudad) {
            document.getElementById('ciudad').classList.add('is-invalid');
            valid = false;
        } else {
            document.getElementById('ciudad').classList.remove('is-invalid');
        }

        if (!region) {
            document.getElementById('region').classList.add('is-invalid');
            valid = false;
        } else {
            document.getElementById('region').classList.remove('is-invalid');
        }

        return valid;
    }

    // ============================================================
    // Renderizar botón de PayPal
    // ============================================================
    function renderPayPalButton() {
        if (typeof paypal_sdk === 'undefined') {
            console.error('PayPal SDK no cargado');
            setTimeout(renderPayPalButton, 1000);
            return;
        }

        try {
            paypal_sdk.Buttons({
                style: {
                    layout: 'vertical',
                    color: 'blue',
                    shape: 'rect',
                    label: 'paypal',
                    height: 45,
                    tagline: false
                },

                createOrder: function(data, actions) {
                    // Validar dirección
                    if (!validarDireccion()) {
                        alert('Por favor, completa todos los campos de dirección.');
                        return;
                    }

                    mostrarCarga();
                    
                    const formData = new FormData(document.getElementById('form-checkout'));
                    formData.append('accion', 'crear_orden');

                    return fetch('api/checkout.php', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Error de red');
                        }
                        return response.json();
                    })
                    .then(data => {
                        ocultarCarga();
                        if (data.success) {
                            ordenCreada = true;
                            return data.data.order_id;
                        } else {
                            throw new Error(data.message || 'Error al crear la orden');
                        }
                    })
                    .catch(error => {
                        ocultarCarga();
                        console.error('Error:', error);
                        alert('Error al procesar el pago: ' + error.message);
                        throw error;
                    });
                },

                onApprove: function(data, actions) {
                    mostrarCarga();
                    
                    return actions.order.capture()
                    .then(function(details) {
                        const formData = new FormData();
                        formData.append('accion', 'capturar_pago');
                        formData.append('order_id', data.orderID);
                        formData.append('payer_id', details.payer.payer_id);
                        formData.append('payment_id', details.id);
                        
                        return fetch('api/checkout.php', {
                            method: 'POST',
                            body: formData
                        });
                    })
                    .then(response => response.json())
                    .then(data => {
                        ocultarCarga();
                        if (data.success) {
                            window.location.href = data.data.redirect || 'exito.php?orden=' + data.data.order_number;
                        } else {
                            throw new Error(data.message || 'Error al procesar el pago');
                        }
                    })
                    .catch(error => {
                        ocultarCarga();
                        console.error('Error:', error);
                        alert('Error al procesar el pago: ' + error.message);
                    });
                },

                onCancel: function(data) {
                    ocultarCarga();
                    console.log('Pago cancelado');
                    alert('Pago cancelado. Puedes intentar nuevamente cuando quieras.');
                },

                onError: function(err) {
                    ocultarCarga();
                    console.error('Error de PayPal:', err);
                    alert('Ocurrió un error con PayPal. Por favor, intenta nuevamente.');
                }
            }).render('#paypal-button-container').catch(err => {
                console.error('Error al renderizar botón PayPal:', err);
                document.getElementById('paypal-button-container').innerHTML = 
                    '<div class="alert alert-danger">Error al cargar PayPal. Por favor, recarga la página.</div>';
            });
        } catch (e) {
            console.error('Error:', e);
        }
    }

    // ============================================================
    // Validar formulario de transferencia
    // ============================================================
    document.getElementById('form-checkout').addEventListener('submit', function(e) {
        if (metodoSeleccionado === 'paypal') {
            e.preventDefault();
            alert('Por favor, utiliza el botón de PayPal para completar el pago.');
            return false;
        }

        if (!validarDireccion()) {
            e.preventDefault();
            return false;
        }

        // Mostrar confirmación
        if (!confirm('¿Confirmas tu compra por ' + '<?= formato_precio($totales['total']) ?>' + '?')) {
            e.preventDefault();
            return false;
        }

        mostrarCarga();
    });

    // ============================================================
    // Inicializar PayPal
    // ============================================================
    document.addEventListener('DOMContentLoaded', function() {
        // Esperar a que el SDK de PayPal esté disponible
        function checkPayPal() {
            if (typeof paypal_sdk !== 'undefined') {
                renderPayPalButton();
            } else {
                setTimeout(checkPayPal, 500);
            }
        }
        checkPayPal();

        // Seleccionar método por defecto
        seleccionarMetodo('paypal');

        // Limpiar validaciones al escribir
        document.querySelectorAll('#form-checkout input, #form-checkout select').forEach(input => {
            input.addEventListener('input', function() {
                this.classList.remove('is-invalid');
            });
        });
    });

    // ============================================================
    // Manejar cierre del navegador
    // ============================================================
    window.addEventListener('beforeunload', function(e) {
        if (ordenCreada) {
            navigator.sendBeacon('api/checkout.php', 
                new URLSearchParams({
                    accion: 'cancelar_orden',
                    order_id: ordenCreada
                })
            );
        }
    });
    </script>

    <!-- ============================================================
         Bootstrap 5.3 JS Bundle
         ============================================================ -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous">
    </script>

</body>
</html>

<?php
// ============================================================
// 8. Incluir footer
// ============================================================
require_once __DIR__ . '/includes/footer.php';
?>