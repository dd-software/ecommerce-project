# Casos de Uso

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo G - Administración**

---

# CU-G-001 Flujo Principal de Administración

## Objetivo

Permitir a los administradores gestionar la configuración, monitoreo, usuarios administrativos, roles y auditoría de la plataforma e-commerce, garantizando el control operativo y la seguridad del sistema.

---

## Actores

### Actor Principal

* Administrador del Sistema

### Actores Secundarios

* Operador Administrativo
* Servicio de Auditoría
* Sistema de Autenticación
* Módulos de Catálogo, Inventario, Checkout y Pagos

---

## Descripción

El módulo de Administración centraliza las funciones de gestión operativa de la plataforma, permitiendo administrar usuarios, roles, configuraciones globales, monitoreo de servicios y consulta de auditorías.

---

## Precondiciones

1. El usuario está autenticado.
2. El usuario posee permisos administrativos.
3. Los servicios principales de la plataforma están disponibles.
4. Existe una sesión válida.

---

## Disparador

El administrador accede al panel de administración para realizar tareas de gestión o monitoreo.

---

## Flujo Principal

| Paso | Acción                                               |
| ---- | ---------------------------------------------------- |
| 1    | El administrador accede al módulo de administración. |
| 2    | El sistema valida autenticación y permisos.          |
| 3    | Se muestra el panel administrativo.                  |
| 4    | El administrador selecciona una operación.           |
| 5    | El sistema ejecuta la acción solicitada.             |
| 6    | Se registran los eventos en auditoría.               |
| 7    | El sistema confirma la operación realizada.          |
| 8    | Finaliza el proceso.                                 |

---

## Flujos Alternativos

### FA-001 Gestión de Usuarios

| Paso | Acción                                                         |
| ---- | -------------------------------------------------------------- |
| 1    | El administrador accede a la gestión de usuarios.              |
| 2    | Consulta, crea, modifica o desactiva usuarios administrativos. |
| 3    | El sistema guarda los cambios.                                 |
| 4    | Se registra la actividad en auditoría.                         |

---

### FA-002 Gestión de Roles

| Paso | Acción                                       |
| ---- | -------------------------------------------- |
| 1    | El administrador consulta roles disponibles. |
| 2    | Asigna o modifica permisos.                  |
| 3    | El sistema valida restricciones.             |
| 4    | Se actualiza la configuración de acceso.     |

---

### FA-003 Configuración del Sistema

| Paso | Acción                                              |
| ---- | --------------------------------------------------- |
| 1    | El administrador accede a configuraciones globales. |
| 2    | Modifica parámetros autorizados.                    |
| 3    | El sistema valida los cambios.                      |
| 4    | Se almacenan las nuevas configuraciones.            |

---

### FA-004 Consulta de Auditoría

| Paso | Acción                                           |
| ---- | ------------------------------------------------ |
| 1    | El administrador accede al historial de eventos. |
| 2    | Aplica filtros de búsqueda.                      |
| 3    | El sistema recupera registros coincidentes.      |
| 4    | Se muestran los resultados.                      |

---

### FA-005 Monitoreo de Servicios

| Paso | Acción                                              |
| ---- | --------------------------------------------------- |
| 1    | El administrador consulta el estado de los módulos. |
| 2    | El sistema obtiene métricas y disponibilidad.       |
| 3    | Se muestran indicadores operativos.                 |

---

## Flujos de Excepción

### FE-001 Usuario No Autorizado

| Paso | Acción                                |
| ---- | ------------------------------------- |
| 1    | Un usuario intenta acceder al módulo. |
| 2    | El sistema verifica permisos.         |
| 3    | Se deniega el acceso.                 |
| 4    | El evento queda registrado.           |

---

### FE-002 Error de Configuración

| Paso | Acción                                              |
| ---- | --------------------------------------------------- |
| 1    | El administrador intenta guardar cambios inválidos. |
| 2    | El sistema detecta inconsistencias.                 |
| 3    | Se rechaza la actualización.                        |
| 4    | Se informa el error.                                |

---

### FE-003 Servicio No Disponible

| Paso | Acción                                           |
| ---- | ------------------------------------------------ |
| 1    | Se intenta consultar un servicio fuera de línea. |
| 2    | El sistema detecta indisponibilidad.             |
| 3    | Se informa el incidente.                         |
| 4    | El evento es auditado.                           |

---

## Postcondiciones

### Éxito

* La operación administrativa fue ejecutada correctamente.
* La información queda actualizada.
* Se registra auditoría de la acción.

### Fallo

* No se realizan cambios inconsistentes.
* El incidente queda registrado.
* Se informa el error al usuario.

---

## Reglas de Negocio

### RN-ADM-001

Solo usuarios con permisos administrativos pueden acceder al módulo.

### RN-ADM-002

Toda acción administrativa debe registrarse en auditoría.

### RN-ADM-003

Las configuraciones críticas requieren validación previa.

### RN-ADM-004

Los cambios de permisos deben respetar la jerarquía de roles.

### RN-ADM-005

Las configuraciones deben mantenerse consistentes en todos los módulos.

---

## Prioridad

Alta

---

## Frecuencia de Uso

Alta

---

## Requisitos Relacionados

| Código     | Descripción               |
| ---------- | ------------------------- |
| RF-ADM-001 | Gestión de usuarios       |
| RF-ADM-002 | Gestión de roles          |
| RF-ADM-003 | Configuración del sistema |
| RF-ADM-004 | Monitoreo de servicios    |
| RF-ADM-005 | Consulta de auditoría     |
| RF-ADM-006 | Gestión de permisos       |

---

## APIs Relacionadas

### Estado del Servicio

```http
GET /api/administracion
```

### Respuesta

```json
{
  "success": true
}
```

### Usuarios

```http
GET /api/administracion/usuarios
```

### Roles

```http
GET /api/administracion/roles
```

### Configuración

```http
GET /api/administracion/configuracion
PUT /api/administracion/configuracion
```

### Auditoría

```http
GET /api/administracion/auditoria
```

### Monitoreo

```http
GET /api/administracion/estado
```

---

## Criterios de Aceptación

* El acceso está restringido a usuarios autorizados.
* La gestión de usuarios funciona correctamente.
* La gestión de roles funciona correctamente.
* Las configuraciones pueden consultarse y modificarse.
* El monitoreo refleja el estado real de los servicios.
* Las auditorías son accesibles y consistentes.
* Todas las operaciones quedan registradas.
* Los contratos API son cumplidos.

---

## Dependencias

* Módulo de Autenticación.
* Servicio de Auditoría.
* Base de Datos.
* Catálogo.
* Inventario.
* Checkout.
* Pagos.

---

## Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Especificación Técnica  | spec.md                 |
| Historias de Usuario    | user-stories.md         |
| Testing                 | testing.md              |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |

---

## Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
