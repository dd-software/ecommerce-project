<?php
// ============================================================
// HEADER - Versión Corregida
// ============================================================
// [PEDAGÓGICO] Este archivo debe incluirse DESPUÉS de toda la
// lógica PHP (redirecciones, sesiones, validaciones, etc.)
// para evitar errores de "headers already sent".

// Iniciar sesión si no está iniciada (por seguridad)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Asegurar que SITE_URL esté definida
if (!defined('SITE_URL')) {
    define('SITE_URL', '/ecommerce-project');
}

if (!defined('SITE_NAME')) {
    define('SITE_NAME', 'Mi Tienda Online');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ============================================================
         Meta Tags Básicos
         ============================================================ -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= SITE_NAME ?></title>

    <!-- ============================================================
         Bootstrap 5.3 CDN (CSS)
         ============================================================ -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- ============================================================
         Estilos personalizados (opcional)
         ============================================================ -->
    <style>
        /* [PEDAGÓGICO] Estilos inline para evitar archivos extra */
        .producto-imagen {
            height: 200px;
            object-fit: cover;
            width: 100%;
        }
        .precio-oferta {
            color: #dc3545;
            font-weight: bold;
        }
        .precio-normal {
            text-decoration: line-through;
            color: #6c757d;
            margin-right: 10px;
        }
        .carrito-badge {
            font-size: 0.65rem;
        }
        .footer {
            margin-top: 3rem;
            padding: 1.5rem 0;
            background-color: #f8f9fa;
            border-top: 1px solid #dee2e6;
        }
    </style>

    <!-- ============================================================
         jQuery 3.7 CDN
         ============================================================ -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous">
    </script>
</head>
<body>

    <!-- ============================================================
         Navbar Responsivo
         ============================================================ -->
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
                        <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>" 
                           href="<?= SITE_URL ?>/index.php">
                            📋 Catálogo
                        </a>
                    </li>

                    <!-- Carrito (con badge dinámico via JavaScript) -->
                    <li class="nav-item">
                        <a class="nav-link position-relative <?= basename($_SERVER['PHP_SELF']) === 'carrito.php' ? 'active' : '' ?>" 
                           href="<?= SITE_URL ?>/carrito.php">
                            🛍️ Carrito
                            <span id="carrito-contador"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger carrito-badge">
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
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/carrito.php">
                                        🛍️ Mi Carrito
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/cuenta.php">
                                        👤 Mi Cuenta
                                    </a>
                                </li>
                                <?php if (es_admin()): ?>
                                <li>
                                    <a class="dropdown-item" href="<?= SITE_URL ?>/admin/">
                                        ⚙️ Panel Admin
                                    </a>
                                </li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
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
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'login.php' ? 'active' : '' ?>" 
                               href="<?= SITE_URL ?>/login.php">
                                🔑 Iniciar Sesión
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'registro.php' ? 'active' : '' ?>" 
                               href="<?= SITE_URL ?>/registro.php">
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
         ============================================================ -->
    <main class="container my-4">