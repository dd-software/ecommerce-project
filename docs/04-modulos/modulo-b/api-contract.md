# API Contract – Carrito de Compras

## Endpoint

### Obtener Carrito de Compras

**Método:** `GET`

**Ruta:** `/api/carrito`

---

## Descripción

Obtiene la información del carrito de compras activo del usuario autenticado.

Este endpoint permite consultar el estado actual del carrito, incluyendo productos agregados, cantidades, precios y resumen de compra.

---

## Autenticación

Requiere autenticación mediante token JWT.

### Headers

```http
Authorization: Bearer {token}
Content-Type: application/json
```

---

## Request

### URL

```http
GET /api/carrito
```

### Parámetros

No requiere parámetros de entrada.

### Body

No aplica.

---

## Response

### Código 200 – Operación Exitosa

```json
{
  "success": true
}
```

### Estructura

| Campo   | Tipo    | Descripción                        |
| ------- | ------- | ---------------------------------- |
| success | boolean | Indica si la operación fue exitosa |

---

## Posibles Respuestas Futuras

### Carrito con Información Completa

```json
{
  "success": true,
  "data": {
    "id": 1,
    "items": [
      {
        "producto_id": 10,
        "nombre": "Mouse Gamer RGB",
        "cantidad": 2,
        "precio_unitario": 19990,
        "subtotal": 39980
      }
    ],
    "total": 39980
  }
}
```

---

## Códigos de Error

### 401 – No Autorizado

```json
{
  "success": false,
  "message": "Usuario no autenticado"
}
```

### 500 – Error Interno

```json
{
  "success": false,
  "message": "Error interno del servidor"
}
```

---

## Reglas de Negocio

### RN-001

El usuario debe estar autenticado para acceder a su carrito.

### RN-002

Cada usuario posee un único carrito activo.

### RN-003

Los productos eliminados del catálogo no deben aparecer en el carrito.

### RN-004

Los subtotales deben calcularse como:

```text
subtotal = cantidad × precio_unitario
```

### RN-005

El total corresponde a la suma de todos los subtotales.

---

## Dependencias

* Servicio de Autenticación
* Servicio de Carrito
* Base de Datos MySQL
* Módulo de Inventario

---

## Casos de Uso Relacionados

* CU-001 Ver Carrito
* CU-002 Agregar Producto al Carrito
* CU-003 Actualizar Cantidad
* CU-004 Eliminar Producto del Carrito
* CU-005 Procesar Compra

---

## Consideraciones Técnicas

* Respuesta en formato JSON.
* UTF-8 como codificación estándar.
* Compatible con frontend web y aplicación móvil.
* Tiempo máximo esperado de respuesta: 2 segundos.
* Debe registrarse la consulta en logs de auditoría.

```
```
