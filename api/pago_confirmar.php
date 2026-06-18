<?php
// ============================================================
// api/pago_confirmar.php - Maneja el retorno de PayPal
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/funciones.php';

$pdo = getDB();

if (!esta_logueado()) {
    redireccionar(SITE_URL . '/login.php?redirect=pago_confirmar.php');
}

$paypal_order_id = $_GET['token'] ?? $_POST['paypal_order_id'] ?? '';
$orden_id = isset($_GET['orden_id']) ? (int) $_GET['orden_id'] : 0;
$success = isset($_GET['success']) ? (int) $_GET['success'] : 0;

if (empty($paypal_order_id) && $orden_id <= 0) {
    $_SESSION['error'] = 'No se recibieron datos de pago de PayPal.';
    redireccionar(SITE_URL . '/checkout.php');
}

if ($orden_id <= 0 && !empty($paypal_order_id)) {
    $stmt = $pdo->prepare("SELECT pedido_id FROM pagos WHERE referencia_pasarela = :ref AND metodo = 'paypal'");
    $stmt->execute([':ref' => $paypal_order_id]);
    $pago = $stmt->fetch();
    if ($pago) {
        $orden_id = (int) $pago['pedido_id'];
    }
}

if ($orden_id <= 0) {
    $_SESSION['error'] = 'No se pudo identificar la orden.';
    redireccionar(SITE_URL . '/checkout.php');
}

$stmt = $pdo->prepare("SELECT id, numero, estado FROM pedidos WHERE id = :id AND usuario_id = :uid");
$stmt->execute([':id' => $orden_id, ':uid' => $_SESSION['usuario_id']]);
$orden = $stmt->fetch();

if (!$orden) {
    $_SESSION['error'] = 'Orden no encontrada o no autorizada.';
    redireccionar(SITE_URL . '/checkout.php');
}

if ($success) {
    $token = csrf_token();
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, SITE_URL . '/api/pago.php');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query([
        'action' => 'capturar',
        'paypal_order_id' => $paypal_order_id,
        'orden_id' => $orden_id,
        '_csrf_token' => $token,
    ]));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    $data = json_decode($response, true);
    
    if ($http_code === 200 && !empty($data['success'])) {
        $_SESSION['exito'] = 'Pago exitoso! Tu orden ' . $orden['numero'] . ' esta confirmada.';
        redireccionar(SITE_URL . '/exito.php?orden=' . urlencode($orden['numero']));
    } else {
        $mensaje_error = $data['message'] ?? 'Error al confirmar el pago con PayPal.';
        $_SESSION['error'] = 'Error: ' . $mensaje_error;
        redireccionar(SITE_URL . '/checkout.php');
    }
} else {
    $_SESSION['error'] = 'Has cancelado el pago en PayPal.';
    redireccionar(SITE_URL . '/checkout.php');
}
