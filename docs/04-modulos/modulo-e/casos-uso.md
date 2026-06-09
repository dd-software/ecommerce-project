# Casos de Uso

## CU-E-001 – Flujo Principal de Pasarela de Pago

### Descripción

Permite al cliente realizar el pago de una orden utilizando los métodos de pago habilitados por la plataforma. El sistema procesa la transacción mediante la pasarela de pago integrada, valida la operación y actualiza el estado del pedido.

### Objetivo

Procesar de forma segura y confiable los pagos de las órdenes generadas en la plataforma ecommerce, garantizando la integridad de la transacción y la actualización del estado de compra.

### Actores

* Cliente
* Sistema Ecommerce
* Pasarela de Pago
* Entidad Financiera (Banco o Emisor)
* Sistema de Pedidos
* Sistema de Inventario

### Precondiciones

* El cliente posee un carrito con productos válidos.
* La orden ha sido generada correctamente.
* Existen productos con stock reservado.
* La pasarela de pago se encuentra disponible.
* El monto total de la compra ha sido calculado.

### Disparador

El cliente selecciona la opción **Pagar Pedido** durante el proceso de checkout.

### Flujo Principal

1. El cliente accede al resumen de compra.
2. El sistema muestra el detalle de la orden y el monto total.
3. El cliente selecciona un método de pago disponible.
4. El sistema redirige o conecta con la pasarela de pago.
5. El cliente ingresa los datos requeridos para el pago.
6. La pasarela valida el formato y consistencia de los datos.
7. La pasarela envía la solicitud de autorización a la entidad financiera.
8. La entidad financiera evalúa la transacción.
9. La entidad financiera autoriza el pago.
10. La pasarela retorna una respuesta exitosa.
11. El sistema registra la transacción.
12. El sistema actualiza el estado del pedido a **Pagado**.
13. El sistema confirma la compra al cliente.
14. El sistema genera el comprobante de pago.
15. El sistema envía la confirmación mediante los canales configurados.
16. El proceso finaliza exitosamente.

### Flujos Alternativos

#### FA-01: Pago rechazado por la entidad financiera

1. La entidad financiera rechaza la operación.
2. La pasarela devuelve el resultado de rechazo.
3. El sistema registra el intento fallido.
4. El sistema informa al cliente el rechazo del pago.
5. El cliente puede seleccionar otro método de pago o reintentar.

#### FA-02: Fondos insuficientes

1. La entidad financiera detecta saldo insuficiente.
2. La transacción es rechazada.
3. El sistema notifica al cliente.
4. El flujo retorna a la selección de método de pago.

#### FA-03: Error de comunicación con la pasarela

1. El sistema intenta conectarse con la pasarela.
2. La comunicación falla por error de red o servicio.
3. El sistema registra el incidente.
4. Se informa al cliente que el servicio no está disponible temporalmente.
5. La orden permanece pendiente de pago.

#### FA-04: Cancelación del pago por parte del cliente

1. El cliente cancela la operación antes de finalizarla.
2. La pasarela informa la cancelación.
3. El sistema mantiene la orden en estado pendiente.
4. El cliente puede reanudar el proceso posteriormente.

#### FA-05: Tiempo de espera excedido

1. La pasarela no recibe respuesta dentro del tiempo establecido.
2. La transacción queda en estado de verificación.
3. El sistema registra el evento.
4. El cliente es informado sobre el estado pendiente.

### Postcondiciones

#### Éxito

* La transacción queda registrada.
* El pedido cambia a estado **Pagado**.
* Se genera un comprobante de pago.
* El cliente recibe confirmación de compra.
* El inventario queda confirmado para despacho.

#### Fallo

* El pedido permanece pendiente de pago.
* No se confirma la venta.
* El sistema registra el motivo del rechazo o error.
* No se realiza el despacho de productos.

### Reglas de Negocio

* RN-001: Toda transacción debe registrarse con identificador único.
* RN-002: Ningún pedido puede cambiar a estado Pagado sin confirmación de la pasarela.
* RN-003: Las comunicaciones con la pasarela deben realizarse mediante canales seguros (HTTPS/TLS).
* RN-004: Los datos sensibles de pago no deben almacenarse en la plataforma.
* RN-005: Cada intento de pago debe quedar auditado.
* RN-006: El comprobante de pago debe generarse únicamente para transacciones aprobadas.
* RN-007: Los pedidos pendientes pueden ser reintentados dentro del período definido por la plataforma.

### Datos de Entrada

* Identificador de pedido.
* Método de pago seleccionado.
* Datos de autenticación requeridos por la pasarela.
* Monto total de la compra.

### Datos de Salida

* Estado de la transacción.
* Código de autorización.
* Identificador de la transacción.
* Comprobante de pago.
* Estado actualizado del pedido.

### Prioridad

Alta

### Frecuencia de Uso

Muy Alta

### Módulos Relacionados

* Gestión de Carrito
* Gestión de Pedidos
* Gestión de Inventario
* Gestión de Clientes
* Gestión de Notificaciones
* Pasarela de Pago
* Auditoría y Seguridad