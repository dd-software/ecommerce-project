<?php
// ============================================================
// config.php - Configuración del sistema
// ============================================================
// [PEDAGÓGICO] Este archivo centraliza todas las configuraciones
// del sistema. Es el primer archivo que se incluye en cada página.
// ============================================================

// ============================================================
// 1. Configuración de rutas y URLs
// ============================================================
// [PEDAGÓGICO] SITE_URL debe terminar sin barra (/)
// Ejemplo: http://localhost/ecommerce-project
// Para producción: https://tudominio.com
// ============================================================
define('SITE_URL', 'http://localhost/ecommerce-project');
define('SITE_NAME', 'Mi Ecommerce UCT');
define('SITE_DESCRIPTION', 'Tienda en línea pedagógica - Proyecto de aprendizaje');

// ============================================================
// 2. Configuración de base de datos
// ============================================================
// [PEDAGÓGICO] Credenciales para conectar a MySQL/MariaDB
// En producción, usar variables de entorno o archivo .env
// ============================================================
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_uct');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// ============================================================
// 3. Configuración de impuestos y envío
// ============================================================
// [PEDAGÓGICO] IVA = 19% en Chile (puede cambiar según región)
// COSTO_ENVIO = Tarifa plana de envío
// ENVIO_GRATIS_DESDE = Monto a partir del cual el envío es gratis
// ============================================================
define('IVA', 19);
define('COSTO_ENVIO', 4990);
define('ENVIO_GRATIS_DESDE', 50000);
define('RESERVA_MINUTOS', 10); // Minutos para reservar inventario

// ============================================================
// 4. Configuración de PayPal
// ============================================================
// [PEDAGÓGICO] Usar 'sandbox' para pruebas y 'live' para producción
// Client ID: Obtener de https://developer.paypal.com/dashboard/
// ============================================================
define('PAYPAL_CLIENT_ID', 'sb'); // Sandbox por defecto
define('PAYPAL_SECRET', ''); // Completar con Secret real
define('PAYPAL_MODE', 'sandbox'); // sandbox | live
define('PAYPAL_CURRENCY', 'CLP');

// ============================================================
// 5. Configuración de Transferencia Bancaria
// ============================================================
// [PEDAGÓGICO] Datos de la cuenta bancaria para transferencias
// Estos datos se muestran en la página de éxito después de una
// compra con transferencia bancaria
// ============================================================
define('TRANSFERENCIA_BANCO', 'Banco de Chile');
define('TRANSFERENCIA_TIPO_CUENTA', 'Cuenta Corriente');
define('TRANSFERENCIA_CUENTA', '123456789');
define('TRANSFERENCIA_TITULAR', 'Mi Ecommerce UCT');
define('TRANSFERENCIA_RUT', '76.123.456-7');
define('TRANSFERENCIA_EMAIL', 'pagos@ecommerce.local');

// ============================================================
// 6. Configuración de imágenes
// ============================================================
// [PEDAGÓGICO] Ruta donde se almacenan las imágenes de productos
// La ruta es relativa a la raíz del proyecto
// ============================================================
define('UPLOAD_PATH', __DIR__ . '/../assets/img/productos/');
define('UPLOAD_URL', SITE_URL . '/assets/img/productos/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5 MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// ============================================================
// 7. Configuración de sesión
// ============================================================
// [PEDAGÓGICO] Configuración de seguridad para sesiones
// ============================================================
define('SESSION_LIFETIME', 3600); // 1 hora en segundos
define('SESSION_NAME', 'ecommerce_session');

// ============================================================
// 8. Configuración de seguridad
// ============================================================
// [PEDAGÓGICO] Configuraciones de seguridad para cookies y cabeceras
// ============================================================
define('CSRF_TOKEN_LENGTH', 32);
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_BCRYPT_COST', 12);

// ============================================================
// 9. Configuración de paginación
// ============================================================
// [PEDAGÓGICO] Cantidad de productos por página en listados
// ============================================================
define('PRODUCTOS_POR_PAGINA', 12);
define('PRODUCTOS_RELACIONADOS', 4);

// ============================================================
// 10. Configuración de caché
// ============================================================
// [PEDAGÓGICO] Tiempo de caché en segundos para consultas pesadas
// ============================================================
define('CACHE_TIME', 3600); // 1 hora

// ============================================================
// 11. Configuración de entorno
// ============================================================
// [PEDAGÓGICO] 'development' muestra errores, 'production' los oculta
// En producción, siempre usar 'production' por seguridad
// ============================================================
define('ENV', 'development'); // development | production

// ============================================================
// 12. Configuración de errores (según entorno)
// ============================================================
// [PEDAGÓGICO] En desarrollo mostramos todos los errores
// En producción los ocultamos para no exponer información sensible
// ============================================================
if (ENV === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
} else {
    error_reporting(0);
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
}

// ============================================================
// 13. Configuración de zona horaria
// ============================================================
// [PEDAGÓGICO] Chile usa UTC-3 en verano y UTC-4 en invierno
// Para simplificar, usamos America/Santiago
// ============================================================
date_default_timezone_set('America/Santiago');
setlocale(LC_TIME, 'es_ES.utf8', 'Spanish_Spain');

// ============================================================
// 14. Iniciar sesión (si no está iniciada)
// ============================================================
// [PEDAGÓGICO] Iniciar sesión al principio para que esté disponible
// en toda la aplicación. Usamos session_status() para evitar
// errores de "headers already sent"
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    // Configurar opciones de sesión
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_secure', (ENV === 'production'));
    
    // Nombre de sesión personalizado
    session_name(SESSION_NAME);
    
    // Iniciar sesión
    session_start();
    
    // Regenerar ID de sesión periódicamente (cada 5 minutos)
    if (!isset($_SESSION['_last_regeneration'])) {
        $_SESSION['_last_regeneration'] = time();
    } elseif (time() - $_SESSION['_last_regeneration'] > 300) {
        session_regenerate_id(true);
        $_SESSION['_last_regeneration'] = time();
    }
}

// ============================================================
// 15. Configuración de depuración
// ============================================================
// [PEDAGÓGICO] Función auxiliar para depuración
// Solo se ejecuta en modo desarrollo
// ============================================================
if (ENV === 'development') {
    function debug($data, $title = 'Debug') {
        echo '<div style="background: #f4f4f4; border: 1px solid #ddd; padding: 15px; margin: 10px 0; border-radius: 5px; font-family: monospace;">';
        echo '<h4 style="margin-top: 0;">' . htmlspecialchars($title) . '</h4>';
        echo '<pre style="background: #fff; padding: 10px; border: 1px solid #eee; overflow: auto; max-height: 400px;">';
        if (is_array($data) || is_object($data)) {
            print_r($data);
        } else {
            var_dump($data);
        }
        echo '</pre>';
        echo '</div>';
    }
    
    // Log de errores en archivo
    ini_set('error_log', __DIR__ . '/../logs/error.log');
} else {
    function debug($data, $title = '') {
        // No hacer nada en producción
    }
}

// ============================================================
// 16. Verificar instalación
// ============================================================
// [PEDAGÓGICO] Verificar que las carpetas necesarias existan
// ============================================================
function verificarEstructura() {
    $carpetas = [
        __DIR__ . '/../assets/img/productos/',
        __DIR__ . '/../logs/',
        __DIR__ . '/../cache/'
    ];
    
    foreach ($carpetas as $carpeta) {
        if (!is_dir($carpeta)) {
            mkdir($carpeta, 0755, true);
        }
    }
}

// Ejecutar verificación solo si estamos en desarrollo
if (ENV === 'development') {
    verificarEstructura();
}

// ============================================================
// 17. Mensaje de bienvenida (solo en desarrollo)
// ============================================================
if (ENV === 'development') {
    // Definir constante para identificar entorno de desarrollo
    define('IS_DEVELOPMENT', true);
    
    // Opcional: Mostrar mensaje en el footer (se implementa en footer.php)
} else {
    define('IS_DEVELOPMENT', false);
}

// ============================================================
// FIN DE CONFIGURACIÓN
// ============================================================
// [PEDAGÓGICO] Todas las constantes están definidas.
// Ahora cualquier archivo puede usar: SITE_URL, DB_HOST, etc.
// ============================================================