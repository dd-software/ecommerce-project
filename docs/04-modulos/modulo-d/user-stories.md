# Historias de Usuario

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Objetivo

Definir las historias de usuario relacionadas con el proceso de checkout, permitiendo al usuario revisar su compra, validar disponibilidad de productos, registrar información de entrega y preparar la orden para el pago.

---

# US-D-001 Realizar Checkout

**Como** usuario

**Quiero** utilizar Checkout

**Para** completar una tarea de negocio.

### Prioridad

Alta

### Descripción

El usuario debe poder iniciar el proceso de checkout para revisar los productos seleccionados, validar la información necesaria para la compra y generar una orden lista para ser procesada por el módulo de pagos.

### Criterios de Aceptación

* Funciona correctamente.
* Cumple contratos.
* El usuario puede iniciar el checkout desde el carrito.
* El sistema recupera correctamente los productos seleccionados.
* El sistema valida la disponibilidad de inventario.
* El sistema calcula correctamente subtotal, impuestos, descuentos y total.
* El usuario puede ingresar y confirmar la información de entrega.
* El sistema genera una orden preliminar válida.
* La orden queda disponible para el módulo de pagos.
* Los errores son gestionados de forma controlada.

### Reglas de Negocio Asociadas

* RN-CHK-001: El carrito debe contener al menos un producto.
* RN-CHK-002: Todos los productos deben tener stock disponible.
* RN-CHK-003: Los cálculos monetarios se realizan en el servidor.
* RN-CHK-004: La orden debe generarse antes del procesamiento del pago.

---

# US-D-002 Validar Inventario Durante Checkout

**Como** usuario

**Quiero** que el sistema valide la disponibilidad de los productos

**Para** evitar comprar productos sin stock.

### Prioridad

Alta

### Criterios de Aceptación

* El sistema consulta inventario antes de generar la orden.
* Los productos sin stock son identificados correctamente.
* El usuario recibe una notificación cuando existe falta de disponibilidad.
* No se permite continuar con productos sin inventario suficiente.

---

# US-D-003 Confirmar Información de Entrega

**Como** usuario

**Quiero** registrar mis datos de entrega

**Para** recibir correctamente los productos adquiridos.

### Prioridad

Media

### Criterios de Aceptación

* Los campos obligatorios son validados.
* Las direcciones inválidas son rechazadas.
* El usuario puede corregir errores antes de continuar.
* La información queda asociada a la orden.

---

# US-D-004 Generar Orden de Compra

**Como** usuario

**Quiero** que el sistema genere una orden

**Para** continuar con el proceso de pago.

### Prioridad

Alta

### Criterios de Aceptación

* La orden se genera correctamente.
* Los productos seleccionados son incluidos.
* Los montos calculados son correctos.
* Se asigna un identificador único de orden.
* El estado inicial de la orden es válido.

---

# US-D-005 Preparar Pago

**Como** usuario

**Quiero** que mi orden quede lista para el pago

**Para** completar la compra.

### Prioridad

Alta

### Criterios de Aceptación

* La orden es enviada correctamente al módulo de pagos.
* La información transferida es consistente.
* Los importes coinciden con los mostrados durante el checkout.
* No se pierde información durante la integración.

---

# Dependencias

* Módulo de Autenticación.
* Módulo de Carrito.
* Módulo de Inventario.
* Módulo de Pagos.
* Servicio de Auditoría.

---

# Trazabilidad

| Historia | Caso de Uso |
| -------- | ----------- |
| US-D-001 | CU-D-001    |
| US-D-002 | CU-D-001    |
| US-D-003 | CU-D-001    |
| US-D-004 | CU-D-001    |
| US-D-005 | CU-D-001    |

---

# Definición de Hecho (Definition of Done)

Una historia de usuario será considerada completada cuando:

* [ ] La funcionalidad esté implementada.
* [ ] Existan pruebas unitarias asociadas.
* [ ] Existan pruebas de integración asociadas.
* [ ] Los criterios de aceptación sean aprobados.
* [ ] La documentación esté actualizada.
* [ ] No existan defectos críticos abiertos.
* [ ] El contrato API sea cumplido.

---

# Estado

**Versión:** 1.0

**Estado:** Pendiente de Implementación
