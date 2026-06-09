# Testing

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Validar el correcto funcionamiento del módulo de checkout mediante pruebas unitarias, integración y aceptación, garantizando la calidad del proceso de compra antes de la ejecución del pago.

---

# Estrategia de Pruebas

| Tipo de Prueba | Objetivo                                        |
| -------------- | ----------------------------------------------- |
| Unitarias      | Validar componentes individuales                |
| Integración    | Verificar interacción entre módulos             |
| Aceptación     | Confirmar cumplimiento de requisitos de negocio |

---

# Pruebas Unitarias

## UT-CHK-001 Validación de Carrito

### Objetivo

Verificar que el sistema recupere correctamente los productos del carrito.

### Precondiciones

* Existe un carrito activo.

### Resultado Esperado

* Se obtiene la lista correcta de productos.
* Se recuperan cantidades válidas.

---

## UT-CHK-002 Validación de Inventario

### Objetivo

Verificar la disponibilidad de stock.

### Resultado Esperado

* El sistema confirma disponibilidad cuando existe stock.
* El sistema rechaza productos sin stock.

---

## UT-CHK-003 Cálculo de Totales

### Objetivo

Validar el cálculo de montos.

### Casos

* Subtotal.
* Impuestos.
* Descuentos.
* Costos de envío.
* Total final.

### Resultado Esperado

* Los cálculos son correctos.

---

## UT-CHK-004 Validación de Datos de Entrega

### Objetivo

Validar los datos ingresados por el usuario.

### Resultado Esperado

* Se aceptan datos válidos.
* Se rechazan datos inválidos.

---

## UT-CHK-005 Generación de Orden

### Objetivo

Verificar la creación de la orden preliminar.

### Resultado Esperado

* Se genera una orden válida.
* Se asigna estado inicial correctamente.

---

## UT-CHK-006 Auditoría

### Objetivo

Validar el registro de eventos.

### Resultado Esperado

* Los eventos quedan almacenados correctamente.

---

# Pruebas de Integración

## IT-CHK-001 Integración con Carrito

### Objetivo

Verificar la comunicación con el módulo de carrito.

### Resultado Esperado

* Recuperación correcta de productos.
* Consistencia de información.

---

## IT-CHK-002 Integración con Inventario

### Objetivo

Validar consulta de disponibilidad de stock.

### Resultado Esperado

* Información sincronizada.
* Validación correcta de existencias.

---

## IT-CHK-003 Integración con Pagos

### Objetivo

Validar transferencia de información hacia el módulo de pagos.

### Resultado Esperado

* Orden preparada correctamente.
* Totales consistentes.

---

## IT-CHK-004 Integración con Auditoría

### Objetivo

Validar registro de eventos de checkout.

### Resultado Esperado

* Eventos registrados correctamente.

---

## IT-CHK-005 Verificación del Contrato API

### Endpoint

```http
GET /api/checkout
```

### Respuesta Esperada

```json
{
  "success": true
}
```

### Validaciones

* Código HTTP 200.
* Respuesta JSON válida.
* Cumplimiento del contrato API.

---

## IT-CHK-006 Manejo de Dependencias

### Objetivo

Validar comportamiento ante fallos de servicios externos.

### Resultado Esperado

* Error controlado.
* Registro del incidente.
* Sin pérdida de información.

---

# Pruebas de Aceptación

## AT-CHK-001 Checkout Exitoso

### Dado

Un usuario autenticado con productos en el carrito.

### Cuando

Inicia el proceso de checkout.

### Entonces

Se genera una orden válida.

---

## AT-CHK-002 Producto Sin Inventario

### Dado

Un producto sin stock disponible.

### Cuando

El usuario intenta continuar.

### Entonces

El sistema bloquea la operación e informa el problema.

---

## AT-CHK-003 Datos de Entrega Inválidos

### Dado

Información de entrega incorrecta.

### Cuando

El usuario intenta finalizar el checkout.

### Entonces

El sistema solicita correcciones.

---

## AT-CHK-004 Preparación para Pago

### Dado

Una orden válida.

### Cuando

El checkout finaliza exitosamente.

### Entonces

La orden queda disponible para el módulo de pagos.

---

# Casos de Prueba Resumidos

| ID         | Tipo        | Descripción                |
| ---------- | ----------- | -------------------------- |
| UT-CHK-001 | Unitaria    | Recuperación de carrito    |
| UT-CHK-002 | Unitaria    | Validación de inventario   |
| UT-CHK-003 | Unitaria    | Cálculo de totales         |
| UT-CHK-004 | Unitaria    | Validación de entrega      |
| UT-CHK-005 | Unitaria    | Generación de orden        |
| IT-CHK-001 | Integración | Integración con carrito    |
| IT-CHK-002 | Integración | Integración con inventario |
| IT-CHK-003 | Integración | Integración con pagos      |
| IT-CHK-005 | Integración | Contrato API               |
| AT-CHK-001 | Aceptación  | Checkout exitoso           |
| AT-CHK-002 | Aceptación  | Inventario insuficiente    |
| AT-CHK-003 | Aceptación  | Datos inválidos            |
| AT-CHK-004 | Aceptación  | Preparación para pago      |

---

# Cobertura Esperada

| Área                   | Cobertura Mínima |
| ---------------------- | ---------------- |
| Lógica de negocio      | 80%              |
| Servicios              | 80%              |
| Validaciones           | 90%              |
| Integraciones críticas | 100%             |

---

# Criterios de Éxito

* [ ] Todas las pruebas unitarias aprobadas.
* [ ] Todas las pruebas de integración aprobadas.
* [ ] Todas las pruebas de aceptación aprobadas.
* [ ] Sin defectos críticos abiertos.
* [ ] Cumplimiento del contrato API.
* [ ] Integración exitosa con inventario y pagos.
* [ ] Evidencias de pruebas documentadas.

---

# Evidencias Requeridas

* Reportes de ejecución.
* Logs de pruebas.
* Evidencias de integración.
* Resultados de cobertura.
* Registro de incidencias.

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

# Estado

**Versión:** 1.0

**Estado:** Pendiente de Ejecución
