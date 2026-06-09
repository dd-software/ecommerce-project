# Testing

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Definir la estrategia, alcance, casos de prueba y criterios de validación para garantizar la calidad, integridad y correcto funcionamiento del proceso de checkout.

---

# Alcance de Pruebas

Las pruebas cubren:

* Inicio del checkout.
* Recuperación de productos desde el carrito.
* Validación de inventario.
* Cálculo de totales.
* Validación de información de entrega.
* Generación de órdenes.
* Integración con pagos.
* API de checkout.
* Seguridad.
* Rendimiento.
* Auditoría.

---

# Estrategia de Testing

| Tipo de Prueba | Objetivo                             |
| -------------- | ------------------------------------ |
| Unitarias      | Validar componentes individuales     |
| Integración    | Verificar interacción entre módulos  |
| Funcionales    | Validar reglas de negocio            |
| Aceptación     | Confirmar cumplimiento de requisitos |
| Seguridad      | Validar controles de protección      |
| Rendimiento    | Medir comportamiento bajo carga      |

---

# Pruebas Unitarias

## Objetivo

Validar el correcto funcionamiento de cada componente del módulo.

### UT-CHK-001 Recuperación de Carrito

**Descripción**

Validar la obtención de productos desde el carrito activo.

**Resultado Esperado**

* Lista de productos recuperada correctamente.
* Cantidades válidas.

---

### UT-CHK-002 Validación de Inventario

**Descripción**

Verificar la validación de stock disponible.

**Resultado Esperado**

* Confirmación correcta cuando existe stock.
* Rechazo cuando el stock es insuficiente.

---

### UT-CHK-003 Cálculo de Totales

**Descripción**

Validar cálculos de subtotal, impuestos y total.

**Resultado Esperado**

* Montos calculados correctamente.

---

### UT-CHK-004 Validación de Datos de Entrega

**Descripción**

Verificar validaciones de campos obligatorios y formatos.

**Resultado Esperado**

* Datos válidos aceptados.
* Datos inválidos rechazados.

---

### UT-CHK-005 Generación de Orden

**Descripción**

Validar la creación de una orden preliminar.

**Resultado Esperado**

* Orden creada correctamente.
* Estado inicial asignado.

---

### UT-CHK-006 Registro de Auditoría

**Descripción**

Verificar el registro de eventos relevantes.

**Resultado Esperado**

* Eventos almacenados correctamente.

---

# Pruebas de Integración

## Objetivo

Validar la interacción entre checkout y sistemas dependientes.

### IT-CHK-001 Integración con Carrito

**Descripción**

Validar recuperación de productos desde el módulo de carrito.

**Resultado Esperado**

* Productos obtenidos correctamente.
* Datos consistentes.

---

### IT-CHK-002 Integración con Inventario

**Descripción**

Validar consulta de disponibilidad de stock.

**Resultado Esperado**

* Inventario sincronizado.
* Disponibilidad correcta.

---

### IT-CHK-003 Integración con Pagos

**Descripción**

Validar transferencia de información al módulo de pagos.

**Resultado Esperado**

* Orden enviada correctamente.
* Totales consistentes.

---

### IT-CHK-004 Integración con Auditoría

**Descripción**

Validar registro de eventos de checkout.

**Resultado Esperado**

* Eventos registrados correctamente.

---

### IT-CHK-005 Endpoint de Verificación

**Endpoint**

```http
GET /api/checkout
```

**Resultado Esperado**

```json
{
  "success": true
}
```

**Validaciones**

* HTTP 200.
* JSON válido.
* Cumplimiento del contrato API.

---

### IT-CHK-006 Error de Dependencias

**Descripción**

Simular indisponibilidad de inventario o pagos.

**Resultado Esperado**

* Error controlado.
* Registro del incidente.
* No se generan órdenes inconsistentes.

---

# Pruebas Funcionales

## FT-CHK-001 Inicio de Checkout

**Escenario**

Usuario con productos en carrito.

**Resultado Esperado**

* El checkout inicia correctamente.

---

## FT-CHK-002 Producto Sin Stock

**Escenario**

Producto con inventario insuficiente.

**Resultado Esperado**

* El sistema impide continuar.

---

## FT-CHK-003 Información de Entrega Inválida

**Escenario**

Dirección incompleta o incorrecta.

**Resultado Esperado**

* Se muestran errores de validación.

---

## FT-CHK-004 Generación Exitosa de Orden

**Escenario**

Todos los datos son válidos.

**Resultado Esperado**

* Orden generada exitosamente.

---

# Pruebas de Aceptación

## Objetivo

Verificar que el módulo satisface las necesidades del negocio.

### AT-CHK-001 Finalización Correcta del Checkout

**Dado** un usuario autenticado

**Y** productos disponibles en el carrito

**Cuando** inicia el checkout

**Entonces** el sistema genera una orden válida.

---

### AT-CHK-002 Inventario Insuficiente

**Dado** un producto sin stock

**Cuando** el usuario intenta finalizar la compra

**Entonces** el sistema bloquea el proceso.

---

### AT-CHK-003 Validación de Datos

**Dado** información de entrega incorrecta

**Cuando** el usuario intenta continuar

**Entonces** el sistema solicita correcciones.

---

### AT-CHK-004 Integración con Pagos

**Dado** una orden válida

**Cuando** finaliza el checkout

**Entonces** la orden queda lista para el procesamiento del pago.

---

# Pruebas de Seguridad

## ST-CHK-001 Comunicación Segura

**Validación**

* Uso obligatorio de HTTPS.

---

## ST-CHK-002 Validación de Entradas

**Validación**

* Rechazo de datos inválidos.
* Prevención de manipulación de parámetros.

---

## ST-CHK-003 Protección de Información

**Validación**

* No exposición de datos sensibles.
* Gestión segura de errores.

---

## ST-CHK-004 Integridad de Montos

**Validación**

* Los montos son recalculados del lado servidor.
* No se aceptan precios manipulados.

---

## ST-CHK-005 Auditoría

**Validación**

* Registro de operaciones críticas.
* Trazabilidad completa de eventos.

---

# Pruebas de Rendimiento

## PT-CHK-001 Tiempo de Respuesta

**Escenario**

100 usuarios concurrentes.

**Resultado Esperado**

* Tiempo promedio menor a 2 segundos.

---

## PT-CHK-002 Disponibilidad

**Escenario**

Operación continua durante una hora.

**Resultado Esperado**

* Disponibilidad superior al 99%.

---

## PT-CHK-003 Concurrencia de Inventario

**Escenario**

Múltiples usuarios compran el mismo producto simultáneamente.

**Resultado Esperado**

* No se produce sobreventa.
* Se mantiene consistencia del stock.

---

# Criterios de Éxito

Las pruebas serán consideradas satisfactorias cuando:

* [ ] 100% de las pruebas unitarias sean exitosas.
* [ ] 100% de las pruebas de integración sean exitosas.
* [ ] 100% de las pruebas funcionales sean exitosas.
* [ ] 100% de las pruebas de aceptación sean exitosas.
* [ ] No existan defectos críticos abiertos.
* [ ] Los requisitos de seguridad sean aprobados.
* [ ] Los requisitos de rendimiento sean cumplidos.

---

# Evidencias Requeridas

* Reportes de pruebas unitarias.
* Resultados de integración.
* Evidencias funcionales.
* Evidencias de aceptación.
* Reportes de seguridad.
* Resultados de rendimiento.
* Registro de incidencias detectadas.

---

# Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Casos de Uso            | casos-uso.md            |
| Historias de Usuario    | user-stories.md         |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |
| Especificación Técnica  | spec.md                 |
| Backlog Técnico         | tareas.md               |

---

# Aprobación

| Rol                    | Estado    |
| ---------------------- | --------- |
| QA                     | Pendiente |
| Líder Técnico          | Pendiente |
| Arquitecto de Software | Pendiente |
| Product Owner          | Pendiente |

---

# Estado del Plan

**Versión:** 1.0

**Estado:** Pendiente de Ejecución
