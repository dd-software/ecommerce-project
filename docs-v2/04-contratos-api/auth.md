# Contratos API — Módulo C: Autenticación

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** No requerida (excepto donde se indique 🔒)

---

## ENDPOINTS

### C.1 Registro de Usuario

`POST /api/auth/registro`

**Body:**

```json
{
  "nombre": "Juan",
  "apellido": "Pérez",
  "email": "juan@ejemplo.com",
  "password": "Password123",
  "password_confirmacion": "Password123"
}
```

**Validaciones:**

| Campo | Regla |
|-------|-------|
| nombre | 2-100 caracteres, solo letras y espacios |
| apellido | 2-100 caracteres, solo letras y espacios |
| email | Formato email válido, único en BD |
| password | Min 8 chars, 1 mayúscula, 1 número |
| password_confirmacion | Debe coincidir con password |

**Respuesta 201:**

```json
{
  "success": true,
  "data": {
    "usuario_id": "uuid",
    "email": "juan@ejemplo.com",
    "nombre_completo": "Juan Pérez",
    "mensaje": "Registro exitoso. Bienvenido."
  }
}
```

**Errores:**

```json
{
  "success": false,
  "error": {
    "code": "EMAIL_DUPLICADO",
    "message": "El email ya está registrado"
  }
}
```

---

### C.2 Inicio de Sesión

`POST /api/auth/login`

**Body:**

```json
{
  "email": "juan@ejemplo.com",
  "password": "Password123"
}
```

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "usuario": {
      "id": "uuid",
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@ejemplo.com",
      "rol": "cliente",
      "avatar_url": null
    },
    "mensaje": "Inicio de sesión exitoso"
  }
}
```

**Errores:**

| Código | Condición |
|--------|-----------|
| `CREDENCIALES_INVALIDAS` | Email o password incorrectos |
| `USUARIO_INACTIVO` | Cuenta deshabilitada |
| `CUENTA_BLOQUEADA` | Demasiados intentos fallidos |
| `CAMPOS_REQUERIDOS` | Falta email o password |

> **Nota didáctica:** Por seguridad, el mensaje de error no debe diferenciar entre "email no existe" y "password incorrecta". Siempre responder "Credenciales inválidas".

---

### C.3 Estado de Sesión 🔒

`GET /api/auth/status`

**Respuesta 200 (autenticado):**

```json
{
  "success": true,
  "data": {
    "autenticado": true,
    "usuario": {
      "id": "uuid",
      "nombre": "Juan",
      "apellido": "Pérez",
      "email": "juan@ejemplo.com",
      "rol": "cliente"
    }
  }
}
```

**Respuesta 200 (no autenticado):**

```json
{
  "success": true,
  "data": {
    "autenticado": false,
    "usuario": null
  }
}
```

---

### C.4 Cerrar Sesión 🔒

`POST /api/auth/logout`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "mensaje": "Sesión cerrada exitosamente"
  }
}
```

---

### C.5 Verificar Email

`GET /api/auth/verificar-email?token=xxx`

Marca `email_verificado = 1` para el usuario.

---

### C.6 Cambiar Contraseña 🔒

`POST /api/auth/cambiar-password`

**Body:**

```json
{
  "password_actual": "Password123",
  "password_nueva": "NewPassword456",
  "password_confirmacion": "NewPassword456"
}
```
