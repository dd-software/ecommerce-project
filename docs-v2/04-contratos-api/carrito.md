# Contratos API — Módulo B: Carrito

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** Requerida para rutas con 🔒

---

## ENDPOINTS

### B.1 Ver Carrito 🔒

`GET /api/carrito`

Obtiene el carrito del usuario autenticado.

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "id": "uuid_carrito",
    "items": [
      {
        "id": "uuid_item",
        "producto_id": "uuid_producto",
        "nombre": "Laptop Gamer",
        "slug": "laptop-gamer",
        "sku": "LPT-001",
        "imagen_url": "https://ejemplo.com/img.jpg",
        "precio_unitario": 799990,
        "cantidad": 2,
        "subtotal": 1599980,
        "stock_disponible": 15,
        "stock_suficiente": true
      }
    ],
    "resumen": {
      "subtotal": 1599980,
      "iva": 303996,
      "costo_envio": 4990,
      "total": 1908966,
      "total_items": 2,
      "items_distintos": 1
    }
  }
}
```

**Carrito vacío:**

```json
{
  "success": true,
  "data": {
    "id": "uuid_carrito",
    "items": [],
    "resumen": {
      "subtotal": 0,
      "iva": 0,
      "costo_envio": 0,
      "total": 0,
      "total_items": 0,
      "items_distintos": 0
    }
  }
}
```

---

### B.2 Agregar Producto 🔒

`POST /api/carrito/agregar`

**Body:**

```json
{
  "producto_id": "uuid",
  "cantidad": 1
}
```

**Validaciones (posibles errores 400):**

| Código | Condición |
|--------|-----------|
| `PRODUCTO_INACTIVO` | El producto no está activo |
| `STOCK_INSUFICIENTE` | No hay stock disponible |
| `CANTIDAD_EXCEDIDA` | Cantidad > 10 por producto |
| `CARRITO_LLENO` | Más de 50 items distintos |
| `PRODUCTO_NO_ENCONTRADO` | producto_id no existe |

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "item_id": "uuid_item",
    "producto_id": "uuid",
    "cantidad": 1,
    "precio_unitario": 799990,
    "subtotal": 799990,
    "mensaje": "Producto agregado al carrito"
  }
}
```

---

### B.3 Actualizar Cantidad 🔒

`POST /api/carrito/actualizar`

**Body:**

```json
{
  "item_id": "uuid_item",
  "cantidad": 3
}
```

**Validaciones extras:**
- `cantidad` entre 1 y 10
- Re-valida stock disponible en servidor

---

### B.4 Eliminar Producto 🔒

`POST /api/carrito/eliminar`

**Body:**

```json
{
  "item_id": "uuid_item"
}
```

---

### B.5 Vaciar Carrito 🔒

`POST /api/carrito/vaciar`

**Body:** Vacío.

---

### B.6 Fusionar Carrito Local 🔒

`POST /api/carrito/fusionar`

Se llama automáticamente después del login. Toma los items que el usuario tenía en localStorage (invitado) y los agrega a su carrito de servidor.

**Body:**

```json
{
  "items_local": [
    { "producto_id": "uuid", "cantidad": 2 },
    { "producto_id": "uuid", "cantidad": 1 }
  ]
}
```

**Reglas de fusión:**
1. Si el producto ya existe en el carrito servidor → sumar cantidades (max 10)
2. Si el producto no existe → agregar normalmente
3. Si el producto está inactivo/sin stock → omitir y reportar
