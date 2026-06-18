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
define('PAYPAL_CLIENT_ID', 'AbTC3-yoQw0W4A4JvK_wigLal39eI3pYjKyOSAwUh13cSF7Sy3MUSs6Gln0n3jqvHQuOcesEcPwhqjhA');
define('PAYPAL_SECRET', 'EHIl_6Jbbj5H91MJ70WVGHGokcOuB4tT9d7zYcEob6qVUxz_5jD_vtmuGwcRc8238KO52porLMs8wvuw');
define('PAYPAL_WEBHOOK_ID', '');

// Sesión
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_samesite', 'Strict');
session_start();

// Variable global para scripts adicionales, evitar warnings en footer
$scripts_adicionales = '';
