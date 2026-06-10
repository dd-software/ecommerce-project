# Historias de Usuario – Módulo E: Pasarela de Pago

## US-E-001 – Realizar pago de una compra

**Como** cliente de la plataforma
**Quiero** realizar el pago de mi pedido mediante una pasarela de pago segura
**Para** completar la compra de los productos seleccionados.

### Criterios de Aceptación

* El usuario puede iniciar el proceso de pago desde el checkout.
* El sistema envía correctamente los datos de la transacción a la pasarela de pago.
* Se muestra el resultado de la operación al usuario.
* La transacción queda registrada en el sistema.
* La operación cumple con los contratos API definidos.

---

## US-E-002 – Seleccionar método de pago

**Como** cliente de la plataforma
**Quiero** seleccionar entre distintos métodos de pago disponibles
**Para** utilizar la opción que mejor se adapte a mis necesidades.

### Criterios de Aceptación

* El sistema muestra los métodos de pago habilitados.
* El usuario puede seleccionar un único método de pago.
* La selección se valida antes de procesar la transacción.
* Se registra el método elegido junto con la orden.
* La funcionalidad cumple con los contratos definidos.

---

## US-E-003 – Validar datos de pago

**Como** cliente de la plataforma
**Quiero** que el sistema valide los datos ingresados para el pago
**Para** evitar errores durante la transacción.

### Criterios de Aceptación

* Los campos obligatorios son validados antes del envío.
* Se notifican errores de formato o datos incompletos.
* No se procesa una transacción con información inválida.
* La validación se realiza tanto en cliente como en servidor.
* Los mensajes de error son claros y comprensibles.

---

## US-E-004 – Confirmar pago exitoso

**Como** cliente de la plataforma
**Quiero** recibir una confirmación cuando el pago sea aprobado
**Para** tener certeza de que mi compra fue completada.

### Criterios de Aceptación

* El sistema muestra un mensaje de confirmación.
* Se genera un identificador de transacción.
* La orden cambia al estado "Pagada".
* Se registra la fecha y hora de la operación.
* El comprobante queda disponible para consulta.

---

## US-E-005 – Gestionar pagos rechazados

**Como** cliente de la plataforma
**Quiero** recibir información cuando un pago sea rechazado
**Para** corregir el problema y volver a intentarlo.

### Criterios de Aceptación

* El sistema informa el rechazo de la transacción.
* Se muestra el motivo proporcionado por la pasarela cuando esté disponible.
* La orden permanece pendiente de pago.
* El usuario puede intentar nuevamente el proceso.
* No se generan cargos duplicados.

---

## US-E-006 – Registrar transacciones de pago

**Como** administrador del sistema
**Quiero** registrar todas las transacciones realizadas
**Para** mantener trazabilidad y control financiero.

### Criterios de Aceptación

* Cada transacción genera un registro único.
* Se almacena el estado de la operación.
* Se registra el monto pagado.
* Se conserva el identificador entregado por la pasarela.
* Los registros pueden ser consultados posteriormente.

---

## US-E-007 – Actualizar estado de la orden tras el pago

**Como** sistema
**Quiero** actualizar automáticamente el estado de la orden cuando se confirme el pago
**Para** mantener la consistencia del proceso de compra.

### Criterios de Aceptación

* Una orden pagada cambia automáticamente de estado.
* El inventario se descuenta después de la confirmación del pago.
* El cambio de estado queda registrado en auditoría.
* Se notifica al módulo de gestión de pedidos.
* La actualización cumple con los contratos de integración.

---

## US-E-008 – Notificar resultado del pago

**Como** cliente de la plataforma
**Quiero** recibir una notificación del resultado de mi pago
**Para** conocer el estado de la transacción sin ingresar nuevamente al sistema.

### Criterios de Aceptación

* Se envía una notificación cuando la transacción finaliza.
* La notificación incluye el estado de la operación.
* Se informa el número de orden asociado.
* El envío queda registrado en el sistema.
* La información enviada es consistente con el resultado real.

---

## US-E-009 – Gestionar reembolsos

**Como** administrador del sistema
**Quiero** procesar solicitudes de reembolso
**Para** devolver pagos cuando corresponda.

### Criterios de Aceptación

* El sistema permite iniciar un reembolso.
* Se valida que la transacción original exista.
* Se registra el motivo del reembolso.
* El estado de la orden se actualiza correctamente.
* El resultado del reembolso queda almacenado para auditoría.

---

## Requisitos No Funcionales Relacionados

* Comunicación segura mediante HTTPS/TLS.
* Cumplimiento de estándares PCI-DSS para procesamiento de pagos.
* Disponibilidad mínima del servicio del 99.9%.
* Registro de auditoría para todas las transacciones.
* Tiempo de respuesta inferior a 5 segundos para operaciones de pago.
* Protección contra fraude y transacciones duplicadas.
* Integridad y consistencia de datos financieros.
* Trazabilidad completa de eventos de pago y reembolso.