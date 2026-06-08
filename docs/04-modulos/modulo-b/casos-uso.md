# Casos de Uso

## CU-B-001 – Flujo Principal de Carrito de Compras

### Descripción

Permite al cliente gestionar los productos seleccionados para una compra antes de proceder al pago. El usuario puede agregar, modificar o eliminar productos del carrito, visualizar subtotales y calcular el total de la orden.

### Objetivo

Facilitar la selección y administración de productos que el cliente desea adquirir, validando disponibilidad de stock y preparando la información para el proceso de checkout.

### Actores

* Cliente
* Sistema de Inventario
* Sistema Ecommerce

### Precondiciones

* El catálogo de productos se encuentra disponible.
* El cliente ha iniciado sesión o posee una sesión activa como invitado.
* Existen productos habilitados para la venta.
* El sistema de inventario está operativo.

### Disparador

El cliente selecciona la opción **Agregar al carrito** desde la ficha de un producto.

### Flujo Principal

1. El cliente navega por el catálogo de productos.
2. El cliente selecciona un producto.
3. El sistema muestra la información detallada del producto.
4. El cliente indica la cantidad deseada.
5. El cliente presiona **Agregar al carrito**.
6. El sistema consulta la disponibilidad de stock.
7. El sistema valida que exista stock suficiente.
8. El sistema agrega el producto al carrito.
9. El sistema recalcula subtotales y total de la compra.
10. El sistema muestra la actualización del carrito.
11. El cliente accede al carrito de compras.
12. El sistema presenta el listado de productos agregados.
13. El cliente puede modificar cantidades.
14. El sistema valida nuevamente la disponibilidad.
15. El sistema actualiza los montos correspondientes.
16. El cliente confirma el contenido del carrito.
17. El sistema habilita la opción **Proceder al Pago**.

### Flujos Alternativos

#### FA-01: Producto sin stock disponible

1. Durante la validación de inventario el sistema detecta stock insuficiente.
2. El sistema informa al cliente que el producto no posee disponibilidad.
3. El sistema impide agregar la cantidad solicitada.
4. El caso de uso finaliza.

#### FA-02: Cantidad superior al stock disponible

1. El cliente intenta agregar una cantidad mayor al stock existente.
2. El sistema muestra la cantidad máxima disponible.
3. El cliente ajusta la cantidad.
4. El flujo retorna al paso 5 del flujo principal.

#### FA-03: Eliminación de producto

1. El cliente selecciona la opción **Eliminar producto**.
2. El sistema elimina el producto del carrito.
3. El sistema recalcula el total de la compra.
4. El sistema actualiza la vista del carrito.

#### FA-04: Carrito vacío

1. El cliente elimina todos los productos.
2. El sistema muestra el mensaje "El carrito está vacío".
3. El sistema deshabilita la opción de pago.

### Postcondiciones

#### Éxito

* El carrito contiene los productos seleccionados.
* Los montos se encuentran actualizados.
* La información queda disponible para el proceso de checkout.

#### Fallo

* No se agregan productos con stock insuficiente.
* No se generan inconsistencias en inventario.

### Reglas de Negocio

* RN-001: No se puede agregar una cantidad superior al stock disponible.
* RN-002: El stock debe validarse cada vez que se modifica la cantidad de un producto.
* RN-003: El total del carrito corresponde a la suma de subtotales más impuestos aplicables.
* RN-004: El carrito se conserva mientras la sesión permanezca activa.
* RN-005: Los precios utilizados corresponden a los vigentes al momento del cálculo.

### Prioridad

Alta

### Frecuencia de Uso

Muy Alta

### Módulo Relacionado

* Gestión de Carrito
* Gestión de Inventario
* Gestión de Pedidos
* Gestión de Pagos
