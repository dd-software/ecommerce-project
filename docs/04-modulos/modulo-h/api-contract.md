# API Contract – Integración de Servicios Externos

## Endpoint

### Consultar Estado de Integración

**Método:** `GET`

**Ruta:** `/api/integracion`

---

## Descripción

Obtiene el estado de las integraciones configuradas en la plataforma Ecommerce.

Este endpoint permite verificar la disponibilidad y conectividad de los servicios externos utilizados por el sistema, incluyendo pasarelas de pago, servicios de envío, sistemas de inventario y herramientas de notificación.

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
GET /api/integracion
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

| Campo   | Tipo    | Descripción                       |
| ------- | ------- | --------------------------------- |
| success | boolean | Indica si la consulta fue exitosa |

---

## Respuesta Extendida

```json
{
  "success": true,
  "data": {
    "integraciones": [
      {
        "nombre": "PasarelaPago",
        "estado": "activo"
      },
      {
        "nombre": "ServicioEnvios",
        "estado": "activo"
      },
      {
        "nombre": "InventarioExterno",
        "estado": "inactivo"
      }
    ]
  }
}
```

### Campos de Respuesta

| Campo         | Tipo   | Descripción                        |
| ------------- | ------ | ---------------------------------- |
| integraciones | array  | Lista de integraciones registradas |
| nombre        | string | Nombre de la integración           |
| estado        | string | Estado actual de la integración    |

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
  "message": "Acceso restringido"
}
```

### 503 – Servicio Externo No Disponible

```json
{
  "success": false,
  "message": "Servicio de integración no disponible"
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

Solo usuarios autenticados pueden consultar las integraciones configuradas.

### RN-002

Las integraciones deben estar registradas previamente en el sistema.

### RN-003

El estado de cada integración debe actualizarse automáticamente mediante verificaciones periódicas.

### RN-004

La información sensible como tokens, claves API y credenciales nunca debe exponerse en la respuesta.

### RN-005

Las integraciones críticas deben generar alertas cuando su estado cambie a inactivo.

---

## Dependencias

* Módulo de Autenticación
* Módulo de Inventario
* Módulo de Pagos
* Módulo de Notificaciones
* Base de Datos MySQL
* Servicios Externos Integrados

---

## Casos de Uso Relacionados

* CU-INT-001 Consultar Integraciones
* CU-INT-002 Registrar Integración
* CU-INT-003 Actualizar Configuración de Integración
* CU-INT-004 Verificar Estado de Servicio
* CU-INT-005 Gestionar Credenciales de Integración

---

## Consideraciones Técnicas

* Respuestas en formato JSON.
* Comunicación mediante HTTPS.
* Compatible con aplicaciones web y móviles.
* Tiempo máximo esperado de respuesta: 3 segundos.
* Registro de auditoría para consultas y errores.
* Validación de disponibilidad de servicios externos antes de responder.

---

## Versionado

**Versión API:** v1

```http
GET /api/v1/integracion
```

---

## Seguridad

* Autenticación JWT obligatoria.
* Uso de TLS 1.2 o superior.
* Protección contra accesos no autorizados.
* Gestión segura de credenciales mediante variables de entorno.
* Registro y monitoreo de eventos relacionados con integraciones.