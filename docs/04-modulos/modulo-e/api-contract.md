# API Contract – Pasarela de Pago

## Endpoint

### Obtener Configuración de Pasarela de Pago

**Método:** `GET`

**Ruta:** `/api/pasarela-de-pago`

---

## Descripción

Obtiene la configuración y el estado de la pasarela de pago utilizada por la plataforma Ecommerce.

Este endpoint permite consultar la disponibilidad del servicio de pagos, los métodos habilitados y la información necesaria para iniciar transacciones de compra.

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
GET /api/pasarela-de-pago
```

### Parámetros

No requiere parámetros.

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

| Campo   | Tipo    | Descripción                       |
| ------- | ------- | --------------------------------- |
| success | boolean | Indica si la consulta fue exitosa |

---

## Respuesta Extendida

```json
{
  "success": true,
  "data": {
    "estado": "activo",
    "proveedor": "Stripe",
    "moneda": "CLP",
    "metodos_pago": [
      "Tarjeta de Crédito",
      "Tarjeta de Débito",
      "Transferencia Bancaria"
    ]
  }
}
```

### Campos de Respuesta

| Campo        | Tipo   | Descripción                   |
| ------------ | ------ | ----------------------------- |
| estado       | string | Estado de la pasarela         |
| proveedor    | string | Nombre del proveedor de pagos |
| moneda       | string | Moneda utilizada              |
| metodos_pago | array  | Métodos de pago habilitados   |

---

## Códigos de Error

### 401 – Usuario No Autorizado

```json
{
  "success": false,
  "message": "Usuario no autenticado"
}
```

### 403 – Acceso Denegado

```json
{
  "success": false,
  "message": "Permisos insuficientes"
}
```

### 503 – Servicio No Disponible

```json
{
  "success": false,
  "message": "Pasarela de pago no disponible"
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

La pasarela debe encontrarse activa para procesar pagos.

### RN-002

Solo usuarios autenticados pueden consultar la configuración de pago.

### RN-003

Los métodos de pago disponibles deben estar previamente configurados por el administrador.

### RN-004

La moneda utilizada debe coincidir con la configuración global del sistema.

### RN-005

Las credenciales sensibles de integración nunca deben exponerse en la respuesta.

---

## Dependencias

* Módulo de Autenticación
* Módulo de Pedidos
* Módulo de Inventario
* Servicio de Pasarela de Pago
* Base de Datos MySQL

---

## Casos de Uso Relacionados

* CU-PAGO-001 Consultar Métodos de Pago
* CU-PAGO-002 Procesar Pago
* CU-PAGO-003 Confirmar Transacción
* CU-PAGO-004 Registrar Pago
* CU-PAGO-005 Generar Comprobante

---

## Consideraciones Técnicas

* Todas las respuestas se entregan en formato JSON.
* Comunicación mediante HTTPS obligatoria.
* Compatible con frontend web y aplicación móvil.
* Tiempo máximo esperado de respuesta: 3 segundos.
* Deben registrarse eventos y errores en logs de auditoría.
* Las credenciales de integración deben almacenarse de forma segura mediante variables de entorno.

---

## Versionado

**Versión API:** v1

```http
GET /api/v1/pasarela-de-pago
```

---

## Seguridad

* Uso obligatorio de JWT.
* Protección contra ataques de repetición (Replay Attacks).
* Validación de origen de solicitudes.
* Registro de intentos fallidos de acceso.
* Cifrado de comunicaciones mediante TLS 1.2 o superior.
