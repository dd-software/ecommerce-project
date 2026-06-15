# Contratos API — Módulo D: Checkout

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** Requerida 🔒

---

## ENDPOINTS

### D.1 Iniciar Checkout 🔒

`GET /api/checkout`

Muestra el resumen de compra antes de confirmar.

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "carrito": {
      "items": [
        {
          "producto_id": "uuid",
          "nombre": "Laptop Gamer",
          "cantidad": 1,
          "precio_unitario": 799990,
          "subtotal": 799990,
          "imagen_url": "https://...",
          "stock_suficiente": true
        }
      ],
      "subtotal": 799990,
      "iva": 151998,
      "costo_envio": 4990,
      "total": 956978
    },
    "direcciones": [
      {
        "id": "uuid",
        "alias": "Casa",
        "calle": "Av. Alemania 123",
        "ciudad": "Temuco",
        "region": "Araucanía",
        "codigo_postal": "4780000",
        "es_predeterminada": true
      }
    ],
    "metodo_pago_disponible": "paypal"
  }
}
```

---

### D.2 Procesar Checkout 🔒

`POST /api/checkout/procesar`

Crea la orden y redirige al pago.

**Body:**

```json
{
  "direccion_envio_id": "uuid_direccion",
  "notas": "Dejar en recepción"
}
```

**Proceso interno (transaccional):**
1. Re-validar stock de todos los items
2. Si algún item falló → error con detalle
3. Si todo OK → crear orden (estado: `pendiente`)
4. Crear detalles de pedido
5. Reservar inventario
6. Registrar movimiento de inventario
7. Registrar auditoría

**Respuesta 201:**

```json
{
  "success": true,
  "data": {
    "orden_id": "uuid",
    "numero_orden": "ORD-2024-00001",
    "total": 956978,
    "moneda": "CLP",
    "redirect_url": "/api/pagos/procesar?orden_id=uuid",
    "mensaje": "Orden creada exitosamente. Redirigiendo al pago..."
  }
}
```

**Error 409 (stock insuficiente):**

```json
{
  "success": false,
  "error": {
    "code": "STOCK_INSUFICIENTE",
    "message": "No hay stock suficiente para completar la compra",
    "items_sin_stock": [
      {
        "producto_id": "uuid",
        "nombre": "Producto X",
        "solicitado": 2,
        "disponible": 0
      }
    ]
  }
}
```

---

### D.3 Obtener Estado de Orden 🔒

`GET /api/checkout/orden/{orden_id}`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "orden_id": "uuid",
    "numero": "ORD-2024-00001",
    "estado": "pendiente",
    "fecha_creacion": "2024-03-15T10:00:00Z",
    "items": [...],
    "total": 956978,
    "direccion_envio": {...},
    "pago": {
      "estado": "pendiente",
      "referencia_paypal": null
    }
  }
}
```
