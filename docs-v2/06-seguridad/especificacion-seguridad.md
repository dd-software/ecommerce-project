# Especificación de Seguridad

> **Propósito educativo:** Cada punto tiene una explicación de por qué es importante. Los estudiantes deben implementarlos y justificarlos en su código.

---

## 1. Checklist OWASP (Open Web Application Security Project)

### 🔴 A01 - Broken Access Control

| # | Medida | Implementación | Verificación |
|---|--------|---------------|--------------|
| 1.1 | Verificar rol en cada endpoint protegido | `Session::isAdmin()` en cada método de AdminController | Probar acceso directo a `/api/admin/*` sin sesión |
| 1.2 | Verificar dueño del recurso | Un usuario no puede ver pedidos de otro usuario | `WHERE usuario_id = ?` en consultas |
| 1.3 | No exponer IDs secuenciales | Usar UUIDs en lugar de auto-increment | Verificar que las URLs contienen UUIDs |

### 🔴 A02 - Cryptographic Failures

| # | Medida | Implementación |
|---|--------|---------------|
| 2.1 | Passwords con bcrypt cost >= 12 | `password_hash($password, PASSWORD_BCRYPT, ['cost' => 12])` |
| 2.2 | Conexión DB sin password en texto plano | Variables de entorno en `.env` (excluido de Git) |
| 2.3 | No almacenar datos sensibles en logs | Filtrar passwords, tokens, tarjetas antes de loguear |
| 2.4 | HTTPS en producción | Redirección 301 de HTTP a HTTPS |

### 🔴 A03 - Injection

| # | Medida | Implementación |
|---|--------|---------------|
| 3.1 | Prepared Statements en TODAS las consultas | Usar PDO con bindValue/bindParam |
| 3.2 | NO concatenar strings SQL | `"SELECT * FROM usuarios WHERE id = $id"` → ❌ PROHIBIDO |
| 3.3 | Sanitizar HTML output | `htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')` en toda vista |
| 3.4 | Validar tipos de datos | `ctype_digit()`, `filter_var(FILTER_VALIDATE_EMAIL)`, etc. |

### 🔴 A04 - Insecure Design

| # | Medida | Implementación |
|---|--------|---------------|
| 4.1 | Rate limiting en login | Máximo 5 intentos en 15 minutos por IP |
| 4.2 | Lockout de cuenta | Deshabilitar login por 15 min tras exceder intentos |
| 4.3 | Validación servidor-dual | Nunca confiar solo en validación cliente-side |
| 4.4 | Máquina de estados finitos | Validar transiciones de estado de pedido |

### 🟡 A05 - Security Misconfiguration

| # | Medida | Implementación |
|---|--------|---------------|
| 5.1 | .env no accesible vía web | Bloquear en `.htaccess`: `Deny from all` |
| 5.2 | Debug mode desactivado en producción | `APP_DEBUG=false` en `.env` |
| 5.3 | Headers de seguridad | `X-Content-Type-Options: nosniff`, `X-Frame-Options: DENY` |
| 5.4 | CORS restrictivo | Solo permitir origen del mismo sitio |

### 🟡 A06 - Vulnerable and Outdated Components

| # | Medida | Implementación |
|---|--------|---------------|
| 6.1 | PHP version >= 8.2 | Usar características modernas con seguridad |
| 6.2 | Sin frameworks externos no auditados | Solo Bootstrap 5.3 (CDN) |
| 6.3 | Composer dependencies auditadas | `composer audit` periódicamente |

### 🔴 A07 - Identification and Authentication Failures

| # | Medida | Implementación |
|---|--------|---------------|
| 7.1 | Regenerar session_id en login | `session_regenerate_id(true)` |
| 7.2 | Cookies seguras | `HttpOnly`, `SameSite=Strict`, `Secure` en producción |
| 7.3 | Sesión expira por inactividad | `session.gc_maxlifetime = 7200` (2 horas) |
| 7.4 | Contraseñas fuertes | Mínimo 8 chars, 1 mayúscula, 1 número |

### 🟡 A08 - Software and Data Integrity Failures

| # | Medida | Implementación |
|---|--------|---------------|
| 8.1 | CSRF Tokens en todo POST/PUT/DELETE | Token por sesión, validado en servidor |
| 8.2 | No auto-increment en actualizaciones | Usar WHERE con UUID + dueño |

---

## 2. Reglas de Validación de Datos

### Email
```php
filter_var($email, FILTER_VALIDATE_EMAIL)  // Formato
// + consulta UNIQUE en BD
```

### Password
```php
// Mínimo 8 caracteres
strlen($password) >= 8

// Al menos 1 mayúscula
preg_match('/[A-Z]/', $password)

// Al menos 1 número
preg_match('/[0-9]/', $password)
```

### Precios y Cantidades
```php
// Precio > 0
$precio > 0

// Cantidad entre 1 y 10
$cantidad >= 1 && $cantidad <= 10

// Decimal con 2 decimales
round($precio, 2)
```

### UUID
```php
preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $uuid)
```

---

## 3. Política de Sesiones

```
Duración:             2 horas de inactividad
Cookie HttpOnly:      Sí
Cookie SameSite:      Strict
Cookie Secure:        Sí (en producción)
Regeneración en login: Sí
Máximo intentos login: 5 en 15 minutos
```

---

## 4. Manejo de Errores Seguro

- ❌ **NO** mostrar stack traces al usuario
- ✅ Mostrar mensajes genéricos: "Ha ocurrido un error"
- ✅ Loguear el error completo en `storage/logs/`
- ✅ En modo DEBUG, permitir stack trace solo para desarrolladores

**Ejemplo:**

```php
try {
    // Operación
} catch (Exception $e) {
    Logger::error("Error en checkout: " . $e->getMessage());
    Response::error("Error al procesar la compra", 500);
}
```
