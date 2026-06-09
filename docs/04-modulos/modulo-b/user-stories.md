# Historias de Usuario – Módulo B: Carrito de Compras

## US-B-001 – Agregar productos al carrito

**Como** cliente de la plataforma
**Quiero** agregar productos al carrito de compras
**Para** reunir los artículos que deseo adquirir antes de realizar el pago.

### Criterios de Aceptación

* El usuario puede agregar productos disponibles desde el catálogo.
* El sistema valida la disponibilidad de stock antes de agregar el producto.
* El carrito muestra el producto agregado inmediatamente.
* Se actualiza el subtotal del carrito.
* La operación cumple con los contratos API definidos.

---

## US-B-002 – Visualizar carrito

**Como** cliente de la plataforma
**Quiero** visualizar el contenido de mi carrito
**Para** revisar los productos seleccionados antes de comprar.

### Criterios de Aceptación

* El usuario puede acceder al carrito desde cualquier página.
* Se muestran productos, cantidades, precios unitarios y subtotales.
* Se muestra el total acumulado de la compra.
* Los datos reflejan el estado actual del carrito.
* La información se obtiene mediante los servicios definidos.

---

## US-B-003 – Modificar cantidad de productos

**Como** cliente de la plataforma
**Quiero** modificar la cantidad de productos en el carrito
**Para** ajustar mi compra según mis necesidades.

### Criterios de Aceptación

* El usuario puede aumentar o disminuir cantidades.
* El sistema valida que exista stock suficiente.
* El subtotal y total se recalculan automáticamente.
* No se permiten cantidades menores a uno.
* Los cambios se almacenan correctamente.

---

## US-B-004 – Eliminar productos del carrito

**Como** cliente de la plataforma
**Quiero** eliminar productos del carrito
**Para** descartar artículos que ya no deseo comprar.

### Criterios de Aceptación

* El usuario puede eliminar cualquier producto agregado.
* El sistema actualiza automáticamente el total del carrito.
* El producto deja de visualizarse una vez eliminado.
* La acción queda registrada correctamente.
* La operación cumple los contratos API establecidos.

---

## US-B-005 – Validar disponibilidad antes del pago

**Como** cliente de la plataforma
**Quiero** que el sistema valide el stock antes de iniciar el pago
**Para** evitar comprar productos sin disponibilidad.

### Criterios de Aceptación

* El sistema verifica el inventario actualizado.
* Los productos sin stock son informados al usuario.
* No se permite continuar al proceso de pago con productos inválidos.
* Se ofrecen opciones para actualizar el carrito.
* La validación se ejecuta antes de confirmar la compra.

---

## US-B-006 – Mantener carrito persistente

**Como** cliente autenticado
**Quiero** conservar mi carrito entre sesiones
**Para** continuar mi compra posteriormente.

### Criterios de Aceptación

* El carrito se almacena asociado a la cuenta del usuario.
* Los productos permanecen disponibles tras cerrar sesión.
* El carrito se recupera automáticamente al volver a ingresar.
* La información se sincroniza con la base de datos.
* Se mantienen la integridad y consistencia de los datos.

---

## US-B-007 – Proceder al checkout

**Como** cliente de la plataforma
**Quiero** iniciar el proceso de pago desde el carrito
**Para** completar la compra de mis productos.

### Criterios de Aceptación

* El usuario puede acceder al checkout desde el carrito.
* El sistema valida stock y precios antes de continuar.
* Se transfiere correctamente la información de la compra.
* Se muestran los datos resumidos de la orden.
* La transición cumple con los contratos definidos entre módulos.

---

## Requisitos No Funcionales Relacionados

* Tiempo de respuesta inferior a 2 segundos para operaciones del carrito.
* Integridad de datos durante actualizaciones concurrentes.
* Compatibilidad con dispositivos móviles y escritorio.
* Persistencia segura de información del usuario.
* Registro de eventos para auditoría y monitoreo.
* Cumplimiento de estándares de seguridad OWASP.
