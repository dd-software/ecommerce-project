<?php
// ============================================================
// Página Principal (Home)
// ============================================================
// [PEDAGÓGICO] Esta es la página de inicio ("Mi Tienda UCT").
// Antes esta misma página también hacía de catálogo completo;
// ahora esa parte vive en catalogo.php. Aquí solo va un banner
// de bienvenida y, para usuarios logueados con historial, sus
// últimos productos vistos.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// ============================================================
// Últimos productos vistos (solo para usuarios con sesión iniciada)
// ============================================================
// [PEDAGÓGICO] producto.php va guardando los IDs vistos en
// $_SESSION['ultimos_vistos'] (más reciente primero). Aquí los
// recuperamos en ese mismo orden. Si el usuario es invitado, o
// está logueado pero todavía no vio ningún producto, esta
// sección completa queda oculta (no mostramos nada de respaldo).
$ultimos_vistos = [];

if (esta_logueado() && !empty($_SESSION['ultimos_vistos'])) {
    $ids = array_slice($_SESSION['ultimos_vistos'], 0, 4);

    $marcadores = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("
        SELECT p.*, i.url as imagen_url, i.alt_text,
               inv.cantidad as stock
        FROM productos p
        LEFT JOIN (
            SELECT producto_id, url, alt_text
            FROM imagenes
            WHERE es_principal = 1
        ) i ON i.producto_id = p.id
        LEFT JOIN inventario inv ON inv.producto_id = p.id
        WHERE p.activo = 1 AND p.id IN ($marcadores)
    ");
    $stmt->execute($ids);
    $encontrados = $stmt->fetchAll();

    // Reordenar según el orden real de "vistos" (el IN de SQL no lo respeta)
    $por_id = [];
    foreach ($encontrados as $prod) {
        $por_id[(int) $prod['id']] = $prod;
    }
    foreach ($ids as $id) {
        if (isset($por_id[(int) $id])) {
            $ultimos_vistos[] = $por_id[(int) $id];
        }
    }
}
?>

<!-- ============================================================
     Banner de bienvenida
     ============================================================ -->
<section class="hero-bienvenida mb-5 p-5 rounded-4 text-center">
    <h1 class="display-5 mb-3">🛒 Bienvenido a Mi Tienda UCT</h1>
    <p class="lead mb-4">Productos de Electrónica, Hogar, Ropa y Deportes a un clic de distancia.</p>
    <a href="catalogo.php" class="btn btn-primary btn-lg">📋 Ver catálogo completo</a>
</section>

<?php if (!empty($ultimos_vistos)): ?>
<!-- ============================================================
     Últimos productos vistos
     ============================================================ -->
<section class="hero-section mb-5">
    <h4 class="mb-3">👀 Últimos productos que viste</h4>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-4 g-4">
        <?php foreach ($ultimos_vistos as $prod): ?>
        <div class="col">
            <div class="card h-100 shadow-sm border-0 featured-card">
                <!-- Imagen del producto -->
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

<?php
require_once __DIR__ . '/includes/footer.php';
?>
