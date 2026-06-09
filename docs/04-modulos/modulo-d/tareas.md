# Backlog Técnico

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Definir las actividades técnicas necesarias para diseñar, desarrollar, probar y documentar el módulo de checkout.

---

# Diseño

## TSK-CHK-DIS-001 Análisis de Requisitos

**Descripción:** Analizar requisitos funcionales, reglas de negocio y criterios de aceptación del módulo.

**Entregable:** spec.md

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DIS-002 Diseño de Arquitectura

**Descripción:** Diseñar la arquitectura e integración del servicio de checkout.

**Entregable:** Diagramas y documentación técnica.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DIS-003 Diseño de Contratos API

**Descripción:** Definir y validar contratos API del módulo.

**Entregable:** api-contract.md

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DIS-004 Diseño de Modelo de Datos

**Descripción:** Diseñar entidades de Orden y Detalle de Orden.

**Entregable:** Modelo de datos documentado.

**Prioridad:** Alta

**Estado:** Pendiente

---

# Desarrollo

## TSK-CHK-DEV-001 Implementar Inicio de Checkout

**Descripción:** Permitir al usuario iniciar el proceso de checkout desde el carrito.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-002 Implementar Recuperación de Carrito

**Descripción:** Obtener productos, cantidades y precios asociados al carrito activo.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-003 Implementar Validación de Inventario

**Descripción:** Verificar disponibilidad de stock antes de generar la orden.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-004 Implementar Cálculo de Totales

**Descripción:** Calcular subtotal, impuestos, descuentos, costos de envío y total final.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-005 Implementar Validación de Datos de Entrega

**Descripción:** Validar información requerida para despacho o entrega.

**Prioridad:** Media

**Estado:** Pendiente

---

## TSK-CHK-DEV-006 Implementar Generación de Orden

**Descripción:** Crear orden preliminar lista para ser enviada al módulo de pagos.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-007 Implementar Integración con Pagos

**Descripción:** Preparar y enviar información necesaria para el procesamiento del pago.

**Prioridad:** Alta

**Estado:** Pendiente

---

## TSK-CHK-DEV-008 Implementar Auditoría

**Descripción:** Registrar eventos relevantes del proceso de checkout.

**Prioridad:** Media

**Estado:** Pendiente

---

## TSK-CHK-DEV-009 Implementar Endpoint de Verificación

**Descripción:** Exponer endpoint de disponibilidad del servicio.

**Endpoint**

```http
GET /api/checkout
```

**Prioridad:** Media

**Estado:** Pendiente

---

## TSK-CHK-DEV-010 Implementar Manejo de Errores

**Descripción:** Gestionar errores de inventario, integración y generación de órdenes.

**Prioridad:** Alta

**Estado:** Pendiente

---

# Testing

## TSK-CHK-TST-001 Pruebas Unitarias

**Descripción:** Validar componentes individuales del módulo.

**Cobertura Objetivo:** ≥ 80%

**Estado:** Pendiente

---

## TSK-CHK-TST-002 Pruebas de Integración con Carrito

**Descripción:** Validar recuperación correcta de productos seleccionados.

**Estado:** Pendiente

---

## TSK-CHK-TST-003 Pruebas de Integración con Inventario

**Descripción:** Validar disponibilidad y reserva de stock.

**Estado:** Pendiente

---

## TSK-CHK-TST-004 Pruebas de Integración con Pagos

**Descripción:** Verificar transferencia correcta de información al módulo de pagos.

**Estado:** Pendiente

---

## TSK-CHK-TST-005 Pruebas Funcionales

**Descripción:** Validar el flujo completo de checkout.

**Estado:** Pendiente

---

## TSK-CHK-TST-006 Pruebas de Seguridad

**Descripción:** Verificar validación de entradas, autenticación y protección de datos.

**Estado:** Pendiente

---

## TSK-CHK-TST-007 Pruebas de Rendimiento

**Descripción:** Evaluar comportamiento bajo carga concurrente.

**Objetivo:** Tiempo promedio menor a 2 segundos.

**Estado:** Pendiente

---

## TSK-CHK-TST-008 Pruebas de Aceptación

**Descripción:** Validar criterios de aceptación definidos para el módulo.

**Estado:** Pendiente

---

# Documentación

## TSK-CHK-DOC-001 Documentar API Contract

**Descripción:** Mantener actualizado el contrato API.

**Entregable:** api-contract.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-002 Documentar Casos de Uso

**Descripción:** Mantener actualizado el flujo principal y alternativo.

**Entregable:** casos-uso.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-003 Documentar Historias de Usuario

**Descripción:** Mantener trazabilidad funcional.

**Entregable:** user-stories.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-004 Documentar Criterios de Aceptación

**Descripción:** Actualizar checklist funcional del módulo.

**Entregable:** criterios-aceptacion.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-005 Documentar Riesgos

**Descripción:** Actualizar matriz de riesgos y mitigaciones.

**Entregable:** riesgos.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-006 Actualizar Especificación Técnica

**Descripción:** Mantener alineación entre diseño e implementación.

**Entregable:** spec.md

**Estado:** Pendiente

---

## TSK-CHK-DOC-007 Documentar Evidencias de Testing

**Descripción:** Registrar resultados y evidencias de pruebas.

**Entregable:** testing.md

**Estado:** Pendiente

---

# Dependencias

| Tarea           | Dependencia                      |
| --------------- | -------------------------------- |
| TSK-CHK-DEV-001 | TSK-CHK-DIS-001                  |
| TSK-CHK-DEV-002 | TSK-CHK-DEV-001                  |
| TSK-CHK-DEV-003 | TSK-CHK-DEV-002                  |
| TSK-CHK-DEV-004 | TSK-CHK-DEV-002                  |
| TSK-CHK-DEV-005 | TSK-CHK-DEV-001                  |
| TSK-CHK-DEV-006 | TSK-CHK-DEV-003, TSK-CHK-DEV-004 |
| TSK-CHK-DEV-007 | TSK-CHK-DEV-006                  |
| TSK-CHK-TST-003 | TSK-CHK-DEV-003                  |
| TSK-CHK-TST-004 | TSK-CHK-DEV-007                  |
| TSK-CHK-TST-008 | Todas las tareas de desarrollo   |

---

# Definición de Hecho (Definition of Done)

Una tarea será considerada completada cuando:

* [ ] La implementación esté finalizada.
* [ ] Existan pruebas asociadas.
* [ ] Las pruebas sean exitosas.
* [ ] La documentación esté actualizada.
* [ ] El código haya sido revisado.
* [ ] No existan defectos críticos abiertos.
* [ ] Los criterios de aceptación estén aprobados.

---

# Resumen del Backlog

| Categoría     | Cantidad |
| ------------- | -------- |
| Diseño        | 4        |
| Desarrollo    | 10       |
| Testing       | 8        |
| Documentación | 7        |
| **Total**     | **29**   |

---

# Estado General

**Versión:** 1.0

**Estado:** Pendiente de Ejecución

**Última Actualización:** Por definir
