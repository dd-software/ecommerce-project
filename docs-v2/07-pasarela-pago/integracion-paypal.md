# Integración con PayPal

> **Propósito educativo:** Este documento explica paso a paso cómo integrar la pasarela de pago PayPal en modo Sandbox. Los estudiantes aprenderán a consumir APIs REST externas, manejar autenticación OAuth2 y procesar webhooks asíncronos.

---

## 1. ¿Qué es PayPal Sandbox?

PayPal Sandbox es un entorno de pruebas que simula el entorno real de PayPal. Usa **dinero ficticio** y permite probar flujos de pago completos sin transacciones reales.

**URL Sandbox:** `https://api-m.sandbox.paypal.com`

---

## 2. Configuración Inicial

### 2.1 Crear Cuenta de Desarrollador

1. Ir a [https://developer.paypal.com](https://developer.paypal.com)
2. Iniciar sesión con cuenta personal de PayPal (puede ser una cuenta real)
3. Ir a **Dashboard > My Apps & Credentials**
4. Crear una **App REST API** en modo Sandbox
5. Obtener credenciales:

```
Client ID:   Aa7B...tu_client_id...xyz
Secret Key:  ENpP...tu_secret...xyz
```

### 2.2 Configurar en `.env`

```env
PAYPAL_MODE=sandbox                     # sandbox | live
PAYPAL_CLIENT_ID=Aa7B...tu_client_id
PAYPAL_SECRET=ENpP...tu_secret
PAYPAL_WEBHOOK_ID=WH-...webhook_id      # Se obtiene al crear el webhook
```

### 2.3 Cuentas de Prueba Sandbox

PayPal genera automáticamente cuentas de prueba:
- **Comprador:** `sb-xxxxx@personal.example.com` (con saldo ficticio)
- **Vendedor:** `sb-xxxxx@business.example.com` (cuenta business)

---

## 3. Flujo de Pago con PayPal

```
┌──────────┐     ┌──────────┐     ┌──────────┐     ┌──────────┐
│  Cliente  │     │ Tu App   │     │  PayPal  │     │  PayPal  │
│ (Browser)  │     │ (Backend) │     │  API     │     │  Webhook │
└────┬─────┘     └────┬─────┘     └────┬─────┘     └────┬─────┘
     │                 │                 │                 │
     │  1. POST /checkout/procesar      │                 │
     │────────────────►│                │                 │
     │                 │                │                 │
     │  2. Crear orden pendiente        │                 │
     │                 │──(BD)─────────►│                 │
     │                 │                │                 │
     │  3. POST /v2/checkout/orders     │                 │
     │                 │───────────────►│                 │
     │                 │ ◄──────────────│ approval_url    │
     │                 │   + order_id   │                 │
     │                 │                │                 │
     │  4. approval_url                 │                 │
     │ ◄───────────────│                │                 │
     │                 │                │                 │
     │  5. Redirige a PayPal            │                 │
     │══════════════════════════════════►│                 │
     │                 │                │                 │
     │  6. Usuario aprueba pago         │                 │
     │ ◄════════════════════════════════│                 │
     │                 │                │                 │
     │  7. Redirect a /confirmar        │                 │
     │────────────────►│                │                 │
     │                 │                │                 │
     │  8. POST /v2/checkout/           │                 │
     │     orders/{id}/capture          │                 │
     │                 │───────────────►│                 │
     │                 │ ◄──────────────│ COMPLETED      │
     │                 │                │                 │
     │  9. Confirmar orden + descontar  │                 │
     │     inventario + limpiar carrito  │                 │
     │                 │                │                 │
     │                 │  10. WEBHOOK:  │                 │
     │                 │  PAYMENT.CAPTURE.COMPLETED       │
     │                 │ ◄───────────────────────────────│
     │                 │                │                 │
     │ 11. "Compra exitosa"             │                 │
     │ ◄───────────────│                │                 │
```

---

## 4. API Calls a PayPal

### 4.1 Obtener Access Token

```php
// POST /v1/oauth2/token
// Authorization: Basic base64(client_id:secret)
// Grant_type: client_credentials

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => "https://api-m.sandbox.paypal.com/v1/oauth2/token",
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_USERPWD => CLIENT_ID . ":" . SECRET,
    CURLOPT_POSTFIELDS => "grant_type=client_credentials"
]);
$response = json_decode(curl_exec($ch), true);
$access_token = $response['access_token']; // Expira en 32400s (9h)
```

### 4.2 Crear Orden de Pago

```php
// POST /v2/checkout/orders
// Authorization: Bearer {access_token}
// Content-Type: application/json

$orderData = [
    "intent" => "CAPTURE",
    "purchase_units" => [[
        "reference_id" => $ordenNumero,
        "description" => "Compra en Mi Tienda Online",
        "amount" => [
            "currency_code" => "CLP",
            "value" => number_format($total, 0, '.', '')  // CLP sin decimales
        ],
        "items" => $items // Opcional
    ]],
    "payment_source" => [
        "paypal" => [
            "experience_context" => [
                "payment_method_preference" => "IMMEDIATE_PAYMENT_REQUIRED",
                "landing_page" => "LOGIN",
                "user_action" => "PAY_NOW",
                "return_url" => URL . "/api/pagos/confirmar?orden_id=" . $ordenId,
                "cancel_url" => URL . "/api/checkout/cancelado?orden_id=" . $ordenId
            ]
        ]
    ]
];
```

### 4.3 Capturar Pago

```php
// POST /v2/checkout/orders/{paypal_order_id}/capture
// Authorization: Bearer {access_token}

curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/$paypalOrderId/capture");
// Status COMPLETED → pago exitoso
```

---

## 5. Webhooks

### 5.1 Configurar Webhook en PayPal Developer

1. Ir a **My Apps & Credentials > Webhooks**
2. Agregar webhook URL: `https://tudominio.com/api/pagos/webhook`
3. Seleccionar eventos:
   - `CHECKOUT.ORDER.APPROVED`
   - `PAYMENT.CAPTURE.COMPLETED`
   - `PAYMENT.CAPTURE.DENIED`
   - `PAYMENT.CAPTURE.REFUNDED`
4. Copiar **Webhook ID** al `.env`

### 5.2 Verificar Firma del Webhook

```php
// POST /v1/notifications/verify-webhook-signature
// PayPal envía headers: PAYPAL-AUTH-ALGO, PAYPAL-CERT-URL,
//                       PAYPAL-TRANSMISSION-ID, PAYPAL-TRANSMISSION-SIG,
//                       PAYPAL-TRANSMISSION-TIME

$verificationData = [
    "auth_algo" => $_SERVER['HTTP_PAYPAL_AUTH_ALGO'],
    "cert_url" => $_SERVER['HTTP_PAYPAL_CERT_URL'],
    "transmission_id" => $_SERVER['HTTP_PAYPAL_TRANSMISSION_ID'],
    "transmission_sig" => $_SERVER['HTTP_PAYPAL_TRANSMISSION_SIG'],
    "transmission_time" => $_SERVER['HTTP_PAYPAL_TRANSMISSION_TIME'],
    "webhook_id" => PAYPAL_WEBHOOK_ID,
    "webhook_event" => json_decode(file_get_contents('php://input'), true)
];

curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($verificationData));
// verification_status == "SUCCESS" → webhook legítimo
```

### 5.3 Endpoint del Webhook

```php
// POST /api/pagos/webhook
public function webhook() {
    $payload = json_decode(file_get_contents('php://input'), true);
    
    // 1. Verificar firma
    if (!$this->verificarFirma($payload)) {
        http_response_code(400);
        exit;
    }
    
    // 2. Procesar según evento
    switch ($payload['event_type']) {
        case 'PAYMENT.CAPTURE.COMPLETED':
            $captureId = $payload['resource']['id'];
            $ordenId = $this->buscarOrdenPorCapture($captureId);
            $this->confirmarPago($ordenId);
            break;
            
        case 'PAYMENT.CAPTURE.DENIED':
            $ordenId = $this->buscarOrdenPorPayPalOrder($payload);
            $this->liberarReservas($ordenId);
            $this->actualizarEstadoPago($ordenId, 'rechazado');
            break;
    }
    
    http_response_code(200); // PayPal espera 200 OK
}
```

---

## 6. Manejo de Errores y Edge Cases

| Escenario | Acción |
|-----------|--------|
| PayPal devuelve `INSTRUMENT_DECLINED` | Rechazar pago, liberar reservas, notificar usuario |
| Timeout de conexión a PayPal | Reintentar 3 veces con backoff exponencial |
| Webhook duplicado (PayPal envía 2 veces) | Verificar `event_id` único antes de procesar |
| Usuario cierra PayPal sin pagar | La reserva expira en 30 min (mecanismo automático) |
| Paypal devuelve error 401 (token expirado) | Renovar token y reintentar |
| URL de retorno sin token | Mostrar error y botón "Reintentar pago" |

---

## 7. Configuración PayPal en la BD

En la tabla `pagos`, la columna `metodo` debe incluir `'paypal'`:

```sql
ALTER TABLE pagos 
MODIFY COLUMN metodo ENUM(
    'paypal',
    'tarjeta_credito', 
    'tarjeta_debito', 
    'transferencia', 
    'efectivo_contra_entrega'
) NOT NULL;
```

La columna `referencia_pasarela` almacena el ID de captura de PayPal.
