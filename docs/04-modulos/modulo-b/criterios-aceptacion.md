# Criterios de Aceptación

## Checklist Funcional del Módulo B

### Gestión de Carrito de Compras

* [ ] El usuario puede agregar productos al carrito desde el catálogo.
* [ ] El usuario puede visualizar todos los productos agregados al carrito.
* [ ] El sistema muestra nombre, precio, cantidad y subtotal de cada producto.
* [ ] El usuario puede modificar la cantidad de productos en el carrito.
* [ ] El usuario puede eliminar productos individuales del carrito.
* [ ] El sistema recalcula automáticamente subtotales y total general.
* [ ] El sistema impide agregar cantidades superiores al stock disponible.
* [ ] El carrito conserva los productos durante la sesión activa del usuario.
* [ ] El carrito se vacía automáticamente después de una compra exitosa.

---

### Gestión de Inventario

* [ ] El sistema registra el stock disponible de cada producto.
* [ ] El stock disminuye automáticamente cuando una compra es confirmada.
* [ ] El sistema impide la compra de productos sin stock.
* [ ] El sistema actualiza el inventario en tiempo real después de cada transacción.
* [ ] Los administradores pueden aumentar existencias manualmente.
* [ ] Los administradores pueden disminuir existencias manualmente.
* [ ] El sistema registra fecha y hora de cada movimiento de inventario.
* [ ] El sistema genera alertas para productos con stock bajo.
* [ ] El sistema permite consultar historial de movimientos de inventario.

---

### Proceso de Pago

* [ ] El usuario puede iniciar el proceso de pago desde el carrito.
* [ ] El sistema valida que existan productos antes de procesar el pago.
* [ ] El sistema valida disponibilidad de stock antes de confirmar la compra.
* [ ] El sistema calcula correctamente el monto total a pagar.
* [ ] El usuario puede seleccionar un método de pago disponible.
* [ ] El sistema registra el estado de la transacción.
* [ ] El sistema confirma visualmente cuando el pago es exitoso.
* [ ] El sistema informa errores cuando el pago es rechazado.
* [ ] El sistema genera un número único de orden de compra.
* [ ] El sistema almacena el historial de pagos realizados.

---

### Gestión de Pedidos

* [ ] El sistema crea automáticamente un pedido después de un pago exitoso.
* [ ] El pedido queda asociado al usuario que realizó la compra.
* [ ] El pedido almacena productos, cantidades, precios y total.
* [ ] El pedido registra fecha y hora de creación.
* [ ] El usuario puede consultar el historial de pedidos.
* [ ] El administrador puede visualizar todos los pedidos generados.

---

### Seguridad y Validaciones

* [ ] Solo usuarios autenticados pueden finalizar compras.
* [ ] El sistema valida datos obligatorios antes de procesar pagos.
* [ ] El sistema protege las operaciones mediante sesiones activas.
* [ ] El sistema registra errores críticos para auditoría.
* [ ] El sistema evita duplicidad de órdenes por múltiples envíos.
* [ ] El sistema valida integridad de los datos enviados desde el cliente.

---

### Pruebas de Aceptación

#### Escenario 1: Compra Exitosa

**Dado** que existe stock disponible para un producto
**Cuando** el usuario agrega el producto al carrito y realiza el pago
**Entonces** el sistema genera el pedido, descuenta inventario y confirma la compra.

#### Escenario 2: Stock Insuficiente

**Dado** que un producto no posee stock suficiente
**Cuando** el usuario intenta agregar una cantidad superior a la disponible
**Entonces** el sistema rechaza la operación e informa el motivo.

#### Escenario 3: Pago Rechazado

**Dado** que ocurre un error durante el pago
**Cuando** el usuario intenta finalizar la compra
**Entonces** el sistema mantiene el carrito y muestra un mensaje de error.

#### Escenario 4: Actualización de Inventario

**Dado** que una compra fue completada exitosamente
**Cuando** el sistema registra la transacción
**Entonces** el stock del producto se actualiza automáticamente.

---

## Criterio de Aprobación del Módulo

El módulo será considerado aceptado cuando:

* El 100% de los casos de uso definidos se encuentren implementados.
* El 100% de los criterios funcionales marcados anteriormente hayan sido verificados.
* No existan errores críticos abiertos.
* Las pruebas de integración entre carrito, inventario y pagos sean exitosas.
* La documentación técnica y funcional se encuentre actualizada.
