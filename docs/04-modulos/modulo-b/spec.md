# Módulo B - Carrito

## Objetivo

Implementar la funcionalidad de carrito de compras que permita a los clientes agregar, modificar y eliminar productos antes de realizar el proceso de pago, garantizando la sincronización con el inventario disponible y la correcta preparación de la orden de compra.

---

## Alcance

El módulo de carrito contempla las siguientes funcionalidades:

### Gestión de Productos

* Agregar productos al carrito.
* Actualizar cantidades de productos agregados.
* Eliminar productos individuales.
* Vaciar completamente el carrito.
* Consultar contenido actual del carrito.

### Validación de Inventario

* Verificar disponibilidad de stock al agregar productos.
* Validar stock al modificar cantidades.
* Revalidar disponibilidad antes de iniciar el proceso de pago.
* Notificar productos sin stock suficiente.

### Cálculo de Totales

* Calcular subtotal de productos.
* Calcular descuentos aplicables.
* Calcular impuestos configurados.
* Calcular costo de envío estimado.
* Calcular total final de compra.

### Persistencia

* Mantener el carrito activo durante la sesión del usuario.
* Asociar carrito a usuarios autenticados.
* Permitir recuperación del carrito al volver a iniciar sesión.

### Integración con Checkout

* Transferir productos seleccionados al módulo de pagos.
* Bloquear modificaciones durante la confirmación de compra.
* Generar resumen final para la orden.

---

## Actores

### Cliente

Usuario final que administra los productos de su carrito y realiza compras.

### Sistema de Inventario

Servicio encargado de validar disponibilidad de stock.

### Sistema de Pagos

Servicio encargado de procesar la transacción económica.

---

## Requisitos Funcionales

### RF-B-001 Agregar Producto

El sistema debe permitir agregar un producto disponible al carrito.

### RF-B-002 Actualizar Cantidad

El sistema debe permitir modificar la cantidad de unidades de un producto existente.

### RF-B-003 Eliminar Producto

El sistema debe permitir eliminar un producto específico del carrito.

### RF-B-004 Vaciar Carrito

El sistema debe permitir eliminar todos los productos contenidos en el carrito.

### RF-B-005 Consultar Carrito

El sistema debe mostrar los productos agregados junto con cantidades, precios y totales.

### RF-B-006 Validar Inventario

El sistema debe verificar la existencia de stock suficiente antes de confirmar cambios.

### RF-B-007 Calcular Totales

El sistema debe recalcular automáticamente los importes cuando existan modificaciones.

### RF-B-008 Preparar Checkout

El sistema debe generar un resumen de compra listo para ser enviado al módulo de pagos.

---

## Requisitos No Funcionales

### Rendimiento

* Las operaciones de carrito deberán responder en menos de 2 segundos.
* El cálculo de totales deberá ejecutarse en tiempo real.

### Seguridad

* Los usuarios solo podrán acceder a sus propios carritos.
* Todas las operaciones deberán requerir autenticación cuando corresponda.
* Las comunicaciones deberán utilizar HTTPS.

### Disponibilidad

* El carrito deberá estar disponible durante toda la sesión activa del usuario.

### Escalabilidad

* El diseño deberá soportar múltiples usuarios concurrentes sin afectar la consistencia de inventario.

---

## Modelo de Datos

### Carrito

| Campo               | Tipo          | Descripción                 |
| ------------------- | ------------- | --------------------------- |
| id                  | UUID          | Identificador único         |
| usuario_id          | UUID          | Usuario propietario         |
| estado              | VARCHAR(20)   | Activo, Pendiente, Comprado |
| subtotal            | DECIMAL(10,2) | Monto parcial               |
| impuestos           | DECIMAL(10,2) | Impuestos aplicados         |
| descuento           | DECIMAL(10,2) | Descuento total             |
| total               | DECIMAL(10,2) | Total final                 |
| fecha_creacion      | DATETIME      | Fecha de creación           |
| fecha_actualizacion | DATETIME      | Última modificación         |

### CarritoDetalle

| Campo           | Tipo          | Descripción         |
| --------------- | ------------- | ------------------- |
| id              | UUID          | Identificador       |
| carrito_id      | UUID          | Carrito asociado    |
| producto_id     | UUID          | Producto agregado   |
| cantidad        | INTEGER       | Cantidad solicitada |
| precio_unitario | DECIMAL(10,2) | Precio vigente      |
| subtotal        | DECIMAL(10,2) | Total de línea      |

---

## Flujo Principal

1. El cliente selecciona un producto.
2. El sistema valida disponibilidad en inventario.
3. El producto se agrega al carrito.
4. El sistema recalcula subtotales y total general.
5. El cliente continúa agregando productos o modifica cantidades.
6. El cliente inicia checkout.
7. El sistema valida nuevamente inventario.
8. El carrito es enviado al módulo de pagos.
9. Se genera la orden correspondiente.

---

## Reglas de Negocio

### RN-B-001

No se podrá agregar una cantidad superior al stock disponible.

### RN-B-002

Las cantidades permitidas deben ser mayores que cero.

### RN-B-003

Los precios utilizados serán los vigentes al momento de la compra.

### RN-B-004

Si un producto queda sin stock antes del pago, el sistema deberá informar al usuario y solicitar actualización del carrito.

### RN-B-005

Un carrito podrá tener como máximo 100 productos diferentes.

---

## Dependencias

### API de Inventario

Responsable de:

* Consultar disponibilidad de stock.
* Validar cantidades solicitadas.
* Confirmar existencia antes del pago.

### API de Pagos

Responsable de:

* Recibir el resumen del carrito.
* Procesar la transacción.
* Retornar resultado de pago.

### API de Productos

Responsable de:

* Obtener información de productos.
* Consultar precios vigentes.
* Obtener imágenes y detalles comerciales.

---

## Criterios de Éxito

* El usuario puede administrar productos sin errores.
* Los cálculos de totales son correctos.
* La validación de inventario es consistente.
* La información enviada al módulo de pagos es íntegra.
* El sistema mantiene la trazabilidad completa de la compra.
