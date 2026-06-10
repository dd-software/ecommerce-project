# Riesgos

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo F - Inventario**

## Objetivo

Identificar, evaluar y definir estrategias de mitigación para los riesgos asociados al módulo de inventario, considerando dependencias, integraciones, disponibilidad, seguridad y consistencia de datos.

---

# Metodología de Evaluación

## Probabilidad

| Valor | Descripción                  |
| ----- | ---------------------------- |
| Baja  | Poco probable                |
| Media | Puede ocurrir ocasionalmente |
| Alta  | Ocurre frecuentemente        |

## Impacto

| Valor | Descripción                        |
| ----- | ---------------------------------- |
| Bajo  | Afectación menor                   |
| Medio | Afectación moderada                |
| Alto  | Impacto crítico sobre la operación |

---

# Riesgos de Dependencias

## R-INV-001 Indisponibilidad de Base de Datos

### Descripción

La base de datos de inventario no está disponible durante una operación.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Imposibilidad de consultar stock.
* Fallo en reservas de inventario.
* Interrupción del checkout.

### Mitigación

* Replicación de base de datos.
* Monitoreo continuo.
* Estrategias de recuperación automática.

---

## R-INV-002 Fallo de Servicio de Auditoría

### Descripción

No es posible registrar eventos de inventario.

### Probabilidad

Media

### Impacto

Medio

### Consecuencias

* Pérdida de trazabilidad.
* Dificultad para investigaciones posteriores.

### Mitigación

* Cola de eventos persistente.
* Reintentos automáticos.
* Almacenamiento temporal de registros.

---

# Riesgos de Integración

## R-INV-003 Error de Integración con Catálogo

### Descripción

El catálogo muestra información de disponibilidad desactualizada.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Venta de productos no disponibles.
* Mala experiencia del usuario.

### Mitigación

* Sincronización en tiempo real.
* Caché con expiración controlada.
* Validación cruzada con inventario.

---

## R-INV-004 Error de Integración con Checkout

### Descripción

El proceso de checkout no logra reservar inventario correctamente.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Órdenes inconsistentes.
* Posibles sobreventas.

### Mitigación

* Validaciones transaccionales.
* Pruebas de integración continuas.
* Monitoreo de reservas fallidas.

---

## R-INV-005 Error de Integración con Pagos

### Descripción

La confirmación de venta no actualiza correctamente el inventario.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Diferencias entre ventas y stock real.
* Inconsistencia de datos.

### Mitigación

* Operaciones idempotentes.
* Confirmaciones transaccionales.
* Auditoría de actualizaciones.

---

# Riesgos Operacionales

## R-INV-006 Sobreventa de Productos

### Descripción

Múltiples usuarios compran simultáneamente las mismas unidades disponibles.

### Probabilidad

Alta

### Impacto

Alto

### Consecuencias

* Inventario negativo.
* Incumplimiento de pedidos.

### Mitigación

* Bloqueo transaccional.
* Reserva inmediata de inventario.
* Control de concurrencia.

---

## R-INV-007 Reservas Huérfanas

### Descripción

Reservas permanecen activas después de cancelar una compra.

### Probabilidad

Media

### Impacto

Medio

### Consecuencias

* Stock bloqueado incorrectamente.
* Menor disponibilidad para otros usuarios.

### Mitigación

* Expiración automática.
* Procesos programados de limpieza.
* Monitoreo de reservas activas.

---

## R-INV-008 Datos Inconsistentes

### Descripción

El stock disponible, reservado y total no coinciden.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Errores de negocio.
* Información incorrecta para usuarios.

### Mitigación

* Validaciones periódicas.
* Conciliaciones automáticas.
* Auditorías programadas.

---

# Riesgos de Seguridad

## R-INV-009 Acceso No Autorizado

### Descripción

Usuarios sin permisos modifican inventario.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Manipulación de existencias.
* Pérdida de integridad de datos.

### Mitigación

* Control de acceso basado en roles.
* Autenticación obligatoria.
* Registro de actividades.

---

## R-INV-010 Manipulación de Solicitudes

### Descripción

Alteración maliciosa de parámetros enviados a la API.

### Probabilidad

Media

### Impacto

Alto

### Consecuencias

* Modificaciones indebidas de inventario.
* Datos corruptos.

### Mitigación

* Validación estricta de entradas.
* Sanitización de datos.
* Verificación de permisos.

---

## R-INV-011 Exposición de Información Sensible

### Descripción

Divulgación accidental de datos internos del sistema.

### Probabilidad

Baja

### Impacto

Alto

### Consecuencias

* Riesgos de seguridad.
* Exposición de arquitectura interna.

### Mitigación

* Gestión segura de errores.
* Ocultamiento de información sensible.
* Revisión periódica de logs.

---

# Riesgos de Rendimiento

## R-INV-012 Degradación Bajo Alta Carga

### Descripción

El servicio pierde rendimiento durante picos de tráfico.

### Probabilidad

Media

### Impacto

Medio

### Consecuencias

* Lentitud en consultas.
* Retrasos en checkout.

### Mitigación

* Escalabilidad horizontal.
* Caché para consultas frecuentes.
* Monitoreo de capacidad.

---

## R-INV-013 Cuellos de Botella en Consultas

### Descripción

Consultas de inventario generan alta carga sobre la base de datos.

### Probabilidad

Media

### Impacto

Medio

### Consecuencias

* Incremento de tiempos de respuesta.
* Saturación de recursos.

### Mitigación

* Optimización de índices.
* Caché distribuida.
* Ajuste de consultas críticas.

---

# Matriz Resumen

| ID        | Riesgo                             | Probabilidad | Impacto |
| --------- | ---------------------------------- | ------------ | ------- |
| R-INV-001 | Base de datos no disponible        | Media        | Alto    |
| R-INV-002 | Fallo de auditoría                 | Media        | Medio   |
| R-INV-003 | Error con catálogo                 | Media        | Alto    |
| R-INV-004 | Error con checkout                 | Media        | Alto    |
| R-INV-005 | Error con pagos                    | Media        | Alto    |
| R-INV-006 | Sobreventa                         | Alta         | Alto    |
| R-INV-007 | Reservas huérfanas                 | Media        | Medio   |
| R-INV-008 | Datos inconsistentes               | Media        | Alto    |
| R-INV-009 | Acceso no autorizado               | Media        | Alto    |
| R-INV-010 | Manipulación de solicitudes        | Media        | Alto    |
| R-INV-011 | Exposición de información sensible | Baja         | Alto    |
| R-INV-012 | Degradación por carga              | Media        | Medio   |
| R-INV-013 | Cuellos de botella                 | Media        | Medio   |

---

# Plan de Monitoreo

* Monitorear disponibilidad del servicio.
* Monitorear reservas activas.
* Monitorear diferencias de inventario.
* Monitorear tiempos de respuesta.
* Monitorear errores de integración.
* Monitorear eventos de seguridad.
* Monitorear operaciones de actualización de stock.

---

# Criterios de Revisión

Los riesgos deben revisarse:

* Antes de cada liberación.
* Después de incidentes críticos.
* Durante revisiones de arquitectura.
* Al incorporar nuevas integraciones.

---

# Estado

**Versión:** 1.0

**Estado:** Activo

**Última Revisión:** Pendiente
