<?php
// ============================================================
// Página Principal - Catálogo / Home
// ============================================================
// [PEDAGÓGICO] Esta es la página principal del ecommerce.
// Muestra productos destacados en un hero section y el
// catálogo completo paginado con filtros por categoría
// y búsqueda por texto.
// ============================================================

// Incluir configuraciones, conexión BD y funciones
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';


$pdo = getDB();

// ============================================================
// Obtener parámetros de filtro y paginación
// ============================================================
// [PEDAGÓGICO] Filtramos y validamos los parámetros GET
// para evitar inyección y valores inválidos.
$pagina      = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$categoria_id = isset($_GET['categoria']) ? (int) $_GET['categoria'] : 0;
$busqueda    = isset($_GET['buscar']) ? trim($_GET['buscar']) : '';
$por_pagina  = 6;
$offset      = ($pagina - 1) * $por_pagina;

// ============================================================
// Productos Destacados (Hero Section)
// ============================================================
// [PEDAGÓGICO] Los productos marcados como 'destacado = 1'
// se muestran en un carousel/cards al inicio.
$stmt = $pdo->query("
    SELECT p.*, i.url as imagen_url, i.alt_text,
           (inv.cantidad - inv.cantidad_reservada) as stock
    FROM productos p
    LEFT JOIN (
        SELECT producto_id, url, alt_text
        FROM imagenes
        WHERE es_principal = 1
    ) i ON i.producto_id = p.id
    LEFT JOIN inventario inv ON inv.producto_id = p.id
    WHERE p.activo = 1 AND p.destacado = 1
    ORDER BY p.fecha_creacion DESC
    LIMIT 4
");
$destacados = $stmt->fetchAll();
?>
<?php
// ============================================================
// Incluir Header para que no afecte logica
// ============================================================
require_once __DIR__ . '/includes/header.php';
?>

<!-- ============================================================
     Hero Section - Productos Destacados
     ============================================================ -->
<?php if (!empty($destacados)): ?>
<section class="hero-section mb-5">
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach ($destacados as $prod): ?>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 featured-card">
                <!-- Imagen del producto destacado -->
                <a href="producto.php?id=<?= (int) $prod['id'] ?>" class="text-decoration-none">
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                         style="height: 220px; overflow: hidden;">
                        <?php if (!empty($prod['imagen_url'])): ?>
                            <img src="<?= escapar($prod['imagen_url']) ?>"
                                 alt="<?= escapar($prod['alt_text'] ?? $prod['nombre']) ?>"
                                 class="img-fluid"
                                 style="max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <!-- Imagen placeholder si no hay imagen -->
                            <div class="text-center text-muted p-4">
                                <div style="font-size: 3rem;">📦</div>
                                <small>Sin imagen</small>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="card-body d-flex flex-column">
                    <!-- Nombre del producto -->
                    <h5 class="card-title">
                        <a href="producto.php?id=<?= (int) $prod['id'] ?>"
                           class="text-decoration-none text-dark stretched-link">
                            <?= escapar($prod['nombre']) ?>
                        </a>
                    </h5>
                    <!-- Precio con/sin descuento -->
                    <div class="mb-2">
                        <?php if (!empty($prod['precio_descuento'])): ?>
                            <span class="text-muted text-decoration-line-through small">
                                <?= formato_precio($prod['precio']) ?>
                            </span>
                            <span class="fs-5 fw-bold text-danger">
                                <?= formato_precio($prod['precio_descuento']) ?>
                            </span>
                        <?php else: ?>
                            <span class="fs-5 fw-bold">
                                <?= formato_precio($prod['precio']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    <!-- Stock disponible -->
                    <p class="text-muted small mb-2">
                        <?php if (($prod['stock'] ?? 0) > 0): ?>
                            <span class="text-success">✔ En stock (<?= (int) $prod['stock'] ?>)</span>
                        <?php else: ?>
                            <span class="text-danger">✖ Sin stock</span>
                        <?php endif; ?>
                    </p>
                    <!-- Botón Agregar al carrito -->
                    <button class="btn btn-primary btn-sm mt-auto agregar-carrito"
                            data-producto-id="<?= (int) $prod['id'] ?>"
                            data-cantidad="1"
                            <?= (($prod['stock'] ?? 0) < 1) ? 'disabled' : '' ?>>
                        🛒 Agregar
                    </button>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<!-- ============================================================
     Filtros y Búsqueda
     ============================================================ -->
<section class="catalogo-filtros mb-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <form method="GET" action="index.php" class="row g-3 align-items-end">
                <!-- Campo de búsqueda por texto -->
                <div class="col-md-5">
                    <label for="buscar" class="form-label fw-semibold">🔍 Buscar producto</label>
                    <input type="text"
                           id="buscar"
                           name="buscar"
                           class="form-control"
                           placeholder="Buscar por nombre..."
                           value="<?= escapar($busqueda) ?>">
                </div>
                <!-- Filtro por categoría -->
                <div class="col-md-4">
                    <label for="categoria" class="form-label fw-semibold">📂 Categoría</label>
                    <select id="categoria" name="categoria" class="form-select">
                        <option value="0">Todas las categorías</option>
                        <?php
                        // [PEDAGÓGICO] Cargamos las categorías activas para el filtro
                        $catStmt = $pdo->query("SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY orden ASC");
                        while ($cat = $catStmt->fetch()):
                        ?>
                            <option value="<?= (int) $cat['id'] ?>"
                                <?= $categoria_id === (int) $cat['id'] ? 'selected' : '' ?>>
                                <?= escapar($cat['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>
                <!-- Botón de filtrar -->
                <div class="col-md-3">
                    <button type="submit" class="btn btn-primary w-100">📊 Filtrar</button>
                </div>
            </form>
        </div>
    </div>
</section>

<!-- ============================================================
     Listado de Productos (Paginado)
     ============================================================ -->
<section class="catalogo-productos">
    <?php
    // ============================================================
    // Construir consulta con filtros dinámicos
    // ============================================================
    // [PEDAGÓGICO] Usamos consultas preparadas con placeholders
    // para filtrar de forma segura sin riesgo de inyección SQL.
    $where  = ["p.activo = 1"];
    $params = [];

    // Filtro por categoría
    if ($categoria_id > 0) {
        $where[]          = "p.categoria_id = :categoria_id";
        $params[':categoria_id'] = $categoria_id;
    }

    // Filtro por búsqueda de texto
    if ($busqueda !== '') {
        $where[]              = "p.nombre LIKE :busqueda";
        $params[':busqueda']  = '%' . $busqueda . '%';
    }

    $where_sql = implode(' AND ', $where);

    // ============================================================
    // Contar total de productos (para paginación)
    // ============================================================
    $stmt_count = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM productos p
        WHERE $where_sql
    ");
    $stmt_count->execute($params);
    $total_productos = (int) $stmt_count->fetch()['total'];
    $total_paginas   = max(1, ceil($total_productos / $por_pagina));

    // ============================================================
    // Obtener productos de la página actual
    // ============================================================
    $stmt = $pdo->prepare("
        SELECT p.*, i.url as imagen_url, i.alt_text,
               (inv.cantidad - inv.cantidad_reservada) as stock,
               c.nombre as categoria_nombre
        FROM productos p
        LEFT JOIN (
            SELECT producto_id, url, alt_text
            FROM imagenes
            WHERE es_principal = 1
        ) i ON i.producto_id = p.id
        LEFT JOIN inventario inv ON inv.producto_id = p.id
        LEFT JOIN categorias c ON c.id = p.categoria_id
        WHERE $where_sql
        ORDER BY p.fecha_creacion DESC
        LIMIT :limite OFFSET :offset
    ");

    // [PEDAGÓGICO] Bind de parámetros con tipos específicos
    foreach ($params as $clave => $valor) {
        $stmt->bindValue($clave, $valor);
    }
    $stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();

    $productos = $stmt->fetchAll();
    ?>

    <!-- Indicador de resultados -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h4 class="mb-0">
            <?php if ($busqueda !== ''): ?>
                Resultados para "<?= escapar($busqueda) ?>"
            <?php else: ?>
                Todos los productos
            <?php endif; ?>
            <small class="text-muted fs-6">(<?= $total_productos ?> productos)</small>
        </h4>
        <?php if (!empty($_GET)): ?>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">✕ Limpiar filtros</a>
        <?php endif; ?>
    </div>

    <!-- Grid de productos -->
    <?php if (empty($productos)): ?>
        <!-- Mensaje si no hay resultados -->
        <div class="alert alert-info text-center py-5">
            <div style="font-size: 3rem;">📭</div>
            <h5>No se encontraron productos</h5>
            <p class="mb-0">Intenta con otra búsqueda o categoría.</p>
        </div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
            <?php foreach ($productos as $prod): ?>
            <div class="col">
                <div class="card h-100 shadow-sm">
                    <!-- Imagen del producto -->
                    <a href="producto.php?id=<?= (int) $prod['id'] ?>">
                        <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                             style="height: 200px; overflow: hidden;">
                            <?php if (!empty($prod['imagen_url'])): ?>
                                <img src="<?= escapar($prod['imagen_url']) ?>"
                                     alt="<?= escapar($prod['alt_text'] ?? $prod['nombre']) ?>"
                                     class="img-fluid"
                                     style="max-height: 100%; object-fit: contain;">
                            <?php else: ?>
                                <div class="text-center text-muted p-4">
                                    <div style="font-size: 2.5rem;">📦</div>
                                    <small>Sin imagen</small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </a>
                    <div class="card-body d-flex flex-column">
                        <!-- Categoría -->
                        <small class="text-muted text-uppercase">
                            <?= escapar($prod['categoria_nombre'] ?? '') ?>
                        </small>
                        <!-- Nombre -->
                        <h6 class="card-title mt-1">
                            <a href="producto.php?id=<?= (int) $prod['id'] ?>"
                               class="text-decoration-none text-dark stretched-link">
                                <?= escapar($prod['nombre']) ?>
                            </a>
                        </h6>
                        <!-- Precio -->
                        <div class="mb-2">
                            <?php if (!empty($prod['precio_descuento'])): ?>
                                <span class="text-muted text-decoration-line-through small">
                                    <?= formato_precio($prod['precio']) ?>
                                </span>
                                <span class="fs-5 fw-bold text-danger">
                                    <?= formato_precio($prod['precio_descuento']) ?>
                                </span>
                                <!-- Badge de descuento -->
                                <?php
                                $descuento = round((1 - $prod['precio_descuento'] / $prod['precio']) * 100);
                                if ($descuento > 0):
                                ?>
                                <span class="badge bg-danger ms-1">-<?= $descuento ?>%</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="fs-5 fw-bold">
                                    <?= formato_precio($prod['precio']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <!-- Stock -->
                        <p class="mb-2">
                            <?php if (($prod['stock'] ?? 0) > 0): ?>
                                <span class="badge bg-success">En stock</span>
                            <?php else: ?>
                                <span class="badge bg-secondary">Agotado</span>
                            <?php endif; ?>
                        </p>
                        <!-- Botón Agregar -->
                        <button class="btn btn-primary btn-sm mt-auto agregar-carrito"
                                data-producto-id="<?= (int) $prod['id'] ?>"
                                data-cantidad="1"
                                <?= (($prod['stock'] ?? 0) < 1) ? 'disabled' : '' ?>>
                            🛒 Agregar al carrito
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- ============================================================
             Paginación
             ============================================================ -->
        <?php if ($total_paginas > 1): ?>
        <nav aria-label="Navegación de páginas" class="mt-4">
            <ul class="pagination justify-content-center">
                <!-- Botón "Anterior" -->
                <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                    <a class="page-link"
                       href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina - 1])) ?>">
                        &laquo; Anterior
                    </a>
                </li>

                <!-- [PEDAGÓGICO] Mostramos un número limitado de páginas
                     y usamos '...' para rangos largos. -->
                <?php
                $rango = 2; // Páginas a mostrar antes y después de la actual
                $inicio = max(1, $pagina - $rango);
                $fin    = min($total_paginas, $pagina + $rango);
                ?>

                <?php if ($inicio > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?<?= http_build_query(array_merge($_GET, ['pagina' => 1])) ?>">1</a>
                    </li>
                    <?php if ($inicio > 2): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                <?php endif; ?>

                <?php for ($i = $inicio; $i <= $fin; $i++): ?>
                    <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                        <a class="page-link"
                           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $i])) ?>">
                            <?= $i ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <?php if ($fin < $total_paginas): ?>
                    <?php if ($fin < $total_paginas - 1): ?>
                        <li class="page-item disabled"><span class="page-link">...</span></li>
                    <?php endif; ?>
                    <li class="page-item">
                        <a class="page-link"
                           href="?<?= http_build_query(array_merge($_GET, ['pagina' => $total_paginas])) ?>">
                            <?= $total_paginas ?>
                        </a>
                    </li>
                <?php endif; ?>

                <!-- Botón "Siguiente" -->
                <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                    <a class="page-link"
                       href="?<?= http_build_query(array_merge($_GET, ['pagina' => $pagina + 1])) ?>">
                        Siguiente &raquo;
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php
// ============================================================
// Incluir footer (cierra </main> y agrega scripts)
// ============================================================
require_once __DIR__ . '/includes/footer.php';
?>
