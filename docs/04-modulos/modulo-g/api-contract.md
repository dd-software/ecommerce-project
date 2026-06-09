# API Contract

## Proyecto

Plataforma E-commerce con Gestión de Inventarios y Pagos

## Módulo

**G - Administración**

## Versión

1.0

---

# Objetivo

Definir los contratos API del módulo de Administración, encargado de la gestión operativa y administrativa de la plataforma, incluyendo usuarios administrativos, configuración, monitoreo y gestión general del sistema.

---

# Estándares Generales

## Protocolo

HTTPS

## Formato de Datos

JSON

## Codificación

UTF-8

## Autenticación

Bearer Token (JWT)

### Header Requerido

```http
Authorization: Bearer <token>
```

---

# Endpoint de Verificación

## Obtener Estado del Servicio

### Request

```http
GET /api/administracion
```

### Response Exitosa (200)

```json
{
  "success": true
}
```

### Response Error (500)

```json
{
  "success": false,
  "message": "Error interno del servidor"
}
```

---

# Gestión de Usuarios Administrativos

## Listar Usuarios Administrativos

### Request

```http
GET /api/administracion/usuarios
```

### Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": "USR-001",
      "nombre": "Administrador Principal",
      "email": "admin@empresa.com",
      "rol": "ADMIN"
    }
  ]
}
```

---

## Obtener Usuario Administrativo

### Request

```http
GET /api/administracion/usuarios/{id}
```

### Parámetros

| Campo | Tipo   | Requerido |
| ----- | ------ | --------- |
| id    | String | Sí        |

### Response (200)

```json
{
  "success": true,
  "data": {
    "id": "USR-001",
    "nombre": "Administrador Principal",
    "email": "admin@empresa.com",
    "rol": "ADMIN"
  }
}
```

---

# Gestión de Roles

## Listar Roles

### Request

```http
GET /api/administracion/roles
```

### Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": "ROL-001",
      "nombre": "ADMIN"
    },
    {
      "id": "ROL-002",
      "nombre": "OPERADOR"
    }
  ]
}
```

---

# Configuración del Sistema

## Consultar Configuración

### Request

```http
GET /api/administracion/configuracion
```

### Response (200)

```json
{
  "success": true,
  "data": {
    "moneda": "USD",
    "timezone": "America/Santiago",
    "modoMantenimiento": false
  }
}
```

---

## Actualizar Configuración

### Request

```http
PUT /api/administracion/configuracion
```

### Body

```json
{
  "moneda": "USD",
  "timezone": "America/Santiago",
  "modoMantenimiento": false
}
```

### Response (200)

```json
{
  "success": true,
  "message": "Configuración actualizada correctamente"
}
```

---

# Monitoreo del Sistema

## Consultar Estado General

### Request

```http
GET /api/administracion/estado
```

### Response (200)

```json
{
  "success": true,
  "data": {
    "catalogo": "UP",
    "inventario": "UP",
    "checkout": "UP",
    "pagos": "UP"
  }
}
```

---

# Auditoría

## Consultar Eventos

### Request

```http
GET /api/administracion/auditoria
```

### Query Params

| Parámetro   | Tipo   | Requerido |
| ----------- | ------ | --------- |
| fechaInicio | Date   | No        |
| fechaFin    | Date   | No        |
| modulo      | String | No        |

### Response (200)

```json
{
  "success": true,
  "data": [
    {
      "id": "AUD-001",
      "modulo": "Inventario",
      "evento": "Reserva creada",
      "fecha": "2026-01-15T10:30:00Z"
    }
  ]
}
```

---

# Códigos de Estado HTTP

| Código | Descripción           |
| ------ | --------------------- |
| 200    | Operación exitosa     |
| 201    | Recurso creado        |
| 400    | Solicitud inválida    |
| 401    | No autenticado        |
| 403    | Sin permisos          |
| 404    | Recurso no encontrado |
| 409    | Conflicto de negocio  |
| 500    | Error interno         |

---

# Modelo de Error Estándar

```json
{
  "success": false,
  "error": {
    "code": "ERROR_CODE",
    "message": "Descripción del error"
  }
}
```

---

# Reglas de Seguridad

* Todas las solicitudes requieren autenticación.
* Las operaciones administrativas requieren autorización basada en roles.
* Todas las acciones deben registrarse en auditoría.
* El acceso a configuraciones críticas debe limitarse a administradores.
* Las respuestas no deben exponer información sensible.

---

# Requisitos de Rendimiento

| Métrica                      | Objetivo     |
| ---------------------------- | ------------ |
| Tiempo de respuesta promedio | < 2 segundos |
| Disponibilidad               | ≥ 99%        |
| Tasa de error                | < 1%         |

---

# Compatibilidad

* PHP 8.2+
* MySQL 8+
* Apache 2.4+
* Clientes REST compatibles con JSON

---

# Trazabilidad

| Documento Relacionado   | Referencia              |
| ----------------------- | ----------------------- |
| spec.md                 | Especificación técnica  |
| casos-uso.md            | Casos de uso            |
| user-stories.md         | Historias de usuario    |
| testing.md              | Plan de pruebas         |
| criterios-aceptacion.md | Criterios de aceptación |

---

# Estado

**Versión:** 1.0

**Estado:** Aprobado para Desarrollo
