<?php
// ============================================================
// Configuración de la Base de Datos
// ============================================================
// [PEDAGÓGICO] Los estudiantes aprenden a separar configuración
// del código. Cambiando estas constantes pueden conectar a
// cualquier servidor MySQL (localhost, producción, etc.)
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'ecommerce_uct');

// Configuración de la aplicación
define('SITE_NAME', 'Mi Tienda UCT');
define('SITE_URL', 'http://localhost/ecommerce-project');
define('IVA', 19); // 19% IVA chileno
define('COSTO_ENVIO', 4990); // $4.990 CLP
define('RESERVA_MINUTOS', 10); // Las reservas expiran en 10 min

// PayPal Sandbox
define('PAYPAL_MODE', 'sandbox');
define('PAYPAL_CLIENT_ID', 'TU_CLIENT_ID_SANDBOX');
define('PAYPAL_SECRET', 'TU_SECRET_SANDBOX');
define('PAYPAL_WEBHOOK_ID', '');

// Variable global para scripts adicionales en el footer
$scripts_adicionales = '';

// Sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();
