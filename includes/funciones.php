<?php
// ============================================================
// Funciones de Utilidad Global
// ============================================================
// [PEDAGÓGICO] Este archivo centraliza funciones reutilizables
// de toda la aplicación. Cada función hace UNA sola tarea bien
// definida (principio de responsabilidad única).

// ============================================================
// Redireccionar
// ============================================================

/**
 * Redirige a otra URL y detiene la ejecución del script.
 *
 * [PEDAGÓGICO] Después de header('Location: ...') SIEMPRE debe
 * llamarse exit/die para evitar que el resto del script se
 * ejecute y envíe HTML accidentalmente.
 *
 * @param string $url Dirección a la que redirigir (relativa o absoluta)
 */
function redireccionar($url): void
{
    header('Location: ' . $url);
    exit;
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
