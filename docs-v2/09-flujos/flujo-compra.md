# Flujo de Compra — Ecommerce UCT

## Diagrama de Flujo

```
Usuario                          Sistema                         PayPal
   │                                │                              │
   ├── Navega catálogo ───────────► │                              │
   │◄── Lista de productos ──────── │                              │
   │                                │                              │
   ├── Agrega al carrito ──────────► │                              │
   │◄── Confirmación ────────────── │                              │
   │                                │                              │
   ├── Va al carrito ──────────────► │                              │
   │◄── Resumen del carrito ─────── │                              │
   │                                │                              │
   ├── Inicia sesión ──────────────► │                              │
   │◄── Sesión confirmada ───────── │                              │
   │                                │                              │
   ├── Va a checkout ──────────────► │                              │
   │                                 │── Verifica stock ──────────► │
   │◄── Formulario de pago ──────── │                              │
   │                                 │── Crea pedido (pendiente) ──►│
   │                                 │── Reserva inventario (10min) │
   │                                 │                              │
   ├── Autoriza pago ──────────────►│                              │
   │                                 │                              ├── Muestra ventana PayPal
   │◄── Ventana PayPal ──────────── │                              │
   │                                 │                              │
   ├── Confirma en PayPal ─────────►│                              │
   │                                 │── Verifica con PayPal ──────►│
   │                                 │◄── Transacción confirmada ───│
   │                                 │                              │
   │                                 │── Actualiza:                 │
   │                                 │   • Pago → completado        │
   │                                 │   • Pedido → confirmado      │
   │                                 │   • Reservas → confirmadas   │
   │                                 │   • Stock descontado         │
   │                                 │                              │
   │◄── Página de éxito ─────────── │                              │
   │                                                              │
   ├── Admin ve pedido ────────────►│                              │
   │                                 │── Cambia estado del pedido  │
   │                                 │   (en_proceso → enviado →   │
   │                                 │    entregado)                │
   │                                 │                              │
```

---

## Descripción Paso a Paso

### Paso 1: Navegación del Catálogo (Público)
- El usuario visita la página principal (`index.php`)
- Ve productos destacados y categorías
- Puede buscar productos por nombre
- Puede filtrar por categoría
- Todo esto es accesible sin autenticación

### Paso 2: Agregar al Carrito (Público + Cliente)
- Al hacer clic en "Agregar al carrito", se envía POST a `api/carrito.php?action=agregar`
- Si el usuario tiene sesión, el carrito se guarda en BD (`items_carrito`)
- Si es invitado, se guarda en `localStorage` (frontend)
- **Validación:** se verifica stock disponible antes de agregar

### Paso 3: Ver Carrito (Público + Cliente)
- `carrito.php` muestra los items con precios, cantidades y total
- Se puede actualizar cantidades o eliminar items
- El total se recalcula dinámicamente

### Paso 4: Iniciar Sesión (Requerido para checkout)
- Si el usuario es invitado y quiere comprar, debe registrarse o iniciar sesión
- `login.php` → POST a `api/auth.php?action=login`
- Al iniciar sesión, el carrito de invitado (del localStorage) se migra al carrito persistente en BD

### Paso 5: Checkout (Cliente autenticado)
- `checkout.php` → resumen de compra
- El usuario ingresa dirección de envío
- Al confirmar, POST a `api/checkout.php` que:
  1. Obtiene items del carrito del usuario
  2. Calcula: subtotal + IVA (19%) + costo envío ($4.990)
  3. Verifica stock para cada producto
  4. Crea reservas en `reservas_inventario` (expiran en 10 min)
  5. Crea `pedido` (estado: `pendiente`)
  6. Crea `detalles_pedido`
  7. Crea `pago` (estado: `pendiente`, método: `paypal`)
  8. Vacía el carrito
  9. Devuelve `pedido_id` y `total`

### Paso 6: Pago con PayPal (Cliente autenticado)
- Se muestra el botón de PayPal con el total del pedido
- El usuario es redirigido a PayPal (sandbox) donde inicia sesión con cuenta de prueba
- Al autorizar, PayPal devuelve un `orderID`
- `pago.php?action=confirmar` verifica la transacción con PayPal via cURL
- Si es exitoso:
  - Pago → `completado`
  - Pedido → `confirmado`
  - Reservas → `confirmadas`
  - Stock descontado definitivamente
- Si falla o se cancela:
  - Las reservas expiran en 10 min o se liberan inmediatamente

### Paso 7: Página de Éxito
- `exito.php?orden=ORD-2026-00001` muestra confirmación
- El usuario puede ver su pedido en su historial

### Paso 8: Administración del Pedido (Admin)
- El admin ve el pedido en `admin/pedidos.php`
- Puede cambiar estados: `confirmado → en_proceso → enviado → entregado`
- O estados terminales: `cancelado`, `reembolsado`

---

## Consideraciones de Error

| Error | Causa | Acción |
|-------|-------|--------|
| Stock insuficiente | Alguien más compró el último | Mostrar mensaje, liberar reservas |
| PayPal rechazado | Fondos insuficientes en sandbox | Liberar reservas, pedir otro método |
| Timeout de pago | Usuario cerró ventana PayPal | Reserva expira en 10 min (automático) |
| Token CSRF inválido | Sesión expirada | Redirigir a login |
| Sesión expirada | Usuario inactivo demasiado tiempo | Redirigir a login con mensaje |

---

## Estados de Transición

```
Carrito con items ──► Checkout ──► Reserva activa ──► Pago PayPal
                                        │                    │
                                        ▼                    ▼
                                   Expirada (10min)    Pago confirmado
                                        │                    │
                                        ▼                    ▼
                                   Stock liberado      Pedido confirmado
                                                         Stock descontado
```
