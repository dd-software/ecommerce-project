
# Reglas de Negocio

## RN-001 No vender productos sin stock.
El sistema debe impedir la compra de un producto cuya cantidad disponible en inventario sea cero o menor a la solicitada. Antes de agregar al carrito o procesar el pago, se debe validar el stock actual.
Acción: el botón de agregar al carrito se deshabilita y muestra el mensaje "Producto sin stock disponible".
  
## RN-002 Solo administradores pueden acceder al dashboard.
El dashboard de administración solo estará disponible para usuarios con rol "admin". Cualquier otro rol (cliente, empleado, supervisor) será redirigido a la página principal si intenta acceder directamente.
Excepción: Empleado, Supervisor y Admin tienen acceso según
sus permisos específicos dentro del dashboard.

## RN-003 El stock se descuenta tras confirmar pago.
La reducción del inventario ocurre únicamente después de recibir la confirmación exitosa del pago (ej: respuesta positiva de Transbank de prueba). No se descuenta stock al agregar productos al carrito ni al iniciar checkout.
Excepción: si el pago falla o es rechazado, el stock no se
modifica y el carrito se mantiene intacto.

## RN-004 Usuarios deshabilitados no pueden iniciar sesión.
Si un usuario ha sido marcado como "deshabilitado" en la base de datos (por ejemplo, por inactividad o sanción), el sistema debe rechazar su intento de autenticación mostrando un mensaje genérico de "usuario o contraseña incorrectos".

## RN-005 Todo pedido debe tener trazabilidad de estados.
Cada compra registrada debe contar con un historial de estados mínimos: "pendiente", "pagado", "en preparación", "enviado", "entregado". Solo el administrador y supervisor pueden modificar estos estados.
Transiciones válidas: Pendiente → Confirmado → En preparación → Despachado → Entregado.
Cancelado puede aplicarse desde Pendiente o Confirmado únicamente

## RN-006 - Carrito persiste al cerrar el navegador.
El carrito de compras debe almacenarse en el navegador (localStorage o IndexedDB), no en sesiones del servidor. Esto garantiza que al cerrar y reabrir el navegador, los productos sigan en el carrito sin necesidad de iniciar sesión.

## RN-007 - Comportamiento del carrito al iniciar sesión.
Cuando un usuario anónimo con productos en el carrito inicie sesión, el sistema debe fusionar el carrito local con el carrito del usuario almacenado en la base de datos (si existe), priorizando cantidades actualizadas y evitando duplicados.

## RN-008 - Carrito con producto sin stock.
Si un producto en el carrito queda sin stock disponible (por ejemplo, porque otro usuario compró la última unidad), el sistema debe marcar dicho producto como "no disponible" en el carrito y notificar al usuario al intentar proceder al checkout.

## RN-009 - Sesión no persiste al cerrar el navegador.
La sesión de usuario no debe ser persistente. Se usará almacenamiento de sesión (sessionStorage) o cookies sin expiración larga. Al cerrar el navegador, el usuario deberá volver a iniciar sesión obligatoriamente.

## RN-010
El sistema debe permitir que usuarios anónimos puedan visualizar el catálogo, agregar productos al carrito y realizar compras como invitado. Sin embargo, funcionalidades como historial de compras, lista de deseos y acceso al dashboard requieren autenticación. al intentar una acción que requiere login, el sistema muestra un modal con opciones de iniciar sesión, registrarse o continuar como invitado

## RN-011
El sistema debe aceptar únicamente correos electrónicos con formato válido pertenecientes a dominios como gmail.com, hotmail.com, outlook.com y dominios corporativos. Se deben rechazar correos con formato incorrecto o incompleto, mostrando un mensaje de error al usuario.

## RN-012 - Contraseña almacenada hasheada.
Todas las contraseñas de usuario deben almacenarse en la base de datos utilizando un algoritmo de hashing seguro ( password_hash() de PHP con BCRYPT o Argon2). Nunca se almacenarán en texto plano.
Requisitos mínimos: 8 caracteres, al menos una mayúscula, al menos un número.

## RN-013 - Alerta de stock configurable por producto.
El sistema debe permitir configurar un umbral mínimo de stock de forma individual por producto (ej: 3 unidades para un producto A, 10 para producto B). Cuando el stock caiga por debajo de ese umbral, se mostrará una alerta visible en el dashboard del administrador y supervisor.

## RN-014 - Solo Supervisor y Admin modifican stock.
La modificación directa del stock de cualquier producto (incrementar o disminuir manualmente) solo puede ser realizada por usuarios con rol "Supervisor" o "Admin". Ni Clientes ni Empleados tienen permisos para esta acción.

## RN-015 - Producto con stock 0 no se puede agregar al carrito.
El sistema debe impedir que un usuario (registrado o invitado) agregue al carrito cualquier producto cuyo stock actual sea 0. El botón de "agregar al carrito" debe deshabilitarse o mostrar un mensaje de "sin stock disponible"