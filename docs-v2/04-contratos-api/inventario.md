# Contratos API — Módulo F: Inventario

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** Varía por endpoint 🔒

---

## ENDPOINTS

### F.1 Consultar Stock

`GET /api/inventario/{producto_id}`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "producto_id": "uuid",
    "sku": "LPT-001",
    "nombre": "Laptop Gamer",
    "inventario": {
      "stock_total": 50,
      "stock_reservado": 3,
      "stock_disponible": 47,
      "umbral_alerta": 10,
      "alerta_stock": false
    }
  }
}
```

---

### F.2 Validar Disponibilidad

`POST /api/inventario/validar`

Verifica si hay stock suficiente para 1 o más productos.

**Body (único producto):**

```json
{
  "producto_id": "uuid",
  "cantidad": 2
}
```

**Body (múltiples productos — usado en checkout):**

```json
{
  "items": [
    { "producto_id": "uuid1", "cantidad": 1 },
    { "producto_id": "uuid2", "cantidad": 3 }
  ]
}
```

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "valido": true,
    "items": [
      { "producto_id": "uuid1", "disponible": 10, "solicitado": 1, "suficiente": true },
      { "producto_id": "uuid2", "disponible": 5, "solicitado": 3, "suficiente": true }
    ]
  }
}
```

**Respuesta con errores parciales:**

```json
{
  "success": true,
  "data": {
    "valido": false,
    "items": [
      { "producto_id": "uuid1", "disponible": 0, "solicitado": 1, "suficiente": false, "mensaje": "Producto agotado" },
      { "producto_id": "uuid2", "disponible": 2, "solicitado": 3, "suficiente": false, "mensaje": "Stock insuficiente. Disponible: 2" }
    ]
  }
}
```

---

### F.3 Reservar Stock 🔒

`POST /api/inventario/reservar`

Reserva stock para una compra en curso. La reserva expira a los 30 minutos.

**Body:**

```json
{
  "orden_id": "uuid_orden",
  "items": [
    { "producto_id": "uuid1", "cantidad": 1 },
    { "producto_id": "uuid2", "cantidad": 2 }
  ]
}
```

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "reserva_id": "uuid_reserva",
    "fecha_expiracion": "2024-03-15T10:30:00Z",
    "items_reservados": 2,
    "mensaje": "Stock reservado exitosamente por 30 minutos"
  }
}
```

---

### F.4 Liberar Reserva 🔒

`POST /api/inventario/liberar`

Libera las reservas de una orden (por cancelación o expiración).

**Body:**

```json
{
  "orden_id": "uuid_orden"
}
```

---

### F.5 Confirmar Descuento 🔒

`POST /api/inventario/confirmar`

Confirma la venta: descuenta stock definitivamente (reserva → salida).

> **Importante:** Solo se llama DESPUÉS de que PayPal confirma el pago.

**Body:**

```json
{
  "orden_id": "uuid_orden"
}
```

---

### F.6 Listar Inventario (Admin) 🔒

`GET /api/admin/inventario`

Listado completo de inventario con alertas.

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "productos": [
      {
        "producto_id": "uuid",
        "sku": "LPT-001",
        "nombre": "Laptop Gamer",
        "stock_total": 50,
        "stock_reservado": 3,
        "stock_disponible": 47,
        "umbral_alerta": 10,
        "alerta_stock": false
      }
    ],
    "total_productos": 100,
    "productos_con_alerta": 2
  }
}
```

---

### F.7 Ajustar Stock (Admin) 🔒

`POST /api/admin/inventario/ajustar`

Ajuste manual de stock por parte del admin.

**Body:**

```json
{
  "producto_id": "uuid",
  "tipo_ajuste": "entrada",
  "cantidad": 20,
  "motivo": "Reabastecimiento mensual"
}
```

**Tipos de ajuste:** `entrada`, `salida`, `ajuste`
