<?php
// ============================================================
// Funciones de Utilidad Global - Versión Corregida
// ============================================================
// [PEDAGÓGICO] Este archivo centraliza funciones reutilizables
// de toda la aplicación. Cada función hace UNA sola tarea bien
// definida (principio de responsabilidad única).

// ============================================================
// Iniciar sesión si no está iniciada
// ============================================================
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ============================================================
// Redireccionar (CORREGIDO con verificación de headers)
// ============================================================

/**
 * Redirige a otra URL y detiene la ejecución del script.
 *
 * [PEDAGÓGICO] Mejorado para manejar casos donde ya se enviaron
 * headers. Verifica si es posible usar header() o si necesita
 * usar JavaScript como fallback.
 *
 * @param string $url Dirección a la que redirigir (relativa o absoluta)
 * @param bool $permanente Si es true, usa redirección 301 (permanente)
 */
function redireccionar($url, $permanente = false): void
{
    // Si la URL es relativa, agregar SITE_URL
    if (strpos($url, 'http') !== 0 && strpos($url, '//') !== 0) {
        $url = SITE_URL . '/' . ltrim($url, '/');
    }

    // Verificar si ya se enviaron headers
    if (!headers_sent()) {
        // Usar redirección HTTP
        if ($permanente) {
            header('HTTP/1.1 301 Moved Permanently');
        }
        header('Location: ' . $url);
        exit;
    } else {
        // Fallback: Usar JavaScript + Meta refresh
        echo '<!DOCTYPE html>
        <html>
        <head>
            <meta http-equiv="refresh" content="0;url=' . $url . '">
            <script>
                window.location.href = "' . $url . '";
            </script>
            <title>Redirigiendo...</title>
        </head>
        <body>
            <p>Redirigiendo a <a href="' . $url . '">' . $url . '</a>...</p>
        </body>
        </html>';
        exit;
    }
}

/**
 * Redirección temporal (302) - Aliase de redireccionar()
 */
function redirigir_temporal($url): void
{
    redireccionar($url, false);
}

/**
 * Redirección permanente (301)
 */
function redirigir_permanente($url): void
{
    redireccionar($url, true);
}

// ============================================================
// Escapar HTML (XSS Prevention)
// ============================================================

/**
 * Escapa texto para evitar ataques XSS (Cross-Site Scripting).
 *
 * [PEDAGÓGICO] XSS ocurre cuando un atacante inyecta código
 * JavaScript o HTML a través de inputs. htmlspecialchars()
 * convierte caracteres especiales (<, >, ", ', &) en entidades
 * HTML (&lt;, &gt;, etc.), haciendo que el navegador los
 * muestre como texto literal en vez de ejecutarlos.
 *
 * @param string|null $texto Texto a escapar
 * @return string Texto escapado (vacío si era null)
 */
function escapar($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

// ============================================================
// Tokens CSRF (Cross-Site Request Forgery)
// ============================================================

/**
 * Genera un token CSRF y lo guarda en sesión.
 *
 * [PEDAGÓGICO] CSRF es un ataque donde un sitio malicioso
 * engaña al navegador del usuario para que envíe una petición
 * a otro sitio donde está autenticado (cambiando su email,
 * haciendo una compra, etc.). El token CSRF es un valor secreto
 * aleatorio que el servidor valida en cada formulario POST,
 * asegurando que la petición vino del sitio legítimo.
 *
 * @return string Token CSRF
 */
function csrf_token(): string
{
    // Si no existe token en sesión, lo generamos
    if (empty($_SESSION['_csrf_token'])) {
        // bin2hex + random_bytes genera una cadena aleatoria segura
        // desde el punto de vista criptográfico (no predecible)
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Valida un token CSRF contra el almacenado en sesión.
 *
 * [PEDAGÓGICO] Siempre se compara con hash_equals() en vez de
 * === para evitar timing attacks. La comparación con === se
 * detiene en el primer byte diferente, mientras que hash_equals
 * siempre compara todos los bytes, evitando que un atacante
 * pueda medir el tiempo de respuesta para adivinar el token.
 *
 * @param string $token Token a validar
 * @return bool True si el token es válido
 */
function csrf_validar($token): bool
{
    if (empty($_SESSION['_csrf_token']) || empty($token)) {
        return false;
    }

    return hash_equals($_SESSION['_csrf_token'], $token);
}

/**
 * Genera un campo hidden de CSRF para formularios.
 *
 * @return string HTML del campo hidden
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="_csrf_token" value="' . csrf_token() . '">';
}

// ============================================================
// Autenticación y Sesión de Usuario
// ============================================================

/**
 * Verifica si el usuario está logueado.
 *
 * [PEDAGÓGICO] Al iniciar sesión guardamos el ID del usuario
 * en $_SESSION['usuario_id']. Si existe y no está vacío,
 * el usuario está autenticado.
 *
 * @return bool True si el usuario tiene una sesión activa
 */
function esta_logueado(): bool
{
    return !empty($_SESSION['usuario_id']);
}

/**
 * Verifica si el usuario actual es administrador.
 *
 * [PEDAGÓGICO] En la base de datos, los usuarios tienen un campo
 * 'rol' que puede ser 'cliente' o 'admin'. Guardamos el rol
 * en sesión al iniciar sesión para no tener que consultar la BD
 * en cada página.
 *
 * @return bool True si el usuario es administrador
 */
function es_admin(): bool
{
    return !empty($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === 'admin';
}

/**
 * Obtiene los datos del usuario logueado desde la sesión.
 *
 * [PEDAGÓGICO] Datos básicos como nombre, email y rol se guardan
 * en sesión al iniciar sesión para evitar consultas repetitivas
 * a la base de datos. Para datos sensibles (contraseña, tarjetas),
 * estos NUNCA deben guardarse en sesión.
 *
 * @return array|null Array con datos del usuario o null si no está logueado
 */
function usuario_actual(): ?array
{
    if (!esta_logueado()) {
        return null;
    }

    return [
        'id'     => $_SESSION['usuario_id'] ?? null,
        'nombre' => $_SESSION['usuario_nombre'] ?? '',
        'email'  => $_SESSION['usuario_email'] ?? '',
        'rol'    => $_SESSION['usuario_rol'] ?? 'cliente',
    ];
}

/**
 * Inicia sesión de usuario.
 *
 * @param array $datusuario Datos del usuario (id, nombre, email, rol)
 */
function iniciar_sesion(array $datusuario): void
{
    $_SESSION['usuario_id'] = $datusuario['id'];
    $_SESSION['usuario_nombre'] = $datusuario['nombre'] ?? $datusuario['email'];
    $_SESSION['usuario_email'] = $datusuario['email'];
    $_SESSION['usuario_rol'] = $datusuario['rol'] ?? 'cliente';
    
    // Regenerar ID de sesión por seguridad (evita fijación de sesión)
    session_regenerate_id(true);
}

/**
 * Cierra la sesión del usuario.
 */
function cerrar_sesion(): void
{
    // Limpiar todas las variables de sesión
    $_SESSION = [];
    
    // Si se usa cookie de sesión, eliminarla
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }
    
    // Destruir la sesión
    session_destroy();
}

/**
 * Verifica que el usuario tenga permisos para acceder a una página.
 * Si no está logueado, redirige al login.
 * Si no es admin, redirige al inicio.
 *
 * @param bool $requiere_admin Si true, requiere rol de administrador
 */
function verificar_autenticacion($requiere_admin = false): void
{
    if (!esta_logueado()) {
        // Guardar la URL a la que intentaba acceder para redirigir después del login
        $_SESSION['url_redirect'] = $_SERVER['REQUEST_URI'];
        redirigir_temporal('login.php');
    }
    
    if ($requiere_admin && !es_admin()) {
        // No es administrador
        $_SESSION['error'] = 'No tienes permisos para acceder a esta página.';
        redirigir_temporal('index.php');
    }
}

// ============================================================
// Cálculo de Totales del Carrito
// ============================================================

/**
 * Calcula subtotal, IVA, costo de envío y total del carrito.
 *
 * [PEDAGÓGICO] Separar la lógica de cálculo de totales facilita
 * las pruebas y el mantenimiento. Si cambia el IVA o el método
 * de envío, solo se modifica esta función.
 *
 * @param array $items Array de items del carrito. Cada item debe
 *                     tener 'precio' y 'cantidad'.
 * @return array Con claves: subtotal, iva, envio, total
 */
function calcular_totales(array $items): array
{
    // Subtotal: suma de (precio unitario * cantidad) de cada item
    $subtotal = 0;
    foreach ($items as $item) {
        $subtotal += (float) ($item['precio'] ?? 0) * (int) ($item['cantidad'] ?? 0);
    }

    // IVA: porcentaje definido en config.php (19% chileno)
    $iva = $subtotal * (IVA / 100);

    // Envío: tarifa plana definida en config.php
    // [PEDAGÓGICO] En una versión avanzada, el costo de envío
    // podría calcularse según comuna, peso total, etc.
    $envio = COSTO_ENVIO;

    // Total: subtotal + IVA + envío, redondeado a 2 decimales
    $total = round($subtotal + $iva + $envio, 0);

    return [
        'subtotal' => $subtotal,
        'iva'      => $iva,
        'envio'    => $envio,
        'total'    => $total,
    ];
}

// ============================================================
// Formateo de Precios
// ============================================================

/**
 * Formatea un número al formato de peso chileno (CLP).
 *
 * [PEDAGÓGICO] El formato chileno usa: punto como separador de
 * miles y sin decimales (el peso chileno no tiene moneda
 * fraccionaria en circulación). Ejemplo: $1.234.567
 *
 * @param float|int $cantidad Monto a formatear
 * @return string Precio formateado con símbolo $
 */
function formato_precio($cantidad): string
{
    // number_format: miles con punto, sin decimales
    return '$' . number_format((float) $cantidad, 0, ',', '.');
}

// ============================================================
// Generación de Número de Orden
// ============================================================

/**
 * Genera un número de orden único con formato ORD-YYYY-NNNNN.
 *
 * [PEDAGÓGICO] Formato: ORD (orden) + año actual + número
 * secuencial de 5 dígitos (rellenado con ceros a la izquierda).
 * Esto da ordenes como ORD-2026-00001, ORD-2026-00002, etc.
 *
 * @param PDO $pdo Conexión a la base de datos
 * @return string Número de orden generado
 */
function generar_numero_orden(PDO $pdo): string
{
    $anio = date('Y');

    // Consultar el último número usado este año
    $stmt = $pdo->prepare("
        SELECT COUNT(*) as total
        FROM pedidos
        WHERE numero LIKE :patron
    ");
    $stmt->execute([':patron' => "ORD-{$anio}-%"]);
    $resultado = $stmt->fetch();

    // El correlativo es el total + 1
    $correlativo = ($resultado['total'] ?? 0) + 1;

    // str_pad rellena con ceros a la izquierda hasta 5 dígitos
    return 'ORD-' . $anio . '-' . str_pad($correlativo, 5, '0', STR_PAD_LEFT);
}

// ============================================================
// Mensajes Flash (Notificaciones)
// ============================================================

/**
 * Agrega un mensaje flash (notificación) para mostrar en la siguiente petición.
 *
 * [PEDAGÓGICO] Los mensajes flash son útiles para mostrar
 * confirmaciones o errores después de una redirección.
 *
 * @param string $tipo 'success', 'error', 'warning', 'info'
 * @param string $mensaje Texto del mensaje
 */
function mensaje_flash($tipo, $mensaje): void
{
    $_SESSION['flash'] = [
        'tipo' => $tipo,
        'mensaje' => $mensaje
    ];
}

/**
 * Muestra y elimina el mensaje flash si existe.
 *
 * @return string HTML del mensaje o cadena vacía
 */
function mostrar_mensaje_flash(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    
    $clases = [
        'success' => 'success',
        'error' => 'danger',
        'warning' => 'warning',
        'info' => 'info'
    ];
    
    $tipo = $clases[$flash['tipo']] ?? 'info';
    $mensaje = escapar($flash['mensaje']);
    
    return '<div class="alert alert-' . $tipo . ' alert-dismissible fade show" role="alert">
                ' . $mensaje . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>';
}

// ============================================================
// Validaciones de Formularios
// ============================================================

/**
 * Valida que un campo no esté vacío.
 *
 * @param string $valor Valor a validar
 * @param string $nombre Nombre del campo (para el mensaje de error)
 * @return string|null Mensaje de error o null si es válido
 */
function validar_requerido($valor, $nombre): ?string
{
    if (empty(trim($valor))) {
        return "El campo '$nombre' es obligatorio.";
    }
    return null;
}

/**
 * Valida que un email sea válido.
 *
 * @param string $email Email a validar
 * @return string|null Mensaje de error o null si es válido
 */
function validar_email($email): ?string
{
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return "El email no es válido.";
    }
    return null;
}

/**
 * Valida que una contraseña cumpla con los requisitos mínimos.
 *
 * @param string $password Contraseña a validar
 * @return string|null Mensaje de error o null si es válida
 */
function validar_password($password): ?string
{
    if (strlen($password) < 8) {
        return "La contraseña debe tener al menos 8 caracteres.";
    }
    if (!preg_match('/[A-Z]/', $password)) {
        return "La contraseña debe tener al menos una mayúscula.";
    }
    if (!preg_match('/[a-z]/', $password)) {
        return "La contraseña debe tener al menos una minúscula.";
    }
    if (!preg_match('/[0-9]/', $password)) {
        return "La contraseña debe tener al menos un número.";
    }
    return null;
}


// ============================================================
// Funciones de Sanitización
// ============================================================

/**
 * Sanitiza un string para evitar inyecciones y caracteres no deseados.
 *
 * @param string $input Texto a sanitizar
 * @return string Texto sanitizado
 */
function sanitizar($input): string
{
    // Eliminar espacios al inicio y final
    $input = trim($input);
    
    // Eliminar etiquetas HTML (permitiendo algunas básicas)
    $input = strip_tags($input, '<p><br><strong><em><b><i><ul><ol><li><a>');
    
    // Convertir caracteres especiales
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitiza un email.
 *
 * @param string $email Email a sanitizar
 * @return string Email sanitizado
 */
function sanitizar_email($email): string
{
    return filter_var(trim($email), FILTER_SANITIZE_EMAIL);
}

// ============================================================
// Funciones de Fecha
// ============================================================

/**
 * Formatea una fecha para mostrar en español.
 *
 * @param string $fecha Fecha en formato YYYY-MM-DD o YYYY-MM-DD HH:MM:SS
 * @param bool $incluir_hora Si incluir la hora en el formato
 * @return string Fecha formateada
 */
function formato_fecha($fecha, $incluir_hora = false): string
{
    $timestamp = strtotime($fecha);
    $dias = [
        'Sunday' => 'Domingo',
        'Monday' => 'Lunes',
        'Tuesday' => 'Martes',
        'Wednesday' => 'Miércoles',
        'Thursday' => 'Jueves',
        'Friday' => 'Viernes',
        'Saturday' => 'Sábado'
    ];
    $meses = [
        'January' => 'Enero',
        'February' => 'Febrero',
        'March' => 'Marzo',
        'April' => 'Abril',
        'May' => 'Mayo',
        'June' => 'Junio',
        'July' => 'Julio',
        'August' => 'Agosto',
        'September' => 'Septiembre',
        'October' => 'Octubre',
        'November' => 'Noviembre',
        'December' => 'Diciembre'
    ];
    
    $dia = $dias[date('l', $timestamp)];
    $dia_num = date('j', $timestamp);
    $mes = $meses[date('F', $timestamp)];
    $anio = date('Y', $timestamp);
    
    $resultado = "$dia $dia_num de $mes de $anio";
    
    if ($incluir_hora) {
        $hora = date('H:i', $timestamp);
        $resultado .= " a las $hora hrs.";
    }
    
    return $resultado;
}

// ============================================================
// Función para obtener la URL actual
// ============================================================

/**
 * Obtiene la URL completa de la página actual.
 *
 * @return string URL actual
 */
function url_actual(): string
{
    $protocolo = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $uri = $_SERVER['REQUEST_URI'];
    return $protocolo . '://' . $host . $uri;
}

// ============================================================
// Función para generar slugs (URLs amigables)
// ============================================================

/**
 * Genera un slug (URL amigable) a partir de un texto.
 *
 * @param string $texto Texto a convertir en slug
 * @return string Slug generado
 */
function generar_slug($texto): string
{
    // Convertir a minúsculas
    $texto = strtolower($texto);
    
    // Reemplazar caracteres especiales
    $texto = preg_replace('/[áäâà]/', 'a', $texto);
    $texto = preg_replace('/[éëêè]/', 'e', $texto);
    $texto = preg_replace('/[íïîì]/', 'i', $texto);
    $texto = preg_replace('/[óöôò]/', 'o', $texto);
    $texto = preg_replace('/[úüûù]/', 'u', $texto);
    $texto = preg_replace('/[ñ]/', 'n', $texto);
    
    // Eliminar caracteres no deseados (solo letras, números y guiones)
    $texto = preg_replace('/[^a-z0-9-]/', '-', $texto);
    
    // Eliminar guiones múltiples
    $texto = preg_replace('/-+/', '-', $texto);
    
    // Eliminar guiones al inicio y final
    $texto = trim($texto, '-');
    
    return $texto;
}