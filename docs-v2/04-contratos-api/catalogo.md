# Contratos API — Módulo A: Catálogo

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** No requerida (lectura pública)

---

## ENDPOINTS

### A.1 Listar Productos

`GET /api/catalogo`

Obtiene el listado paginado de productos activos.

**Parámetros (query string):**

| Parámetro | Tipo | Default | Descripción |
|-----------|------|---------|-------------|
| `page` | int | 1 | Número de página |
| `limit` | int | 12 | Productos por página (max 48) |
| `categoria` | string | — | Slug de categoría para filtrar |
| `busqueda` | string | — | Texto de búsqueda (nombre + descripción) |
| `orden` | string | `fecha` | `precio_asc`, `precio_desc`, `nombre_asc`, `nombre_desc`, `fecha` |
| `destacados` | bool | false | Solo productos destacados |

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "productos": [
      {
        "id": "uuid",
        "sku": "LPT-001",
        "nombre": "Laptop Gamer",
        "slug": "laptop-gamer",
        "precio": 799990,
        "precio_descuento": 699990,
        "tiene_descuento": true,
        "imagen_principal": "https://ejemplo.com/img.jpg",
        "categoria": "Electrónica",
        "categoria_slug": "electronica",
        "stock_disponible": 15,
        "es_nuevo": true,
        "destacado": false,
        "created_at": "2024-03-15T10:00:00Z"
      }
    ],
    "paginacion": {
      "page": 1,
      "limit": 12,
      "total": 45,
      "total_paginas": 4
    },
    "categorias_disponibles": [
      {
        "nombre": "Electrónica",
        "slug": "electronica",
        "total_productos": 15
      }
    ]
  }
}
```

---

### A.2 Detalle de Producto

`GET /api/producto/{slug}`

Obtiene la información completa de un producto.

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "id": "uuid",
    "sku": "LPT-001",
    "nombre": "Laptop Gamer XYZ",
    "slug": "laptop-gamer",
    "descripcion": "Laptop con RTX 4060, 16GB RAM, i7 13th gen...",
    "precio": 799990,
    "precio_descuento": 699990,
    "tiene_descuento": true,
    "categoria": {
      "id": "uuid",
      "nombre": "Electrónica",
      "slug": "electronica"
    },
    "imagenes": [
      {
        "url": "https://ejemplo.com/img1.jpg",
        "alt_text": "Laptop frente",
        "es_principal": true,
        "orden": 0
      }
    ],
    "inventario": {
      "stock_disponible": 15,
      "stock_total": 20,
      "alerta_stock": false
    },
    "productos_relacionados": [
      { "id": "uuid", "nombre": "...", "precio": 599990, "slug": "..." }
    ],
    "es_nuevo": true,
    "destacado": false,
    "created_at": "2024-03-15T10:00:00Z"
  }
}
```

**Respuesta 404:**

```json
{
  "success": false,
  "error": {
    "code": "PRODUCTO_NO_ENCONTRADO",
    "message": "El producto solicitado no existe o no está disponible"
  }
}
```

---

### A.3 Listar Categorías

`GET /api/categorias`

Obtiene el árbol de categorías activas.

**Respuesta 200:**

```json
{
  "success": true,
  "data": [
    {
      "id": "uuid",
      "nombre": "Electrónica",
      "slug": "electronica",
      "descripcion": "Productos electrónicos",
      "imagen_url": null,
      "total_productos": 15,
      "subcategorias": [
        {
          "id": "uuid",
          "nombre": "Laptops",
          "slug": "laptops",
          "total_productos": 8
        }
      ]
    }
  ]
}
```

---

### A.4 Productos Destacados

`GET /api/productos/destacados`

Obtiene los productos marcados como destacados (máximo 8).

**Respuesta:** Mismo formato que listar productos, sin paginación.
