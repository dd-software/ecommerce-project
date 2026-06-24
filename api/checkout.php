<?php
// ============================================================
// API de Checkout - Procesa pagos con PayPal y Transferencia
// ============================================================
// [PEDAGÓGICO] Este archivo maneja todas las operaciones
// relacionadas con el checkout: crear orden PayPal, capturar
// pago, y procesar pagos por transferencia.
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

// Configurar respuesta JSON
header('Content-Type: application/json');

// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$pdo = getDB();

// ============================================================
// Verificar autenticación
// ============================================================
if (!esta_logueado()) {
    echo json_encode(['success' => false, 'message' => 'No autorizado']);
    exit;
}

// ============================================================
// Obtener acción
// ============================================================
$accion = $_POST['accion'] ?? '';

// ============================================================
// Función: Obtener datos del carrito
// ============================================================
function obtenerDatosCarrito($pdo, $usuario_id) {
    $stmt = $pdo->prepare("
        SELECT ic.*, p.nombre, p.sku
        FROM items_carrito ic
        JOIN productos p ON p.id = ic.producto_id
        WHERE ic.usuario_id = :uid
    ");
    $stmt->execute([':uid' => $usuario_id]);
    return $stmt->fetchAll();
}

// ============================================================
// Función: Calcular totales del carrito
// ============================================================
function calcularTotalesCarrito($items) {
    $items_totales = [];
    foreach ($items as $item) {
        $items_totales[] = [
            'precio' => $item['precio_unitario'],
            'cantidad' => $item['cantidad']
        ];
    }
    return calcular_totales($items_totales);
}

// ============================================================
// Función: Crear orden en la base de datos (CORREGIDA)
// ============================================================
function crearOrdenDB($pdo, $usuario_id, $items, $totales, $direccion, $metodo_pago, $paypal_order_id = null) {
    try {
        // Generar número de orden
        $numero_orden = generar_numero_orden($pdo);
        
        // Iniciar transacción
        $pdo->beginTransaction();
        
        // Construir dirección completa (para campo legacy)
        $direccion_completa = $direccion['calle'] . ', ' . $direccion['ciudad'] . ', ' . $direccion['region'];
        if (!empty($direccion['codigo_postal'])) {
            $direccion_completa .= ', CP: ' . $direccion['codigo_postal'];
        }
        
        // Insertar pedido - Usando los campos correctos de tu tabla
        $stmt = $pdo->prepare("
            INSERT INTO pedidos (
                numero, usuario_id, estado, subtotal, iva, envio, total,
                calle, ciudad, region, codigo_postal, notas,
                direccion_envio, metodo_pago, paypal_order_id,
                fecha_creacion
            ) VALUES (
                :numero, :usuario_id, 'pendiente', :subtotal, :iva, :envio, :total,
                :calle, :ciudad, :region, :codigo_postal, :notas,
                :direccion_envio, :metodo_pago, :paypal_order_id,
                NOW()
            )
        ");
        
        $stmt->execute([
            ':numero' => $numero_orden,
            ':usuario_id' => $usuario_id,
            ':subtotal' => $totales['subtotal'],
            ':iva' => $totales['iva'],
            ':envio' => $totales['envio'],
            ':total' => $totales['total'],
            ':calle' => $direccion['calle'],
            ':ciudad' => $direccion['ciudad'],
            ':region' => $direccion['region'],
            ':codigo_postal' => $direccion['codigo_postal'] ?? '',
            ':notas' => $direccion['notas'] ?? '',
            ':direccion_envio' => $direccion_completa,
            ':metodo_pago' => $metodo_pago,
            ':paypal_order_id' => $paypal_order_id
        ]);
        
        $pedido_id = $pdo->lastInsertId();
        
        // Insertar items del pedido - Usando detalles_pedido
        $stmt_detalle = $pdo->prepare("
            INSERT INTO detalles_pedido (
                pedido_id, producto_id, nombre_producto, cantidad, precio_unitario, subtotal
            ) VALUES (
                :pedido_id, :producto_id, :nombre_producto, :cantidad, :precio_unitario, :subtotal
            )
        ");
        
        foreach ($items as $item) {
            $subtotal_item = (float) $item['precio_unitario'] * (int) $item['cantidad'];
            $stmt_detalle->execute([
                ':pedido_id' => $pedido_id,
                ':producto_id' => $item['producto_id'],
                ':nombre_producto' => $item['nombre'],
                ':cantidad' => (int) $item['cantidad'],
                ':precio_unitario' => $item['precio_unitario'],
                ':subtotal' => $subtotal_item
            ]);
            
            // Actualizar stock (si existe campo stock en productos)
            // Si usas inventario separado, ajusta aquí
            $stmt_stock = $pdo->prepare("
                UPDATE productos 
                SET stock = stock - :cantidad 
                WHERE id = :producto_id
            ");
            $stmt_stock->execute([
                ':cantidad' => (int) $item['cantidad'],
                ':producto_id' => $item['producto_id']
            ]);
        }
        
        // Limpiar carrito
        $stmt = $pdo->prepare("DELETE FROM items_carrito WHERE usuario_id = :uid");
        $stmt->execute([':uid' => $usuario_id]);
        
        // Confirmar transacción
        $pdo->commit();
        
        return [
            'success' => true,
            'pedido_id' => $pedido_id,
            'numero_orden' => $numero_orden
        ];
        
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

// ============================================================
// Procesar acciones
// ============================================================
try {
    $response = ['success' => false, 'message' => '', 'data' => []];
    
    switch ($accion) {
        // ============================================================
        // 1. Crear orden PayPal
        // ============================================================
        case 'crear_orden':
            // Obtener datos del carrito
            $items = obtenerDatosCarrito($pdo, $_SESSION['usuario_id']);
            if (empty($items)) {
                $response['message'] = 'Carrito vacío';
                break;
            }
            
            // Validar dirección
            $calle = trim($_POST['calle'] ?? '');
            $ciudad = trim($_POST['ciudad'] ?? '');
            $region = trim($_POST['region'] ?? '');
            
            if (empty($calle) || empty($ciudad) || empty($region)) {
                $response['message'] = 'Dirección incompleta';
                break;
            }
            
            // Calcular total
            $totales = calcularTotalesCarrito($items);
            
            // Guardar dirección en sesión para usarla después
            $_SESSION['checkout_direccion'] = [
                'calle' => $calle,
                'ciudad' => $ciudad,
                'region' => $region,
                'codigo_postal' => $_POST['codigo_postal'] ?? '',
                'notas' => $_POST['notas'] ?? ''
            ];
            
            // Generar ID de orden PayPal (simulado por ahora)
            $order_id = 'PAYPAL-' . date('Ymd') . '-' . uniqid();
            
            // Crear orden en la base de datos
            $direccion = $_SESSION['checkout_direccion'];
            $resultado = crearOrdenDB(
                $pdo,
                $_SESSION['usuario_id'],
                $items,
                $totales,
                $direccion,
                'paypal',
                $order_id
            );
            
            if ($resultado['success']) {
                $response['success'] = true;
                $response['message'] = 'Orden creada correctamente';
                $response['data'] = [
                    'order_id' => $order_id,
                    'order_number' => $resultado['numero_orden'],
                    'pedido_id' => $resultado['pedido_id']
                ];
            } else {
                $response['message'] = 'Error al crear la orden';
            }
            break;
            
        // ============================================================
        // 2. Capturar pago PayPal
        // ============================================================
        case 'capturar_pago':
            $order_id = $_POST['order_id'] ?? '';
            $payer_id = $_POST['payer_id'] ?? '';
            $payment_id = $_POST['payment_id'] ?? '';
            
            if (empty($order_id) || empty($payer_id) || empty($payment_id)) {
                $response['message'] = 'Datos de pago incompletos';
                break;
            }
            
            // Buscar el pedido por paypal_order_id
            $stmt = $pdo->prepare("
                SELECT id, numero 
                FROM pedidos 
                WHERE paypal_order_id = :order_id 
                AND usuario_id = :usuario_id
                AND estado = 'pendiente'
            ");
            $stmt->execute([
                ':order_id' => $order_id,
                ':usuario_id' => $_SESSION['usuario_id']
            ]);
            $pedido = $stmt->fetch();
            
            if (!$pedido) {
                $response['message'] = 'Pedido no encontrado';
                break;
            }
            
            // Actualizar estado del pedido a 'pagado'
            $stmt = $pdo->prepare("
                UPDATE pedidos 
                SET estado = 'pagado',
                    paypal_payer_id = :payer_id,
                    paypal_payment_id = :payment_id,
                    fecha_pago = NOW()
                WHERE id = :pedido_id
            ");
            $stmt->execute([
                ':payer_id' => $payer_id,
                ':payment_id' => $payment_id,
                ':pedido_id' => $pedido['id']
            ]);
            
            $response['success'] = true;
            $response['message'] = 'Pago confirmado correctamente';
            $response['data'] = [
                'order_number' => $pedido['numero'],
                'redirect' => SITE_URL . '/exito.php?orden=' . $pedido['numero']
            ];
            break;
            
        // ============================================================
        // 3. Procesar transferencia bancaria
        // ============================================================
        case 'transferencia':
            // Obtener datos del carrito
            $items = obtenerDatosCarrito($pdo, $_SESSION['usuario_id']);
            if (empty($items)) {
                $response['message'] = 'Carrito vacío';
                break;
            }
            
            // Validar dirección
            $calle = trim($_POST['calle'] ?? '');
            $ciudad = trim($_POST['ciudad'] ?? '');
            $region = trim($_POST['region'] ?? '');
            
            if (empty($calle) || empty($ciudad) || empty($region)) {
                $response['message'] = 'Dirección incompleta';
                break;
            }
            
            // Calcular total
            $totales = calcularTotalesCarrito($items);
            
            $direccion = [
                'calle' => $calle,
                'ciudad' => $ciudad,
                'region' => $region,
                'codigo_postal' => $_POST['codigo_postal'] ?? '',
                'notas' => $_POST['notas'] ?? ''
            ];
            
            // Crear orden
            $resultado = crearOrdenDB(
                $pdo,
                $_SESSION['usuario_id'],
                $items,
                $totales,
                $direccion,
                'transferencia'
            );
            
            if ($resultado['success']) {
                // Guardar datos de transferencia en sesión
                $_SESSION['transferencia_datos'] = [
                    'banco' => TRANSFERENCIA_BANCO ?? 'Banco de Chile',
                    'cuenta' => TRANSFERENCIA_CUENTA ?? '123456789',
                    'titular' => TRANSFERENCIA_TITULAR ?? 'Mi Tienda Online',
                    'rut' => TRANSFERENCIA_RUT ?? '76.123.456-7',
                    'email' => TRANSFERENCIA_EMAIL ?? 'pagos@mitienda.cl',
                    'monto' => formato_precio($totales['total'])
                ];
                
                $response['success'] = true;
                $response['message'] = 'Orden creada para transferencia';
                $response['data'] = [
                    'order_number' => $resultado['numero_orden'],
                    'redirect' => SITE_URL . '/exito.php?orden=' . $resultado['numero_orden'] . '&metodo=transferencia'
                ];
            } else {
                $response['message'] = 'Error al crear la orden';
            }
            break;
            
        // ============================================================
        // 4. Cancelar orden
        // ============================================================
        case 'cancelar_orden':
            $order_id = $_POST['order_id'] ?? '';
            if (!empty($order_id)) {
                $stmt = $pdo->prepare("
                    UPDATE pedidos 
                    SET estado = 'cancelado' 
                    WHERE paypal_order_id = :order_id 
                    AND estado = 'pendiente'
                ");
                $stmt->execute([':order_id' => $order_id]);
            }
            $response['success'] = true;
            $response['message'] = 'Orden cancelada';
            break;
            
        default:
            $response['message'] = 'Acción no válida';
    }
    
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Error: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}