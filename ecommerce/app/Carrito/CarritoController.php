<?php
declare(strict_types=1);

namespace App\Carrito;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;

class CarritoController
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

    public function ver(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
            return;
        }

        $usuarioId = $this->session->getUsuarioId();
        $carrito = $this->getOrCreateCarrito($usuarioId);
        $items = $this->getItemsCarrito($carrito['id']);

        $totales = $this->calcularTotales($items);

        if (Request::isAjax() || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
            Response::success([
                'carrito' => $carrito,
                'items' => $items,
                'totales' => $totales
            ]);
        }

        $this->renderCarrito($carrito, $items, $totales);
    }

    public function agregar(array $params = []): void
    {
        $data = Request::validate([
            'producto_id' => 'required',
            'cantidad' => 'required|numeric'
        ]);

        $productoId = $data['producto_id'];
        $cantidad = (int) $data['cantidad'];

        if ($cantidad < 1) {
            Response::error('La cantidad debe ser mayor que 0');
        }

        // Validate stock
        $stock = $this->db->queryOne(
            'SELECT (cantidad - cantidad_reservada) as disponible FROM inventario WHERE producto_id = ?',
            [$productoId]
        );

        if (!$stock) {
            Response::error('Producto no encontrado', 404, 'PRODUCT_NOT_FOUND');
        }

        // RN-B-001: No se puede agregar más que el stock disponible
        if ($stock['disponible'] <= 0) {
            Response::error('Producto sin stock disponible', 409, 'INSUFFICIENT_STOCK');
        }

        // Get product
        $producto = $this->db->queryOne(
            'SELECT id, nombre, precio, precio_descuento FROM productos WHERE id = ? AND activo = 1',
            [$productoId]
        );
        if (!$producto) {
            Response::error('Producto no encontrado', 404, 'PRODUCT_NOT_FOUND');
        }

        $precio = $producto['precio_descuento'] ?? $producto['precio'];

        // Get or create cart for authenticated user (or use guest cart)
        if ($this->session->isAuthenticated()) {
            $usuarioId = $this->session->getUsuarioId();
            $carrito = $this->getOrCreateCarrito($usuarioId);
            $carritoId = $carrito['id'];

            // RN-B: If same product exists, increment quantity
            $existing = $this->db->queryOne(
                'SELECT * FROM items_carrito WHERE carrito_id = ? AND producto_id = ?',
                [$carritoId, $productoId]
            );

            if ($existing) {
                $nuevaCantidad = $existing['cantidad'] + $cantidad;
                if ($nuevaCantidad > $stock['disponible']) {
                    Response::error('Cantidad solicitada supera el stock disponible', 409, 'INSUFFICIENT_STOCK');
                }
                $this->db->execute(
                    'UPDATE items_carrito SET cantidad = ?, precio_unitario = ?, updated_at = NOW() WHERE id = ?',
                    [$nuevaCantidad, $precio, $existing['id']]
                );
            } else {
                // RN-B-005: Max 50 items
                $count = $this->db->queryOne(
                    'SELECT COUNT(*) as cnt FROM items_carrito WHERE carrito_id = ?', [$carritoId]
                );
                if ($count['cnt'] >= 50) {
                    Response::error('Carrito lleno (máximo 50 productos diferentes)', 409);
                }

                $itemId = Auditoria::generateUuid();
                $this->db->execute(
                    'INSERT INTO items_carrito (id, carrito_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)',
                    [$itemId, $carritoId, $productoId, $cantidad, $precio]
                );
            }

            // Update cart timestamp
            $this->db->execute('UPDATE carritos SET updated_at = NOW() WHERE id = ?', [$carritoId]);

            $this->auditoria->registrar($usuarioId, 'Carrito', 'Agregar producto', 'producto', $productoId, [
                'cantidad' => $cantidad,
                'precio' => $precio
            ]);

            Response::success(['cantidad' => $cantidad], 'Producto agregado al carrito');
        } else {
            // Guest: return success for localStorage-based cart
            Response::success(['cantidad' => $cantidad, 'producto' => $producto], 'Producto agregado (modo invitado)');
        }
    }

    public function actualizar(array $params = []): void
    {
        $data = Request::validate([
            'item_id' => 'required',
            'cantidad' => 'required|numeric'
        ]);

        $itemId = $data['item_id'];
        $cantidad = (int) $data['cantidad'];

        if ($cantidad < 1) {
            $this->eliminar(['item_id' => $itemId]);
            return;
        }

        // RN-B-001: Validate stock
        $item = $this->db->queryOne(
            'SELECT ic.*, (i.cantidad - i.cantidad_reservada) as stock_disponible 
             FROM items_carrito ic 
             JOIN inventario i ON i.producto_id = ic.producto_id 
             WHERE ic.id = ?',
            [$itemId]
        );

        if (!$item) {
            Response::error('Item no encontrado', 404);
        }

        if ($cantidad > $item['stock_disponible']) {
            Response::error('Cantidad solicitada supera el stock disponible', 409, 'INSUFFICIENT_STOCK');
        }

        $this->db->execute(
            'UPDATE items_carrito SET cantidad = ?, updated_at = NOW() WHERE id = ?',
            [$cantidad, $itemId]
        );

        $items = $this->db->query(
            'SELECT ic.*, p.nombre, p.sku FROM items_carrito ic JOIN productos p ON ic.producto_id = p.id WHERE ic.carrito_id = ?',
            [$item['carrito_id']]
        );
        $totales = $this->calcularTotales($items);

        Response::success(['items' => $items, 'totales' => $totales], 'Cantidad actualizada');
    }

    public function eliminar(array $params = []): void
    {
        $itemId = Request::input('item_id') ?? $params['item_id'] ?? null;

        if (!$itemId) {
            Response::error('Se requiere item_id', 400);
        }

        $item = $this->db->queryOne('SELECT * FROM items_carrito WHERE id = ?', [$itemId]);
        if (!$item) {
            Response::error('Item no encontrado', 404);
        }

        $this->db->execute('DELETE FROM items_carrito WHERE id = ?', [$itemId]);

        // Recalculate
        $items = $this->db->query(
            'SELECT ic.*, p.nombre, p.sku FROM items_carrito ic JOIN productos p ON ic.producto_id = p.id WHERE ic.carrito_id = ?',
            [$item['carrito_id']]
        );
        $totales = $this->calcularTotales($items);

        Response::success(['items' => $items, 'totales' => $totales], 'Producto eliminado');
    }

    public function vaciar(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
        }

        $usuarioId = $this->session->getUsuarioId();
        $carrito = $this->getOrCreateCarrito($usuarioId);

        $this->db->execute('DELETE FROM items_carrito WHERE carrito_id = ?', [$carrito['id']]);
        $this->db->execute('UPDATE carritos SET updated_at = NOW() WHERE id = ?', [$carrito['id']]);

        $this->auditoria->registrar($usuarioId, 'Carrito', 'Vaciar carrito', 'carrito', $carrito['id']);

        Response::success(null, 'Carrito vaciado');
    }

    public function fusionar(array $params = []): void
    {
        // Fusionar carrito de localStorage (invitado) con carrito del servidor al iniciar sesión
        $data = Request::body();
        $itemsLocales = $data['items'] ?? [];

        if (!$this->session->isAuthenticated() || empty($itemsLocales)) {
            Response::success(['fusionados' => 0]);
            return;
        }

        $usuarioId = $this->session->getUsuarioId();
        $carrito = $this->getOrCreateCarrito($usuarioId);
        $fusionados = 0;

        foreach ($itemsLocales as $item) {
            $productoId = $item['producto_id'];
            $cantidad = (int) ($item['cantidad'] ?? 1);

            $stock = $this->db->queryOne(
                'SELECT (cantidad - cantidad_reservada) as disponible FROM inventario WHERE producto_id = ?',
                [$productoId]
            );
            if (!$stock || $stock['disponible'] <= 0) continue;

            $producto = $this->db->queryOne(
                'SELECT precio, precio_descuento FROM productos WHERE id = ? AND activo = 1', [$productoId]
            );
            if (!$producto) continue;

            $precio = $producto['precio_descuento'] ?? $producto['precio'];
            $cantidad = min($cantidad, $stock['disponible']);

            $existing = $this->db->queryOne(
                'SELECT * FROM items_carrito WHERE carrito_id = ? AND producto_id = ?',
                [$carrito['id'], $productoId]
            );

            if ($existing) {
                $nuevaCantidad = min($existing['cantidad'] + $cantidad, $stock['disponible']);
                $this->db->execute(
                    'UPDATE items_carrito SET cantidad = ?, precio_unitario = ?, updated_at = NOW() WHERE id = ?',
                    [$nuevaCantidad, $precio, $existing['id']]
                );
            } else {
                $itemId = Auditoria::generateUuid();
                $this->db->execute(
                    'INSERT INTO items_carrito (id, carrito_id, producto_id, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)',
                    [$itemId, $carrito['id'], $productoId, $cantidad, $precio]
                );
            }
            $fusionados++;
        }

        Response::success(['fusionados' => $fusionados], 'Carrito fusionado');
    }

    private function getOrCreateCarrito(string $usuarioId): array
    {
        $carrito = $this->db->queryOne(
            'SELECT * FROM carritos WHERE usuario_id = ? AND estado = ?',
            [$usuarioId, 'activo']
        );

        if (!$carrito) {
            $id = Auditoria::generateUuid();
            $this->db->execute(
                'INSERT INTO carritos (id, usuario_id) VALUES (?, ?)',
                [$id, $usuarioId]
            );
            $carrito = $this->db->queryOne('SELECT * FROM carritos WHERE id = ?', [$id]);
        }

        return $carrito;
    }

    private function getItemsCarrito(string $carritoId): array
    {
        return $this->db->query(
            'SELECT ic.*, p.nombre, p.sku, p.slug,
                    (i.cantidad - i.cantidad_reservada) as stock_disponible,
                    (SELECT url FROM imagenes WHERE producto_id = p.id AND es_principal = 1 LIMIT 1) as imagen
             FROM items_carrito ic
             JOIN productos p ON ic.producto_id = p.id
             JOIN inventario i ON i.producto_id = p.id
             WHERE ic.carrito_id = ?
             ORDER BY ic.created_at DESC',
            [$carritoId]
        );
    }

    private function calcularTotales(array $items): array
    {
        $subtotal = 0;
        $totalItems = 0;

        foreach ($items as $item) {
            $subtotal += $item['precio_unitario'] * $item['cantidad'];
            $totalItems += $item['cantidad'];
        }

        $impuesto = round($subtotal * 0.19, 2); // IVA 19%
        $envio = $subtotal > 0 ? 4990 : 0;
        $total = $subtotal + $impuesto + $envio;

        return [
            'subtotal' => round($subtotal, 2),
            'impuestos' => $impuesto,
            'envio' => $envio,
            'total' => round($total, 2),
            'total_items' => $totalItems
        ];
    }

    private function renderCarrito(array $carrito, array $items, array $totales): void
    {
        $isAuthenticated = $this->session->isAuthenticated();
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Carrito — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark sticky-top">
        <div class="container">
            <a class="navbar-brand fw-bold" href="/">🛒 Ecommerce</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item"><a class="nav-link" href="/catalogo">← Catálogo</a></li>
                </ul>
                <ul class="navbar-nav">
                    <?php if ($isAuthenticated): ?>
                        <li class="nav-item"><a class="nav-link" href="/logout">Cerrar Sesión</a></li>
                    <?php else: ?>
                        <li class="nav-item"><a class="nav-link" href="/login">Iniciar Sesión</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <h2 class="mb-4"><i class="bi bi-cart"></i> Carrito de Compras</h2>

        <?php if (empty($items)): ?>
        <div class="text-center py-5">
            <i class="bi bi-cart-x text-muted" style="font-size: 5rem;"></i>
            <h4 class="mt-3">Tu carrito está vacío</h4>
            <a href="/catalogo" class="btn btn-primary mt-2">Explorar Catálogo</a>
        </div>
        <?php else: ?>
        <div class="row">
            <div class="col-lg-8">
                <?php foreach ($items as $item): ?>
                <div class="card bg-dark border-secondary mb-3 cart-item" data-id="<?= $item['id'] ?>">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-md-2">
                                <?php if ($item['imagen']): ?>
                                <img src="<?= htmlspecialchars($item['imagen']) ?>" class="img-fluid rounded" style="max-height: 80px;">
                                <?php else: ?>
                                <div class="bg-secondary rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                    <i class="bi bi-image"></i>
                                </div>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-4">
                                <h6><a href="/producto/<?= htmlspecialchars($item['slug']) ?>" class="text-light"><?= htmlspecialchars($item['nombre']) ?></a></h6>
                                <small class="text-muted">SKU: <?= htmlspecialchars($item['sku']) ?></small>
                                <?php if ($item['stock_disponible'] <= 0): ?>
                                    <span class="badge bg-danger d-block mt-1">Sin stock</span>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-2">
                                <small class="text-muted">Precio</small><br>
                                $<?= number_format($item['precio_unitario'], 0, ',', '.') ?>
                            </div>
                            <div class="col-md-2">
                                <div class="input-group input-group-sm">
                                    <button class="btn btn-outline-secondary qty-btn" onclick="updateQty('<?= $item['id'] ?>', -1)">-</button>
                                    <input type="number" class="form-control bg-dark text-light text-center qty-input" 
                                           value="<?= $item['cantidad'] ?>" min="1" max="<?= $item['stock_disponible'] ?>"
                                           onchange="updateQtyDirect('<?= $item['id'] ?>', this.value)">
                                    <button class="btn btn-outline-secondary qty-btn" onclick="updateQty('<?= $item['id'] ?>', 1)">+</button>
                                </div>
                            </div>
                            <div class="col-md-1">
                                <strong>$<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?></strong>
                            </div>
                            <div class="col-md-1">
                                <button class="btn btn-danger btn-sm" onclick="removeItem('<?= $item['id'] ?>')" title="Eliminar">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="col-lg-4">
                <div class="card bg-dark border-secondary sticky-top" style="top: 80px;">
                    <div class="card-header"><h5 class="mb-0">Resumen</h5></div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Subtotal</span>
                            <span>$<?= number_format($totales['subtotal'], 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>IVA (19%)</span>
                            <span>$<?= number_format($totales['impuestos'], 0, ',', '.') ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Envío</span>
                            <span>$<?= number_format($totales['envio'], 0, ',', '.') ?></span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span>
                            <span>$<?= number_format($totales['total'], 0, ',', '.') ?></span>
                        </div>
                        <div class="text-muted small mt-1"><?= $totales['total_items'] ?> productos</div>
                        <?php if ($isAuthenticated): ?>
                        <a href="/checkout" class="btn btn-success w-100 mt-3 btn-lg">
                            <i class="bi bi-credit-card"></i> Proceder al Pago
                        </a>
                        <?php else: ?>
                        <a href="/login?redirect=checkout" class="btn btn-primary w-100 mt-3">
                            <i class="bi bi-box-arrow-in-right"></i> Iniciar Sesión para Comprar
                        </a>
                        <?php endif; ?>
                        <button class="btn btn-outline-danger w-100 mt-2" onclick="vaciarCarrito()">
                            <i class="bi bi-trash"></i> Vaciar Carrito
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function fetchAPI(url, method = 'GET', body = null) {
        const opts = {method, headers: {'Content-Type': 'application/json'}};
        if (body) opts.body = JSON.stringify(body);
        return await (await fetch(url, opts)).json();
    }

    async function updateQty(itemId, delta) {
        const input = document.querySelector(`.cart-item[data-id="${itemId}"] .qty-input`);
        let val = parseInt(input.value) + delta;
        if (val < 1) val = 1;
        await updateQtyDirect(itemId, val);
    }

    async function updateQtyDirect(itemId, val) {
        const data = await fetchAPI('/api/carrito/actualizar', 'POST', {item_id: itemId, cantidad: parseInt(val)});
        if (data.success) location.reload();
        else alert(data.message || 'Error al actualizar');
    }

    async function removeItem(itemId) {
        if (!confirm('¿Eliminar este producto?')) return;
        await fetchAPI('/api/carrito/eliminar', 'POST', {item_id: itemId});
        location.reload();
    }

    async function vaciarCarrito() {
        if (!confirm('¿Vaciar todo el carrito?')) return;
        await fetchAPI('/api/carrito/vaciar', 'POST');
        location.reload();
    }
    </script>
</body>
</html><?php
    }
}
