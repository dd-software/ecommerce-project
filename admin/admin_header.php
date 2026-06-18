<!DOCTYPE html>
<html lang="es">
<head>
    <!-- ============================================================
         Admin Header - Panel de Administración
         ============================================================
         [PEDAGÓGICO] Este header extiende el diseño del frontend
         con una navbar lateral (sidebar) específica para el panel
         de administración. Bootstrap 5.3 se carga desde CDN.
    -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <!-- Token CSRF para formularios admin -->
    <meta name="csrf-token" content="<?= csrf_token() ?>">

    <title>Admin · <?= SITE_NAME ?></title>

    <!-- Bootstrap 5.3 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH"
          crossorigin="anonymous">

    <!-- Bootstrap Icons (para iconos del sidebar) -->
    <link rel="stylesheet"
          href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

    <style>
        /* ============================================================
           Estilos para el layout del panel de administración
           ============================================================
           [PEDAGÓGICO] Sidebar fijo a la izquierda con 250px de ancho.
           El contenido principal se desplaza a la derecha con un
           margen equivalente. En móvil, el sidebar colapsa. */
        :root {
            --sidebar-width: 250px;
        }

        /* Sidebar fijo */
        .admin-sidebar {
            position: fixed;
            top: 0;
            left: 0;
            width: var(--sidebar-width);
            height: 100vh;
            background: #212529;
            color: #fff;
            z-index: 1000;
            overflow-y: auto;
            transition: transform 0.3s ease;
        }

        /* Marca del panel en el sidebar */
        .admin-sidebar .sidebar-brand {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 1rem 1.25rem;
            font-size: 1.15rem;
            font-weight: 700;
            color: #fff;
            text-decoration: none;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .admin-sidebar .sidebar-brand:hover {
            color: #0d6efd;
        }

        /* Navegación del sidebar */
        .admin-sidebar .nav-link {
            color: rgba(255,255,255,0.7);
            padding: 0.7rem 1.25rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            transition: all 0.2s;
        }

        .admin-sidebar .nav-link:hover,
        .admin-sidebar .nav-link.active {
            color: #fff;
            background: rgba(13,110,253,0.15);
        }

        .admin-sidebar .nav-link i {
            font-size: 1.1rem;
            width: 1.4rem;
            text-align: center;
        }

        /* Contenido principal (desplazado) */
        .admin-main {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            background: #f8f9fa;
        }

        /* Navbar superior del admin */
        .admin-topbar {
            background: #fff;
            border-bottom: 1px solid #dee2e6;
            padding: 0.75rem 1.5rem;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }

        /* Breadcrumb */
        .admin-topbar .breadcrumb {
            margin-bottom: 0;
            background: transparent;
        }

        /* Botón toggle del sidebar en móvil */
        .sidebar-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 1.5rem;
            cursor: pointer;
            color: #212529;
        }

        /* Responsive: ocultar sidebar en pantallas pequeñas */
        @media (max-width: 768px) {
            .admin-sidebar {
                transform: translateX(-100%);
            }
            .admin-sidebar.show {
                transform: translateX(0);
            }
            .admin-main {
                margin-left: 0;
            }
            .sidebar-toggle {
                display: inline-block;
            }
        }

        /* Tarjetas de métricas en el dashboard */
        .metric-card {
            border-left: 4px solid #0d6efd;
            transition: transform 0.2s;
        }
        .metric-card:hover {
            transform: translateY(-2px);
        }
        .metric-card .metric-icon {
            font-size: 2rem;
            opacity: 0.2;
        }
        .metric-card .metric-value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        .metric-card .metric-label {
            font-size: 0.85rem;
            color: #6c757d;
        }

        /* Contenedor interno del contenido */
        .admin-content {
            padding: 1.5rem;
        }

        /* Badge de stock bajo */
        .stock-bajo {
            color: #dc3545;
            font-weight: 600;
        }
        .stock-normal {
            color: #198754;
        }
    </style>
</head>
<body>

<!-- ============================================================
     Sidebar de Administración
     ============================================================ -->
<aside class="admin-sidebar" id="adminSidebar">
    <!-- Marca -->
    <a href="<?= SITE_URL ?>/admin/index.php" class="sidebar-brand">
        <i class="bi bi-shield-lock"></i>
        Admin · <?= SITE_NAME ?>
    </a>

    <!-- Menú de navegación -->
    <nav class="mt-2">
        <ul class="nav flex-column">
            <!-- Dashboard -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'index.php' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/admin/index.php">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>

            <!-- Productos -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'productos.php' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/admin/productos.php">
                    <i class="bi bi-box-seam"></i>
                    Productos
                </a>
            </li>

            <!-- Categorías -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'categorias.php' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/admin/categorias.php">
                    <i class="bi bi-tags"></i>
                    Categorías
                </a>
            </li>

            <!-- Pedidos -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'pedidos.php' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/admin/pedidos.php">
                    <i class="bi bi-cart-check"></i>
                    Pedidos
                </a>
            </li>

            <!-- Inventario -->
            <li class="nav-item">
                <a class="nav-link <?= basename($_SERVER['PHP_SELF']) === 'inventario.php' ? 'active' : '' ?>"
                   href="<?= SITE_URL ?>/admin/inventario.php">
                    <i class="bi bi-boxes"></i>
                    Inventario
                </a>
            </li>

            <!-- Separador -->
            <li class="nav-item mt-3">
                <hr class="mx-3" style="border-color: rgba(255,255,255,0.1);">
            </li>

            <!-- Volver al sitio público -->
            <li class="nav-item">
                <a class="nav-link" href="<?= SITE_URL ?>/index.php" target="_blank">
                    <i class="bi bi-house-door"></i>
                    Ver tienda
                </a>
            </li>

            <!-- Cerrar sesión -->
            <li class="nav-item">
                <a class="nav-link text-danger" href="<?= SITE_URL ?>/logout.php">
                    <i class="bi bi-box-arrow-right"></i>
                    Cerrar sesión
                </a>
            </li>
        </ul>
    </nav>
</aside>

<!-- ============================================================
     Contenido Principal
     ============================================================ -->
<div class="admin-main">

    <!-- Barra superior con breadcrumb y usuario -->
    <header class="admin-topbar">
        <div>
            <!-- Botón toggle para móvil -->
            <button class="sidebar-toggle me-2" type="button"
                    onclick="document.getElementById('adminSidebar').classList.toggle('show')"
                    aria-label="Abrir menú">
                <i class="bi bi-list"></i>
            </button>

            <!-- Breadcrumb dinámico -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="<?= SITE_URL ?>/admin/index.php" class="text-decoration-none">
                            Admin
                        </a>
                    </li>
                    <?php
                    // [PEDAGÓGICO] El breadcrumb se muestra según la página actual
                    $pagina_actual = basename($_SERVER['PHP_SELF']);
                    $titulos = [
                        'index.php'      => 'Dashboard',
                        'productos.php'  => 'Productos',
                        'pedidos.php'    => 'Pedidos',
                        'inventario.php' => 'Inventario',
                    ];
                    if (isset($titulos[$pagina_actual])):
                    ?>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= $titulos[$pagina_actual] ?>
                    </li>
                    <?php endif; ?>
                </ol>
            </nav>
        </div>

        <!-- Información del administrador logueado -->
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-person-circle fs-5"></i>
            <span class="fw-semibold">
                <?= escapar($_SESSION['usuario_nombre'] ?? 'Admin') ?>
            </span>
            <span class="badge bg-primary">admin</span>
        </div>
    </header>

    <!-- ============================================================
         Contenedor del contenido de cada página
         Cada página admin incluirá su contenido dentro de este div
         ============================================================ -->
    <main class="admin-content">
