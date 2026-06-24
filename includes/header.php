<?php
// Header principal del frontend
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_token() ?>">
    <title><?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fredoka:wght@500;600;700&family=Plus+Jakarta+Sans:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="<?= SITE_URL ?>/assets/css/estilos.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"
            integrity="sha256-/JqT3SQfawRcv/BIHPThkBvs0OEvtFFmqPF/lYI/Cxo="
            crossorigin="anonymous">
    </script>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= SITE_URL ?>">🛒 <?= SITE_NAME ?></a>
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
                    <li class="nav-item"><a class="nav-link" href="<?= SITE_URL ?>/catalogo.php">📋 Catálogo</a></li>
                    <li class="nav-item">
                        <a class="nav-link position-relative" href="<?= SITE_URL ?>/carrito.php">
                            🛍️ Carrito
                            <span id="carrito-contador"
                                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  style="font-size:0.65rem;">0</span>
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
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="usuarioDropdown">
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/cuenta.php">👤 Mi Cuenta</a></li>
                                <li><a class="dropdown-item" href="<?= SITE_URL ?>/carrito.php">🛍️ Mi Carrito</a></li>
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
    <div class="toldo-franja" aria-hidden="true"></div>
    <main class="container my-4">