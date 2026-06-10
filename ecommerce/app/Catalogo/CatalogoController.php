<?php
declare(strict_types=1);

namespace App\Catalogo;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;

class CatalogoController
{
    private Database $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function index(array $params = []): void
    {
        $page = (int) ($_GET['page'] ?? 1);
        $limit = (int) ($_GET['limit'] ?? 12);
        $offset = ($page - 1) * $limit;
        $categoriaId = $_GET['categoria'] ?? null;
        $busqueda = $_GET['q'] ?? null;
        $orden = $_GET['orden'] ?? 'nombre_asc';

        $where = ['p.activo = 1'];
        $bindings = [];

        if ($categoriaId) {
            $where[] = 'p.categoria_id = ?';
            $bindings[] = $categoriaId;
        }
        if ($busqueda) {
            $where[] = '(p.nombre LIKE ? OR p.descripcion LIKE ?)';
            $bindings[] = "%$busqueda%";
            $bindings[] = "%$busqueda%";
        }

        $whereClause = implode(' AND ', $where);

        $orderMap = [
            'nombre_asc' => 'p.nombre ASC',
            'nombre_desc' => 'p.nombre DESC',
            'precio_asc' => 'p.precio ASC',
            'precio_desc' => 'p.precio DESC',
            'nuevos' => 'p.created_at DESC',
        ];
        $orderClause = $orderMap[$orden] ?? 'p.nombre ASC';

        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug,
                       i.cantidad, i.cantidad_reservada,
                       (i.cantidad - i.cantidad_reservada) as stock_disponible,
                       (SELECT url FROM imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen_principal
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                JOIN inventario i ON i.producto_id = p.id
                WHERE $whereClause
                ORDER BY $orderClause
                LIMIT ? OFFSET ?";
        
        $bindings[] = $limit;
        $bindings[] = $offset;
        $productos = $this->db->query($sql, $bindings);

        $countSql = "SELECT COUNT(*) as total FROM productos p WHERE $whereClause";
        array_pop($bindings); // remove offset
        array_pop($bindings); // remove limit
        $countResult = $this->db->queryOne($countSql, $bindings);
        $total = $countResult['total'] ?? 0;
        $totalPages = (int) ceil($total / $limit);

        // Load categories for filters
        $categorias = $this->db->query(
            'SELECT id, nombre, slug FROM categorias WHERE activa = 1 ORDER BY orden ASC, nombre ASC'
        );

        $isApi = Request::isAjax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
        if ($isApi) {
            Response::success([
                'productos' => $productos,
                'categorias' => $categorias,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => $total,
                    'total_pages' => $totalPages
                ]
            ]);
        }

        // Render HTML
        $this->renderCatalogo($productos, $categorias, $page, $totalPages, $total);
    }

    public function detalle(array $params = []): void
    {
        $slug = $params['slug'] ?? null;
        $id = $params['id'] ?? null;

        $sql = "SELECT p.*, c.nombre as categoria_nombre, c.slug as categoria_slug,
                       i.cantidad, i.cantidad_reservada,
                       (i.cantidad - i.cantidad_reservada) as stock_disponible
                FROM productos p
                JOIN categorias c ON p.categoria_id = c.id
                JOIN inventario i ON i.producto_id = p.id
                WHERE p.activo = 1 AND ";
        
        $bindings = [];
        if ($slug) {
            $sql .= 'p.slug = ?';
            $bindings[] = $slug;
        } elseif ($id) {
            $sql .= 'p.id = ?';
            $bindings[] = $id;
        } else {
            Response::error('Producto no especificado', 400);
            return;
        }

        $producto = $this->db->queryOne($sql, $bindings);

        if (!$producto) {
            if (Request::isAjax()) {
                Response::error('Producto no encontrado', 404);
            }
            $this->render404();
            return;
        }

        // Load images
        $imagenes = $this->db->query(
            'SELECT * FROM imagenes WHERE producto_id = ? ORDER BY es_principal DESC, orden ASC',
            [$producto['id']]
        );

        // Load related products
        $relacionados = $this->db->query(
            'SELECT p.*, (SELECT url FROM imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen_principal
             FROM productos p 
             WHERE p.categoria_id = ? AND p.id != ? AND p.activo = 1 
             LIMIT 4',
            [$producto['categoria_id'], $producto['id']]
        );

        if (Request::isAjax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::success([
                'producto' => $producto,
                'imagenes' => $imagenes,
                'relacionados' => $relacionados
            ]);
        }

        $this->renderDetalle($producto, $imagenes, $relacionados);
    }

    private function renderCatalogo(array $productos, array $categorias, int $page, int $totalPages, int $total): void
    {
        $isAuthenticated = (new \App\Core\Session())->isAuthenticated();
        $cartCount = 0;
        if ($isAuthenticated) {
            $cart = $this->db->queryOne(
                'SELECT COUNT(*) as cnt FROM items_carrito ic JOIN carritos c ON ic.carrito_id = c.id WHERE c.usuario_id = ? AND c.estado = ?',
                [\App\Core\Session::getInstance()->getUsuarioId(), 'activo']
            );
            $cartCount = $cart['cnt'] ?? 0;
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catálogo — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">🛒 Ecommerce</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="/catalogo">Catálogo</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/carrito"><i class="bi bi-cart"></i> Carrito <span class="badge bg-primary"><?= $cartCount ?></span></a></li>
                    <?php if ($isAuthenticated): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                                <i class="bi bi-person"></i> <?= htmlspecialchars(\App\Core\Session::getInstance()->get('usuario_nombre', 'Usuario')) ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="/mis-pedidos">Mis Pedidos</a></li>
                                <?php if (\App\Core\Session::getInstance()->isAdmin()): ?>
                                    <li><a class="dropdown-item" href="/admin">Administración</a></li>
                                <?php endif; ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="/logout">Cerrar Sesión</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Iniciar Sesión</a></li>
                        <li class="nav-item"><a class="nav-link" href="/registro">Registrarse</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="card bg-dark border-secondary">
                    <div class="card-header"><h5 class="mb-0">Categorías</h5></div>
                    <div class="list-group list-group-flush">
                        <a href="/catalogo" class="list-group-item list-group-item-action bg-dark text-light border-secondary <?= empty($_GET['categoria']) ? 'active' : '' ?>">Todas</a>
                        <?php foreach ($categorias as $cat): ?>
                            <a href="/catalogo?categoria=<?= $cat['id'] ?>" 
                               class="list-group-item list-group-item-action bg-dark text-light border-secondary <?= ($_GET['categoria'] ?? '') === $cat['id'] ? 'active' : '' ?>">
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2>Catálogo <small class="text-muted">(<?= $total ?> productos)</small></h2>
                    <select class="form-select w-auto bg-dark text-light" onchange="window.location.href=this.value">
                        <option value="?orden=nombre_asc" <?= ($_GET['orden'] ?? '') === 'nombre_asc' ? 'selected' : '' ?>>Nombre A-Z</option>
                        <option value="?orden=nombre_desc" <?= ($_GET['orden'] ?? '') === 'nombre_desc' ? 'selected' : '' ?>>Nombre Z-A</option>
                        <option value="?orden=precio_asc" <?= ($_GET['orden'] ?? '') === 'precio_asc' ? 'selected' : '' ?>>Menor precio</option>
                        <option value="?orden=precio_desc" <?= ($_GET['orden'] ?? '') === 'precio_desc' ? 'selected' : '' ?>>Mayor precio</option>
                    </select>
                </div>

                <div class="row g-4">
                    <?php foreach ($productos as $producto): ?>
                    <div class="col-md-4 col-sm-6">
                        <div class="card h-100 bg-dark border-secondary product-card">
                            <?php if ($producto['imagen_principal']): ?>
                            <img src="<?= htmlspecialchars($producto['imagen_principal']) ?>" class="card-img-top" alt="<?= htmlspecialchars($producto['nombre']) ?>" style="height: 200px; object-fit: cover;">
                            <?php else: ?>
                            <div class="card-img-top bg-secondary d-flex align-items-center justify-content-center" style="height: 200px;">
                                <i class="bi bi-image text-light" style="font-size: 3rem;"></i>
                            </div>
                            <?php endif; ?>
                            <div class="card-body d-flex flex-column">
                                <span class="badge bg-secondary mb-1"><?= htmlspecialchars($producto['categoria_nombre']) ?></span>
                                <h6 class="card-title"><?= htmlspecialchars($producto['nombre']) ?></h6>
                                <div class="mt-auto">
                                    <?php if ($producto['precio_descuento']): ?>
                                        <span class="text-decoration-line-through text-muted">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                                        <span class="text-danger fw-bold fs-5">$<?= number_format($producto['precio_descuento'], 0, ',', '.') ?></span>
                                    <?php else: ?>
                                        <span class="fw-bold fs-5">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                                    <?php endif; ?>
                                    <div class="mt-1">
                                        <?php if ($producto['stock_disponible'] > 0): ?>
                                            <span class="badge bg-success">Stock: <?= $producto['stock_disponible'] ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Sin stock</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="mt-2 d-flex gap-2">
                                    <a href="/producto/<?= htmlspecialchars($producto['slug']) ?>" class="btn btn-outline-light btn-sm flex-grow-1">Ver</a>
                                    <?php if ($producto['stock_disponible'] > 0): ?>
                                    <button class="btn btn-primary btn-sm add-to-cart" data-id="<?= $producto['id'] ?>">
                                        <i class="bi bi-cart-plus"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($totalPages > 1): ?>
                <nav class="mt-4">
                    <ul class="pagination justify-content-center">
                        <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                        <li class="page-item <?= $p === $page ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $p ?>&categoria=<?= $_GET['categoria'] ?? '' ?>&orden=<?= $_GET['orden'] ?? 'nombre_asc' ?>"><?= $p ?></a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="bg-dark text-light py-3 mt-4 border-top border-secondary">
        <div class="container text-center">
            <small>&copy; <?= date('Y') ?> Ecommerce. Todos los derechos reservados.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.querySelectorAll('.add-to-cart').forEach(btn => {
        btn.addEventListener('click', async () => {
            const id = btn.dataset.id;
            const res = await fetch('/api/carrito/agregar', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({producto_id: id, cantidad: 1})
            });
            const data = await res.json();
            if (data.success) {
                const badge = document.querySelector('.badge.bg-primary');
                if (badge) badge.textContent = parseInt(badge.textContent || 0) + 1;
                alert('Producto agregado al carrito');
            } else {
                alert(data.message || 'Error al agregar');
            }
        });
    });
    </script>
</body>
</html><?php
    }

    private function renderDetalle(array $producto, array $imagenes, array $relacionados): void
    {
        $isAuthenticated = (new \App\Core\Session())->isAuthenticated();
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($producto['nombre']) ?> — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">🛒 Ecommerce</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/catalogo">← Volver al Catálogo</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/carrito"><i class="bi bi-cart"></i> Carrito</a></li>
                    <?php if ($isAuthenticated): ?>
                        <li class="nav-item"><a class="nav-link" href="/mis-pedidos">Mis Pedidos</a></li>
                        <li class="nav-item"><a class="nav-link" href="/logout">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Iniciar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-lg-6">
                <?php if (!empty($imagenes)): ?>
                <div id="productCarousel" class="carousel slide mb-3" data-bs-ride="carousel">
                    <div class="carousel-inner">
                        <?php foreach ($imagenes as $i => $img): ?>
                        <div class="carousel-item <?= $i === 0 ? 'active' : '' ?>">
                            <img src="<?= htmlspecialchars($img['url']) ?>" class="d-block w-100 rounded" 
                                 alt="<?= htmlspecialchars($img['alt_text'] ?? $producto['nombre']) ?>" 
                                 style="height: 400px; object-fit: cover;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if (count($imagenes) > 1): ?>
                    <button class="carousel-control-prev" type="button" data-bs-target="#productCarousel" data-bs-slide="prev">
                        <span class="carousel-control-prev-icon"></span>
                    </button>
                    <button class="carousel-control-next" type="button" data-bs-target="#productCarousel" data-bs-slide="next">
                        <span class="carousel-control-next-icon"></span>
                    </button>
                    <?php endif; ?>
                </div>
                <?php else: ?>
                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 400px;">
                    <i class="bi bi-image text-light" style="font-size: 5rem;"></i>
                </div>
                <?php endif; ?>
            </div>
            <div class="col-lg-6">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="/catalogo" class="text-light">Catálogo</a></li>
                        <li class="breadcrumb-item"><a href="/catalogo?categoria=<?= $producto['categoria_id'] ?>" class="text-light"><?= htmlspecialchars($producto['categoria_nombre']) ?></a></li>
                        <li class="breadcrumb-item active"><?= htmlspecialchars($producto['nombre']) ?></li>
                    </ol>
                </nav>
                <h1><?= htmlspecialchars($producto['nombre']) ?></h1>
                <p class="text-muted">SKU: <?= htmlspecialchars($producto['sku']) ?></p>
                
                <div class="my-3">
                    <?php if ($producto['precio_descuento']): ?>
                        <span class="text-decoration-line-through text-muted fs-4">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                        <span class="text-danger fw-bold fs-3">$<?= number_format($producto['precio_descuento'], 0, ',', '.') ?></span>
                        <span class="badge bg-danger ms-2">-<?= round((1 - $producto['precio_descuento'] / $producto['precio']) * 100) ?>%</span>
                    <?php else: ?>
                        <span class="fw-bold fs-3">$<?= number_format($producto['precio'], 0, ',', '.') ?></span>
                    <?php endif; ?>
                </div>

                <div class="mb-3">
                    <?php if ($producto['stock_disponible'] > 0): ?>
                        <span class="badge bg-success fs-6">
                            <i class="bi bi-check-circle"></i> Disponible (<?= $producto['stock_disponible'] ?> unidades)
                        </span>
                    <?php else: ?>
                        <span class="badge bg-danger fs-6"><i class="bi bi-x-circle"></i> Sin stock disponible</span>
                    <?php endif; ?>
                    <?php if ($producto['es_nuevo']): ?>
                        <span class="badge bg-info ms-1">Nuevo</span>
                    <?php endif; ?>
                </div>

                <div class="mb-4">
                    <h5>Descripción</h5>
                    <div class="text-muted"><?= $producto['descripcion'] ?></div>
                </div>

                <div class="d-flex gap-2 align-items-center">
                    <?php if ($producto['stock_disponible'] > 0): ?>
                    <div class="input-group" style="max-width: 150px;">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(-1)">-</button>
                        <input type="number" id="cantidad" class="form-control bg-dark text-light text-center" value="1" min="1" max="<?= $producto['stock_disponible'] ?>">
                        <button class="btn btn-outline-secondary" type="button" onclick="changeQty(1)">+</button>
                    </div>
                    <button class="btn btn-primary btn-lg flex-grow-1" onclick="addToCart()">
                        <i class="bi bi-cart-plus"></i> Agregar al Carrito
                    </button>
                    <?php else: ?>
                    <button class="btn btn-secondary btn-lg flex-grow-1" disabled>Producto sin stock disponible</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($relacionados)): ?>
        <div class="mt-5">
            <h4>Productos Relacionados</h4>
            <div class="row g-3 mt-2">
                <?php foreach ($relacionados as $rel): ?>
                <div class="col-md-3 col-sm-6">
                    <div class="card bg-dark border-secondary h-100">
                        <?php if ($rel['imagen_principal']): ?>
                        <img src="<?= htmlspecialchars($rel['imagen_principal']) ?>" class="card-img-top" style="height: 150px; object-fit: cover;">
                        <?php endif; ?>
                        <div class="card-body">
                            <h6 class="card-title"><?= htmlspecialchars($rel['nombre']) ?></h6>
                            <small class="fw-bold">$<?= number_format($rel['precio'], 0, ',', '.') ?></small>
                            <a href="/producto/<?= htmlspecialchars($rel['slug']) ?>" class="btn btn-sm btn-outline-light w-100 mt-2">Ver</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function changeQty(delta) {
        const input = document.getElementById('cantidad');
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        if (val > <?= $producto['stock_disponible'] ?>) val = <?= $producto['stock_disponible'] ?>;
        input.value = val;
    }

    async function addToCart() {
        const cantidad = document.getElementById('cantidad').value;
        const res = await fetch('/api/carrito/agregar', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({producto_id: '<?= $producto['id'] ?>', cantidad: parseInt(cantidad)})
        });
        const data = await res.json();
        if (data.success) {
            if (confirm('Producto agregado. ¿Ir al carrito?')) {
                window.location.href = '/carrito';
            }
        } else {
            alert(data.message || 'Error al agregar producto');
        }
    }
    </script>
</body>
</html><?php
    }

    private function render404(): void
    {
        http_response_code(404);
        echo '<!DOCTYPE html><html lang="es" data-bs-theme="dark"><head><meta charset="UTF-8"><title>404</title>';
        echo '<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">';
        echo '</head><body class="bg-dark text-light d-flex align-items-center justify-content-center vh-100">';
        echo '<div class="text-center"><h1 class="display-1">404</h1><p>Producto no encontrado</p><a href="/catalogo" class="btn btn-primary">Volver al Catálogo</a></div>';
        echo '</body></html>';
    }
}
