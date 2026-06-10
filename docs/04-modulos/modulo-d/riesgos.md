# Riesgos

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Identificar, evaluar y mitigar los riesgos asociados al proceso de checkout, garantizando la continuidad operativa, la integridad de la información y una experiencia de compra confiable para el usuario.

---

# Matriz de Riesgos

| ID        | Riesgo                                    | Categoría    | Probabilidad | Impacto | Nivel   |
| --------- | ----------------------------------------- | ------------ | ------------ | ------- | ------- |
| R-CHK-001 | Indisponibilidad del servicio de checkout | Dependencias | Media        | Alto    | Alto    |
| R-CHK-002 | Fallo de integración con carrito          | Integración  | Media        | Alto    | Alto    |
| R-CHK-003 | Fallo de integración con inventario       | Integración  | Alta         | Alto    | Crítico |
| R-CHK-004 | Inconsistencia en cálculo de totales      | Funcional    | Media        | Alto    | Alto    |
| R-CHK-005 | Error en generación de órdenes            | Funcional    | Media        | Alto    | Alto    |
| R-CHK-006 | Exposición de información sensible        | Seguridad    | Baja         | Crítico | Alto    |
| R-CHK-007 | Manipulación de datos de checkout         | Seguridad    | Media        | Crítico | Crítico |
| R-CHK-008 | Integración incorrecta con pagos          | Integración  | Media        | Crítico | Crítico |
| R-CHK-009 | Procesamiento concurrente de stock        | Integración  | Alta         | Crítico | Crítico |
| R-CHK-010 | Falta de trazabilidad de operaciones      | Auditoría    | Baja         | Medio   | Medio   |

---

# Riesgos de Dependencias

## R-CHK-001 Indisponibilidad del Servicio de Checkout

### Descripción

El servicio principal de checkout puede dejar de responder debido a problemas de infraestructura, despliegues fallidos o errores internos.

### Impacto

* Imposibilidad de finalizar compras.
* Pérdida de ventas.
* Afectación de la experiencia del cliente.

### Mitigación

* Arquitectura de alta disponibilidad.
* Monitoreo continuo.
* Alertas automáticas.
* Procedimientos de recuperación ante fallos.

---

## R-CHK-002 Dependencia del Módulo de Carrito

### Descripción

El checkout depende de la correcta recuperación de productos y cantidades almacenadas en el carrito.

### Impacto

* Pedidos incorrectos.
* Información inconsistente.
* Errores durante la compra.

### Mitigación

* Validaciones de integridad.
* Pruebas de integración.
* Monitoreo de servicios dependientes.

---

# Riesgos de Integración

## R-CHK-003 Fallo de Integración con Inventario

### Descripción

La disponibilidad real de productos puede no coincidir con la información utilizada durante el checkout.

### Impacto

* Venta de productos sin stock.
* Cancelaciones posteriores.
* Insatisfacción del cliente.

### Mitigación

* Validación de stock en tiempo real.
* Bloqueo temporal de inventario.
* Sincronización de datos.

---

## R-CHK-004 Integración Incorrecta con Pagos

### Descripción

Errores en la transferencia de información hacia el módulo de pagos.

### Impacto

* Cobros incorrectos.
* Pagos rechazados.
* Inconsistencias financieras.

### Mitigación

* Contratos API validados.
* Pruebas de integración automatizadas.
* Trazabilidad de transacciones.

---

## R-CHK-005 Condiciones de Carrera en Inventario

### Descripción

Múltiples usuarios pueden intentar comprar simultáneamente las últimas unidades disponibles.

### Impacto

* Sobreventa.
* Órdenes inválidas.
* Reclamaciones de clientes.

### Mitigación

* Reserva temporal de stock.
* Control transaccional.
* Bloqueos optimistas o pesimistas según diseño.

---

# Riesgos Funcionales

## R-CHK-006 Error en Cálculo de Totales

### Descripción

Los cálculos de subtotal, impuestos, descuentos o total pueden ser incorrectos.

### Impacto

* Cobros erróneos.
* Problemas contables.
* Reclamos de clientes.

### Mitigación

* Validaciones automáticas.
* Pruebas unitarias de cálculos.
* Revisión de reglas de negocio.

---

## R-CHK-007 Error en Generación de Órdenes

### Descripción

La orden no se genera correctamente después de validar la compra.

### Impacto

* Pérdida de pedidos.
* Compras incompletas.
* Incidencias operativas.

### Mitigación

* Manejo transaccional.
* Reintentos controlados.
* Auditoría de operaciones.

---

# Riesgos de Seguridad

## R-CHK-008 Exposición de Información Sensible

### Descripción

Información de clientes o pedidos puede ser expuesta accidentalmente.

### Impacto

* Incumplimiento normativo.
* Riesgos reputacionales.
* Posibles sanciones.

### Mitigación

* Uso obligatorio de HTTPS.
* Enmascaramiento de datos sensibles.
* Control de accesos.

---

## R-CHK-009 Manipulación de Datos de Checkout

### Descripción

Un usuario malicioso puede intentar modificar precios, cantidades o descuentos.

### Impacto

* Pérdidas económicas.
* Fraude.
* Alteración de órdenes.

### Mitigación

* Validación del lado servidor.
* Recalcular montos antes de generar la orden.
* Auditoría de cambios.

---

## R-CHK-010 Acceso No Autorizado

### Descripción

Usuarios sin permisos pueden intentar ejecutar operaciones de checkout.

### Impacto

* Acciones fraudulentas.
* Exposición de información.
* Riesgos operativos.

### Mitigación

* Integración con autenticación.
* Validación de sesiones.
* Controles de autorización.

---

# Riesgos de Auditoría

## R-CHK-011 Falta de Trazabilidad

### Descripción

No registrar adecuadamente los eventos del proceso de checkout.

### Impacto

* Dificultad para investigar incidencias.
* Falta de evidencia operativa.
* Problemas de cumplimiento.

### Mitigación

* Registro completo de eventos.
* Centralización de logs.
* Monitoreo continuo.

---

# Plan de Monitoreo

## Indicadores

* Número de checkouts iniciados.
* Número de órdenes generadas.
* Errores de validación de inventario.
* Errores de integración con pagos.
* Tiempo promedio de checkout.
* Disponibilidad del servicio.
* Incidentes de seguridad detectados.

---

# Controles de Seguridad Requeridos

* Uso obligatorio de HTTPS.
* Validación de sesiones activas.
* Protección contra manipulación de parámetros.
* Validación de datos de entrada.
* Registro de auditoría.
* Gestión segura de errores.
* Monitoreo de actividades sospechosas.

---

# Revisión de Riesgos

Los riesgos deberán revisarse:

* Antes de cada despliegue a producción.
* Después de incidentes operativos.
* Durante auditorías técnicas.
* Cuando se incorporen nuevas integraciones.

---

# Estado de Riesgos

| Estado      | Descripción                                       |
| ----------- | ------------------------------------------------- |
| Abierto     | Riesgo identificado sin mitigación completa       |
| Mitigado    | Riesgo controlado mediante acciones implementadas |
| Monitoreado | Riesgo bajo seguimiento                           |
| Cerrado     | Riesgo eliminado o aceptado formalmente           |

---

# Aprobación

| Rol                      | Estado    |
| ------------------------ | --------- |
| Arquitecto de Software   | Pendiente |
| Líder Técnico            | Pendiente |
| QA                       | Pendiente |
| Responsable de Seguridad | Pendiente |
| Product Owner            | Pendiente |

---

# Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Casos de Uso            | casos-uso.md            |
| Historias de Usuario    | user-stories.md         |
| Testing                 | testing.md              |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Especificación Técnica  | spec.md                 |

---

# Estado

**Versión:** 1.0

**Estado:** En Análisis

**Última Revisión:** Pendiente
