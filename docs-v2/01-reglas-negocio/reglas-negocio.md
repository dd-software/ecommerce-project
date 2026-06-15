# Reglas de Negocio

> **Uso didáctico:** Cada regla de negocio debe implementarse como una validación explícita en el código. Si una regla no se cumple, el sistema debe rechazar la operación con un mensaje claro al usuario.

---

## Categorías

| Prefijo | Categoría |
|---------|-----------|
| RN-CAT | Catálogo y productos |
| RN-CAR | Carrito de compras |
| RN-AUT | Autenticación y acceso |
| RN-CHK | Checkout y pedidos |
| RN-PAG | Pagos y PayPal |
| RN-INV | Inventario |
| RN-ADM | Administración |
| RN-SEG | Seguridad |

---

## 📂 RN-CAT: Catálogo y Productos

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-CAT-01 | **Todo producto debe tener SKU único** | El SKU es el identificador interno del producto. Se usa para tracking en inventario. |
| RN-CAT-02 | **Todo producto debe tener slug único** | El slug se usa en la URL (ej: `/producto/laptop-gamer-2024`). Debe ser URL-safe. |
| RN-CAT-03 | **Todo producto debe tener al menos 1 imagen** | Los productos sin imagen no se muestran en el catálogo. |
| RN-CAT-04 | **El precio debe ser mayor a 0** | No se permiten precios cero ni negativos. |
| RN-CAT-05 | **El precio con descuento debe ser menor al precio normal** | Si se define `precio_descuento`, debe ser estrictamente menor que `precio`. |
| RN-CAT-06 | **Un producto inactivo no se muestra en el catálogo** | Solo el admin puede ver productos inactivos (para reactivarlos). |
| RN-CAT-07 | **Las categorías pueden tener subcategorías (anidadas)** | Una categoría hija hereda la visibilidad de su padre. |
| RN-CAT-08 | **No se puede eliminar una categoría con productos asociados** | Solo se puede desactivar. Para eliminarla debe estar vacía. |
| RN-CAT-09 | **El stock disponible se muestra siempre en la ficha del producto** | `stock_disponible = stock_total - stock_reservado`. |

---

## 🛒 RN-CAR: Carrito de Compras

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-CAR-01 | **No se puede agregar un producto inactivo al carrito** | Validar que `producto.activo = 1` antes de agregar. |
| RN-CAR-02 | **No se puede agregar un producto sin stock disponible** | `stock_disponible > 0` al momento de agregar. |
| RN-CAR-03 | **La cantidad máxima de un producto en el carrito es 10** | Límite por item para evitar abusos. |
| RN-CAR-04 | **El carrito tiene un máximo de 50 items distintos** | Límite de items únicos en el carrito. |
| RN-CAR-05 | **El carrito de invitado se fusiona al iniciar sesión** | Si el usuario tenía items en localStorage, se migran al carrito del servidor. |
| RN-CAR-06 | **El carrito abandonado (>24h sin actividad) se limpia automáticamente** | Mantiene la BD limpia de carritos huérfanos. |
| RN-CAR-07 | **Los precios en el carrito congelan el valor al agregar** | Si el precio cambia después, el carrito muestra el precio al momento de agregar. |

---

## 🔐 RN-AUT: Autenticación y Acceso

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-AUT-01 | **El email debe ser único por usuario** | No pueden existir dos cuentas con el mismo email. |
| RN-AUT-02 | **La contraseña debe tener mínimo 8 caracteres, 1 mayúscula y 1 número** | Política de password mínimo. |
| RN-AUT-03 | **La contraseña se almacena con bcrypt (costo ≥ 12)** | Costo 12 es el mínimo aceptable de seguridad. |
| RN-AUT-04 | **Un usuario deshabilitado no puede iniciar sesión** | Validar `usuario.activo = 1` en login. |
| RN-AUT-05 | **Máximo 5 intentos de login fallidos en 15 minutos** | Previene ataques de fuerza bruta. Bloqueo temporal de la cuenta. |
| RN-AUT-06 | **Al iniciar sesión se regenera el session_id** | Previene session fixation attacks. |
| RN-AUT-07 | **Las sesiones expiran después de 2 horas de inactividad** | La sesión PHP tiene tiempo de vida finito. |
| RN-AUT-08 | **Solo usuarios con rol pueden acceder a rutas protegidas** | Verificar permisos antes de cada operación sensible. |
| RN-AUT-09 | **El registro crea automáticamente un carrito vacío** | El usuario recién registrado tiene carrito listo para usar. |

---

## 📋 RN-CHK: Checkout y Pedidos

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-CHK-01 | **El checkout requiere sesión iniciada** | Solo clientes registrados pueden comprar. |
| RN-CHK-02 | **El checkout requiere carrito con al menos 1 item** | No se puede procesar un pedido vacío. |
| RN-CHK-03 | **Antes de crear la orden se re-valida el stock** | El stock pudo cambiar desde que el usuario agregó al carrito. |
| RN-CHK-04 | **Si un producto no tiene stock, se elimina del carrito y se notifica** | El usuario debe saber qué producto falló. |
| RN-CHK-05 | **La orden genera un número único (ORD-YYYY-NNNNN)** | Formato legible: año + número secuencial de 5 dígitos. |
| RN-CHK-06 | **Al crear la orden se reserva el inventario (30 min)** | La reserva expira si no se completa el pago. |
| RN-CHK-07 | **La orden se puede cancelar solo si está en estado `pendiente` o `confirmado`** | Una vez enviado, no se puede cancelar (solo devolución). |
| RN-CHK-08 | **El total se calcula: subtotal + IVA (19%) + envío** | El IVA se aplica sobre el subtotal. El envío tiene costo base configurable. |

---

## 💳 RN-PAG: Pagos y PayPal

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-PAG-01 | **El pago solo se procesa si la orden está en estado `pendiente`** | No se puede pagar una orden ya pagada o cancelada. |
| RN-PAG-02 | **El monto del pago debe coincidir con el total de la orden** | Validación contra manipulación del monto. |
| RN-PAG-03 | **PayPal opera en modo sandbox para desarrollo** | No se usa dinero real. Credenciales sandbox de PayPal Developer. |
| RN-PAG-04 | **El pago se considera exitoso solo cuando PayPal confirma (vía webhook)** | No confiar solo en el return URL — esperar IPN/webhook. |
| RN-PAG-05 | **Al confirmar el pago se descuenta el inventario definitivamente** | Se pasa de `reserva` a `salida` definitiva. |
| RN-PAG-06 | **Un pago rechazado libera las reservas de inventario** | Si PayPal rechaza, se libera stock para otros compradores. |
| RN-PAG-07 | **Un pago expirado (30 min sin confirmación) libera inventario automáticamente** | Cron job que barre reservas expiradas. |
| RN-PAG-08 | **Se puede reembolsar un pago solo si está `aprobado`** | El reembolso se registra y la orden vuelve a `cancelado`. |
| RN-PAG-09 | **Todo cambio de estado de pago queda registrado en historial** | Trazabilidad: pendiente → aprobado/rechazado → reembolsado. |

---

## 📦 RN-INV: Inventario

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-INV-01 | **El stock nunca puede ser negativo** | `cantidad >= 0` siempre. Usar CHECK constraint en BD. |
| RN-INV-02 | **No se puede reservar más stock del disponible** | `cantidad_reservada + nueva_reserva <= cantidad`. |
| RN-INV-03 | **Una reserva expira a los 30 minutos** | Tiempo configurable via `configuracion`. |
| RN-INV-04 | **Una reserva expirada libera el stock automáticamente** | Ver RN-PAG-07. |
| RN-INV-05 | **Todo movimiento de inventario se registra como trazabilidad** | Entradas, salidas, reservas, liberaciones, ajustes. |
| RN-INV-06 | **Cuando el stock disponible baja del umbral, se genera alerta** | `umbral_alerta` configurable por producto. |
| RN-INV-07 | **Solo administradores pueden hacer ajustes manuales de stock** | Ajustes quedan registrados con responsable. |
| RN-INV-08 | **Al crear un producto, su inventario inicia en 0** | El stock se carga manualmente por el admin. |

---

## 🛠️ RN-ADM: Administración

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-ADM-01 | **Solo administradores acceden al dashboard** | Redirección si no tiene rol admin. |
| RN-ADM-02 | **Toda acción admin se registra en auditoría** | Quién hizo qué, cuándo, desde qué IP. |
| RN-ADM-03 | **No se puede eliminar el último administrador** | Evita dejar el sistema sin administradores. |
| RN-ADM-04 | **La configuración del sistema tiene efecto inmediato** | Sin necesidad de reiniciar el servidor. |
| RN-ADM-05 | **Los reportes muestran datos de los últimos 12 meses** | Con filtro por rango de fechas. |

---

## 🛡️ RN-SEG: Seguridad

| ID | Regla | Explicación |
|----|-------|-------------|
| RN-SEG-01 | **Toda entrada de usuario se valida y sanitiza** | Server-side validation obligatoria. |
| RN-SEG-02 | **Todas las consultas SQL usan prepared statements (PDO)** | Protección contra SQL injection. |
| RN-SEG-03 | **Toda salida HTML se escapa** | `htmlspecialchars()` para prevenir XSS. |
| RN-SEG-04 | **Toda mutación (POST/PUT/DELETE) requiere token CSRF** | Token por sesión, validado en servidor. |
| RN-SEG-05 | **Las cookies de sesión tienen flags: HttpOnly, SameSite=Strict** | Previene robo de sesión por XSS. |
| RN-SEG-06 | **El modo mantenimiento bloquea todo acceso no-admin** | Mensaje claro para usuarios regulares. |
| RN-SEG-07 | **Los archivos .env y de configuración no son accesibles vía web** | `.htaccess` bloquea acceso directo. |
| RN-SEG-08 | **Los logs no contienen información sensible** | Sin passwords, tokens ni datos de tarjetas en logs. |
