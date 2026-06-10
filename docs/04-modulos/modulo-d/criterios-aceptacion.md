# Criterios de Aceptación

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Validar que el proceso de checkout permita al usuario finalizar correctamente la preparación de una compra, verificando productos, inventario, información de entrega y generación de la orden previa al pago.

---

# Checklist Funcional del Módulo D

## CU-D-001 Flujo Principal de Checkout

### Inicio del Checkout

* [ ] El usuario puede iniciar el proceso de checkout desde el carrito.
* [ ] El sistema recupera correctamente los productos seleccionados.
* [ ] El checkout solo puede iniciarse cuando existen productos en el carrito.
* [ ] El usuario visualiza el resumen de la compra antes de continuar.

---

### Validación de Inventario

* [ ] El sistema verifica la disponibilidad de todos los productos.
* [ ] Los productos sin stock son identificados correctamente.
* [ ] El usuario es informado cuando un producto no posee disponibilidad.
* [ ] No se permite continuar con productos sin stock suficiente.

---

### Cálculo de la Compra

* [ ] El subtotal se calcula correctamente.
* [ ] Los impuestos se calculan correctamente.
* [ ] Los descuentos aplicables son considerados.
* [ ] El total final de la compra es correcto.
* [ ] Los montos mostrados coinciden con los almacenados en la orden.

---

### Validación de Datos de Entrega

* [ ] Los campos obligatorios son validados.
* [ ] Las direcciones incompletas son rechazadas.
* [ ] Los formatos inválidos son detectados.
* [ ] El usuario puede corregir la información ingresada.

---

### Generación de Orden

* [ ] El sistema genera una orden preliminar correctamente.
* [ ] La orden contiene todos los productos seleccionados.
* [ ] La orden almacena cantidades y precios correctos.
* [ ] La orden queda disponible para el proceso de pago.

---

### Integración con Carrito

* [ ] Los productos del carrito son recuperados correctamente.
* [ ] Las modificaciones recientes del carrito se reflejan en el checkout.
* [ ] El contenido del carrito permanece consistente durante el proceso.

---

### Integración con Inventario

* [ ] El sistema consulta correctamente la disponibilidad de stock.
* [ ] Los cambios de inventario son considerados antes de generar la orden.
* [ ] Se evita la generación de órdenes con stock insuficiente.

---

### Integración con Pagos

* [ ] La orden generada puede ser enviada al módulo de pagos.
* [ ] Los importes transferidos son correctos.
* [ ] La información necesaria para el pago está disponible.

---

### API de Checkout

#### Endpoint

```http
GET /api/checkout
```

#### Validaciones

* [ ] El endpoint responde con código HTTP 200 cuando la operación es exitosa.
* [ ] La respuesta cumple el contrato definido.
* [ ] El contenido es entregado en formato JSON.
* [ ] El campo `success` es retornado correctamente.

#### Respuesta Esperada

```json
{
  "success": true
}
```

---

### Seguridad

* [ ] Todas las comunicaciones utilizan HTTPS.
* [ ] La información sensible no es expuesta.
* [ ] Las entradas son validadas antes de ser procesadas.
* [ ] Los errores son manejados sin revelar información interna.
* [ ] Los eventos relevantes son registrados para auditoría.

---

### Manejo de Errores

* [ ] El sistema maneja correctamente errores de inventario.
* [ ] El sistema maneja correctamente errores de generación de órdenes.
* [ ] El sistema informa problemas de integración de forma controlada.
* [ ] Los mensajes de error son comprensibles para el usuario.

---

### Auditoría y Trazabilidad

* [ ] El inicio del checkout queda registrado.
* [ ] La generación de órdenes queda registrada.
* [ ] Los errores relevantes quedan registrados.
* [ ] Los eventos son trazables para auditoría.

---

### Requisitos No Funcionales

* [ ] El tiempo de respuesta promedio es menor a 2 segundos.
* [ ] La disponibilidad del servicio es igual o superior al 99%.
* [ ] El sistema soporta múltiples procesos de checkout concurrentes.
* [ ] El comportamiento es estable bajo carga normal.

---

# Resultado de Aceptación

El módulo será considerado aceptado cuando:

* [ ] El 100% de los criterios funcionales sean aprobados.
* [ ] El 100% de los criterios de integración sean aprobados.
* [ ] El 100% de los criterios de seguridad sean aprobados.
* [ ] No existan defectos críticos abiertos.
* [ ] Las pruebas unitarias sean satisfactorias.
* [ ] Las pruebas de integración sean satisfactorias.
* [ ] Las pruebas de aceptación sean satisfactorias.
* [ ] La documentación esté actualizada.

---

# Evidencias Requeridas

* [ ] Resultados de pruebas unitarias.
* [ ] Resultados de pruebas de integración.
* [ ] Evidencias de pruebas funcionales.
* [ ] Evidencias de pruebas de aceptación.
* [ ] Evidencias de validación de inventario.
* [ ] Evidencias de integración con pagos.
* [ ] Evidencias de cumplimiento del contrato API.

---

# Aprobación

| Rol                    | Responsable | Estado    |
| ---------------------- | ----------- | --------- |
| QA                     |             | Pendiente |
| Líder Técnico          |             | Pendiente |
| Arquitecto de Software |             | Pendiente |
| Product Owner          |             | Pendiente |

**Estado Final:** ☐ Aprobado ☐ Rechazado

---

# Trazabilidad

| Artefacto              | Referencia      |
| ---------------------- | --------------- |
| API Contract           | api-contract.md |
| Casos de Uso           | casos-uso.md    |
| Historias de Usuario   | user-stories.md |
| Testing                | testing.md      |
| Checklist              | checklist.md    |
| Riesgos                | riesgos.md      |
| Especificación Técnica | spec.md         |
