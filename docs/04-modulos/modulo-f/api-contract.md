# API Contract

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo E - Inventario**

## Objetivo

Definir los contratos API del módulo de inventario para consultar disponibilidad de productos, controlar existencias y soportar las operaciones de catálogo, checkout y gestión de pedidos.

---

# Información General

| Campo        | Valor      |
| ------------ | ---------- |
| Protocolo    | HTTP/HTTPS |
| Formato      | JSON       |
| Codificación | UTF-8      |
| Arquitectura | REST       |
| Versión API  | v1         |

---

# Endpoint de Verificación

## Obtener Estado del Servicio

### Request

```http
GET /api/inventario
```

### Headers

```http
Accept: application/json
Content-Type: application/json
```

### Response Exitosa

**HTTP 200 OK**

```json
{
  "success": true
}
```

### Definición de Campos

| Campo   | Tipo    | Obligatorio | Descripción                            |
| ------- | ------- | ----------- | -------------------------------------- |
| success | Boolean | Sí          | Indica que el servicio está disponible |

---

# Endpoint de Consulta de Inventario

## Consultar Disponibilidad de Producto

### Request

```http
GET /api/inventario/{productoId}
```

### Parámetros

| Parámetro  | Tipo | Requerido | Descripción                |
| ---------- | ---- | --------- | -------------------------- |
| productoId | UUID | Sí        | Identificador del producto |

### Response Exitosa

**HTTP 200 OK**

```json
{
  "success": true,
  "data": {
    "productoId": "8f2c4f1a-1234-5678-9012-abcdef123456",
    "stockDisponible": 120,
    "stockReservado": 10,
    "stockTotal": 130
  }
}
```

### Response Producto No Encontrado

**HTTP 404 Not Found**

```json
{
  "success": false,
  "error": {
    "code": "PRODUCT_NOT_FOUND",
    "message": "Producto no encontrado"
  }
}
```

---

# Endpoint de Validación de Stock

## Validar Disponibilidad para Compra

### Request

```http
POST /api/inventario/validar
```

### Body

```json
{
  "productos": [
    {
      "productoId": "8f2c4f1a-1234-5678-9012-abcdef123456",
      "cantidad": 2
    }
  ]
}
```

### Response Exitosa

```json
{
  "success": true,
  "data": {
    "disponible": true
  }
}
```

### Response Sin Stock

```json
{
  "success": false,
  "data": {
    "disponible": false
  }
}
```

---

# Endpoint de Reserva de Inventario

## Reservar Stock

### Request

```http
POST /api/inventario/reservar
```

### Body

```json
{
  "ordenId": "ORD-10001",
  "productos": [
    {
      "productoId": "8f2c4f1a-1234-5678-9012-abcdef123456",
      "cantidad": 2
    }
  ]
}
```

### Response Exitosa

```json
{
  "success": true,
  "data": {
    "reservaId": "RES-50001"
  }
}
```

---

# Endpoint de Liberación de Reserva

## Liberar Inventario Reservado

### Request

```http
POST /api/inventario/liberar
```

### Body

```json
{
  "reservaId": "RES-50001"
}
```

### Response Exitosa

```json
{
  "success": true
}
```

---

# Endpoint de Confirmación de Descuento de Stock

## Confirmar Venta

### Request

```http
POST /api/inventario/confirmar
```

### Body

```json
{
  "ordenId": "ORD-10001"
}
```

### Response Exitosa

```json
{
  "success": true
}
```

---

# Códigos de Estado HTTP

| Código | Descripción             |
| ------ | ----------------------- |
| 200    | Operación exitosa       |
| 201    | Recurso creado          |
| 400    | Solicitud inválida      |
| 401    | No autorizado           |
| 404    | Recurso no encontrado   |
| 409    | Conflicto de inventario |
| 500    | Error interno           |

---

# Códigos de Error

| Código                | Descripción             |
| --------------------- | ----------------------- |
| PRODUCT_NOT_FOUND     | Producto inexistente    |
| INSUFFICIENT_STOCK    | Inventario insuficiente |
| INVALID_REQUEST       | Solicitud inválida      |
| INVENTORY_LOCK_FAILED | Error de reserva        |
| INTERNAL_ERROR        | Error interno           |

---

# Reglas de Negocio

## RN-INV-001

El stock disponible nunca puede ser negativo.

## RN-INV-002

No se puede reservar más inventario del disponible.

## RN-INV-003

Toda reserva debe estar asociada a una orden.

## RN-INV-004

La confirmación de venta descuenta definitivamente el stock.

## RN-INV-005

Las reservas expiradas deben liberarse automáticamente.

---

# Seguridad

* Uso obligatorio de HTTPS.
* Validación de autenticación.
* Validación de autorización por rol.
* Validación de entradas.
* Registro de auditoría.
* Protección contra manipulación de parámetros.

---

# Compatibilidad

## Consumidores

* Módulo Catálogo.
* Módulo Checkout.
* Módulo Pagos.
* Módulo Administración.

---

# Versionado

| Versión | Estado |
| ------- | ------ |
| v1      | Activa |

---

# Trazabilidad

| Artefacto       | Referencia             |
| --------------- | ---------------------- |
| spec.md         | Especificación Técnica |
| casos-uso.md    | Casos de Uso           |
| user-stories.md | Historias de Usuario   |
| testing.md      | Plan de Pruebas        |

---

# Estado

**Versión:** 1.0

**Estado:** Aprobado para Desarrollo
