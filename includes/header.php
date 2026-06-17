<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ============================================================
         Meta Tags Básicos
         ============================================================ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- [PEDAGÓGICO] Meta tag CSRF para peticiones AJAX.
         JavaScript puede leer este valor y enviarlo en cabeceras
         HTTP (X-CSRF-Token) al hacer fetch/axios. -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <title><?= SITE_NAME ?></title>

    <!-- ============================================================
         Bootstrap 5.3 CDN (CSS)
         ============================================================
         [PEDAGÓGICO] Bootstrap es el framework CSS más popular.
         Incluye sistema de grillas responsivo, componentes UI
         (navbar, cards, modales, botones) y utilidades.
         CDN = Content Delivery Network, entrega archivos estáticos
         desde servidores rápidos alrededor del mundo. -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- ============================================================
         Estilos personalizados
         ============================================================
         [PEDAGÓGICO] Si necesitas estilos adicionales a Bootstrap,
         crea el archivo assets/css/estilos.css y descomenta la línea.
         Por ahora Bootstrap 5.3 es suficiente para todo el diseño. -->
    <!-- <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/estilos.css"> -->

    <!-- ============================================================
         jQuery 3.7 CDN
         ============================================================
         [PEDAGÓGICO] jQuery simplifica la manipulación del DOM,
         peticiones AJAX y eventos en JavaScript. Se incluye antes
         que otros scripts para que esté disponible globalmente. -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous">
    </script>
</head>
<body>

    <!-- ============================================================
         Navbar Responsivo
         ============================================================
         [PEDAGÓGICO] navbar-expand-lg colapsa en pantallas
         pequeñas (< lg). navbar-light + bg-light define colores. -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        <div class="container">
            <!-- Logo / Marca -->
            <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">
                🛒 <?= SITE_NAME ?>
            </a>

            <!-- Botón hamburguesa para móvil -->
            <button class="navbar-toggler" type="button"
                    data-bs-toggle="collapse"
                    data-bs-target="#navbarPrincipal"
                    aria-controls="navbarPrincipal"
                    aria-expanded="false"
                    aria-label="Menú de navegación">
                <span class="navbar-toggler-icon"></span>
            </button>

            <!-- Menú colapsable -->
            <div class="collapse navbar-collapse" id="navbarPrincipal">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <!-- Catálogo -->
                    <li class="nav-item">
                        <a class="nav-link" href="<?= SITE_URL ?>/index.php">
                            📋 Catálogo
                        </a>
                    </li>

                    <!-- Carrito (con badge dinámico via JavaScript) -->
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= SITE_URL ?>/carrito.php">
                            🛍️ Carrito
                            <span id="carrito-contador"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="font-size: 0.65rem;">
                                0
                            </span>
                        </a>
                    </li>
                </ul>

                <!-- Menú de autenticación (derecha) -->
                <ul class="navbar-nav">
                    <?php if (esta_logueado()): ?>
                        <!-- Usuario logueado -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#"
                               id="usuarioDropdown"
                               role="button"
                               data-bs-toggle="dropdown"
                               aria-expanded="false">
                                👤 <?= escapar($_SESSION['usuario_nombre'] ?? 'Usuario') ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end"
                                aria-labelledby="usuarioDropdown">

                                <!-- Enlace al carrito actual -->
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/carrito.php">
                                        🛍️ Mi Carrito
                                    </a>
                                </li>

                                <!-- [PEDAGÓGICO] Enlace al historial de compras.
                                     Los usuarios pueden ver sus pedidos aquí. -->
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/cuenta.php">
                                        📋 Mis Compras
                                    </a>
                                </li>

                                <!-- [PEDAGÓGICO] Solo los administradores
                                     ven el enlace al panel de administración.
                                     La verificación es_role está en cada
                                     página admin por seguridad. -->
                                <?php if (es_admin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/admin/">
                                        ⚙️ Panel Admin
                                    </a>
                                </li>
                                <?php endif; ?>

                                <li><hr class="dropdown-divider"></li>

                                <!-- Cerrar sesión -->
                                <li>
                                    <a class="dropdown-item text-danger"
                                       href="<?= SITE_URL ?>/logout.php">
                                        🚪 Cerrar Sesión
                                    </a>
                                </li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Usuario NO logueado -->
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/login.php">
                                🔑 Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="<?= SITE_URL ?>/registro.php">
                                📝 Registrarse
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- ============================================================
         Contenedor Principal
         ============================================================
         Cada página debe extender su contenido dentro de este
         contenedor para mantener una estructura consistente. -->
    <main class="container my-4">
