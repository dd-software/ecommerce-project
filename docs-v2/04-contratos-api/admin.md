# Contratos API — Módulo G: Administración

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** Requerida 🔒 — Solo admin/supervisor

---

## ENDPOINTS

### G.1 Dashboard

`GET /api/admin/dashboard`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "metricas": {
      "total_productos": 150,
      "total_pedidos_mes": 45,
      "total_usuarios": 230,
      "ingresos_mes": 15000000,
      "pedidos_pendientes": 8,
      "pedidos_en_proceso": 12,
      "stock_alerta": 3
    },
    "pedidos_recientes": [
      {
        "numero": "ORD-2024-00045",
        "cliente": "Juan Pérez",
        "total": 956978,
        "estado": "pendiente",
        "fecha": "2024-03-15T10:00:00Z"
      }
    ],
    "alertas_stock": [
      {
        "producto": "Mouse Inalámbrico",
        "sku": "MOU-001",
        "stock_disponible": 2,
        "umbral": 10
      }
    ]
  }
}
```

---

### G.2 Listar Usuarios 🔒

`GET /api/admin/usuarios`

**Parámetros:** `page`, `limit`, `rol`, `activo`, `busqueda`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "usuarios": [
      {
        "id": "uuid",
        "nombre": "Juan",
        "apellido": "Pérez",
        "email": "juan@ejemplo.com",
        "rol": "cliente",
        "activo": true,
        "fecha_registro": "2024-01-15T10:00:00Z",
        "ultimo_acceso": "2024-03-14T15:30:00Z",
        "total_pedidos": 5
      }
    ],
    "paginacion": { "page": 1, "total": 230, "total_paginas": 23 }
  }
}
```

---

### G.3 Cambiar Estado de Usuario 🔒

`POST /api/admin/usuarios/toggle`

**Body:**

```json
{
  "usuario_id": "uuid",
  "activo": false
}
```

> **Importante:** No se puede desactivar al último administrador.

---

### G.4 Listar Pedidos (Admin) 🔒

`GET /api/admin/pedidos`

**Parámetros:** `page`, `estado`, `fecha_desde`, `fecha_hasta`, `busqueda`

---

### G.5 Cambiar Estado de Pedido 🔒

`POST /api/admin/pedidos/estado`

**Body:**

```json
{
  "pedido_id": "uuid",
  "nuevo_estado": "en_preparacion"
}
```

**Transiciones válidas:**
- `pendiente` → `confirmado`, `cancelado`
- `confirmado` → `en_preparacion`, `cancelado`
- `en_preparacion` → `enviado`
- `enviado` → `entregado`
- `cancelado` → (estado final, no cambia)

---

### G.6 CRUD de Productos 🔒

#### Listar (Admin)
`GET /api/admin/productos`

#### Crear
`POST /api/admin/productos`

**Body:**

```json
{
  "sku": "LPT-002",
  "nombre": "Laptop Office",
  "descripcion": "Descripción detallada...",
  "precio": 599990,
  "precio_descuento": null,
  "categoria_id": "uuid",
  "destacado": false,
  "activo": true
}
```

#### Editar
`PUT /api/admin/productos/{producto_id}`

#### Eliminar (desactivar)
`DELETE /api/admin/productos/{producto_id}`

> Los productos no se eliminan físicamente, se marcan como `activo = false`.

---

### G.7 Gestión de Categorías 🔒

`GET /api/admin/categorias` | `POST` | `PUT /{id}` | `DELETE /{id}`

**Regla:** No se puede eliminar una categoría con productos asociados.

---

### G.8 Auditoría 🔒

`GET /api/admin/auditoria`

**Parámetros:** `modulo`, `accion`, `fecha_desde`, `fecha_hasta`, `page`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "eventos": [
      {
        "fecha": "2024-03-15T10:00:00Z",
        "usuario": "Admin Sistema",
        "modulo": "Inventario",
        "accion": "Ajuste de stock",
        "detalle": "Entrada de 20 unidades - Mouse Inalámbrico",
        "ip": "192.168.1.100"
      }
    ],
    "paginacion": { "page": 1, "total": 1500 }
  }
}
```

---

### G.9 Configuración 🔒

`GET /api/admin/configuracion` | `POST /api/admin/configuracion`

**Body POST:**

```json
[
  { "clave": "moneda", "valor": "CLP" },
  { "clave": "impuesto_porcentaje", "valor": "19" },
  { "clave": "envio_costo_base", "valor": "4990" },
  { "clave": "reserva_expiracion_minutos", "valor": "30" },
  { "clave": "modo_mantenimiento", "valor": "false" }
]
```
