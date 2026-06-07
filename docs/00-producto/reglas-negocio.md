
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
