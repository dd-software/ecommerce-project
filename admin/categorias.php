<?php
// ============================================================
// admin/categorias.php - Gestión de Categorías
// ============================================================
// [PEDAGÓGICO] CRUD completo de categorías para administradores.
// Permite listar, crear, editar y eliminar categorías.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// Verificar permisos de administrador
if (!esta_logueado() || !es_admin()) {
    redireccionar(SITE_URL . '/login.php');
}

$pdo = getDB();
$mensaje = '';
$error   = '';

// ============================================================
// Procesar acciones (crear, editar, eliminar)
// ============================================================
$accion = $_GET['accion'] ?? 'listar';

// --- CREAR CATEGORÍA ---
if ($accion === 'crear' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Recarga la página.';
    } else {
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $orden       = max(0, (int) ($_POST['orden'] ?? 0));

        if (empty($nombre)) {
            $error = 'El nombre de la categoría es obligatorio.';
        } else {
            // Verificar que no exista una categoría con el mismo nombre
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre");
            $stmt->execute([':nombre' => $nombre]);
            if ($stmt->fetch()) {
                $error = 'Ya existe una categoría con ese nombre.';
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO categorias (nombre, descripcion, activa, orden)
                    VALUES (:nombre, :descripcion, 1, :orden)
                ");
                $stmt->execute([
                    ':nombre'      => $nombre,
                    ':descripcion' => $descripcion,
                    ':orden'       => $orden,
                ]);
                $mensaje = '✅ Categoría creada exitosamente.';
            }
        }
    }
}

// --- EDITAR CATEGORÍA ---
if ($accion === 'editar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    $id    = (int) ($_POST['id'] ?? 0);

    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Recarga la página.';
    } elseif ($id <= 0) {
        $error = 'ID de categoría inválido.';
    } else {
        $nombre      = trim($_POST['nombre'] ?? '');
        $descripcion = trim($_POST['descripcion'] ?? '');
        $activa      = isset($_POST['activa']) ? 1 : 0;
        $orden       = max(0, (int) ($_POST['orden'] ?? 0));

        if (empty($nombre)) {
            $error = 'El nombre de la categoría es obligatorio.';
        } else {
            // Verificar que no exista otra categoría con el mismo nombre
            $stmt = $pdo->prepare("SELECT id FROM categorias WHERE nombre = :nombre AND id != :id");
            $stmt->execute([':nombre' => $nombre, ':id' => $id]);
            if ($stmt->fetch()) {
                $error = 'Ya existe otra categoría con ese nombre.';
            } else {
                $stmt = $pdo->prepare("
                    UPDATE categorias
                    SET nombre = :nombre, descripcion = :descripcion, activa = :activa, orden = :orden
                    WHERE id = :id
                ");
                $stmt->execute([
                    ':nombre'      => $nombre,
                    ':descripcion' => $descripcion,
                    ':activa'      => $activa,
                    ':orden'       => $orden,
                    ':id'          => $id,
                ]);
                $mensaje = '✅ Categoría actualizada exitosamente.';
            }
        }
    }
}

// --- ELIMINAR CATEGORÍA ---
if ($accion === 'eliminar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['_csrf_token'] ?? '';
    $id    = (int) ($_POST['id'] ?? 0);

    if (!csrf_validar($token)) {
        $error = 'Error de seguridad. Recarga la página.';
    } elseif ($id <= 0) {
        $error = 'ID de categoría inválido.';
    } else {
        // Verificar si hay productos asociados
        $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM productos WHERE categoria_id = :id AND activo = 1");
        $stmt->execute([':id' => $id]);
        $productos_asociados = (int) $stmt->fetch()['total'];

        if ($productos_asociados > 0) {
            $error = "No se puede eliminar la categoría porque tiene {$productos_asociados} producto(s) asociado(s). Desactívala en su lugar.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM categorias WHERE id = :id");
            $stmt->execute([':id' => $id]);
            $mensaje = '🗑️ Categoría eliminada exitosamente.';
        }
    }
}

// ============================================================
// Obtener listado de categorías
// ============================================================
$stmt = $pdo->query("
    SELECT c.*,
           (SELECT COUNT(*) FROM productos p WHERE p.categoria_id = c.id AND p.activo = 1) as total_productos
    FROM categorias c
    ORDER BY c.orden ASC, c.nombre ASC
");
$categorias = $stmt->fetchAll();

// ============================================================
// Si se pide editar, obtener datos de la categoría
// ============================================================
$editar_categoria = null;
if ($accion === 'editar' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $pdo->prepare("SELECT * FROM categorias WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $editar_categoria = $stmt->fetch();
    if (!$editar_categoria) {
        $error = 'Categoría no encontrada.';
        $accion = 'listar';
    }
}

require_once __DIR__ . '/admin_header.php';
?>

<!-- ============================================================
     Título y botón de nueva categoría
     ============================================================ -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <h2 class="mb-0">
        <i class="bi bi-tags me-2"></i>
        Gestionar Categorías
    </h2>
    <button class="btn btn-primary"
            data-bs-toggle="modal"
            data-bs-target="#modalCategoria"
            onclick="limpiarModal()">
        <i class="bi bi-plus-circle"></i> Nueva Categoría
    </button>
</div>

<!-- Mensajes -->
<?php if ($mensaje): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $mensaje ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>
<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $error ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
<?php endif; ?>

<!-- ============================================================
     Tabla de Categorías
     ============================================================ -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Productos</th>
                        <th>Orden</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($categorias)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">
                                <i class="bi bi-inbox" style="font-size: 1.5rem;"></i>
                                <p class="mb-0 mt-1">No hay categorías registradas.</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($categorias as $cat): ?>
                        <tr>
                            <td><?= (int) $cat['id'] ?></td>
                            <td>
                                <strong><?= escapar($cat['nombre']) ?></strong>
                            </td>
                            <td>
                                <small class="text-muted">
                                    <?= escapar(substr($cat['descripcion'] ?? '', 0, 80)) ?>
                                    <?= strlen($cat['descripcion'] ?? '') > 80 ? '...' : '' ?>
                                </small>
                            </td>
                            <td>
                                <span class="badge bg-<?= $cat['total_productos'] > 0 ? 'primary' : 'secondary' ?>">
                                    <?= (int) $cat['total_productos'] ?>
                                </span>
                            </td>
                            <td><?= (int) $cat['orden'] ?></td>
                            <td>
                                <?php if ((int) $cat['activa'] === 1): ?>
                                    <span class="badge bg-success">Activa</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Inactiva</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <button class="btn btn-outline-primary"
                                            onclick="editarCategoria(<?= (int) $cat['id'] ?>)"
                                            title="Editar">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-outline-danger"
                                            onclick="eliminarCategoria(<?= (int) $cat['id'] ?>, '<?= escapar($cat['nombre']) ?>')"
                                            title="Eliminar"
                                            <?= $cat['total_productos'] > 0 ? 'disabled' : '' ?>>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ============================================================
     Modal para Crear/Editar Categoría
     ============================================================ -->
<div class="modal fade" id="modalCategoria" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="categorias.php" id="formCategoria">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalTitulo">Nueva Categoría</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" id="accion_modal" value="crear">
                    <input type="hidden" name="id" id="categoria_id" value="0">

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text"
                               id="nombre"
                               name="nombre"
                               class="form-control"
                               placeholder="Ej: Electrónica"
                               required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion"
                                  name="descripcion"
                                  class="form-control"
                                  rows="3"
                                  placeholder="Descripción visible para los clientes..."></textarea>
                    </div>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="orden" class="form-label">Orden</label>
                            <input type="number"
                                   id="orden"
                                   name="orden"
                                   class="form-control"
                                   value="0"
                                   min="0">
                        </div>
                        <div class="col-md-6 mb-3 d-flex align-items-end">
                            <div class="form-check">
                                <input type="checkbox"
                                       id="activa"
                                       name="activa"
                                       class="form-check-input"
                                       value="1"
                                       checked>
                                <label for="activa" class="form-check-label">Categoría activa</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary" id="btnGuardar">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ============================================================
     Modal para Confirmar Eliminación
     ============================================================ -->
<div class="modal fade" id="modalEliminar" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="POST" action="categorias.php">
                <div class="modal-header">
                    <h5 class="modal-title">Confirmar Eliminación</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="_csrf_token" value="<?= csrf_token() ?>">
                    <input type="hidden" name="accion" value="eliminar">
                    <input type="hidden" name="id" id="eliminar_id" value="0">
                    <p>¿Estás seguro de eliminar la categoría <strong id="eliminar_nombre"></strong>?</p>
                    <p class="text-danger mb-0"><small>Esta acción no se puede deshacer.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// ============================================================
// Funciones JavaScript para el modal
// ============================================================

/**
 * Limpia el formulario del modal para nueva categoría
 */
function limpiarModal() {
    document.getElementById('modalTitulo').textContent = 'Nueva Categoría';
    document.getElementById('accion_modal').value = 'crear';
    document.getElementById('categoria_id').value = '0';
    document.getElementById('nombre').value = '';
    document.getElementById('descripcion').value = '';
    document.getElementById('orden').value = '0';
    document.getElementById('activa').checked = true;
    document.getElementById('btnGuardar').textContent = 'Guardar';
}

/**
 * Carga datos de una categoría para editar
 * @param {number} id - ID de la categoría
 */
function editarCategoria(id) {
    // Redirigir a la misma página con el ID para cargar datos del servidor
    window.location.href = 'categorias.php?accion=editar&id=' + id;
}

/**
 * Abre modal de confirmación para eliminar categoría
 * @param {number} id - ID de la categoría
 * @param {string} nombre - Nombre de la categoría
 */
function eliminarCategoria(id, nombre) {
    document.getElementById('eliminar_id').value = id;
    document.getElementById('eliminar_nombre').textContent = nombre;
    new bootstrap.Modal(document.getElementById('modalEliminar')).show();
}
</script>

<?php
// ============================================================
// Si hay datos para editar, inyectar script para abrir modal
// ============================================================
if ($editar_categoria):
?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('modalTitulo').textContent = 'Editar Categoría';
    document.getElementById('accion_modal').value = 'editar';
    document.getElementById('categoria_id').value = <?= (int) $editar_categoria['id'] ?>;
    document.getElementById('nombre').value = '<?= escapar($editar_categoria['nombre']) ?>';
    document.getElementById('descripcion').value = '<?= escapar($editar_categoria['descripcion'] ?? '') ?>';
    document.getElementById('orden').value = <?= (int) ($editar_categoria['orden'] ?? 0) ?>;
    document.getElementById('activa').checked = <?= (int) ($editar_categoria['activa'] ?? 1) === 1 ? 'true' : 'false' ?>;
    document.getElementById('btnGuardar').textContent = 'Actualizar';
    new bootstrap.Modal(document.getElementById('modalCategoria')).show();
});
</script>
<?php endif; ?>

<?php
require_once __DIR__ . '/admin_footer.php';
?>