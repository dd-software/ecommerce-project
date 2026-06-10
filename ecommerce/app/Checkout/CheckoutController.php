<?php
declare(strict_types=1);

namespace App\Checkout;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;

class CheckoutController
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

    public function index(array $params = []): void
    {
        // RN-CHK-001: Solo usuarios autenticados
        if (!$this->session->isAuthenticated()) {
            header('Location: /login?redirect=checkout');
            exit;
        }

        $usuarioId = $this->session->getUsuarioId();

        // RN-CHK-001: Recuperar carrito
        $carrito = $this->db->queryOne(
            'SELECT * FROM carritos WHERE usuario_id = ? AND estado = ?',
            [$usuarioId, 'activo']
        );

        if (!$carrito) {
            header('Location: /carrito');
            exit;
        }

        $items = $this->db->query(
            'SELECT ic.*, p.nombre, p.sku, p.slug, 
                    (i.cantidad - i.cantidad_reservada) as stock_disponible 
             FROM items_carrito ic 
             JOIN productos p ON ic.producto_id = p.id 
             JOIN inventario i ON i.producto_id = p.id 
             WHERE ic.carrito_id = ?',
            [$carrito['id']]
        );

        // RN-CHK-002: Carrito debe tener al menos un producto
        if (empty($items)) {
            header('Location: /carrito');
            exit;
        }

        // RN-CHK-002: Validar inventario
        $sinStock = [];
        foreach ($items as $item) {
            if ($item['stock_disponible'] < $item['cantidad']) {
                $sinStock[] = $item['nombre'];
            }
        }

        if (!empty($sinStock)) {
            $this->renderSinStock($sinStock);
            return;
        }

        // Cargar direcciones del usuario
        $direcciones = $this->db->query(
            'SELECT * FROM direcciones WHERE usuario_id = ? AND archivada = 0 ORDER BY es_predeterminada DESC',
            [$usuarioId]
        );

        // Calcular totales
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio_unitario'] * $item['cantidad'];
        }
        $impuestos = round($subtotal * 0.19, 2);
        $envio = $subtotal > 0 ? 4990 : 0;
        $total = $subtotal + $impuestos + $envio;

        $this->renderCheckout($carrito, $items, $direcciones, [
            'subtotal' => $subtotal,
            'impuestos' => $impuestos,
            'envio' => $envio,
            'total' => $total
        ]);
    }

    public function procesar(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
        }

        $usuarioId = $this->session->getUsuarioId();

        $data = Request::validate([
            'direccion_id' => 'required',
            'notas' => 'max:500'
        ]);

        // Get cart
        $carrito = $this->db->queryOne(
            'SELECT * FROM carritos WHERE usuario_id = ? AND estado = ?',
            [$usuarioId, 'activo']
        );

        if (!$carrito) {
            Response::error('Carrito no encontrado', 404);
        }

        $items = $this->db->query(
            'SELECT ic.*, p.nombre, p.sku, (i.cantidad - i.cantidad_reservada) as stock_disponible 
             FROM items_carrito ic 
             JOIN productos p ON ic.producto_id = p.id 
             JOIN inventario i ON i.producto_id = p.id 
             WHERE ic.carrito_id = ?',
            [$carrito['id']]
        );

        // RN-CHK-003: Todos deben tener stock
        foreach ($items as $item) {
            if ($item['stock_disponible'] < $item['cantidad']) {
                Response::error("Producto sin stock suficiente: {$item['nombre']}", 409);
            }
        }

        // RN-CHK-004: Validar dirección
        $direccion = $this->db->queryOne(
            'SELECT * FROM direcciones WHERE id = ? AND usuario_id = ?',
            [$data['direccion_id'], $usuarioId]
        );

        if (!$direccion) {
            Response::error('Dirección no encontrada', 404);
        }

        // RN-CHK-004: Calcular totales en servidor
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['precio_unitario'] * $item['cantidad'];
        }
        $impuestos = round($subtotal * 0.19, 2);
        $envio = 4990;
        $total = $subtotal + $impuestos + $envio;

        // Generar número de orden
        $year = date('Y');
        $countResult = $this->db->queryOne('SELECT COUNT(*) + 1 as cnt FROM pedidos WHERE YEAR(created_at) = ?', [$year]);
        $secuencia = str_pad((string) ($countResult['cnt'] ?? 1), 5, '0', STR_PAD_LEFT);
        $numeroOrden = "ORD-{$year}-{$secuencia}";

        // RN-CHK-005: Generar orden antes del pago
        $this->db->beginTransaction();

        try {
            $ordenId = Auditoria::generateUuid();
            $direccionJson = json_encode([
                'alias' => $direccion['alias'],
                'calle' => $direccion['calle'],
                'ciudad' => $direccion['ciudad'],
                'estado' => $direccion['estado'],
                'codigo_postal' => $direccion['codigo_postal'],
                'pais' => $direccion['pais'],
                'referencia' => $direccion['referencia']
            ]);

            $this->db->execute(
                'INSERT INTO pedidos (id, numero, usuario_id, estado, subtotal, descuento_total, costo_envio, total, direccion_envio, notas) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)',
                [$ordenId, $numeroOrden, $usuarioId, 'pendiente', $subtotal, 0, $envio, $total, 
                 $direccionJson, $data['notas'] ?? null]
            );

            // Insertar detalles
            foreach ($items as $item) {
                $detalleId = Auditoria::generateUuid();
                $lineaSubtotal = ($item['precio_unitario']) * $item['cantidad'];
                $this->db->execute(
                    'INSERT INTO detalles_pedido (id, pedido_id, producto_id, nombre_producto, sku_producto, cantidad, precio_unitario, descuento_unitario, subtotal) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)',
                    [$detalleId, $ordenId, $item['producto_id'], $item['nombre'], $item['sku'], 
                     $item['cantidad'], $item['precio_unitario'], 0, $lineaSubtotal]
                );
            }

            // Reservar inventario
            foreach ($items as $item) {
                $reservaId = Auditoria::generateUuid();
                $this->db->execute(
                    'UPDATE inventario SET cantidad_reservada = cantidad_reservada + ? WHERE producto_id = ?',
                    [$item['cantidad'], $item['producto_id']]
                );
                
                $this->db->execute(
                    'INSERT INTO reservas_inventario (id, orden_id, producto_id, cantidad, estado, fecha_expiracion) 
                     VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))',
                    [$reservaId, $ordenId, $item['producto_id'], $item['cantidad'], 'activa']
                );

                $this->db->execute(
                    'INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, referencia, usuario_id) 
                     VALUES (?, ?, ?, ?, ?, ?)',
                    [Auditoria::generateUuid(), $item['producto_id'], 'reserva', $item['cantidad'], $numeroOrden, $usuarioId]
                );
            }

            // Marcar carrito como pendiente
            $this->db->execute(
                'UPDATE carritos SET estado = ?, updated_at = NOW() WHERE id = ?',
                ['pendiente', $carrito['id']]
            );

            $this->auditoria->registrar($usuarioId, 'Checkout', 'Orden generada', 'pedido', $ordenId, [
                'numero' => $numeroOrden,
                'total' => $total
            ]);

            $this->db->commit();

            Response::success([
                'orden_id' => $ordenId,
                'numero' => $numeroOrden,
                'total' => $total,
                'estado' => 'pendiente'
            ], 'Orden generada correctamente', 201);

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Checkout error: " . $e->getMessage());
            Response::error('Error al generar la orden. Intente nuevamente.', 500);
        }
    }

    public function estado(array $params = []): void
    {
        Response::success(['disponible' => true]);
    }

    private function renderCheckout(array $carrito, array $items, array $direcciones, array $totales): void
    {
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout — Ecommerce</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="/">🛒 Ecommerce</a>
            <a href="/carrito" class="text-light">← Volver al Carrito</a>
        </div>
    </nav>

    <div class="container py-4">
        <h2 class="mb-4">Finalizar Compra</h2>
        
        <div class="row">
            <div class="col-lg-8">
                <!-- Dirección -->
                <div class="card bg-dark border-secondary mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-geo-alt"></i> Dirección de Envío</h5></div>
                    <div class="card-body">
                        <?php if (empty($direcciones)): ?>
                            <p class="text-muted">No tienes direcciones guardadas. Agrega una.</p>
                            <div class="mb-3">
                                <label class="form-label">Dirección completa</label>
                                <input type="text" id="calle" class="form-control bg-dark text-light mb-2" placeholder="Calle y número">
                                <input type="text" id="ciudad" class="form-control bg-dark text-light mb-2" placeholder="Ciudad">
                                <input type="text" id="estado" class="form-control bg-dark text-light mb-2" placeholder="Estado/Provincia">
                                <input type="text" id="codigo_postal" class="form-control bg-dark text-light mb-2" placeholder="Código Postal">
                                <input type="text" id="pais" class="form-control bg-dark text-light mb-2" value="CL" placeholder="País (CL)">
                            </div>
                        <?php else: ?>
                            <?php foreach ($direcciones as $dir): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="radio" name="direccion_id" value="<?= $dir['id'] ?>" 
                                       <?= $dir['es_predeterminada'] ? 'checked' : '' ?>>
                                <label class="form-check-label">
                                    <strong><?= htmlspecialchars($dir['alias'] ?? 'Dirección') ?></strong><br>
                                    <small class="text-muted">
                                        <?= htmlspecialchars($dir['calle']) ?>, <?= htmlspecialchars($dir['ciudad']) ?>, <?= htmlspecialchars($dir['estado']) ?>, CP <?= htmlspecialchars($dir['codigo_postal']) ?>, <?= htmlspecialchars($dir['pais']) ?>
                                    </small>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        <div class="mb-3">
                            <label class="form-label">Notas adicionales (opcional)</label>
                            <textarea id="notas" class="form-control bg-dark text-light" rows="2" maxlength="500" placeholder="Instrucciones de entrega..."></textarea>
                        </div>
                    </div>
                </div>

                <!-- Productos -->
                <div class="card bg-dark border-secondary mb-3">
                    <div class="card-header"><h5 class="mb-0"><i class="bi bi-box"></i> Productos (<?= count($items) ?>)</h5></div>
                    <div class="card-body">
                        <?php foreach ($items as $item): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2 pb-2 border-bottom border-secondary">
                            <div>
                                <strong><?= htmlspecialchars($item['nombre']) ?></strong>
                                <br><small class="text-muted">x<?= $item['cantidad'] ?> | SKU: <?= htmlspecialchars($item['sku']) ?></small>
                            </div>
                            <div class="text-end">
                                $<?= number_format($item['precio_unitario'] * $item['cantidad'], 0, ',', '.') ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card bg-dark border-secondary sticky-top" style="top: 80px;">
                    <div class="card-header"><h5 class="mb-0">Resumen del Pedido</h5></div>
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
                        <div class="d-flex justify-content-between fw-bold fs-5 mb-3">
                            <span>Total</span>
                            <span id="total-display">$<?= number_format($totales['total'], 0, ',', '.') ?></span>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select id="metodo_pago" class="form-select bg-dark text-light">
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="efectivo_contra_entrega">Efectivo Contra Entrega</option>
                            </select>
                        </div>

                        <button id="btn-pagar" class="btn btn-success w-100 btn-lg" onclick="procesarPago()">
                            <i class="bi bi-lock"></i> Pagar $<?= number_format($totales['total'], 0, ',', '.') ?>
                        </button>

                        <div id="pago-error" class="alert alert-danger mt-2 d-none"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function procesarPago() {
        const btn = document.getElementById('btn-pagar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando...';
        document.getElementById('pago-error').classList.add('d-none');

        // Get selected address
        let direccionId = document.querySelector('input[name="direccion_id"]:checked')?.value;
        if (!direccionId) {
            direccionId = 'guest';
        }

        const notas = document.getElementById('notas')?.value || '';
        const metodo = document.getElementById('metodo_pago')?.value || 'tarjeta_credito';

        try {
            const res = await fetch('/api/checkout/procesar', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({direccion_id: direccionId, notas, metodo_pago: metodo})
            });
            const data = await res.json();
            
            if (data.success) {
                // Redirigir a pagos
                window.location.href = '/pago/' + data.data.orden_id;
            } else {
                document.getElementById('pago-error').textContent = data.message || 'Error al procesar';
                document.getElementById('pago-error').classList.remove('d-none');
            }
        } catch (e) {
            document.getElementById('pago-error').textContent = 'Error de conexión';
            document.getElementById('pago-error').classList.remove('d-none');
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lock"></i> Pagar $<?= number_format($totales['total'], 0, ',', '.') ?>';
    }
    </script>
</body>
</html><?php
    }

    private function renderSinStock(array $productos): void
    {
        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8"><title>Productos sin stock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-dark text-light d-flex align-items-center justify-content-center vh-100">
    <div class="text-center">
        <i class="bi bi-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
        <h4 class="mt-3">Productos sin stock suficiente</h4>
        <p>Los siguientes productos ya no tienen stock: <?= implode(', ', $productos) ?></p>
        <a href="/carrito" class="btn btn-primary">Volver al Carrito</a>
    </div>
</body>
</html><?php
    }
}
