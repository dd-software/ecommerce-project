<?php
declare(strict_types=1);

namespace App\PasarelaPago;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;

class PagoController
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
        // RN-E-001: Verificar servicio
        Response::success([
            'estado' => 'activo',
            'proveedor' => 'Pasarela Local (Simulación)',
            'moneda' => 'CLP',
            'metodos_pago' => ['Tarjeta de Crédito', 'Tarjeta de Débito', 'Transferencia Bancaria', 'Efectivo Contra Entrega']
        ]);
    }

    public function procesar(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
        }

        $data = Request::validate([
            'orden_id' => 'required',
            'metodo' => 'required'
        ]);

        $usuarioId = $this->session->getUsuarioId();

        // RN-E-001: Validar orden
        $orden = $this->db->queryOne(
            'SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?',
            [$data['orden_id'], $usuarioId]
        );

        if (!$orden) {
            Response::error('Orden no encontrada', 404, 'PAY-002');
        }

        // RN-E-002: No procesar pagos de órdenes canceladas
        if ($orden['estado'] === 'cancelado') {
            Response::error('No se puede procesar pago para una orden cancelada', 400, 'PAY-002');
        }

        if ($orden['estado'] !== 'pendiente') {
            Response::error('La orden ya fue procesada', 409, 'PAY-002');
        }

        // Simular procesamiento de pasarela externa
        $metodo = $data['metodo'];
        $proveedor = 'LOCAL_SIMULADO';
        $esAprobado = true; // Simulación: siempre aprobado para testing

        $this->db->beginTransaction();

        try {
            $pagoId = Auditoria::generateUuid();
            $referencia = 'TXN-' . strtoupper(substr(bin2hex(random_bytes(6)), 0, 12));
            $estado = $esAprobado ? 'aprobado' : 'rechazado';

            // RN-E-004: Registrar resultado
            $this->db->execute(
                'INSERT INTO pagos (id, pedido_id, metodo, estado, monto, moneda, referencia_pasarela, fecha_pago) 
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW())',
                [$pagoId, $orden['id'], $metodo, $estado, $orden['total'], 'CLP', $referencia]
            );

            // Registrar historial
            $histId = Auditoria::generateUuid();
            $this->db->execute(
                'INSERT INTO historial_transacciones (id, pago_id, estado_anterior, estado_nuevo, observacion) 
                 VALUES (?, ?, ?, ?, ?)',
                [$histId, $pagoId, null, $estado, "Pago procesado vía $proveedor"]
            );

            // RN-E-003: Actualizar estado de orden si fue aprobado
            if ($esAprobado) {
                $this->db->execute(
                    'UPDATE pedidos SET estado = ?, updated_at = NOW() WHERE id = ?',
                    ['confirmado', $orden['id']]
                );

                // Confirmar descuento de inventario
                $detalles = $this->db->query(
                    'SELECT * FROM detalles_pedido WHERE pedido_id = ?',
                    [$orden['id']]
                );

                foreach ($detalles as $detalle) {
                    $this->db->execute(
                        'UPDATE inventario SET cantidad = cantidad - ?, cantidad_reservada = cantidad_reservada - ? WHERE producto_id = ?',
                        [$detalle['cantidad'], $detalle['cantidad'], $detalle['producto_id']]
                    );

                    // Actualizar reservas
                    $this->db->execute(
                        "UPDATE reservas_inventario SET estado = 'confirmada' WHERE orden_id = ? AND producto_id = ?",
                        [$orden['id'], $detalle['producto_id']]
                    );
                }

                // Limpiar carrito
                $carrito = $this->db->queryOne(
                    'SELECT id FROM carritos WHERE usuario_id = ? AND estado = ?',
                    [$usuarioId, 'pendiente']
                );
                if ($carrito) {
                    $this->db->execute('DELETE FROM items_carrito WHERE carrito_id = ?', [$carrito['id']]);
                    // Crear nuevo carrito activo
                    $nuevoCarritoId = Auditoria::generateUuid();
                    $this->db->execute('INSERT INTO carritos (id, usuario_id) VALUES (?, ?)', [$nuevoCarritoId, $usuarioId]);
                }
            }

            $this->auditoria->registrar($usuarioId, 'Pagos', "Pago $estado", 'pago', $pagoId, [
                'orden' => $orden['numero'],
                'monto' => $orden['total'],
                'metodo' => $metodo,
                'referencia' => $referencia
            ]);

            $this->db->commit();

            Response::success([
                'pago_id' => $pagoId,
                'estado' => $estado,
                'referencia' => $referencia,
                'orden' => $orden['numero'],
                'monto' => $orden['total']
            ], $esAprobado ? 'Pago procesado exitosamente' : 'Pago rechazado');

        } catch (\Throwable $e) {
            $this->db->rollback();
            error_log("Payment error: " . $e->getMessage());
            Response::error('Error al procesar el pago', 500, 'PAY-007');
        }
    }

    public function confirmar(array $params = []): void
    {
        $ordenId = $params['ordenId'] ?? Request::input('orden_id');

        if (!$ordenId) {
            Response::error('Se requiere orden_id', 400);
        }

        $pago = $this->db->queryOne(
            'SELECT p.*, pe.estado as orden_estado, pe.numero as orden_numero 
             FROM pagos p JOIN pedidos pe ON p.pedido_id = pe.id 
             WHERE p.pedido_id = ?',
            [$ordenId]
        );

        if (!$pago) {
            Response::error('Pago no encontrado', 404, 'PAY-002');
        }

        Response::success([
            'pago' => $pago
        ]);
    }

    public function reembolsar(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            Response::error('Usuario no autenticado', 401);
        }

        // RN-E-006: Solo pagos aprobados
        $data = Request::validate(['pago_id' => 'required']);

        $pago = $this->db->queryOne(
            'SELECT * FROM pagos WHERE id = ?',
            [$data['pago_id']]
        );

        if (!$pago) {
            Response::error('Pago no encontrado', 404);
        }

        if ($pago['estado'] !== 'aprobado') {
            Response::error('Solo se pueden reembolsar pagos aprobados', 400, 'PAY-006');
        }

        $this->db->beginTransaction();

        try {
            $this->db->execute(
                'UPDATE pagos SET estado = ?, updated_at = NOW() WHERE id = ?',
                ['reembolsado', $pago['id']]
            );

            $this->db->execute(
                'UPDATE pedidos SET estado = ?, updated_at = NOW() WHERE id = ?',
                ['cancelado', $pago['pedido_id']]
            );

            // Registrar historial
            $histId = Auditoria::generateUuid();
            $this->db->execute(
                'INSERT INTO historial_transacciones (id, pago_id, estado_anterior, estado_nuevo, observacion) VALUES (?, ?, ?, ?, ?)',
                [$histId, $pago['id'], 'aprobado', 'reembolsado', 'Reembolso procesado']
            );

            $this->auditoria->registrar($this->session->getUsuarioId(), 'Pagos', 'Reembolso', 'pago', $pago['id']);

            $this->db->commit();
            Response::success(null, 'Reembolso procesado correctamente');
        } catch (\Throwable $e) {
            $this->db->rollback();
            Response::error('Error al procesar reembolso', 500);
        }
    }

    public function paginaPago(array $params = []): void
    {
        if (!$this->session->isAuthenticated()) {
            header('Location: /login');
            exit;
        }

        $ordenId = $params['ordenId'] ?? null;
        if (!$ordenId) {
            header('Location: /mis-pedidos');
            exit;
        }

        $orden = $this->db->queryOne(
            'SELECT * FROM pedidos WHERE id = ? AND usuario_id = ?',
            [$ordenId, $this->session->getUsuarioId()]
        );

        if (!$orden) {
            header('Location: /mis-pedidos');
            exit;
        }

        ?><!DOCTYPE html>
<html lang="es" data-bs-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pago — <?= htmlspecialchars($orden['numero']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="bg-dark">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <i class="bi bi-shield-lock text-success" style="font-size: 3rem;"></i>
                    <h3>Pago Seguro</h3>
                </div>
                <div class="card bg-dark border-secondary">
                    <div class="card-body p-4">
                        <h5>Orden: <?= htmlspecialchars($orden['numero']) ?></h5>
                        <h3 class="text-success mt-3">$<?= number_format($orden['total'], 0, ',', '.') ?></h3>
                        <hr class="border-secondary">
                        
                        <div class="mb-3">
                            <label class="form-label">Método de Pago</label>
                            <select id="metodo" class="form-select bg-dark text-light">
                                <option value="tarjeta_credito">Tarjeta de Crédito</option>
                                <option value="tarjeta_debito">Tarjeta de Débito</option>
                                <option value="transferencia">Transferencia Bancaria</option>
                                <option value="efectivo_contra_entrega">Efectivo Contra Entrega</option>
                            </select>
                        </div>

                        <!-- Simulación de tarjeta -->
                        <div id="card-form">
                            <div class="mb-3">
                                <label class="form-label">Número de Tarjeta</label>
                                <input type="text" class="form-control bg-dark text-light" placeholder="4242 4242 4242 4242" value="4242424242424242">
                            </div>
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <label class="form-label">Vencimiento</label>
                                    <input type="text" class="form-control bg-dark text-light" placeholder="MM/AA" value="12/28">
                                </div>
                                <div class="col-6 mb-3">
                                    <label class="form-label">CVV</label>
                                    <input type="text" class="form-control bg-dark text-light" placeholder="123" value="123">
                                </div>
                            </div>
                        </div>

                        <button id="btn-pagar" class="btn btn-success w-100 btn-lg" onclick="pagar()">
                            <i class="bi bi-lock"></i> Pagar $<?= number_format($orden['total'], 0, ',', '.') ?>
                        </button>
                        <div id="resultado" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    async function pagar() {
        const btn = document.getElementById('btn-pagar');
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Procesando pago...';

        try {
            const res = await fetch('/api/pagos/procesar', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    orden_id: '<?= $orden['id'] ?>',
                    metodo: document.getElementById('metodo').value
                })
            });
            const data = await res.json();
            
            const resultadoDiv = document.getElementById('resultado');
            if (data.success) {
                resultadoDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h5><i class="bi bi-check-circle"></i> ¡Pago Exitoso!</h5>
                        <p>Referencia: ${data.data.referencia}</p>
                        <a href="/mis-pedidos" class="btn btn-primary btn-sm">Ver mis pedidos</a>
                    </div>`;
            } else {
                resultadoDiv.innerHTML = `<div class="alert alert-danger">${data.message || 'Error al procesar el pago'}</div>`;
            }
        } catch (e) {
            document.getElementById('resultado').innerHTML = '<div class="alert alert-danger">Error de conexión</div>';
        }
        
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-lock"></i> Pagar $<?= number_format($orden['total'], 0, ',', '.') ?>';
    }
    </script>
</body>
</html><?php
    }
}
