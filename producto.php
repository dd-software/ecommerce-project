<?php
// ============================================================
// Página de Detalle de Producto
// ============================================================
// [PEDAGÓGICO] Muestra la información completa de un producto:
// galería de imágenes, precio, descuento, stock, selector de
// cantidad, botón de carrito y productos relacionados.
// ============================================================

// ============================================================
// Incluir configuración, BD y funciones
// ============================================================
require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/funciones.php';

$pdo = getDB();

// ============================================================
// Validar ID del producto (ANTES del header)
// ============================================================
// [PEDAGÓGICO] Verificamos que el parámetro 'id' exista y sea
// un número entero válido. Si no, redirigimos al inicio.
// IMPORTANTE: Esta validación debe estar ANTES de incluir header.php
// para evitar el error "headers already sent".
$producto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

if ($producto_id <= 0) {
    $_SESSION['error'] = 'ID de producto inválido.';
    redireccionar('index.php');
}

// ============================================================
// Obtener datos del producto (ANTES del header)
// ============================================================
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre, c.id as categoria_id,
           inv.cantidad as stock
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN inventario inv ON inv.producto_id = p.id
    WHERE p.id = :id AND p.activo = 1
");
$stmt->execute([':id' => $producto_id]);
$producto = $stmt->fetch();

// Si el producto no existe o no está activo, redirigir ANTES del header
if (!$producto) {
    $_SESSION['error'] = 'Producto no encontrado.';
    redireccionar('index.php');
}

// ============================================================
// AHORA incluimos el header (después de las validaciones)
// ============================================================
require_once __DIR__ . '/includes/header.php';

// ============================================================
// Obtener galería de imágenes del producto
// ============================================================
// [PEDAGÓGICO] Un producto puede tener múltiples imágenes.
// La primera imagen (es_principal = 1) se muestra por defecto.
$stmt = $pdo->prepare("
    SELECT id, url, alt_text, es_principal
    FROM imagenes
    WHERE producto_id = :producto_id
    ORDER BY es_principal DESC, orden ASC, id ASC
");
$stmt->execute([':producto_id' => $producto_id]);
$imagenes = $stmt->fetchAll();

// Si no hay imágenes, usamos un array vacío
if (empty($imagenes)) {
    $imagenes = [];
}
?>

<!-- ============================================================
     Breadcrumb (Navegación de migas de pan)
     ============================================================
     [PEDAGÓGICO] El breadcrumb ayuda al usuario a saber dónde
     está y permite volver a secciones anteriores fácilmente. -->
<nav aria-label="breadcrumb">
    <ol class="breadcrumb bg-light p-3 rounded">
        <li class="breadcrumb-item">
            <a href="index.php" class="text-decoration-none">Inicio</a>
        </li>
        <?php if (!empty($producto['categoria_nombre'])): ?>
        <li class="breadcrumb-item">
            <a href="index.php?categoria=<?= (int) $producto['categoria_id'] ?>"
               class="text-decoration-none">
                <?= escapar($producto['categoria_nombre']) ?>
            </a>
        </li>
        <?php endif; ?>
        <li class="breadcrumb-item active" aria-current="page">
            <?= escapar($producto['nombre']) ?>
        </li>
    </ol>
</nav>

<!-- ============================================================
     Sección Principal del Producto
     ============================================================ -->
<div class="row g-5">
    <!-- ============================================================
         Columna Izquierda: Galería de Imágenes
         ============================================================ -->
    <div class="col-md-6">
        <?php if (!empty($imagenes)): ?>
            <!-- Imagen grande principal -->
            <div class="mb-3">
                <img id="imagen-principal"
                     src="<?= escapar(ruta_imagen_producto($imagenes[0]['url'])) ?>"
                     alt="<?= escapar($imagenes[0]['alt_text'] ?? $producto['nombre']) ?>"
                     class="img-fluid rounded shadow-sm w-100"
                     style="max-height: 400px; object-fit: contain; background: #f8f9fa;">
            </div>

            <!-- Miniaturas para carousel/galería -->
            <?php if (count($imagenes) > 1): ?>
            <div class="row row-cols-4 g-2">
                <?php foreach ($imagenes as $img): ?>
                <div class="col">
                    <img src="<?= escapar(ruta_imagen_producto($img['url'])) ?>"
                         alt="<?= escapar($img['alt_text'] ?? 'Imagen del producto') ?>"
                         class="img-fluid rounded border cursor-pointer miniatura-imagen"
                         style="height: 80px; width: 100%; object-fit: cover; cursor: pointer;"
                         onclick="document.getElementById('imagen-principal').src = this.src;">
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <!-- Placeholder cuando no hay imágenes -->
            <div class="bg-light d-flex align-items-center justify-content-center rounded shadow-sm"
                 style="height: 400px;">
                <div class="text-center text-muted">
                    <div style="font-size: 5rem;">📦</div>
                    <p class="mt-2">Imagen no disponible</p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ============================================================
         Columna Derecha: Información del Producto
         ============================================================ -->
    <div class="col-md-6">
        <!-- Nombre del producto -->
        <h1 class="display-6 fw-bold mb-2"><?= escapar($producto['nombre']) ?></h1>

        <!-- Categoría -->
        <p class="text-muted mb-3">
            📂 <?= escapar($producto['categoria_nombre'] ?? 'Sin categoría') ?>
            &nbsp;|&nbsp; SKU: <?= escapar($producto['sku'] ?? 'N/A') ?>
        </p>

        <!-- Precio con descuento -->
        <div class="mb-3">
            <?php if (!empty($producto['precio_descuento'])): ?>
                <!-- Precio original tachado -->
                <span class="text-muted text-decoration-line-through fs-5">
                    <?= formato_precio($producto['precio']) ?>
                </span>
                <!-- Precio con descuento -->
                <span class="display-6 fw-bold text-danger ms-2">
                    <?= formato_precio($producto['precio_descuento']) ?>
                </span>
                <?php
                $descuento = round((1 - $producto['precio_descuento'] / $producto['precio']) * 100);
                if ($descuento > 0):
                ?>
                <span class="badge bg-danger fs-6 ms-2">-<?= $descuento ?>%</span>
                <?php endif; ?>
                <p class="mt-1">
                    <small class="text-muted">Ahorras <?= formato_precio($producto['precio'] - $producto['precio_descuento']) ?></small>
                </p>
            <?php else: ?>
                <span class="display-6 fw-bold">
                    <?= formato_precio($producto['precio']) ?>
                </span>
            <?php endif; ?>
        </div>

        <!-- Stock disponible -->
        <div class="mb-3">
            <?php if (($producto['stock'] ?? 0) > 0): ?>
                <span class="badge bg-success fs-6 p-2">✔ En stock (<?= (int) $producto['stock'] ?> disponibles)</span>
            <?php else: ?>
                <span class="badge bg-secondary fs-6 p-2">✖ Producto agotado</span>
            <?php endif; ?>
        </div>

        <!-- Descripción del producto -->
        <?php if (!empty($producto['descripcion'])): ?>
        <div class="mb-4">
            <h5>Descripción</h5>
            <p class="text-muted"><?= nl2br(escapar($producto['descripcion'])) ?></p>
        </div>
        <?php endif; ?>

        <!-- ============================================================
             Formulario: Agregar al carrito
             ============================================================ -->
        <?php if (($producto['stock'] ?? 0) > 0): ?>
        <form method="POST" action="carrito.php" class="mt-4">
            <!-- Token CSRF para seguridad -->
            <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="agregar">
            <input type="hidden" name="producto_id" value="<?= (int) $producto['id'] ?>">

            <!-- Selector de cantidad -->
            <div class="row g-2 align-items-center mb-3">
                <div class="col-auto">
                    <label for="cantidad" class="form-label fw-semibold mb-0">Cantidad:</label>
                </div>
                <div class="col-auto">
                    <div class="input-group" style="max-width: 140px;">
                        <!-- Botón disminuir -->
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="cambiarCantidad(-1)">−</button>
                        <!-- Input de cantidad -->
                        <input type="number"
                               id="cantidad"
                               name="cantidad"
                               class="form-control text-center"
                               value="1"
                               min="1"
                               max="<?= (int) $producto['stock'] ?>">
                        <!-- Botón aumentar -->
                        <button type="button" class="btn btn-outline-secondary"
                                onclick="cambiarCantidad(1)">+</button>
                    </div>
                </div>
            </div>

            <!-- Botón de agregar al carrito -->
            <button type="submit" class="btn btn-primary btn-lg w-100">
                🛒 Agregar al carrito
            </button>
        </form>

        <!-- Script para el selector de cantidad -->
        <script>
        function cambiarCantidad(delta) {
            var input = document.getElementById('cantidad');
            var valor = parseInt(input.value) + delta;
            var min = parseInt(input.min);
            var max = parseInt(input.max);
            if (valor < min) valor = min;
            if (valor > max) valor = max;
            input.value = valor;
        }
        </script>
        <?php else: ?>
            <button class="btn btn-secondary btn-lg w-100" disabled>
                ✖ Producto agotado
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- ============================================================
     Productos Relacionados
     ============================================================
     [PEDAGÓGICO] Mostramos productos de la misma categoría
     excluyendo el producto actual. Si el producto no tiene
     categoría, no se muestran relacionados. -->
<?php if (!empty($producto['categoria_id'])): ?>
<?php
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
    WHERE p.categoria_id = :cat_id
      AND p.id != :prod_id
      AND p.activo = 1
    ORDER BY p.destacado DESC, p.fecha_creacion DESC
    LIMIT 4
");
$stmt->execute([
    ':cat_id'  => $producto['categoria_id'],
    ':prod_id' => $producto['id'],
]);
$relacionados = $stmt->fetchAll();
?>

<?php if (!empty($relacionados)): ?>
<hr class="my-5">
<section class="productos-relacionados">
    <h3 class="mb-4">🔄 Productos Relacionados</h3>
    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
        <?php foreach ($relacionados as $rel): ?>
        <div class="col">
            <div class="card h-100 shadow-sm">
                <!-- Imagen -->
                <a href="producto.php?id=<?= (int) $rel['id'] ?>">
                    <div class="card-img-top bg-light d-flex align-items-center justify-content-center"
                         style="height: 180px; overflow: hidden;">
                        <?php if (!empty($rel['imagen_url'])): ?>
                            <img src="<?= escapar(ruta_imagen_producto($rel['imagen_url'])) ?>"
                                 alt="<?= escapar($rel['alt_text'] ?? $rel['nombre']) ?>"
                                 class="img-fluid"
                                 style="max-height: 100%; object-fit: contain;">
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <div style="font-size: 2rem;">📦</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </a>
                <div class="card-body d-flex flex-column">
                    <h6 class="card-title">
                        <a href="producto.php?id=<?= (int) $rel['id'] ?>"
                           class="text-decoration-none text-dark">
                            <?= escapar($rel['nombre']) ?>
                        </a>
                    </h6>
                    <div class="mt-auto">
                        <?php if (!empty($rel['precio_descuento'])): ?>
                            <span class="text-muted text-decoration-line-through small">
                                <?= formato_precio($rel['precio']) ?>
                            </span>
                            <span class="fw-bold text-danger">
                                <?= formato_precio($rel['precio_descuento']) ?>
                            </span>
                        <?php else: ?>
                            <span class="fw-bold">
                                <?= formato_precio($rel['precio']) ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>
<?php endif; ?>

<?php
require_once __DIR__ . '/includes/footer.php';
?>
