<?php
// ============================================================
// Cerrar Sesión
// ============================================================
// [PEDAGÓGICO] Esta página solo ejecuta una acción:
// destruir la sesión actual y redirigir al inicio.
// No muestra HTML, solo hace redirect.
// ============================================================

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/funciones.php';

// ============================================================
// Destruir la sesión completamente
// ============================================================
// [PEDAGÓGICO] session_destroy() elimina todos los datos de
// la sesión del servidor. También removemos la cookie de
// sesión del navegador para una limpieza completa.
// ============================================================

// 1. Vaciar el array de sesión
$_SESSION = [];

// 2. Eliminar la cookie de sesión del navegador
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000, // Fecha en el pasado para que el navegador la elimine
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// 3. Destruir la sesión en el servidor
session_destroy();

// ============================================================
// Redirigir al inicio
// ============================================================
redireccionar('index.php');
