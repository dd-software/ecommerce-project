# Contratos API — Módulo E: Pasarela de Pago (PayPal)

> **Base URL:** `/api`
> **Formato:** JSON
> **Autenticación:** Varía por endpoint

---

## ENDPOINTS

### E.1 Procesar Pago 🔒

`POST /api/pagos/procesar?orden_id={uuid}`

Redirige al pago por PayPal.

**Proceso:**
1. Verificar que la orden existe y está en estado `pendiente`
2. Verificar que el pago no haya sido procesado antes
3. Verificar que el usuario autenticado es el dueño de la orden
4. Crear orden de pago en PayPal (API REST)
5. Almacenar `referencia_pasarela` = PayPal Order ID
6. Devolver URL de aprobación de PayPal

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "paypal_order_id": "PAYPAL_ORDER_ID",
    "approval_url": "https://www.sandbox.paypal.com/checkoutnow?token=TOKEN",
    "orden_id": "uuid",
    "monto": 956978,
    "moneda": "CLP",
    "mensaje": "Redirigiendo a PayPal para completar el pago..."
  }
}
```

---

### E.2 Confirmar Pago (Return URL)

`GET /api/pagos/confirmar?token={paypal_token}&orden_id={uuid}`

Endpoint al que PayPal redirige al usuario después de aprobar el pago.

**Proceso:**
1. Capturar la orden de PayPal (POST /v2/checkout/orders/{id}/capture)
2. Verificar que el estado de PayPal es `COMPLETED`
3. Actualizar pago en BD: estado = `aprobado`, fecha_pago = NOW
4. Actualizar orden: estado = `confirmado`
5. Confirmar descuento de inventario (pasa de reserva a salida)
6. Limpiar carrito del usuario
7. Registrar historial de transacción
8. Registrar auditoría

**Respuesta 200 (éxito):**

```json
{
  "success": true,
  "data": {
    "orden_id": "uuid",
    "numero_orden": "ORD-2024-00001",
    "estado_pago": "aprobado",
    "referencia_paypal": "PAYPAL_CAPTURE_ID",
    "mensaje": "Pago confirmado exitosamente. Tu pedido está en proceso."
  }
}
```

**Respuesta 200 (rechazado):**

```json
{
  "success": false,
  "error": {
    "code": "PAGO_RECHAZADO",
    "message": "El pago fue rechazado por PayPal",
    "detalle_paypal": "INSTRUMENT_DECLINED"
  }
}
```

---

### E.3 Webhook de PayPal

`POST /api/pagos/webhook`

Endpoint que PayPal llama automáticamente para eventos asíncronos.

**Eventos manejados:**

| Evento PayPal | Acción |
|--------------|--------|
| `CHECKOUT.ORDER.APPROVED` | Pago aprobado por el cliente (esperar capture) |
| `PAYMENT.CAPTURE.COMPLETED` | Captura exitosa → confirmar pago en BD |
| `PAYMENT.CAPTURE.DENIED` | Captura rechazada → liberar reservas |
| `PAYMENT.CAPTURE.REFUNDED` | Reembolso → actualizar estado |

**Verificación:** Validar firma del webhook usando `POST /v1/notifications/verify-webhook-signature`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "event_type": "PAYMENT.CAPTURE.COMPLETED",
    "processed": true
  }
}
```

---

### E.4 Reembolsar Pago 🔒 (Admin)

`POST /api/pagos/reembolsar`

**Body:**

```json
{
  "orden_id": "uuid"
}
```

**Proceso:**
1. Verificar que el pago está `aprobado`
2. Llamar a PayPal: `POST /v2/payments/captures/{capture_id}/refund`
3. Actualizar pago: estado = `reembolsado`
4. Actualizar orden: estado = `cancelado`
5. Liberar inventario (entrada por devolución)
6. Registrar historial

---

### E.5 Estado del Servicio

`GET /api/pasarela-de-pago`

**Respuesta 200:**

```json
{
  "success": true,
  "data": {
    "servicio": "PayPal Sandbox",
    "estado": "conectado",
    "modo": "sandbox",
    "ultima_verificacion": "2024-03-15T10:00:00Z"
  }
}
```
