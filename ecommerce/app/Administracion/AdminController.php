<?php
declare(strict_types=1);

namespace App\Administracion;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;

class AdminController
{
    private Database $db;
    private Auditoria $auditoria;
    private Session $session;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->auditoria = new Auditoria();
        $this->session = Session::getInstance();
    }

    private function checkAdmin(): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
        }
        if (!$this->session->isAdminOrEmpleado()) {
            Response::error('Acceso denegado', 403);
        }
    }

    public function status(array $params = []): void
    {
        Response::success(['disponible' => true]);
    }

    // Dashboard
    public function dashboard(array $params = []): void
    {
        // RN-002: Solo admin/supervisor/empleado
        if (!$this->session->isAuthenticated()) {
            header('Location: /login?redirect=admin');
            exit;
        }
        if (!$this->session->isAdminOrEmpleado()) {
            header('Location: /');
            exit;
        }

        // Métricas
        $totalProductos = $this->db->queryOne('SELECT COUNT(*) as cnt FROM productos WHERE activo = 1')['cnt'] ?? 0;
        $totalPedidos = $this->db->queryOne('SELECT COUNT(*) as cnt FROM pedidos')['cnt'] ?? 0;
        $totalUsuarios = $this->db->queryOne('SELECT COUNT(*) as cnt FROM usuarios WHERE activo = 1')['cnt'] ?? 0;
        $ingresos = $this->db->queryOne(
            "SELECT COALESCE(SUM(total), 0) as total FROM pedidos WHERE estado IN ('confirmado','en_preparacion','enviado','entregado') AND MONTH(created_at) = MONTH(NOW())"
        )['total'] ?? 0;

        $alertasStock = $this->db->query(
            'SELECT i.*, p.nombre, p.sku FROM inventario i JOIN productos p ON i.producto_id = p.id 
             WHERE i.umbral_alerta IS NOT NULL AND (i.cantidad - i.cantidad_reservada) <= i.umbral_alerta AND p.activo = 1
             LIMIT 10'
        );

        $pedidosRecientes = $this->db->query(
            'SELECT p.*, u.nombre, u.email FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.created_at DESC LIMIT 10'
        );

        $estadoServicios = [
            'catalogo' => 'UP',
            'inventario' => 'UP',
            'checkout' => 'UP',
            'pagos' => 'UP',
            'base_datos' => 'UP'
        ];

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel de Administración — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container-fluid">
            <a class="navbar-brand" href="/admin">⚙️ Admin Panel</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link active" href="/admin">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/usuarios">Usuarios</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/pedidos">Pedidos</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/inventario">Inventario</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/auditoria">Auditoría</a></li>
                    <li class="nav-item"><a class="nav-link" href="/admin/configuracion">Configuración</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/">🏠 Tienda</a></li>
                    <li class="nav-item"><a class="nav-link" href="/logout">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid py-4">
        <h3>Dashboard</h3>
        
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card bg-dark border-success"><div class="card-body text-center"><h5><?= $totalProductos ?></h5><small class="text-success">Productos Activos</small></div></div></div>
            <div class="col-md-3"><div class="card bg-dark border-primary"><div class="card-body text-center"><h5><?= $totalPedidos ?></h5><small class="text-primary">Pedidos Totales</small></div></div></div>
            <div class="col-md-3"><div class="card bg-dark border-info"><div class="card-body text-center"><h5><?= $totalUsuarios ?></h5><small class="text-info">Usuarios</small></div></div></div>
            <div class="col-md-3"><div class="card bg-dark border-warning"><div class="card-body text-center"><h5>$<?= number_format($ingresos, 0, ',', '.') ?></h5><small class="text-warning">Ingresos del Mes</small></div></div></div>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <div class="card bg-dark border-secondary">
                    <div class="card-header"><h5 class="mb-0">🟢 Estado de Servicios</h5></div>
                    <div class="card-body">
                        <?php foreach ($estadoServicios as $svc => $status): ?>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-capitalize"><?= str_replace('_', ' ', $svc) ?></span>
                            <span class="badge bg-<?= $status === 'UP' ? 'success' : 'danger' ?>"><?= $status ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card bg-dark border-warning">
                    <div class="card-header"><h5 class="mb-0">⚠️ Alertas de Stock Bajo</h5></div>
                    <div class="card-body">
                        <?php if (empty($alertasStock)): ?>
                            <p class="text-success">No hay alertas</p>
                        <?php else: ?>
                            <?php foreach ($alertasStock as $alerta): ?>
                            <div class="d-flex justify-content-between mb-1">
                                <span><?= htmlspecialchars($alerta['nombre']) ?></span>
                                <span class="badge bg-danger"><?= (int)$alerta['cantidad'] - (int)$alerta['cantidad_reservada'] ?> / umbral: <?= $alerta['umbral_alerta'] ?></span>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card bg-dark border-secondary">
            <div class="card-header"><h5 class="mb-0">📦 Pedidos Recientes</h5></div>
            <div class="table-responsive">
                <table class="table table-dark table-hover mb-0">
                    <thead><tr><th>N° Orden</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
                    <tbody>
                        <?php foreach ($pedidosRecientes as $pedido): ?>
                        <tr>
                            <td><?= htmlspecialchars($pedido['numero']) ?></td>
                            <td><?= htmlspecialchars($pedido['nombre']) ?></td>
                            <td>$<?= number_format($pedido['total'], 0, ',', '.') ?></td>
                            <td><span class="badge bg-<?= 
                                $pedido['estado'] === 'entregado' ? 'success' : 
                                ($pedido['estado'] === 'cancelado' ? 'danger' : 
                                ($pedido['estado'] === 'pendiente' ? 'warning' : 'info')) 
                            ?>"><?= $pedido['estado'] ?></span></td>
                            <td><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></td>
                            <td>
                                <button class="btn btn-sm btn-outline-light" onclick="cambiarEstado('<?= $pedido['id'] ?>')">
                                    <i class="bi bi-pencil"></i>
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function cambiarEstado(pedidoId) {
        const nuevoEstado = prompt('Nuevo estado (pendiente, confirmado, en_preparacion, enviado, entregado, cancelado):');
        if (!nuevoEstado) return;
        const res = await fetch('/api/admin/pedidos/estado', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({pedido_id: pedidoId, estado: nuevoEstado})
        });
        const data = await res.json();
        alert(data.message || 'Estado actualizado');
        if (data.success) location.reload();
    }
    </script>
</body>
</html><?php
    }

    // Usuarios
    public function usuarios(array $params = []): void
    {
        $this->checkAdmin();

        $users = $this->db->query('SELECT id, nombre, apellido, email, rol, activo, fecha_registro FROM usuarios ORDER BY fecha_registro DESC');

        if (Request::isAjax()) {
            Response::success($users);
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="UTF-8"><title>Usuarios — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/admin">← Admin</a></div></nav>
<div class="container py-4">
    <h3>Gestión de Usuarios</h3>
    <table class="table table-dark table-hover">
        <thead><tr><th>Nombre</th><th>Email</th><th>Rol</th><th>Activo</th><th>Registro</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($users as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['nombre'] . ' ' . $u['apellido']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><span class="badge bg-info"><?= $u['rol'] ?></span></td>
                <td><span class="badge bg-<?= $u['activo'] ? 'success' : 'danger' ?>"><?= $u['activo'] ? 'Sí' : 'No' ?></span></td>
                <td><?= date('d/m/Y', strtotime($u['fecha_registro'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-warning" onclick="toggleUsuario('<?= $u['id'] ?>', <?= $u['activo'] ?>)">
                        <?= $u['activo'] ? 'Desactivar' : 'Activar' ?>
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function toggleUsuario(id, activo) {
    const res = await fetch('/api/admin/usuarios/toggle', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id, activo: activo ? 0 : 1})
    });
    const data = await res.json();
    alert(data.message || 'Actualizado');
    if (data.success) location.reload();
}
</script>
</body></html><?php
    }

    // Toggle usuario activo/inactivo
    public function toggleUsuario(array $params = []): void
    {
        $this->checkAdmin();
        $data = Request::validate(['id' => 'required']);
        
        $user = $this->db->queryOne('SELECT * FROM usuarios WHERE id = ?', [$data['id']]);
        if (!$user) {
            Response::error('Usuario no encontrado', 404);
        }

        $nuevoEstado = Request::input('activo') ? 1 : 0;
        $this->db->execute('UPDATE usuarios SET activo = ? WHERE id = ?', [$nuevoEstado, $data['id']]);
        
        $this->auditoria->registrar($this->session->getUsuarioId(), 'Admin', 'Toggle usuario', 'usuario', $data['id'], ['activo' => $nuevoEstado]);
        
        Response::success(null, 'Usuario actualizado');
    }

    // Pedidos admin
    public function pedidos(array $params = []): void
    {
        $this->checkAdmin();

        $pedidos = $this->db->query(
            'SELECT p.*, u.nombre, u.email FROM pedidos p JOIN usuarios u ON p.usuario_id = u.id ORDER BY p.created_at DESC LIMIT 50'
        );

        if (Request::isAjax()) {
            Response::success($pedidos);
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="UTF-8"><title>Pedidos — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/admin">← Admin</a></div></nav>
<div class="container py-4">
    <h3>Gestión de Pedidos</h3>
    <table class="table table-dark table-hover">
        <thead><tr><th>N°</th><th>Cliente</th><th>Total</th><th>Estado</th><th>Fecha</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($pedidos as $p): ?>
            <tr>
                <td><?= htmlspecialchars($p['numero']) ?></td>
                <td><?= htmlspecialchars($p['nombre']) ?></td>
                <td>$<?= number_format($p['total'], 0, ',', '.') ?></td>
                <td><span class="badge bg-<?= $p['estado'] === 'entregado' ? 'success' : ($p['estado'] === 'cancelado' ? 'danger' : 'info') ?>"><?= $p['estado'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($p['created_at'])) ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-light" onclick="cambiarEstado('<?= $p['id'] ?>')">Editar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function cambiarEstado(id) {
    const estados = ['pendiente','confirmado','en_preparacion','enviado','entregado','cancelado'];
    const estado = prompt('Estado: ' + estados.join(', '));
    if (!estado) return;
    const res = await fetch('/api/admin/pedidos/estado', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({pedido_id: id, estado})
    });
    const data = await res.json();
    alert(data.message || 'OK');
    if (data.success) location.reload();
}
</script>
</body></html><?php
    }

    // Cambiar estado de pedido
    public function cambiarEstado(array $params = []): void
    {
        $this->checkAdmin();
        $data = Request::validate([
            'pedido_id' => 'required',
            'estado' => 'required'
        ]);

        $estadosValidos = ['pendiente', 'confirmado', 'en_preparacion', 'enviado', 'entregado', 'cancelado'];
        if (!in_array($data['estado'], $estadosValidos)) {
            Response::error('Estado inválido', 400);
        }

        $pedido = $this->db->queryOne('SELECT * FROM pedidos WHERE id = ?', [$data['pedido_id']]);
        if (!$pedido) {
            Response::error('Pedido no encontrado', 404);
        }

        // Validar transiciones
        $transiciones = [
            'pendiente' => ['confirmado', 'cancelado'],
            'confirmado' => ['en_preparacion', 'cancelado'],
            'en_preparacion' => ['enviado'],
            'enviado' => ['entregado'],
            'entregado' => [],
            'cancelado' => []
        ];

        if (!in_array($data['estado'], $transiciones[$pedido['estado']] ?? [])) {
            Response::error("Transición no válida de {$pedido['estado']} a {$data['estado']}", 409);
        }

        $this->db->execute(
            'UPDATE pedidos SET estado = ?, updated_at = NOW() WHERE id = ?',
            [$data['estado'], $data['pedido_id']]
        );

        // Si se cancela, liberar inventario
        if ($data['estado'] === 'cancelado') {
            $reservas = $this->db->query("SELECT * FROM reservas_inventario WHERE orden_id = ? AND estado = 'activa'", [$data['pedido_id']]);
            foreach ($reservas as $reserva) {
                $this->db->execute('UPDATE inventario SET cantidad_reservada = cantidad_reservada - ? WHERE producto_id = ?', [$reserva['cantidad'], $reserva['producto_id']]);
                $this->db->execute("UPDATE reservas_inventario SET estado = 'liberada' WHERE id = ?", [$reserva['id']]);
            }
        }

        $this->auditoria->registrar($this->session->getUsuarioId(), 'Admin', 'Cambio estado pedido', 'pedido', $data['pedido_id'], [
            'anterior' => $pedido['estado'],
            'nuevo' => $data['estado']
        ]);

        Response::success(null, 'Estado actualizado');
    }

    // Inventario admin
    public function inventarioAdmin(array $params = []): void
    {
        $this->checkAdmin();

        $inventario = $this->db->query(
            'SELECT i.*, p.nombre, p.sku, p.precio, p.activo as producto_activo, (i.cantidad - i.cantidad_reservada) as disponible 
             FROM inventario i JOIN productos p ON i.producto_id = p.id ORDER BY disponible ASC'
        );

        $alertas = [];
        foreach ($inventario as $item) {
            if ($item['umbral_alerta'] && $item['disponible'] <= $item['umbral_alerta']) {
                $alertas[] = $item;
            }
        }

        if (Request::isAjax()) {
            Response::success(['inventario' => $inventario, 'alertas' => $alertas]);
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="UTF-8"><title>Inventario — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/admin">← Admin</a></div></nav>
<div class="container py-4">
    <h3>Gestión de Inventario</h3>
    <?php if (!empty($alertas)): ?>
    <div class="alert alert-warning"><strong>⚠️ Alertas de stock bajo:</strong> <?= count($alertas) ?> productos</div>
    <?php endif; ?>
    <table class="table table-dark table-hover">
        <thead><tr><th>SKU</th><th>Producto</th><th>Precio</th><th>Stock Total</th><th>Reservado</th><th>Disponible</th><th>Umbral</th><th>Acción</th></tr></thead>
        <tbody>
            <?php foreach ($inventario as $inv): ?>
            <tr class="<?= $inv['umbral_alerta'] && $inv['disponible'] <= $inv['umbral_alerta'] ? 'table-warning' : '' ?>">
                <td><?= htmlspecialchars($inv['sku']) ?></td>
                <td><?= htmlspecialchars($inv['nombre']) ?></td>
                <td>$<?= number_format($inv['precio'], 0, ',', '.') ?></td>
                <td id="stock-<?= $inv['producto_id'] ?>"><?= $inv['cantidad'] ?></td>
                <td><?= $inv['cantidad_reservada'] ?></td>
                <td><strong><?= $inv['disponible'] ?></strong></td>
                <td><?= $inv['umbral_alerta'] ?? '-' ?></td>
                <td>
                    <button class="btn btn-sm btn-outline-light" onclick="ajustarStock('<?= $inv['producto_id'] ?>')">Ajustar</button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
async function ajustarStock(productoId) {
    const nuevaCantidad = prompt('Nueva cantidad:');
    if (nuevaCantidad === null) return;
    const res = await fetch('/api/admin/inventario/ajustar', {
        method: 'POST', headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({producto_id: productoId, cantidad: parseInt(nuevaCantidad)})
    });
    const data = await res.json();
    alert(data.message || 'OK');
    if (data.success) location.reload();
}
</script>
</body></html><?php
    }

    // Ajustar stock
    public function ajustarStock(array $params = []): void
    {
        $this->checkAdmin();
        
        $data = Request::validate([
            'producto_id' => 'required',
            'cantidad' => 'required|numeric'
        ]);

        $cantidad = (int) $data['cantidad'];
        if ($cantidad < 0) {
            Response::error('La cantidad no puede ser negativa');
        }

        $inventario = $this->db->queryOne('SELECT * FROM inventario WHERE producto_id = ?', [$data['producto_id']]);
        if (!$inventario) {
            Response::error('Producto no encontrado', 404);
        }

        $diferencia = $cantidad - $inventario['cantidad'];
        $this->db->execute('UPDATE inventario SET cantidad = ?, updated_at = NOW() WHERE producto_id = ?', [$cantidad, $data['producto_id']]);

        $this->db->execute(
            'INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, referencia, usuario_id) VALUES (?, ?, ?, ?, ?, ?)',
            [Auditoria::generateUuid(), $data['producto_id'], 'ajuste', abs($diferencia), 'Ajuste manual', $this->session->getUsuarioId()]
        );

        $this->auditoria->registrar($this->session->getUsuarioId(), 'Admin', 'Ajuste stock', 'producto', $data['producto_id'], [
            'anterior' => $inventario['cantidad'],
            'nuevo' => $cantidad
        ]);

        Response::success(null, 'Stock actualizado');
    }

    // Auditoría
    public function auditoria(array $params = []): void
    {
        $this->checkAdmin();

        $modulo = $_GET['modulo'] ?? null;
        $fechaInicio = $_GET['fechaInicio'] ?? date('Y-m-d', strtotime('-7 days'));
        $fechaFin = $_GET['fechaFin'] ?? date('Y-m-d');

        $sql = 'SELECT a.*, COALESCE(u.email, \'Sistema\') as usuario_email FROM auditoria a LEFT JOIN usuarios u ON a.usuario_id = u.id WHERE a.created_at BETWEEN ? AND ?';
        $bindings = [$fechaInicio . ' 00:00:00', $fechaFin . ' 23:59:59'];

        if ($modulo) {
            $sql .= ' AND a.modulo = ?';
            $bindings[] = $modulo;
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 100';
        $eventos = $this->db->query($sql, $bindings);

        if (Request::isAjax()) {
            Response::success($eventos);
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="UTF-8"><title>Auditoría — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/admin">← Admin</a></div></nav>
<div class="container py-4">
    <h3>Auditoría de Eventos</h3>
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-3"><input type="date" name="fechaInicio" class="form-control bg-dark text-light" value="<?= $fechaInicio ?>"></div>
        <div class="col-md-3"><input type="date" name="fechaFin" class="form-control bg-dark text-light" value="<?= $fechaFin ?>"></div>
        <div class="col-md-3">
            <select name="modulo" class="form-select bg-dark text-light">
                <option value="">Todos</option>
                <?php
                $modulos = $this->db->query('SELECT DISTINCT modulo FROM auditoria ORDER BY modulo');
                foreach ($modulos as $m): ?>
                <option value="<?= $m['modulo'] ?>" <?= $modulo === $m['modulo'] ? 'selected' : '' ?>><?= $m['modulo'] ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3"><button type="submit" class="btn btn-primary w-100">Filtrar</button></div>
    </form>
    <table class="table table-dark table-hover">
        <thead><tr><th>Fecha</th><th>Módulo</th><th>Acción</th><th>Usuario</th><th>Detalles</th></tr></thead>
        <tbody>
            <?php foreach ($eventos as $evt): ?>
            <tr>
                <td><?= date('d/m/Y H:i', strtotime($evt['created_at'])) ?></td>
                <td><span class="badge bg-secondary"><?= htmlspecialchars($evt['modulo']) ?></span></td>
                <td><?= htmlspecialchars($evt['accion']) ?></td>
                <td><?= htmlspecialchars($evt['usuario_email']) ?></td>
                <td><small class="text-muted"><?= htmlspecialchars($evt['detalles'] ?? '-') ?></small></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body></html><?php
    }

    // Configuración
    public function configuracion(array $params = []): void
    {
        $this->checkAdmin();

        $config = [];
        $rows = $this->db->query('SELECT * FROM configuracion ORDER BY clave');
        foreach ($rows as $row) {
            $config[$row['clave']] = $row['valor'];
        }

        if (Request::method() === 'PUT' || Request::input('_method') === 'PUT') {
            if (!$this->session->isAdmin()) {
                Response::error('Solo administradores pueden modificar configuración', 403);
            }
            $body = Request::body();
            foreach ($body as $clave => $valor) {
                $this->db->execute(
                    'UPDATE configuracion SET valor = ?, updated_at = NOW() WHERE clave = ?',
                    [(string)$valor, $clave]
                );
            }
            $this->auditoria->registrar($this->session->getUsuarioId(), 'Admin', 'Configuración actualizada');
            Response::success(null, 'Configuración actualizada');
        }

        if (Request::isAjax()) {
            Response::success($config);
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head><meta charset="UTF-8"><title>Configuración — Admin</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"></head>
<body>
<nav class="navbar navbar-dark bg-dark"><div class="container"><a class="navbar-brand" href="/admin">← Admin</a></div></nav>
<div class="container py-4">
    <h3>Configuración del Sistema</h3>
    <div class="card bg-dark border-secondary">
        <div class="card-body">
            <form id="config-form">
                <?php foreach ($config as $key => $val): ?>
                <div class="mb-3">
                    <label class="form-label text-capitalize"><?= str_replace('_', ' ', $key) ?></label>
                    <input type="text" name="<?= $key ?>" class="form-control bg-dark text-light" value="<?= htmlspecialchars($val) ?>">
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-success">Guardar Cambios</button>
                <div id="msg" class="mt-2"></div>
            </form>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.getElementById('config-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const data = {};
    fd.forEach((v, k) => data[k] = v);
    const res = await fetch('/api/admin/configuracion', {
        method: 'POST',
        headers: {'Content-Type': 'application/json', 'X-HTTP-Method-Override': 'PUT'},
        body: JSON.stringify(data)
    });
    const result = await res.json();
    document.getElementById('msg').innerHTML = result.success 
        ? '<div class="alert alert-success">Configuración guardada</div>'
        : '<div class="alert alert-danger">Error al guardar</div>';
});
</script>
</body></html><?php
    }

    // Estado de servicios
    public function estadoServicios(array $params = []): void
    {
        $this->checkAdmin();

        Response::success([
            'catalogo' => 'UP',
            'carrito' => 'UP',
            'inventario' => 'UP',
            'checkout' => 'UP',
            'pagos' => 'UP',
            'autenticacion' => 'UP',
            'base_datos' => 'UP'
        ]);
    }
}
