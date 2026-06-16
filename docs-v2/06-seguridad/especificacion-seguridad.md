# Especificación de Seguridad — Ecommerce UCT

## Principios Generales

1. **Seguridad por capas** — Protección en backend (PHP), frontend (JS/HTML) y base de datos
2. **Mínimo privilegio** — Cada rol solo accede a lo que necesita
3. **Validación siempre en servidor** — La validación del cliente (JS) es complementaria, nunca única
4. **Sin dependencias externas de seguridad** — Todo se implementa con funciones nativas de PHP

---

## 1. CSRF Tokens (Cross-Site Request Forgery)

### Generación del token
```php
<?php
// En config.php o al iniciar sesión
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
```

### Inclusión en formularios
```php
<input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
```

### En peticiones AJAX (jQuery)
```javascript
$.ajaxSetup({
    data: {
        csrf_token: '<?= $_SESSION['csrf_token'] ?>'
    }
});
```

### Validación en el servidor
```php
<?php
function verificar_csrf($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        http_response_code(403);
        echo json_encode(['error' => 'Token CSRF inválido']);
        exit;
    }
}
```

---

## 2. Protección XSS (Cross-Site Scripting)

Toda salida de datos generados por usuarios debe escaparse:

```php
<?php
// En lugar de: echo $nombre;
echo htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8');

// Para atributos HTML:
echo 'value="' . htmlspecialchars($valor, ENT_QUOTES, 'UTF-8') . '"';
```

**Nunca usar:**
- `strip_tags()` como única defensa (no es suficiente)
- `htmlentities()` con charset incorrecto (siempre UTF-8)
- `echo` directo de datos no escapados

---

## 3. Sesiones Seguras

Configuración en `includes/config.php`:

```php
<?php
// Cookies HTTP-only (no accesibles desde JS)
ini_set('session.cookie_httponly', 1);

// Solo cookies, no propagación por URL
ini_set('session.use_only_cookies', 1);

// SameSite Strict (protección CSRF adicional)
ini_set('session.cookie_samesite', 'Strict');

// Cookie segura solo si usas HTTPS
// ini_set('session.cookie_secure', 1);

session_start();
```

---

## 4. Protección de Contraseñas

Se usa `password_hash()` con algoritmo bcrypt (coste por defecto 10):

```php
<?php
// Hash al registrar
$hash = password_hash($password, PASSWORD_DEFAULT);

// Verificar al login
if (password_verify($password, $hash)) {
    // Contraseña correcta
}
```

No se implementa una política de "bcrypt mínimo" como validación separada — el coste por defecto de PHP es suficiente para un proyecto pedagógico.

---

## 5. Rate Limiting Simple

Control de intentos de login usando contador en sesión:

```php
<?php
function verificar_rate_limit() {
    $max_intentos = 5;
    $ventana = 300; // 5 minutos en segundos

    if (!isset($_SESSION['login_intentos'])) {
        $_SESSION['login_intentos'] = 0;
        $_SESSION['login_ultimo_intento'] = time();
    }

    // Resetear si pasó la ventana
    if (time() - $_SESSION['login_ultimo_intento'] > $ventana) {
        $_SESSION['login_intentos'] = 0;
    }

    if ($_SESSION['login_intentos'] >= $max_intentos) {
        http_response_code(429);
        echo json_encode(['error' => 'Demasiados intentos. Espere 5 minutos.']);
        exit;
    }

    $_SESSION['login_intentos']++;
    $_SESSION['login_ultimo_intento'] = time();
}
```

---

## 6. Sanitización de IDs y Parámetros

```php
<?php
// IDs numéricos (los más comunes)
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if ($id === false || $id === null) {
    http_response_code(400);
    exit;
}

// Strings (sanitizar para SQL)
$nombre = filter_input(INPUT_POST, 'nombre', FILTER_SANITIZE_STRING);

// Email
$email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
```

---

## 7. Consultas Preparadas (SQL Injection)

Todas las consultas SQL usan PDO con prepared statements:

```php
<?php
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
$producto = $stmt->fetch();
```

**NUNCA** se concatenan valores directamente en SQL:

```php
// ❌ INCORRECTO
$sql = "SELECT * FROM productos WHERE id = $id";

// ✅ CORRECTO
$stmt = $pdo->prepare("SELECT * FROM productos WHERE id = ?");
$stmt->execute([$id]);
```

---

## 8. Control de Acceso por Roles

```php
<?php
function require_login() {
    if (!isset($_SESSION['usuario_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Debe iniciar sesión']);
        exit;
    }
}

function require_admin() {
    require_login();
    if ($_SESSION['usuario_rol'] !== 'admin') {
        http_response_code(403);
        echo json_encode(['error' => 'Acceso denegado: se requiere rol admin']);
        exit;
    }
}
```

---

## 9. Headers de Seguridad HTTP

```php
<?php
// En cada página/API
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: strict-origin-when-cross-origin');
```

---

## Resumen de Controles de Seguridad

| Amenaza | Control | Implementación |
|---------|---------|----------------|
| CSRF | Token en sesión | `bin2hex(random_bytes(32))` |
| XSS | Escape de salida | `htmlspecialchars()` |
| SQL Injection | Prepared Statements | PDO con placeholders |
| Sesión secuestrada | HttpOnly + SameSite | `ini_set()` en config.php |
| Fuerza bruta | Rate limiting | Contador en sesión |
| Contraseñas débiles | bcrypt hash | `password_hash()` |
| Acceso no autorizado | Control de roles | Funciones `require_*()` |
| Manipulación de IDs | Validación de tipos | `filter_input()` |
