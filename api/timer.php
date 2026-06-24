<?php
// ============================================================
// API JSON: Timer de sesión de checkout (OBJ-06)
// ============================================================
// [PEDAGÓGICO] Endpoint minimalista para iniciar/reiniciar el
// countdown de la sesión de checkout. El estado vive en
// $_SESSION (por usuario, por servidor) — el cliente solo recibe
// el timestamp absoluto y descuenta en pantalla.
//
// Acciones (POST):
//   iniciar   -> Si no existe, guarda time() + RESERVA_MINUTOS*60
//                en $_SESSION['checkout_expira_at']. Devuelve el
//                timestamp en milisegundos.
//   reiniciar -> Borra la clave de la sesión. Lo usa carrito.php
//                / api/carrito.php para que el próximo intento
//                de pago empiece con un timer fresco.
//
// Respuesta: {success: bool, expira_at_ms?: int, message?: string}
// ============================================================

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/funciones.php';

header('Content-Type: application/json; charset=utf-8');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Usa POST.');
    }

    $token = $_POST['_csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    if (!csrf_validar($token)) {
        throw new Exception('CSRF inválido.');
    }

    if (!esta_logueado()) {
        throw new Exception('Debes iniciar sesión.');
    }

    $accion = trim($_POST['action'] ?? '');

    switch ($accion) {
        case 'iniciar':
            // [PEDAGÓGICO] Sólo arrancamos si NO hay timer activo.
            // Esto preserva el contador entre reloads: si el usuario
            // hizo clic en PayPal y refresca la página, el timer
            // sigue siendo el mismo.
            if (empty($_SESSION['checkout_expira_at'])) {
                $_SESSION['checkout_expira_at'] = time() + (RESERVA_MINUTOS * 60);
            }
            die(json_encode([
                'success'      => true,
                'expira_at_ms' => $_SESSION['checkout_expira_at'] * 1000,
            ]));

        case 'reiniciar':
            unset($_SESSION['checkout_expira_at']);
            die(json_encode(['success' => true]));

        default:
            throw new Exception("Acción desconocida: {$accion}");
    }
} catch (Exception $e) {
    die(json_encode([
        'success' => false,
        'message' => $e->getMessage(),
    ]));
}
