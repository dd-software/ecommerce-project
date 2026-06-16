# Contratos API — Ecommerce UCT

## Convenciones Generales

- **Formato:** Todas las APIs reciben y responden JSON
- **Base URL:** `http://localhost/ecommerce/api/`
- **Métodos HTTP:** GET (leer), POST (crear/actualizar)
- **Autenticación:** Sesión PHP (no tokens JWT, no API keys)
- **CSRF:** Toda petición POST debe incluir `csrf_token` en el body
- **Roles:** Solo `cliente` y `admin`. No hay empleados ni supervisores.
- **IDs:** Todos los IDs son numéricos (INT), no UUIDs
- **Sin namespaces:** PHP plano, sin Composer, sin PSR-4

---

## Índice de Contratos

| # | API | Archivo | Roles |
|---|-----|---------|-------|
| 1 | Auth | `api/auth.php` | público, cliente, admin |
| 2 | Carrito | `api/carrito.php` | público (sesión), cliente |
| 3 | Checkout | `api/checkout.php` | cliente |
| 4 | Pago | `api/pago.php` | cliente |
| 5 | Inventario | `api/inventario.php` | admin |

---

## 1. API Auth — `api/auth.php`

### 1.1 POST /api/auth.php?action=register
Registrar nuevo cliente.

**Permisos:** Público (sin sesión requerida)

**Body:**
```json
{
    "csrf_token": "abc123...",
    "nombre": "Juan",
    "apellido": "Pérez",
    "email": "juan@ejemplo.com",
    "password": "MiClave123!"
}
```

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "mensaje": "Registro exitoso",
    "usuario_id": 1
}
```

**Notas pedagógicas:**
- Siempre asigna rol `cliente`. No existe registro de admin.
- Password se almacena con `password_hash()` (bcrypt).
- El email se verifica con `filter_var($email, FILTER_VALIDATE_EMAIL)`.

### 1.2 POST /api/auth.php?action=login
Iniciar sesión.

**Permisos:** Público

**Body:**
```json
{
    "csrf_token": "abc123...",
    "email": "juan@ejemplo.com",
    "password": "MiClave123!"
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "mensaje": "Inicio de sesión exitoso",
    "usuario": {
        "id": 1,
        "nombre": "Juan",
        "rol": "cliente"
    }
}
```

### 1.3 POST /api/auth.php?action=logout
Cerrar sesión.

**Permisos:** Cliente o admin (sesión activa)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "mensaje": "Sesión cerrada"
}
```

---

## 2. API Carrito — `api/carrito.php`

### 2.1 GET /api/carrito.php
Obtener items del carrito actual (según sesión).

**Permisos:** Cualquiera con sesión (incluye invitados)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "items": [
        {
            "id": 1,
            "producto_id": 1,
            "nombre": "Audífonos Bluetooth Pro",
            "precio_unitario": 24990.00,
            "cantidad": 2,
            "subtotal": 49980.00,
            "imagen_url": "assets/img/audifonos.jpg"
        }
    ],
    "total": 49980.00
}
```

### 2.2 POST /api/carrito.php?action=agregar
Agregar producto al carrito.

**Permisos:** Cualquiera con sesión

**Body:**
```json
{
    "csrf_token": "abc123...",
    "producto_id": 1,
    "cantidad": 2
}
```

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "mensaje": "Producto agregado al carrito",
    "total_items": 3
}
```

**Errores:**
- `401` — Sesión no iniciada
- `400` — Stock insuficiente (`"error": "Stock insuficiente"`)

### 2.3 POST /api/carrito.php?action=actualizar
Actualizar cantidad de un item.

**Body:**
```json
{
    "csrf_token": "abc123...",
    "item_id": 1,
    "cantidad": 3
}
```

### 2.4 POST /api/carrito.php?action=eliminar
Eliminar item del carrito.

**Body:**
```json
{
    "csrf_token": "abc123...",
    "item_id": 1
}
```

---

## 3. API Checkout — `api/checkout.php`

### 3.1 POST /api/checkout.php
Procesar el checkout y crear pedido.

**Permisos:** Cliente autenticado (no invitado)

**Body:**
```json
{
    "csrf_token": "abc123...",
    "direccion_envio": "Av. Siempre Viva 123, Santiago",
    "notas": "Dejar en recepción"
}
```

**Proceso del servidor:**
1. Validar sesión de cliente
2. Obtener items del carrito del usuario
3. Calcular subtotal, IVA (19%), costo envío ($4.990)
4. Verificar stock disponible para cada producto
5. Crear reservas en `reservas_inventario` (expira: NOW + 10 min)
6. Crear pedido en `pedidos` (estado: `pendiente`)
7. Crear detalles en `detalles_pedido`
8. Vaciar carrito del usuario
9. Devolver ID del pedido y total a pagar

**Respuesta exitosa (201):**
```json
{
    "success": true,
    "pedido_id": 1,
    "numero": "ORD-2026-00001",
    "total": 59980.00
}
```

---

## 4. API Pago — `api/pago.php`

### 4.1 POST /api/pago.php
Registrar pago PayPal confirmado y finalizar pedido.

**Permisos:** Cliente autenticado

**Body:**
```json
{
    "csrf_token": "abc123...",
    "pedido_id": 1,
    "paypal_order_id": "PAYPAL-ORDER-ABC123",
    "paypal_payer_id": "PAYPAL-PAYER-DEF456"
}
```

**Proceso del servidor:**
1. Validar la transacción con PayPal (verificar con cURL a API PayPal)
2. Actualizar pago: estado = `completado`, `referencia_pasarela` = paypal_order_id
3. Cambiar estado del pedido a `confirmado`
4. Cambiar reservas a estado `confirmada`
5. Descontar stock definitivamente en `inventario`

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "mensaje": "Pago confirmado exitosamente",
    "pedido_numero": "ORD-2026-00001"
}
```

---

## 5. API Inventario (Admin) — `api/inventario.php`

### 5.1 GET /api/inventario.php
Obtener inventario de todos los productos.

**Permisos:** Admin

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "items": [
        {
            "producto_id": 1,
            "nombre": "Audífonos Bluetooth Pro",
            "cantidad": 50,
            "cantidad_reservada": 2,
            "disponible": 48,
            "umbral_alerta": 5
        }
    ]
}
```

### 5.2 POST /api/inventario.php?action=ajustar
Ajustar stock de un producto.

**Body:**
```json
{
    "csrf_token": "abc123...",
    "producto_id": 1,
    "cantidad": 100,
    "motivo": "Reabastecimiento"
}
```

### 5.3 POST /api/inventario.php?action=liberar_expiradas
Liberar todas las reservas expiradas (más de 10 min).

**Permisos:** Admin (o llamado interno)

**Respuesta exitosa (200):**
```json
{
    "success": true,
    "liberadas": 3,
    "mensaje": "3 reservas expiradas liberadas"
}
```

---

## Errores Comunes (Todas las APIs)

```json
{
    "success": false,
    "error": "Mensaje descriptivo del error"
}
```

| Código HTTP | Significado |
|-------------|-------------|
| 400 | Bad Request — parámetros inválidos o faltantes |
| 401 | No autorizado — sesión requerida |
| 403 | Prohibido — rol insuficiente |
| 404 | No encontrado — recurso no existe |
| 405 | Método no permitido |
| 409 | Conflicto — stock insuficiente, email duplicado |
| 500 | Error interno del servidor |
