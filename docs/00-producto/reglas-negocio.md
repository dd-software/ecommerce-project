
# Reglas de Negocio

## RN-001 No vender productos sin stock.
El sistema debe impedir la compra de un producto cuya cantidad disponible en inventario sea cero o menor a la solicitada. Antes de agregar al carrito o procesar el pago, se debe validar el stock actual.
  
## RN-002 Solo administradores pueden acceder al dashboard.
El dashboard de administración solo estará disponible para usuarios con rol "admin". Cualquier otro rol (cliente, empleado, supervisor) será redirigido a la página principal si intenta acceder directamente.

## RN-003 El stock se descuenta tras confirmar pago.
La reducción del inventario ocurre únicamente después de recibir la confirmación exitosa del pago (ej: respuesta positiva de Transbank de prueba). No se descuenta stock al agregar productos al carrito ni al iniciar checkout.

## RN-004 Usuarios deshabilitados no pueden iniciar sesión.
Si un usuario ha sido marcado como "deshabilitado" en la base de datos (por ejemplo, por inactividad o sanción), el sistema debe rechazar su intento de autenticación mostrando un mensaje genérico de "usuario o contraseña incorrectos".

## RN-005 Todo pedido debe tener trazabilidad de estados.
Cada compra registrada debe contar con un historial de estados mínimos: "pendiente", "pagado", "en preparación", "enviado", "entregado". Solo el administrador y supervisor pueden modificar estos estados.

## RN-006 - Carrito persiste al cerrar el navegador.
El carrito de compras debe almacenarse en el navegador (localStorage o IndexedDB), no en sesiones del servidor. Esto garantiza que al cerrar y reabrir el navegador, los productos sigan en el carrito sin necesidad de iniciar sesión.

## RN-007 - Comportamiento del carrito al iniciar sesión.
Cuando un usuario anónimo con productos en el carrito inicie sesión, el sistema debe fusionar el carrito local con el carrito del usuario almacenado en la base de datos (si existe), priorizando cantidades actualizadas y evitando duplicados.

## RN-008 - Carrito con producto sin stock.
Si un producto en el carrito queda sin stock disponible (por ejemplo, porque otro usuario compró la última unidad), el sistema debe marcar dicho producto como "no disponible" en el carrito y notificar al usuario al intentar proceder al checkout.