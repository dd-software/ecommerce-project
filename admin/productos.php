<?php
// ============================================================
// productos.php - CRUD de Productos (Panel Admin)
// ============================================================
// [PEDAGÓGICO] Este archivo implementa el CRUD (Create, Read,
// Update, Delete/Desactivar) de productos desde el panel de
// administración. Soporta:
//   - Listado paginado con tabla
//   - Formulario para crear nuevo producto
//   - Formulario para editar producto existente
//   - Activar/desactivar producto (toggle)
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// ============================================================
// Verificar permisos de administrador
// ============================================================
if (!esta_logueado() || !es_admin()) {
    redireccionar(SITE_URL . '/login.php');
}

$pdo = getDB();

// ============================================================
// Mensajes de sesión (flash messages)
// ============================================================
// [PEDAGÓGICO] Usamos $_SESSION para pasar mensajes entre
// peticiones (POST -> GET). Después de mostrarlos, se limpian.
$mensaje_exito = $_SESSION['mensaje_exito'] ?? '';
$mensaje_error = $_SESSION['mensaje_error'] ?? '';
unset($_SESSION['mensaje_exito'], $_SESSION['mensaje_error']);

// ============================================================
// Determinar acción solicitada
// ============================================================
// [PEDAGÓGICO] Acciones vía parámetro GET:
// - listar (default): muestra tabla paginada
// - crear: muestra formulario vacío
// - editar: muestra formulario con datos del producto
// - activar / desactivar: cambia estado activo/inactivo
$accion = $_GET['accion'] ?? 'listar';
$producto_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// ============================================================
// Manejar POST (crear, editar, activar/desactivar)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Validar token CSRF
    $csrf = $_POST['csrf_token'] ?? '';
    if (!csrf_validar($csrf)) {
        $_SESSION['mensaje_error'] = 'Error de seguridad: token CSRF inválido.';
        redireccionar(SITE_URL . '/admin/productos.php');
    }

    $accion_post = $_POST['accion'] ?? '';

    // ----------------------------------------------------------
    // Crear o actualizar producto
    // ----------------------------------------------------------
    if ($accion_post === 'guardar') {
        // Obtener y sanitizar datos del formulario
        $id           = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $sku          = trim($_POST['sku'] ?? '');
        $nombre       = trim($_POST['nombre'] ?? '');
        $descripcion  = trim($_POST['descripcion'] ?? '');
        $precio       = (float) ($_POST['precio'] ?? 0);
        $precio_descuento = $_POST['precio_descuento'] !== '' ? (float) $_POST['precio_descuento'] : null;
        $categoria_id = (int) ($_POST['categoria_id'] ?? 0);
        $activo       = isset($_POST['activo']) ? 1 : 0;
        $destacado    = isset($_POST['destacado']) ? 1 : 0;

        // [PEDAGÓGICO] Validaciones server-side antes de guardar
        $errores = [];

        if ($sku === '') {
            $errores[] = 'El campo SKU es obligatorio.';
        }
        if ($nombre === '') {
            $errores[] = 'El campo Nombre es obligatorio.';
        }
        if ($precio <= 0) {
            $errores[] = 'El precio debe ser mayor a 0.';
        }
        if ($categoria_id <= 0) {
            $errores[] = 'Debe seleccionar una categoría.';
        }
        if ($precio_descuento !== null && $precio_descuento <= 0) {
            $errores[] = 'El precio con descuento debe ser mayor a 0 o dejarlo vacío.';
        }
        if ($precio_descuento !== null && $precio_descuento >= $precio) {
            $errores[] = 'El precio con descuento debe ser menor al precio normal.';
        }

        // Verificar SKU único (excepto si es el mismo producto editando)
        $check_sku = $pdo->prepare("SELECT id FROM productos WHERE sku = :sku AND id != :id");
        $check_sku->execute([':sku' => $sku, ':id' => $id]);
        if ($check_sku->fetch()) {
            $errores[] = 'El SKU ya está registrado por otro producto.';
        }

        if (empty($errores)) {
            // Generar slug a partir del nombre
            $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9-]+/', '-', $nombre), '-'));

            if ($id > 0) {
                // --- ACTUALIZAR producto existente ---
                $stmt = $pdo->prepare("
                    UPDATE productos
                    SET sku = :sku, nombre = :nombre, descripcion = :descripcion,
                        precio = :precio, precio_descuento = :precio_descuento,
                        categoria_id = :categoria_id, activo = :activo,
                        destacado = :destacado, slug = :slug
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':sku'              => $sku,
                    ':nombre'           => $nombre,
                    ':descripcion'      => $descripcion,
                    ':precio'           => $precio,
                    ':precio_descuento' => $precio_descuento,
                    ':categoria_id'     => $categoria_id,
                    ':activo'           => $activo,
                    ':destacado'        => $destacado,
                    ':slug'             => $slug,
                    ':id'               => $id,
                ]);
                $_SESSION['mensaje_exito'] = "Producto «{$nombre}» actualizado correctamente.";
            } else {
                // --- CREAR nuevo producto ---
                $stmt = $pdo->prepare("
                    INSERT INTO productos
                        (sku, nombre, descripcion, precio, precio_descuento,
                         categoria_id, activo, destacado, slug)
                    VALUES
                        (:sku, :nombre, :descripcion, :precio, :precio_descuento,
                         :categoria_id, :activo, :destacado, :slug)
                ");
                $stmt->execute([
                    ':sku'              => $sku,
                    ':nombre'           => $nombre,
                    ':descripcion'      => $descripcion,
                    ':precio'           => $precio,
                    ':precio_descuento' => $precio_descuento,
                    ':categoria_id'     => $categoria_id,
                    ':activo'           => $activo,
                    ':destacado'        => $destacado,
                    ':slug'             => $slug,
                ]);

                // Crear registro de inventario para el nuevo producto
                $nuevo_id = (int) $pdo->lastInsertId();
                $stmt_inv = $pdo->prepare("
                    INSERT INTO inventario (producto_id, cantidad, cantidad_reservada, umbral_alerta)
                    VALUES (:producto_id, 0, 0, 5)
                ");
                $stmt_inv->execute([':producto_id' => $nuevo_id]);

                $_SESSION['mensaje_exito'] = "Producto «{$nombre}» creado correctamente.";
            }

            redireccionar(SITE_URL . '/admin/productos.php');
        } else {
            // Mostrar errores en el formulario
            $_SESSION['mensaje_error'] = implode('<br>', $errores);
            // Redirigir de vuelta al formulario con los datos
            $redir_accion = $id > 0 ? 'editar&id=' . $id : 'crear';
            redireccionar(SITE_URL . '/admin/productos.php?accion=' . $redir_accion);
        }
    }

    // ----------------------------------------------------------
    // Activar / Desactivar producto (via POST)
    // ----------------------------------------------------------
    if ($accion_post === 'toggle_activo') {
        $id = (int) ($_POST['id'] ?? 0);
        $nuevo_estado = (int) ($_POST['activo'] ?? 0);

        $stmt = $pdo->prepare("UPDATE productos SET activo = :activo WHERE id = :id");
        $stmt->execute([':activo' => $nuevo_estado, ':id' => $id]);

        $estado_texto = $nuevo_estado ? 'activado' : 'desactivado';
        $_SESSION['mensaje_exito'] = "Producto #{$id} {$estado_texto} correctamente.";
        redireccionar(SITE_URL . '/admin/productos.php');
    }
}

// ============================================================
// Obtener listado de categorías (para el formulario)
// ============================================================
$categorias = $pdo->query("
    SELECT id, nombre FROM categorias WHERE activa = 1 ORDER BY nombre ASC
")->fetchAll();

// ============================================================
// Obtener datos del producto si estamos editando
// ============================================================
$producto_editar = null;
if ($accion === 'editar' && $producto_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM productos WHERE id = :id");
    $stmt->execute([':id' => $producto_id]);
    $producto_editar = $stmt->fetch();

    if (!$producto_editar) {
        $_SESSION['mensaje_error'] = 'Producto no encontrado.';
        redireccionar(SITE_URL . '/admin/productos.php');
    }
}

// ============================================================
// Paginación del listado
// ============================================================
$pagina     = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;
$por_pagina = 15;
$offset     = ($pagina - 1) * $por_pagina;

$total_stmt = $pdo->query("SELECT COUNT(*) as total FROM productos");
$total_prods = (int) $total_stmt->fetch()['total'];
$total_paginas = max(1, ceil($total_prods / $por_pagina));

// Obtener productos con datos de categoría e inventario
$stmt = $pdo->prepare("
    SELECT p.*, c.nombre as categoria_nombre,
           inv.cantidad as stock, inv.umbral_alerta
    FROM productos p
    LEFT JOIN categorias c ON c.id = p.categoria_id
    LEFT JOIN inventario inv ON inv.producto_id = p.id
    ORDER BY p.fecha_creacion DESC
    LIMIT :limite OFFSET :offset
");
$stmt->bindValue(':limite', $por_pagina, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$productos = $stmt->fetchAll();

// Incluir header del admin
require_once __DIR__ . '/admin_header.php';
?>

<!-- ============================================================
     Título y botón de nuevo producto
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-box-seam me-2"></i>
        <?php if ($accion === 'crear'): ?>
            Nuevo Producto
        <?php elseif ($accion === 'editar'): ?>
            Editar Producto
        <?php else: ?>
            Productos
        <?php endif; ?>
    </h2>
    <?php if ($accion === 'listar'): ?>
        <a href="?accion=crear" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo Producto
        </a>
    <?php else: ?>
        <a href="<?= SITE_URL ?>/admin/productos.php" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left"></i> Volver al listado
        </a>
    <?php endif; ?>
</div>

<!-- ============================================================
     Mensajes flash (éxito / error)
     ============================================================ -->
<?php if ($mensaje_exito): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="bi bi-check-circle me-2"></i>
        <?= $mensaje_exito ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($mensaje_error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bi bi-exclamation-triangle me-2"></i>
        <?= $mensaje_error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<?php if ($accion === 'crear' || $accion === 'editar'): ?>

<!-- ============================================================
     FORMULARIO: Crear / Editar Producto
     ============================================================ -->
<div class="card shadow-sm border-0">
    <div class="card-body p-4">
        <form method="POST" action="productos.php">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
            <input type="hidden" name="accion" value="guardar">

            <!-- Si es edición, guardamos el ID -->
            <?php if ($producto_editar): ?>
                <input type="hidden" name="id" value="<?= (int) $producto_editar['id'] ?>">
            <?php endif; ?>

            <div class="row g-3">

                <!-- SKU -->
                <div class="col-md-4">
                    <label for="sku" class="form-label fw-semibold">SKU *</label>
                    <input type="text"
                           id="sku"
                           name="sku"
                           class="form-control"
                           required
                           placeholder="Ej: TEC-003"
                           value="<?= escapar($producto_editar['sku'] ?? '') ?>">
                    <div class="form-text">Código único del producto (Stock Keeping Unit).</div>
                </div>

                <!-- Nombre -->
                <div class="col-md-8">
                    <label for="nombre" class="form-label fw-semibold">Nombre *</label>
                    <input type="text"
                           id="nombre"
                           name="nombre"
                           class="form-control"
                           required
                           placeholder="Ej: Teclado Mecánico RGB"
                           value="<?= escapar($producto_editar['nombre'] ?? '') ?>">
                </div>

                <!-- Descripción -->
                <div class="col-12">
                    <label for="descripcion" class="form-label fw-semibold">Descripción</label>
                    <textarea id="descripcion"
                              name="descripcion"
                              class="form-control"
                              rows="4"
                              placeholder="Descripción detallada del producto..."><?= escapar($producto_editar['descripcion'] ?? '') ?></textarea>
                </div>

                <!-- Precio normal -->
                <div class="col-md-4">
                    <label for="precio" class="form-label fw-semibold">Precio *</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number"
                               id="precio"
                               name="precio"
                               class="form-control"
                               required
                               min="1"
                               step="0.01"
                               placeholder="29990"
                               value="<?= escapar($producto_editar['precio'] ?? '') ?>">
                    </div>
                </div>

                <!-- Precio con descuento -->
                <div class="col-md-4">
                    <label for="precio_descuento" class="form-label fw-semibold">Precio con descuento</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number"
                               id="precio_descuento"
                               name="precio_descuento"
                               class="form-control"
                               min="1"
                               step="0.01"
                               placeholder="24990 (opcional)"
                               value="<?= escapar($producto_editar['precio_descuento'] ?? '') ?>">
                    </div>
                    <div class="form-text">Dejar vacío si no tiene descuento.</div>
                </div>

                <!-- Categoría -->
                <div class="col-md-4">
                    <label for="categoria_id" class="form-label fw-semibold">Categoría *</label>
                    <select id="categoria_id" name="categoria_id" class="form-select" required>
                        <option value="">Seleccionar categoría...</option>
                        <?php foreach ($categorias as $cat): ?>
                            <?php $selected = (isset($producto_editar) && (int) $producto_editar['categoria_id'] === (int) $cat['id']) ? 'selected' : ''; ?>
                            <option value="<?= (int) $cat['id'] ?>" <?= $selected ?>>
                                <?= escapar($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Activo (checkbox) -->
                <div class="col-md-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input"
                               type="checkbox"
                               id="activo"
                               name="activo"
                               value="1"
                               <?= (isset($producto_editar) && $producto_editar['activo']) ? 'checked' : '' ?>
                               <?= !isset($producto_editar) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="activo">
                            Producto activo
                        </label>
                        <div class="form-text">Visible en la tienda.</div>
                    </div>
                </div>

                <!-- Destacado (checkbox) -->
                <div class="col-md-3">
                    <div class="form-check form-switch mt-2">
                        <input class="form-check-input"
                               type="checkbox"
                               id="destacado"
                               name="destacado"
                               value="1"
                               <?= (isset($producto_editar) && $producto_editar['destacado']) ? 'checked' : '' ?>>
                        <label class="form-check-label fw-semibold" for="destacado">
                            Producto destacado
                        </label>
                        <div class="form-text">Mostrar en la página principal.</div>
                    </div>
                </div>

            </div>

            <!-- Botones -->
            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-save me-1"></i>
                    <?= isset($producto_editar) ? 'Actualizar Producto' : 'Crear Producto' ?>
                </button>
                <a href="<?= SITE_URL ?>/admin/productos.php" class="btn btn-outline-secondary">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

<?php else: ?>

<!-- ============================================================
     LISTADO: Tabla de Productos (Paginada)
     ============================================================ -->
<div class="card shadow-sm border-0">
    <div class="card-body p-0">
        <?php if (empty($productos)): ?>
            <!-- Mensaje cuando no hay productos -->
            <div class="text-center py-5 text-muted">
                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                <p class="mt-2 mb-0">No hay productos registrados.</p>
                <a href="?accion=crear" class="btn btn-primary mt-3">
                    <i class="bi bi-plus-lg"></i> Crear primer producto
                </a>
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>SKU</th>
                            <th>Nombre</th>
                            <th>Precio</th>
                            <th>Stock</th>
                            <th>Categoría</th>
                            <th>Activo</th>
                            <th>Destacado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($productos as $prod): ?>
                        <tr>
                            <td>
                                <code><?= escapar($prod['sku']) ?></code>
                            </td>
                            <td>
                                <strong><?= escapar($prod['nombre']) ?></strong>
                            </td>
                            <td>
                                <?php if (!empty($prod['precio_descuento'])): ?>
                                    <span class="text-decoration-line-through text-muted small">
                                        <?= formato_precio($prod['precio']) ?>
                                    </span>
                                    <br>
                                    <span class="text-danger fw-bold">
                                        <?= formato_precio($prod['precio_descuento']) ?>
                                    </span>
                                <?php else: ?>
                                    <?= formato_precio($prod['precio']) ?>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $stock = (int) ($prod['stock'] ?? 0);
                                $umbral = (int) ($prod['umbral_alerta'] ?? 5);
                                if ($stock <= 0):
                                ?>
                                    <span class="badge bg-danger">Agotado</span>
                                <?php elseif ($stock < $umbral): ?>
                                    <span class="badge bg-warning text-dark"><?= $stock ?> uds</span>
                                <?php else: ?>
                                    <span class="badge bg-success"><?= $stock ?> uds</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge bg-secondary">
                                    <?= escapar($prod['categoria_nombre'] ?? 'Sin categoría') ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($prod['activo']): ?>
                                    <span class="badge bg-success">Sí</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">No</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($prod['destacado']): ?>
                                    <span class="badge bg-warning text-dark">★</span>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <!-- Editar -->
                                    <a href="?accion=editar&id=<?= (int) $prod['id'] ?>"
                                       class="btn btn-outline-primary"
                                       title="Editar producto">
                                        <i class="bi bi-pencil"></i>
                                    </a>

                                    <!-- Activar / Desactivar -->
                                    <form method="POST" action="productos.php"
                                          style="display:inline;"
                                          onsubmit="return confirm('¿<?= $prod['activo'] ? 'Desactivar' : 'Activar' ?> este producto?');">
                                        <input type="hidden" name="csrf_token" value="<?= csrf_token() ?>">
                                        <input type="hidden" name="accion" value="toggle_activo">
                                        <input type="hidden" name="id" value="<?= (int) $prod['id'] ?>">
                                        <input type="hidden" name="activo" value="<?= $prod['activo'] ? 0 : 1 ?>">
                                        <button type="submit"
                                                class="btn btn-outline-<?= $prod['activo'] ? 'warning' : 'success' ?>"
                                                title="<?= $prod['activo'] ? 'Desactivar' : 'Activar' ?> producto">
                                            <i class="bi bi-<?= $prod['activo'] ? 'eye-slash' : 'eye' ?>"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Paginación -->
            <?php if ($total_paginas > 1): ?>
            <nav aria-label="Paginación de productos" class="p-3">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= $pagina <= 1 ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina - 1 ?>">&laquo; Anterior</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_paginas; $i++): ?>
                        <li class="page-item <?= $i === $pagina ? 'active' : '' ?>">
                            <a class="page-link" href="?pagina=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= $pagina >= $total_paginas ? 'disabled' : '' ?>">
                        <a class="page-link" href="?pagina=<?= $pagina + 1 ?>">Siguiente &raquo;</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<?php endif; ?>

<?php
// ============================================================
// Incluir footer del admin
// ============================================================
require_once __DIR__ . '/admin_footer.php';
?>
