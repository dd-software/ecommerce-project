<?php
declare(strict_types=1);

use App\Core\Router;
use App\Core\Request;
use App\Core\Response;

use App\Catalogo\CatalogoController;
use App\Carrito\CarritoController;
use App\Autenticacion\AuthController;
use App\Checkout\CheckoutController;
use App\PasarelaPago\PagoController;
use App\Inventario\InventarioController;
use App\Administracion\AdminController;
use App\Integracion\IntegracionController;

$router = new Router();

// ============================================================
// MÓDULO A: CATÁLOGO
// ============================================================
$router->get('/api/catalogo', [CatalogoController::class, 'index']);
$router->get('/catalogo', [CatalogoController::class, 'index']);
$router->get('/', [CatalogoController::class, 'index']);
$router->get('/producto/{slug}', [CatalogoController::class, 'detalle']);
$router->get('/api/producto/{slug}', [CatalogoController::class, 'detalle']);

// ============================================================
// MÓDULO C: AUTENTICACIÓN
// ============================================================
$router->get('/api/autenticacion', [AuthController::class, 'status']);
$router->get('/login', [AuthController::class, 'loginForm']);
$router->get('/registro', [AuthController::class, 'registroForm']);
$router->get('/logout', [AuthController::class, 'logout']);
$router->post('/api/auth/login', [AuthController::class, 'login']);
$router->post('/api/auth/registro', [AuthController::class, 'registro']);
$router->get('/api/auth/status', [AuthController::class, 'status']);

// ============================================================
// MÓDULO B: CARRITO
// ============================================================
$router->get('/carrito', [CarritoController::class, 'ver'], true);
$router->get('/api/carrito', [CarritoController::class, 'ver'], true);
$router->post('/api/carrito/agregar', [CarritoController::class, 'agregar']);
$router->post('/api/carrito/actualizar', [CarritoController::class, 'actualizar'], true);
$router->post('/api/carrito/eliminar', [CarritoController::class, 'eliminar'], true);
$router->post('/api/carrito/vaciar', [CarritoController::class, 'vaciar'], true);
$router->post('/api/carrito/fusionar', [CarritoController::class, 'fusionar'], true);

// ============================================================
// MÓDULO D: CHECKOUT
// ============================================================
$router->get('/api/checkout', [CheckoutController::class, 'estado']);
$router->get('/checkout', [CheckoutController::class, 'index'], true);
$router->post('/api/checkout/procesar', [CheckoutController::class, 'procesar'], true);

// ============================================================
// MÓDULO E: PASARELA DE PAGO
// ============================================================
$router->get('/api/pasarela-de-pago', [PagoController::class, 'index'], true);
$router->get('/api/v1/pasarela-de-pago', [PagoController::class, 'index'], true);
$router->get('/api/pagos/estado', [PagoController::class, 'index'], true);
$router->post('/api/pagos/procesar', [PagoController::class, 'procesar'], true);
$router->get('/api/pagos/confirmar/{ordenId}', [PagoController::class, 'confirmar'], true);
$router->post('/api/pagos/reembolsar', [PagoController::class, 'reembolsar'], true);
$router->get('/pago/{ordenId}', [PagoController::class, 'paginaPago'], true);

// ============================================================
// MÓDULO F: INVENTARIO
// ============================================================
$router->get('/api/inventario', [InventarioController::class, 'status']);
$router->get('/api/inventario/{productoId}', [InventarioController::class, 'consultar']);
$router->post('/api/inventario/validar', [InventarioController::class, 'validar']);
$router->post('/api/inventario/reservar', [InventarioController::class, 'reservar'], true);
$router->post('/api/inventario/liberar', [InventarioController::class, 'liberar'], true);
$router->post('/api/inventario/confirmar', [InventarioController::class, 'confirmar'], true);
$router->get('/api/inventario/listar', [InventarioController::class, 'listar'], true);

// ============================================================
// MÓDULO G: ADMINISTRACIÓN
// ============================================================
$router->get('/api/administracion', [AdminController::class, 'status']);
$router->get('/admin', [AdminController::class, 'dashboard']);
$router->get('/admin/usuarios', [AdminController::class, 'usuarios']);
$router->get('/admin/pedidos', [AdminController::class, 'pedidos']);
$router->get('/admin/inventario', [AdminController::class, 'inventarioAdmin']);
$router->get('/admin/auditoria', [AdminController::class, 'auditoria']);
$router->get('/admin/configuracion', [AdminController::class, 'configuracion']);

// Admin API
$router->get('/api/administracion/usuarios', [AdminController::class, 'usuarios']);
$router->get('/api/administracion/usuarios/{id}', [AdminController::class, 'usuarios']);
$router->get('/api/administracion/roles', [AdminController::class, 'usuarios']);
$router->get('/api/administracion/configuracion', [AdminController::class, 'configuracion']);
$router->post('/api/admin/configuracion', [AdminController::class, 'configuracion']);
$router->get('/api/administracion/estado', [AdminController::class, 'estadoServicios']);
$router->get('/api/administracion/auditoria', [AdminController::class, 'auditoria']);
$router->post('/api/admin/usuarios/toggle', [AdminController::class, 'toggleUsuario']);
$router->post('/api/admin/pedidos/estado', [AdminController::class, 'cambiarEstado']);
$router->post('/api/admin/inventario/ajustar', [AdminController::class, 'ajustarStock']);

// ============================================================
// MÓDULO H: INTEGRACIÓN
// ============================================================
$router->get('/api/integracion', [IntegracionController::class, 'status'], true);
$router->get('/api/v1/integracion', [IntegracionController::class, 'status'], true);
$router->get('/api/health', [IntegracionController::class, 'healthCheck']);
$router->post('/api/integracion/orquestar', [IntegracionController::class, 'orquestarCompra'], true);
$router->get('/api/integracion/logs', [IntegracionController::class, 'logs'], true);
$router->post('/api/integracion/eventos', [IntegracionController::class, 'publicarEvento'], true);
$router->post('/api/integracion/sincronizar', [IntegracionController::class, 'sincronizar'], true);

// ============================================================
// MIS PEDIDOS (USER)
// ============================================================
$router->get('/mis-pedidos', function() {
    $session = \App\Core\Session::getInstance();
    if (!$session->isAuthenticated()) {
        header('Location: /login?redirect=mis-pedidos');
        exit;
    }

    $db = \App\Core\Database::getInstance();
    $pedidos = $db->query(
        'SELECT p.*, 
                (SELECT estado FROM pagos WHERE pedido_id = p.id LIMIT 1) as pago_estado 
         FROM pedidos p WHERE p.usuario_id = ? ORDER BY p.created_at DESC LIMIT 20',
        [$session->getUsuarioId()]
    );

    $isAuth = true;
    ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mis Pedidos — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">🛒 Ecommerce</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/catalogo">Catálogo</a></li>
                    <li class="nav-item"><a class="nav-link" href="/carrito">Carrito</a></li>
                </ul>
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="/logout">Cerrar Sesión</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h3><i class="bi bi-box"></i> Mis Pedidos</h3>
        
        <?php if (empty($pedidos)): ?>
        <div class="text-center py-5">
            <i class="bi bi-inbox text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3">No tienes pedidos aún</h4>
            <a href="/catalogo" class="btn btn-primary mt-2">Explorar Catálogo</a>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-dark table-hover">
                <thead>
                    <tr>
                        <th>N° Orden</th>
                        <th>Fecha</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Pago</th>
                        <th>Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $pedido): ?>
                    <tr>
                        <td><?= htmlspecialchars($pedido['numero']) ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($pedido['created_at'])) ?></td>
                        <td><strong>$<?= number_format($pedido['total'], 0, ',', '.') ?></strong></td>
                        <td>
                            <span class="badge bg-<?= 
                                $pedido['estado'] === 'entregado' ? 'success' : 
                                ($pedido['estado'] === 'cancelado' ? 'danger' : 
                                ($pedido['estado'] === 'pendiente' ? 'warning' : 'info')) 
                            ?>"><?= $pedido['estado'] ?></span>
                        </td>
                        <td>
                            <?php if ($pedido['pago_estado']): ?>
                            <span class="badge bg-<?= $pedido['pago_estado'] === 'aprobado' ? 'success' : ($pedido['pago_estado'] === 'rechazado' ? 'danger' : 'secondary') ?>">
                                <?= $pedido['pago_estado'] ?>
                            </span>
                            <?php else: ?>
                            <span class="badge bg-secondary">sin pago</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($pedido['estado'] === 'pendiente' && !$pedido['pago_estado']): ?>
                            <a href="/pago/<?= $pedido['id'] ?>" class="btn btn-sm btn-primary">Pagar</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html><?php
});

// ============================================================
// DISPATCH
// ============================================================
$router->dispatch(Request::method(), Request::uri());
