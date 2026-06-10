<?php
declare(strict_types=1);

namespace App\Inventario;

use App\Core\Database;
use App\Core\Response;
use App\Core\Request;
use App\Core\Session;
use App\Core\Auditoria;

class InventarioController
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

    public function status(array $params = []): void
    {
        Response::success(['disponible' => true]);
    }

    // RF-INV-001: Consultar inventario por producto
    public function consultar(array $params = []): void
    {
        $productoId = $params['productoId'] ?? Request::get('productoId');

        if (!$productoId) {
            Response::error('Se requiere productoId', 400);
        }

        $inventario = $this->db->queryOne(
            'SELECT i.*, p.nombre, p.sku, (i.cantidad - i.cantidad_reservada) as stock_disponible 
             FROM inventario i 
             JOIN productos p ON i.producto_id = p.id 
             WHERE i.producto_id = ?',
            [$productoId]
        );

        if (!$inventario) {
            Response::error('Producto no encontrado', 404, 'PRODUCT_NOT_FOUND');
        }

        Response::success([
            'productoId' => $inventario['producto_id'],
            'nombre' => $inventario['nombre'],
            'sku' => $inventario['sku'],
            'stockDisponible' => (int) $inventario['stock_disponible'],
            'stockReservado' => (int) $inventario['cantidad_reservada'],
            'stockTotal' => (int) $inventario['cantidad'],
            'enAlerta' => $inventario['umbral_alerta'] !== null && $inventario['cantidad'] <= $inventario['umbral_alerta']
        ]);
    }

    // RF-INV-002: Validar disponibilidad
    public function validar(array $params = []): void
    {
        $data = Request::body();

        if (!isset($data['productos']) || !is_array($data['productos'])) {
            $productoId = $data['productoId'] ?? null;
            $cantidad = (int) ($data['cantidad'] ?? 1);

            if (!$productoId) {
                Response::error('Se requiere productoId y cantidad', 400);
            }

            $stock = $this->db->queryOne(
                'SELECT (cantidad - cantidad_reservada) as disponible FROM inventario WHERE producto_id = ?',
                [$productoId]
            );

            if (!$stock) {
                Response::error('Producto no encontrado', 404, 'PRODUCT_NOT_FOUND');
            }

            Response::success([
                'disponible' => $stock['disponible'] >= $cantidad,
                'stockDisponible' => (int) $stock['disponible']
            ]);
            return;
        }

        // Validación masiva
        $resultados = [];
        foreach ($data['productos'] as $item) {
            $stock = $this->db->queryOne(
                'SELECT (cantidad - cantidad_reservada) as disponible FROM inventario WHERE producto_id = ?',
                [$item['productoId']]
            );

            $resultados[] = [
                'productoId' => $item['productoId'],
                'disponible' => $stock ? $stock['disponible'] >= (int) $item['cantidad'] : false,
                'stockDisponible' => $stock ? (int) $stock['disponible'] : 0
            ];
        }

        $todosDisponibles = !in_array(false, array_column($resultados, 'disponible'), true);

        Response::success([
            'disponible' => $todosDisponibles,
            'productos' => $resultados
        ]);
    }

    // RF-INV-003: Reservar inventario
    public function reservar(array $params = []): void
    {
        $data = Request::validate([
            'ordenId' => 'required',
            'productos' => 'required'
        ]);

        if (!is_array($data['productos'])) {
            Response::error('Formato de productos inválido', 400);
        }

        $this->db->beginTransaction();

        try {
            $reservasIds = [];

            foreach ($data['productos'] as $item) {
                $productoId = $item['productoId'] ?? $item['producto_id'];
                $cantidad = (int) ($item['cantidad'] ?? 0);

                if ($cantidad <= 0) continue;

                // RN-INV-002: Validar stock disponible
                $stock = $this->db->queryOne(
                    'SELECT (cantidad - cantidad_reservada) as disponible FROM inventario WHERE producto_id = ?',
                    [$productoId]
                );

                if (!$stock || $stock['disponible'] < $cantidad) {
                    $this->db->rollback();
                    Response::error('Inventario insuficiente para producto ' . $productoId, 409, 'INSUFFICIENT_STOCK');
                    return;
                }

                // Incrementar reserva
                $this->db->execute(
                    'UPDATE inventario SET cantidad_reservada = cantidad_reservada + ? WHERE producto_id = ?',
                    [$cantidad, $productoId]
                );

                // Crear registro de reserva
                $reservaId = Auditoria::generateUuid();
                $this->db->execute(
                    'INSERT INTO reservas_inventario (id, orden_id, producto_id, cantidad, estado, fecha_expiracion) 
                     VALUES (?, ?, ?, ?, ?, DATE_ADD(NOW(), INTERVAL 30 MINUTE))',
                    [$reservaId, $data['ordenId'], $productoId, $cantidad, 'activa']
                );

                // Registrar movimiento
                $this->db->execute(
                    'INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, referencia) 
                     VALUES (?, ?, ?, ?, ?)',
                    [Auditoria::generateUuid(), $productoId, 'reserva', $cantidad, $data['ordenId']]
                );

                $reservasIds[] = $reservaId;
            }

            $this->auditoria->registrar(
                $this->session->getUsuarioId(), 
                'Inventario', 
                'Reserva creada', 
                'orden', 
                $data['ordenId']
            );

            $this->db->commit();

            Response::success([
                'reservaId' => implode(',', $reservasIds),
                'reservas' => $reservasIds
            ], 'Reserva creada', 201);

        } catch (\Throwable $e) {
            $this->db->rollback();
            Response::error('Error al reservar inventario', 500, 'INVENTORY_LOCK_FAILED');
        }
    }

    // RF-INV-004: Liberar reserva
    public function liberar(array $params = []): void
    {
        $data = Request::validate(['reservaId' => 'required']);

        $reservaIds = explode(',', $data['reservaId']);

        $this->db->beginTransaction();

        try {
            foreach ($reservaIds as $reservaId) {
                $reserva = $this->db->queryOne(
                    'SELECT * FROM reservas_inventario WHERE id = ? AND estado = ?',
                    [$reservaId, 'activa']
                );

                if (!$reserva) continue;

                // Liberar stock reservado
                $this->db->execute(
                    'UPDATE inventario SET cantidad_reservada = cantidad_reservada - ? WHERE producto_id = ?',
                    [$reserva['cantidad'], $reserva['producto_id']]
                );

                // Marcar reserva como liberada
                $this->db->execute(
                    "UPDATE reservas_inventario SET estado = 'liberada' WHERE id = ?",
                    [$reservaId]
                );

                // Registrar movimiento
                $this->db->execute(
                    'INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, referencia) 
                     VALUES (?, ?, ?, ?, ?)',
                    [Auditoria::generateUuid(), $reserva['producto_id'], 'liberacion', $reserva['cantidad'], $reservaId]
                );
            }

            $this->auditoria->registrar(
                $this->session->getUsuarioId(), 
                'Inventario', 
                'Reservas liberadas'
            );

            $this->db->commit();

            Response::success(null, 'Reservas liberadas correctamente');

        } catch (\Throwable $e) {
            $this->db->rollback();
            Response::error('Error al liberar reservas', 500);
        }
    }

    // RF-INV-005: Confirmar venta (descontar stock)
    public function confirmar(array $params = []): void
    {
        $data = Request::validate(['ordenId' => 'required']);

        $this->db->beginTransaction();

        try {
            $reservas = $this->db->query(
                "SELECT * FROM reservas_inventario WHERE orden_id = ? AND estado = 'activa'",
                [$data['ordenId']]
            );

            foreach ($reservas as $reserva) {
                // RN-INV-005: Descontar definitivamente
                $this->db->execute(
                    'UPDATE inventario SET cantidad = cantidad - ?, cantidad_reservada = cantidad_reservada - ?, updated_at = NOW() WHERE producto_id = ?',
                    [$reserva['cantidad'], $reserva['cantidad'], $reserva['producto_id']]
                );

                // Marcar reserva como confirmada
                $this->db->execute(
                    "UPDATE reservas_inventario SET estado = 'confirmada' WHERE id = ?",
                    [$reserva['id']]
                );

                // Registrar movimiento
                $this->db->execute(
                    'INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, cantidad, referencia) 
                     VALUES (?, ?, ?, ?, ?)',
                    [Auditoria::generateUuid(), $reserva['producto_id'], 'salida', $reserva['cantidad'], $data['ordenId']]
                );
            }

            $this->auditoria->registrar(
                $this->session->getUsuarioId(), 
                'Inventario', 
                'Venta confirmada - stock descontado', 
                'orden', 
                $data['ordenId']
            );

            $this->db->commit();

            Response::success(null, 'Venta confirmada y stock actualizado');

        } catch (\Throwable $e) {
            $this->db->rollback();
            Response::error('Error al confirmar venta', 500);
        }
    }

    // Listar inventario completo (admin)
    public function listar(array $params = []): void
    {
        if (!$this->session->isAdmin()) {
            Response::error('Acceso denegado', 403);
        }

        $inventario = $this->db->query(
            'SELECT i.*, p.nombre, p.sku, p.precio, (i.cantidad - i.cantidad_reservada) as disponible
             FROM inventario i 
             JOIN productos p ON i.producto_id = p.id 
             ORDER BY i.cantidad ASC'
        );

        Response::success(['productos' => $inventario]);
    }
}
