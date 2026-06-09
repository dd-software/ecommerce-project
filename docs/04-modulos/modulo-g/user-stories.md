# Historias de Usuario

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**G - Administración**

## Objetivo

Definir las historias de usuario asociadas a las funcionalidades administrativas de la plataforma, garantizando la trazabilidad entre requisitos, casos de uso, pruebas y criterios de aceptación.

---

# US-G-001 Acceso al Panel de Administración

## Historia

**Como** administrador del sistema
**Quiero** acceder al módulo de Administración
**Para** gestionar la configuración y operación general de la plataforma.

## Prioridad

Alta

## Criterios de Aceptación

* El usuario debe estar autenticado.
* El usuario debe poseer permisos administrativos.
* El sistema debe mostrar el panel administrativo.
* Los accesos deben quedar registrados en auditoría.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-002 Gestionar Usuarios Administrativos

## Historia

**Como** administrador
**Quiero** administrar usuarios con acceso al sistema
**Para** controlar quién puede realizar operaciones administrativas.

## Prioridad

Alta

## Criterios de Aceptación

* Se pueden consultar usuarios existentes.
* Se pueden crear nuevos usuarios administrativos.
* Se pueden modificar usuarios existentes.
* Se pueden desactivar usuarios.
* Las acciones quedan registradas en auditoría.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-003 Gestionar Roles y Permisos

## Historia

**Como** administrador
**Quiero** administrar roles y permisos
**Para** controlar el acceso a funcionalidades específicas.

## Prioridad

Alta

## Criterios de Aceptación

* Se pueden consultar roles disponibles.
* Se pueden asignar roles a usuarios.
* Los permisos se aplican correctamente.
* Las restricciones de acceso son respetadas.
* Los cambios quedan auditados.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-004 Configurar Parámetros del Sistema

## Historia

**Como** administrador
**Quiero** modificar configuraciones globales
**Para** adaptar la plataforma a las necesidades operativas.

## Prioridad

Alta

## Criterios de Aceptación

* Se puede consultar la configuración actual.
* Se pueden modificar parámetros autorizados.
* Los cambios son validados antes de almacenarse.
* Los cambios permanecen después de reiniciar el sistema.
* Las modificaciones quedan registradas.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-005 Monitorear el Estado de la Plataforma

## Historia

**Como** administrador
**Quiero** visualizar el estado de los servicios
**Para** supervisar la operación de la plataforma.

## Prioridad

Media

## Criterios de Aceptación

* Se muestra el estado de Catálogo.
* Se muestra el estado de Inventario.
* Se muestra el estado de Checkout.
* Se muestra el estado de Pagos.
* La información es consistente y actualizada.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-006 Consultar Auditorías

## Historia

**Como** administrador o auditor
**Quiero** consultar registros de actividad
**Para** realizar seguimiento y trazabilidad de acciones.

## Prioridad

Alta

## Criterios de Aceptación

* Se pueden consultar eventos registrados.
* Se pueden aplicar filtros por fecha y módulo.
* Los registros contienen usuario, fecha y acción.
* Los registros son consistentes e inmutables.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-007 Gestionar Accesos y Seguridad

## Historia

**Como** administrador de seguridad
**Quiero** controlar permisos y accesos administrativos
**Para** proteger los recursos críticos del sistema.

## Prioridad

Alta

## Criterios de Aceptación

* Solo usuarios autorizados acceden a funciones administrativas.
* Los permisos son evaluados en cada solicitud.
* Los intentos de acceso fallidos son registrados.
* Las sesiones inválidas son rechazadas.
* Funciona correctamente.
* Cumple contratos API.

---

# US-G-008 Consultar Estado Operativo General

## Historia

**Como** administrador
**Quiero** visualizar indicadores operativos globales
**Para** detectar incidentes y tomar decisiones rápidamente.

## Prioridad

Media

## Criterios de Aceptación

* Se muestran métricas relevantes.
* Los indicadores reflejan información actualizada.
* Se identifican servicios degradados.
* La información es accesible desde el dashboard.
* Funciona correctamente.
* Cumple contratos API.

---

# Dependencias

* Módulo de Autenticación.
* Servicio de Auditoría.
* Catálogo.
* Inventario.
* Checkout.
* Pagos.
* Base de Datos.

---

# Reglas de Negocio Relacionadas

| Código     | Descripción                                              |
| ---------- | -------------------------------------------------------- |
| RN-ADM-001 | Solo usuarios autenticados pueden acceder.               |
| RN-ADM-002 | Solo usuarios autorizados pueden administrar el sistema. |
| RN-ADM-003 | Toda acción administrativa debe quedar auditada.         |
| RN-ADM-004 | Los cambios de configuración deben validarse.            |
| RN-ADM-005 | Los permisos deben respetar la jerarquía organizacional. |

---

# Trazabilidad

| Historia | Caso de Uso | Requisito  |
| -------- | ----------- | ---------- |
| US-G-001 | CU-G-001    | RF-ADM-001 |
| US-G-002 | CU-G-001    | RF-ADM-001 |
| US-G-003 | CU-G-001    | RF-ADM-002 |
| US-G-004 | CU-G-001    | RF-ADM-003 |
| US-G-005 | CU-G-001    | RF-ADM-004 |
| US-G-006 | CU-G-001    | RF-ADM-005 |
| US-G-007 | CU-G-001    | RF-ADM-006 |
| US-G-008 | CU-G-001    | RF-ADM-004 |

---

# Definición de Terminado (Definition of Done)

Una historia de usuario será considerada completada cuando:

* [ ] La funcionalidad esté implementada.
* [ ] Las pruebas unitarias estén aprobadas.
* [ ] Las pruebas de integración estén aprobadas.
* [ ] Los criterios de aceptación estén aprobados.
* [ ] La documentación esté actualizada.
* [ ] No existan defectos críticos abiertos.
* [ ] Se cumpla el contrato API correspondiente.

---

# Estado

**Versión:** 1.0

**Estado:** En Elaboración

**Aprobación:** Pendiente
