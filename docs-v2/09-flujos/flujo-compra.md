# Flujo de Compra Completo

> **Propósito educativo:** Este documento describe paso a paso el flujo completo de una compra exitosa. Los estudiantes deben entender cómo los 8 módulos (A–H) se coordinan para completar una transacción.

---

## 1. Diagrama de Flujo General

```
VISITANTE                               CLIENTE REGISTRADO
    │                                        │
    ▼                                        │
┌──────────┐                                 │
│ Catálogo │                                 │
│ (A)      │                                 │
└────┬─────┘                                 │
     │ Ve producto                           │
     ▼                                       │
┌──────────┐                                 │
│ Producto │                                 │
│ (A)      │                                 │
└────┬─────┘                                 │
     │ "Agregar al carrito"                  │
     ▼                                       │
┌──────────┐     ┌──────────────────┐        │
│ ¿Login?  │────→│ Login / Registro │        │
│ (C)      │ NO  │ (C)              │        │
└────┬─────┘     └────────┬─────────┘        │
     │ SI                  │ Registro OK      │
     ▼                     ▼                  │
┌──────────┐     ┌──────────────────┐        │
│ Carrito  │←────│ Fusionar Carrito │        │
│ (B)      │     │ Local (B)       │        │
└────┬─────┘     └──────────────────┘        │
     │ "Ir a pagar"                          │
     ▼                                       │
┌──────────┐                                 │
│ Checkout │     ─── Validar stock (F)       │
│ (D)      │     ─── Elegir dirección        │
└────┬─────┘     ─── Resumen + total         │
     │ "Confirmar compra"                     │
     ▼                                       │
┌──────────┐                                 │
│ Crear    │     ─── Transacción DB          │
│ Orden    │     ─── Reservar stock (F)      │
│ (D)      │     ─── Registrar auditoría     │
└────┬─────┘                                 │
     │ Redirigir a pago                       │
     ▼                                       │
┌──────────┐                                 │
│ PayPal   │     ─── Crear orden PayPal (E)  │
│ Sandbox  │     ─── Redirigir a PayPal       │
└────┬─────┘                                  │
     │ Usuario aprueba en PayPal              │
     ▼                                       │
┌──────────┐                                 │
│ Capturar │     ─── POST capture (E)        │
│ Pago     │     ─── PayPal confirma         │
└────┬─────┘                                  │
     │ COMPLETED                              │
     ▼                                       │
┌──────────┐                                 │
│ Confirmar│     ─── Descontar stock (F)     │
│ Orden    │     ─── Limpiar carrito (B)     │
│ (D + E)  │     ─── Orden → confirmado      │
└────┬─────┘     ─── Audit trail              │
     │ "Compra exitosa"                       │
     ▼                                       │
┌──────────┐                                 │
│ Página   │                                 │
│ Éxito    │                                 │
└──────────┘                                 │
```

---

## 2. Flujo Detallado

### Paso 1: Navegar Catálogo (Módulo A)

```
GET /api/catalogo?categoria=electronica&orden=precio_asc&page=1
    → Lista de productos con imagen principal, precio, stock disponible
    → Cada producto tiene link a /producto/{slug}
```

**Vista:** Tarjetas con imagen, nombre, precio, badge "En oferta" si tiene descuento, badge "Últimas unidades" si stock < 5, botón "Agregar" + selector de cantidad.

### Paso 2: Ver Producto (Módulo A)

```
GET /api/producto/{slug}
    → Info completa: galería de imágenes, descripción, precio, categoría con breadcrumb
    → Stock disponible y alerta
    → Productos relacionados
    → Botón "Agregar al carrito"
```

### Paso 3: Agregar al Carrito (Módulo B)

```
POST /api/carrito/agregar
Body: { "producto_id": "uuid", "cantidad": 1 }

Validaciones:
1. ¿Producto activo? RN-CAT-06
2. ¿Stock disponible? RN-CAR-02
3. ¿Cantidad <= 10? RN-CAR-03
4. ¿Carrito < 50 items? RN-CAR-04
```

**Si no está autenticado:** Guardar en localStorage y mostrar badge con contador.

### Paso 4: Login / Registro (Módulo C)

```
POST /api/auth/login
Body: { "email": "...", "password": "..." }

O

POST /api/auth/registro
Body: { "nombre": "...", "email": "...", "password": "..." }
```

**Tras login:** Llamar a fusionar carrito local (B.6) si hay items en localStorage.

### Paso 5: Ver Carrito (Módulo B)

```
GET /api/carrito
    → Items con foto, precio, cantidad (editable), subtotal
    → Resumen: subtotal + IVA (19%) + envío ($4.990) = TOTAL
    → Botón "Proceder al pago"
```

### Paso 6: Checkout (Módulo D)

```
GET /api/checkout
    → Resumen final de la compra
    → Selección de dirección de envío
    → Confirmación de método de pago (PayPal)
    → Botón "Confirmar compra"
```

### Paso 7: Crear Orden (Módulo D)

```
POST /api/checkout/procesar
Body: { "direccion_envio_id": "uuid" }

Proceso transaccional:
BEGIN TRANSACTION
    1. Re-validar stock de todos los items (F.2)
    2. Si todo OK:
       - Generar número de orden: ORD-2024-NNNNN
       - INSERT pedido
       - INSERT detalles_pedido
       - Reservar inventario (F.3)
       - Registrar movimiento inventario
       - Registrar auditoría
    3. Si algún item falló:
       - ROLLBACK
       - Devolver error con detalle
COMMIT
```

### Paso 8: Iniciar Pago PayPal (Módulo E)

```
POST /api/pagos/procesar?orden_id={uuid}

Proceso:
1. Obtener access token de PayPal
2. Crear orden de pago en PayPal
3. Devolver approval_url

Response: {
  "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=..."
}
```

### Paso 9: Cliente Aprueba en PayPal

1. Redirección a PayPal
2. Cliente inicia sesión con cuenta sandbox
3. Cliente revisa el monto
4. Cliente aprueba el pago
5. PayPal redirige a `return_url` con token

### Paso 10: Capturar y Confirmar (Módulo E + D)

```
GET /api/pagos/confirmar?token=BA-...&orden_id={uuid}

Proceso:
1. Capturar orden PayPal (POST capture)
2. Si COMPLETED:
   - Actualizar pago → aprobado
   - Actualizar orden → confirmado
   - Confirmar descuento inventario (F.5)
   - Limpiar carrito (B.5)
   - Registrar historial transacción
   - Registrar auditoría
3. Si no:
   - Liberar reservas (F.4)
   - Actualizar pago → rechazado
```

### Paso 11: Página de Éxito

```
GET /checkout/exito?orden_id={uuid}
    → Número de orden
    → Resumen de compra
    → Botón "Ver mis pedidos"
```

---

## 3. Webhook de PayPal (Confirmación Asíncrona)

Además de la confirmación síncrona (paso 10), PayPal envía un webhook para confirmación asíncrona:

```
POST /api/pagos/webhook
Event: PAYMENT.CAPTURE.COMPLETED

Proceso:
1. Verificar firma del webhook
2. Buscar orden por PayPal Order ID
3. Verificar que el pago no esté ya confirmado
4. Confirmar pago (mismo proceso que paso 10)
```

**¿Por qué ambos?** El webhook es la fuente de verdad. La return URL es solo para UX (mostrar pantalla de éxito inmediato). Siempre esperar el webhook para confirmar definitivamente.

---

## 4. Flujo de Error (Pago Rechazado)

```
1. PayPal devuelve capture con status DENIED
2. Liberar reservas de inventario (F.4)
3. Actualizar pago → rechazado
4. Actualizar orden → pendiente (o cancelado)
5. Mostrar página de error con:
   - Mensaje: "El pago fue rechazado"
   - Botón: "Intentar con otro método"
   - Botón: "Contactar soporte"
```
