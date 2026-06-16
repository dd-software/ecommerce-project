# Reglas de Negocio — Ecommerce UCT

## Visión General

Este documento define las reglas de negocio (RN) del sistema. Cada regla tiene un identificador único (RN-XXX) y se usa como referencia en los contratos API y en el código fuente.

---

## RN-USU — Gestión de Usuarios

### RN-USU-01: Solo clientes pueden registrarse
El formulario de registro público solo crea usuarios con rol `cliente`. El rol `admin` solo se asigna manualmente desde la base de datos o mediante seed inicial.

**Implementación:** El campo `rol` en `usuarios` tiene por defecto `'cliente'`. No existe endpoint público para registrarse como admin.

### RN-USU-02: Email único
Cada email debe ser único en el sistema. No pueden existir dos usuarios con el mismo email.

### RN-USU-03: Soft-delete de usuarios
Cuando un usuario se desactiva (campo `activo = 0`), no puede iniciar sesión pero sus pedidos históricos se mantienen.

---

## RN-AUT — Autenticación y Autorización

### RN-AUT-01: Solo 2 roles
El sistema reconoce exactamente dos roles: `cliente` y `admin`. No hay empleados, supervisores ni otros roles intermedios.

### RN-AUT-02: Sesión requerida para operaciones críticas
Toda operación que modifique datos (carrito, checkout, admin) requiere sesión activa. Las páginas públicas (catálogo, producto individual) son accesibles sin autenticación.

### RN-AUT-03: Protección CSRF
Toda operación POST/PUT/DELETE debe incluir un token CSRF almacenado en sesión. El servidor valida el token antes de procesar la solicitud.

---

## RN-CARRITO — Carrito de Compras

### RN-CARRITO-01: Carrito por sesión
El carrito se asocia a `sesion_id` (PHP session ID). Si el usuario inicia sesión, se migra el carrito de invitado al usuario autenticado.

### RN-CARRITO-02: Stock verificado al agregar
No se puede agregar al carrito más unidades de las disponibles en inventario (`cantidad - cantidad_reservada`).

### RN-CARRITO-03: Precio congelado al agregar
El `precio_unitario` en `items_carrito` se guarda al momento de agregar el producto, no se actualiza si el precio cambia después.

---

## RN-INV — Gestión de Inventario

### RN-INV-01: Stock nunca negativo
`cantidad` en `inventario` nunca puede ser menor que 0. Verificado con `CHECK (cantidad >= 0)` en MySQL.

### RN-INV-02: Reserva temporal de 10 minutos (👈 clave pedagógica)
Cuando un usuario inicia el checkout, se crea una reserva en `reservas_inventario`. Si no se confirma el pago en **10 minutos**, la reserva expira y el stock se libera automáticamente.

**Implementación:** Al confirmar pago, la reserva pasa a estado `confirmada`. Un cron job (o verificación bajo demanda) libera reservas expiradas comparando `fecha_expiracion` con `NOW()`.

### RN-INV-03: Liberación por cancelación
Si el usuario cancela el pedido antes de pagar, o si el pago es rechazado, la reserva se libera inmediatamente (estado `liberada`).

### RN-INV-04: Trazabilidad de movimientos
Cada cambio de stock (entrada, salida, reserva, liberación, ajuste) se registra en `movimientos_inventario` con fecha, tipo y referencia.

---

## RN-PEDIDO — Gestión de Pedidos

### RN-PEDIDO-01: Estados del pedido
Los pedidos siguen esta secuencia de estados:
`pendiente → confirmado → en_proceso → enviado → entregado`

Estados terminales: `cancelado`, `reembolsado`.

### RN-PEDIDO-02: Número de orden único
Cada pedido tiene un número único con formato `ORD-YYYY-NNNNN` (ej: `ORD-2026-00001`).

### RN-PEDIDO-03: IVA chileno (19%)
El IVA se calcula como `subtotal * 0.19` y se almacena separadamente. El `total = subtotal + iva + costo_envio`.

---

## RN-PAGO — Pagos

### RN-PAGO-01: Solo PayPal (sandbox)
El único método de pago habilitado es PayPal en modo sandbox. No hay dinero real involucrado.

### RN-PAGO-02: Pago único por pedido
Cada pedido tiene exactamente un registro de pago (relación 1:1).

---

## RN-SEG — Seguridad

### RN-SEG-01: CSRF obligatorio en todo POST
Ver RN-AUT-03. Token generado con `bin2hex(random_bytes(32))`, almacenado en `$_SESSION['csrf_token']`.

### RN-SEG-02: Sesiones seguras
- `session.cookie_httponly = 1`
- `session.use_only_cookies = 1`
- `session.cookie_samesite = 'Strict'`

### RN-SEG-03: Protección XSS
Toda salida de datos generada por usuarios debe escaparse con `htmlspecialchars($texto, ENT_QUOTES, 'UTF-8')`.

### RN-SEG-04: Sanitización de IDs
Todo parámetro numérico recibido por GET/POST debe validarse con `filter_input()` o `intval()` antes de usarse en consultas SQL.
