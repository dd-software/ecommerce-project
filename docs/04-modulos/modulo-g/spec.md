# Módulo G - Administración

## Información General

| Campo       | Valor                                                    |
| ----------- | -------------------------------------------------------- |
| Proyecto    | Plataforma E-commerce con Gestión de Inventarios y Pagos |
| Módulo      | Administración                                           |
| Código      | G                                                        |
| Versión     | 1.0                                                      |
| Metodología | SDD (Software Design Description)                        |

---

# Objetivo

Implementar las funcionalidades administrativas de la plataforma e-commerce para gestionar usuarios, roles, configuraciones globales, monitoreo de servicios y auditoría operativa, garantizando el control, la seguridad y la gobernanza del sistema.

---

# Alcance

El módulo de Administración permitirá:

* Gestión de usuarios administrativos.
* Gestión de roles y permisos.
* Configuración global del sistema.
* Monitoreo de módulos y servicios.
* Consulta de auditorías.
* Administración de parámetros operativos.
* Gestión de acceso administrativo.
* Supervisión del estado general de la plataforma.

## Fuera de Alcance

* Gestión de productos.
* Gestión directa de inventario.
* Procesamiento de pagos.
* Gestión de pedidos.
* Funcionalidades del cliente final.

---

# Dependencias

## Dependencias Funcionales

* Módulo de Autenticación.
* Módulo de Catálogo.
* Módulo de Inventario.
* Módulo de Checkout.
* Módulo de Pagos.
* Servicio de Auditoría.

## Dependencias Técnicas

* API REST.
* PHP 8.2+.
* MySQL 8+.
* Apache HTTP Server.
* JWT para autenticación.
* Sistema de monitoreo.

---

# Requisitos Funcionales

## RF-ADM-001 Gestión de Usuarios

El sistema debe permitir administrar usuarios con acceso al panel administrativo.

### Funcionalidades

* Consultar usuarios.
* Crear usuarios.
* Modificar usuarios.
* Desactivar usuarios.
* Asignar roles.

---

## RF-ADM-002 Gestión de Roles

El sistema debe permitir administrar permisos y perfiles de acceso.

### Funcionalidades

* Consultar roles.
* Asignar permisos.
* Gestionar privilegios.

---

## RF-ADM-003 Configuración del Sistema

El sistema debe permitir consultar y modificar configuraciones globales.

### Ejemplos

* Moneda.
* Zona horaria.
* Parámetros operativos.
* Configuración de mantenimiento.

---

## RF-ADM-004 Monitoreo de Servicios

El sistema debe mostrar el estado de los módulos de la plataforma.

### Servicios Monitoreados

* Catálogo.
* Inventario.
* Checkout.
* Pagos.
* Base de datos.

---

## RF-ADM-005 Auditoría

El sistema debe permitir consultar eventos registrados.

### Funcionalidades

* Búsqueda de eventos.
* Filtrado por fecha.
* Filtrado por módulo.
* Consulta de actividad administrativa.

---

## RF-ADM-006 Gestión de Permisos

El sistema debe validar permisos antes de ejecutar operaciones administrativas.

---

# Requisitos No Funcionales

## RNF-ADM-001 Seguridad

* Autenticación obligatoria.
* Control de acceso basado en roles (RBAC).
* Comunicación cifrada mediante HTTPS.

---

## RNF-ADM-002 Disponibilidad

* Disponibilidad mínima del servicio: 99%.

---

## RNF-ADM-003 Rendimiento

* Tiempo promedio de respuesta inferior a 2 segundos.

---

## RNF-ADM-004 Escalabilidad

* Capacidad para soportar crecimiento de usuarios administrativos y eventos auditados.

---

## RNF-ADM-005 Auditabilidad

* Todas las acciones críticas deben quedar registradas.

---

# Arquitectura del Módulo

```text
                 +------------------+
                 | Autenticación    |
                 +--------+---------+
                          |
                          v
+------------------------------------------------+
|              Administración                    |
+------------------------------------------------+
| Usuarios | Roles | Configuración | Auditoría   |
| Monitoreo | Permisos | Dashboard Administrativo |
+------------------------------------------------+
      |          |          |          |
      v          v          v          v
 Catálogo   Inventario   Checkout   Pagos
      |
      v
 Base de Datos
```

---

# Modelo Conceptual

## UsuarioAdministrativo

| Campo         | Tipo     |
| ------------- | -------- |
| id            | UUID     |
| nombre        | String   |
| email         | String   |
| rol           | String   |
| estado        | String   |
| fechaCreacion | DateTime |

---

## Rol

| Campo       | Tipo   |
| ----------- | ------ |
| id          | UUID   |
| nombre      | String |
| descripcion | String |

---

## Permiso

| Campo       | Tipo   |
| ----------- | ------ |
| id          | UUID   |
| codigo      | String |
| descripcion | String |

---

## Configuracion

| Campo              | Tipo     |
| ------------------ | -------- |
| id                 | UUID     |
| clave              | String   |
| valor              | String   |
| fechaActualizacion | DateTime |

---

## EventoAuditoria

| Campo   | Tipo     |
| ------- | -------- |
| id      | UUID     |
| usuario | String   |
| modulo  | String   |
| accion  | String   |
| fecha   | DateTime |

---

# Contratos API Relacionados

## Estado del Servicio

```http
GET /api/administracion
```

### Respuesta

```json
{
  "success": true
}
```

---

## Gestión de Usuarios

```http
GET /api/administracion/usuarios
GET /api/administracion/usuarios/{id}
```

---

## Gestión de Roles

```http
GET /api/administracion/roles
```

---

## Configuración

```http
GET /api/administracion/configuracion
PUT /api/administracion/configuracion
```

---

## Auditoría

```http
GET /api/administracion/auditoria
```

---

## Monitoreo

```http
GET /api/administracion/estado
```

---

# Flujo Principal

1. El administrador inicia sesión.
2. El sistema valida credenciales y permisos.
3. Se presenta el panel administrativo.
4. El usuario selecciona una operación.
5. El sistema ejecuta la acción solicitada.
6. Se actualizan los datos correspondientes.
7. Se registra la actividad en auditoría.
8. Se devuelve el resultado de la operación.

---

# Reglas de Negocio

## RN-ADM-001

Solo usuarios autenticados pueden acceder al módulo.

## RN-ADM-002

Solo usuarios con permisos administrativos pueden ejecutar operaciones administrativas.

## RN-ADM-003

Toda acción administrativa debe quedar registrada en auditoría.

## RN-ADM-004

Los cambios de configuración deben validarse antes de almacenarse.

## RN-ADM-005

La asignación de permisos debe respetar la jerarquía organizacional.

## RN-ADM-006

Los registros de auditoría no pueden ser modificados por usuarios estándar.

---

# Manejo de Errores

## Usuario No Autorizado

### Condición

El usuario no posee permisos suficientes.

### Acción

* Retornar HTTP 403.
* Registrar intento de acceso.

---

## Configuración Inválida

### Condición

Parámetros incorrectos.

### Acción

* Rechazar actualización.
* Mostrar mensaje descriptivo.

---

## Servicio No Disponible

### Condición

Dependencia externa fuera de servicio.

### Acción

* Registrar incidente.
* Mostrar estado degradado.

---

# Seguridad

## Autenticación

* JWT obligatorio.
* Expiración de sesión configurable.

## Autorización

* Control RBAC.
* Validación de permisos por operación.

## Protección de Datos

* Validación de entradas.
* Sanitización de parámetros.
* Ocultamiento de información sensible.

## Auditoría

* Registro obligatorio de eventos administrativos.

---

# Integraciones

## Autenticación

Validación de identidad y permisos.

## Catálogo

Consulta de estado operativo.

## Inventario

Consulta de estado operativo.

## Checkout

Consulta de estado operativo.

## Pagos

Consulta de estado operativo.

## Auditoría

Registro de eventos administrativos.

---

# Métricas

| Métrica              | Objetivo     |
| -------------------- | ------------ |
| Disponibilidad       | ≥ 99%        |
| Tiempo de respuesta  | < 2 segundos |
| Errores críticos     | 0            |
| Cobertura de pruebas | ≥ 80%        |
| Eventos auditados    | 100%         |

---

# Criterios de Aceptación

* Gestión de usuarios operativa.
* Gestión de roles operativa.
* Configuración funcional.
* Monitoreo funcional.
* Auditoría funcional.
* Seguridad implementada.
* Contratos API cumplidos.
* Integraciones verificadas.

---

# Riesgos Relacionados

| ID        | Riesgo                             |
| --------- | ---------------------------------- |
| R-ADM-001 | Fallo de autenticación             |
| R-ADM-002 | Base de datos no disponible        |
| R-ADM-003 | Fallo de auditoría                 |
| R-ADM-007 | Acceso no autorizado               |
| R-ADM-008 | Escalada de privilegios            |
| R-ADM-009 | Exposición de información sensible |

---

# Trazabilidad

| Documento               | Referencia              |
| ----------------------- | ----------------------- |
| api-contract.md         | Contratos API           |
| casos-uso.md            | Casos de uso            |
| user-stories.md         | Historias de usuario    |
| testing.md              | Plan de pruebas         |
| checklist.md            | Checklist de validación |
| criterios-aceptacion.md | Criterios de aceptación |
| riesgos.md              | Gestión de riesgos      |

---

# Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
