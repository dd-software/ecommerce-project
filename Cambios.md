"# Cambios Realizados en el Proyecto E-commerce

A continuación se documentan todos los cambios y mejoras implementadas a lo largo del desarrollo del proyecto, desde la creación de nuevas funcionalidades hasta la corrección de errores en la integración con PayPal.

---

## 1. Archivos Nuevos Creados

| Archivo | Descripción |
|---------|-------------|
| `cuenta.php` | Página de perfil del usuario donde puede ver el historial de compras, filtrar por número de pedido, nombre de producto y estado, y pagar pedidos pendientes. |
| `admin/categorias.php` | Dashboard CRUD para gestionar categorías: crear, editar, eliminar, activar/desactivar. Incluye modal Bootstrap y validaciones. |
| `pago.php` | Página intermedia que se comunica con PayPal Sandbox vía AJAX, redirige al usuario a aprobar el pago y luego permite confirmar manualmente la captura del pago. |
| `api/pago_confirmar.php` | Endpoint de respaldo para manejar el retorno de PayPal (aprobación/cancelación) y redirigir al usuario a la página correspondiente. |

---

## 2. Archivos Modificados

| Archivo | Cambio |
|---------|--------|
| `includes/config.php` | - Corregido `SITE_URL` a `http://localhost/ecommerce-project`<br>- Configuradas las credenciales de PayPal Sandbox (Client ID y Secret)<br>- Agregada variable global `$scripts_adicionales` para evitar warnings en el footer |
| `includes/header.php` | - Agregado enlace **\"Mi Cuenta\"** (`cuenta.php`) en el menú desplegable del usuario<br>- Enlace **\"Panel Admin\"** visible solo para usuarios con rol `admin` |
| `admin/admin_header.php` | - Enlace a **\"Categorías\"** habilitado y funcional (ya no muestra \"Pronto\")<br>- Ahora redirige a `admin/categorias.php` con clase `active` cuando corresponde |
| `index.php` | - Agregado **banner de administración** visible solo para usuarios con rol `admin`<br>- El banner contiene un botón **\"Ir al Panel Admin\"** que redirige a `admin/index.php` |
| `api/checkout.php` | - Después de crear la orden exitosamente, en lugar de redirigir a `exito.php`, ahora redirige a `pago.php` para iniciar el flujo de pago con PayPal<br>- Guarda el `orden_id` en sesión (`$_SESSION['orden_pendiente_id']`) |
| `api/pago.php` | - Cambiada la moneda de `CLP` a `USD` (PayPal Sandbox no soporta CLP)<br>- Monto convertido dividiendo entre 950 (1 USD ≈ 950 CLP)<br>- URLs de retorno corregidas para que PayPal redirija a `pago.php?paypal_aprobado=1` o `pago.php?paypal_cancelado=1`<br>- Corrección en el manejo de errores y logs |
| `cuenta.php` | - Agregado **botón \"💳 Pagar ahora\"** en cada pedido con estado `pendiente`<br>- El botón redirige a `pago.php?orden_id=XX` para completar el pago |
| `.htaccess` | - Reactivado con sintaxis compatible con Apache 2.4<br>- Permite acceso AJAX a los archivos dentro de `api/` sin restricciones |
| `login.php` | - Después de iniciar sesión exitosamente, redirige a `index.php` en lugar de una página genérica |
| `registro.php` | - Después de registrarse exitosamente, redirige a `index.php` |

---

## 3. Corrección de Errores Identificados

| Error | Causa | Solución |
|-------|-------|----------|
| **PayPal devolvía error al crear orden** | Se usaba `CLP` como moneda, pero PayPal Sandbox solo acepta `USD`, `EUR`, etc. | Cambiar `currency_code` a `'USD'` y convertir el monto (total / 950). |
| **PayPal no redirigía de vuelta correctamente** | Las URLs `return_url` y `cancel_url` apuntaban a `api/pago_confirmar.php` que no manejaba bien el flujo. | Cambiar URLs a `pago.php?paypal_aprobado=1` y `pago.php?paypal_cancelado=1`, y agregar lógica en `pago.php` para mostrar botón de confirmación manual. |
| **Función JavaScript `mostrarError` mal llamada** | En `pago.php` se llamaba `showError()` pero la función se llamaba `mostrarError()`. | Corregir la llamada a `mostrarError()`. |
| **Header corrupto** | Operaciones fallidas dejaron `includes/header.php` vacío o con contenido basura. | Regenerar el header con un script PHP intermedio, verificando la sintaxis con `php -l`. |
| **Variables `scripts_adicionales` indefinidas en footer** | Footer intentaba imprimir `$scripts_adicionales` sin declararla. | Agregar `$scripts_adicionales = '';` en `config.php`. |

---

## 4. Flujo Completo de Pago con PayPal (Funcionamiento Actual)

1. **Usuario** agrega productos al carrito y va a **Checkout**.
2. En checkout completa la dirección de envío y confirma la compra.
3. `api/checkout.php` crea la orden en la BD (estado `pendiente`), reserva inventario y redirige a `pago.php?orden_id=XX`.
4. `pago.php` muestra un spinner y llama vía **AJAX** a `api/pago.php` (acción `crear`).
5. `api/pago.php` obtiene un token de PayPal, crea una orden en PayPal Sandbox con monto en USD y devuelve la `approval_url`.
6. `pago.php` redirige al navegador del usuario a **PayPal Sandbox**.
7. El usuario inicia sesión con su cuenta sandbox y **aprueba el pago**.
8. PayPal redirige al navegador a `pago.php?orden_id=XX&paypal_aprobado=1`.
9. `pago.php` detecta `paypal_aprobado=1` y muestra un **botón \"💳 Confirmar Pago\"**.
10. El usuario hace clic en el botón → se envía un formulario **POST** a `api/pago.php` (acción `capturar`).
11. `api/pago.php` captura la orden en PayPal, si es `COMPLETED` actualiza la BD (estado `confirmado`, descuenta inventario, registra movimiento) y redirige a `exito.php`.
12. Si el pago falla, se liberan las reservas de inventario y se muestra un mensaje de error.

---

## 5. Roles y Permisos

| Rol | Acceso |
|-----|--------|
| **cliente** | Puede ver su perfil (`cuenta.php`), historial de compras, pagar pedidos pendientes, navegar por el catálogo, agregar al carrito. |
| **admin** | Todo lo del cliente + banner de administración en `index.php`, enlace \"Panel Admin\" en el menú, acceso a `admin/categorias.php` para gestionar categorías. |

---

## 6. Pruebas Recomendadas

1. **Registrar un nuevo usuario** → debe redirigir a `index.php`.
2. **Iniciar sesión** → debe redirigir a `index.php`.
3. **Ver \"Mi Cuenta\"** (logueado) → debe mostrar el historial de compras.
4. **Pedido pendiente** → debe mostrar botón \"💳 Pagar ahora\".
5. **Admin** → debe ver banner en `index.php` y enlace a panel en el menú.
6. **Categorías (admin)** → debe poder crear/editar/eliminar categorías.
7. **Flujo PayPal completo** → desde agregar producto hasta pago exitoso.
8. **Cancelar pago en PayPal** → debe volver a `pago.php` con mensaje de cancelación.

---

*Documento generado el 17 de junio de 2026.*"