# Integración PayPal — Ecommerce UCT

## Visión General

La plataforma integra **PayPal REST API v2** en modo **sandbox** para procesar pagos. No se usa dinero real — todas las transacciones son simuladas en el entorno sandbox de PayPal.

No se usa Composer ni el SDK oficial de PayPal. La comunicación se hace mediante **cURL nativo de PHP**.

---

## Arquitectura de Pagos

```
Cliente (navegador)              Servidor PHP                   PayPal API
┌─────────────────┐             ┌────────────────┐            ┌────────────┐
│ PayPal JS SDK   │             │ api/pago.php   │            │ Sandbox    │
│ (botón PayPal)  │──pedido──►  │ (cURL)         │──verificar─►│ REST API   │
│                 │◄─approval──│                 │◄─confirm───│ v2         │
│                 │             │                │            └────────────┘
│                 │             │                │
│   checkout.php  │◄─success───│  (respuesta)   │
│   (jQuery AJAX)  │             └────────────────┘
└─────────────────┘
```

---

## Flujo de Pago

1. **Usuario hace checkout** → se crea pedido (estado: `pendiente`) + reservas de inventario
2. **Se muestra botón PayPal** en la página de pago con el total del pedido
3. **Usuario autoriza pago** en ventana de PayPal (sandbox)
4. **PayPal devuelve `orderID`** al frontend JavaScript
5. **Frontend envía `orderID` al servidor** via AJAX (`api/pago.php`)
6. **Servidor verifica la transacción** con PayPal via cURL
7. **Servidor confirma el pedido** (estado: `confirmado`) y descuenta stock

---

## Configuración en `includes/config.php`

```php
<?php
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' o 'live'
define('PAYPAL_CLIENT_ID', 'AQ...tu_client_id...'); // Desde developer.paypal.com
define('PAYPAL_SECRET', 'EN...tu_secret...');       // Desde developer.paypal.com
define('PAYPAL_WEBHOOK_ID', '');
```

---

## Frontend — Botón PayPal (checkout.php)

```html
<!-- Incluir SDK de PayPal -->
<script src="https://www.paypal.com/sdk/js?client-id=<?= PAYPAL_CLIENT_ID ?>&currency=CLP"
        data-namespace="paypal_sdk">
</script>

<!-- Contenedor del botón -->
<div id="paypal-button-container"></div>

<script>
paypal_sdk.Buttons({
    createOrder: function(data, actions) {
        // Crear orden con el total del pedido
        return fetch('/ecommerce/api/pago.php?action=crear_orden', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: '<?= $_SESSION['csrf_token'] ?>',
                pedido_id: <?= $pedido_id ?>
            })
        }).then(res => res.json())
          .then(data => data.paypal_order_id);
    },
    onApprove: function(data, actions) {
        // Pago autorizado — confirmar en servidor
        return fetch('/ecommerce/api/pago.php?action=confirmar', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                csrf_token: '<?= $_SESSION['csrf_token'] ?>',
                pedido_id: <?= $pedido_id ?>,
                paypal_order_id: data.orderID,
                paypal_payer_id: data.payerID
            })
        }).then(res => res.json())
          .then(data => {
              if (data.success) {
                  window.location.href = '/ecommerce/exito.php?orden=' + data.pedido_numero;
              } else {
                  alert('Error: ' + data.error);
              }
          });
    },
    onCancel: function(data) {
        alert('Pago cancelado');
    }
}).render('#paypal-button-container');
</script>
```

---

## Backend — API Pago (`api/pago.php`)

### Acción: `crear_orden`

```php
<?php
case 'crear_orden':
    $pedido_id = filter_input(INPUT_POST, 'pedido_id', FILTER_VALIDATE_INT);

    // Obtener el pedido de la BD
    $stmt = $pdo->prepare("SELECT total, numero FROM pedidos WHERE id = ? AND usuario_id = ?");
    $stmt->execute([$pedido_id, $_SESSION['usuario_id']]);
    $pedido = $stmt->fetch();

    if (!$pedido) {
        http_response_code(404);
        echo json_encode(['error' => 'Pedido no encontrado']);
        exit;
    }

    // Obtener access token de PayPal
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api-m.sandbox.paypal.com/v1/oauth2/token',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_SECRET,
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials'
    ]);
    $token_resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    // Crear orden en PayPal
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://api-m.sandbox.paypal.com/v2/checkout/orders',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token_resp['access_token']
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'intent' => 'CAPTURE',
            'purchase_units' => [[
                'reference_id' => $pedido['numero'],
                'amount' => [
                    'currency_code' => 'CLP',
                    'value' => number_format($pedido['total'], 2, '.', '')
                ]
            ]]
        ])
    ]);
    $order_resp = json_decode(curl_exec($ch), true);
    curl_close($ch);

    echo json_encode(['paypal_order_id' => $order_resp['id']]);
    break;
```

### Acción: `confirmar`

```php
<?php
case 'confirmar':
    $data = json_decode(file_get_contents('php://input'), true);

    $pedido_id      = (int)$data['pedido_id'];
    $paypal_order_id = $data['paypal_order_id'];

    // Verificar con PayPal
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => "https://api-m.sandbox.paypal.com/v2/checkout/orders/{$paypal_order_id}",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $token
        ]
    ]);
    $verificar = json_decode(curl_exec($ch), true);
    curl_close($ch);

    if ($verificar['status'] !== 'COMPLETED') {
        http_response_code(400);
        echo json_encode(['error' => 'Pago no completado en PayPal']);
        exit;
    }

    // Actualizar BD
    $pdo->beginTransaction();
    try {
        // 1. Actualizar pago
        $stmt = $pdo->prepare("UPDATE pagos SET estado='completado', referencia_pasarela=?, fecha_pago=NOW() WHERE pedido_id=?");
        $stmt->execute([$paypal_order_id, $pedido_id]);

        // 2. Actualizar pedido
        $stmt = $pdo->prepare("UPDATE pedidos SET estado='confirmado', fecha_actualizacion=NOW() WHERE id=?");
        $stmt->execute([$pedido_id]);

        // 3. Confirmar reservas
        $stmt = $pdo->prepare("UPDATE reservas_inventario SET estado='confirmada' WHERE orden_id=? AND estado='activa'");
        $stmt->execute([$pedido_id]);

        // 4. Descontar stock
        $stmt = $pdo->prepare("UPDATE inventario i
            JOIN reservas_inventario r ON i.producto_id = r.producto_id
            SET i.cantidad = i.cantidad - r.cantidad,
                i.cantidad_reservada = i.cantidad_reservada - r.cantidad
            WHERE r.orden_id = ? AND r.estado = 'confirmada'");
        $stmt->execute([$pedido_id]);

        $pdo->commit();

        echo json_encode(['success' => true, 'pedido_numero' => $pedido['numero']]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(['error' => 'Error al confirmar pago']);
    }
    break;
```

---

## Configuración PayPal Sandbox

### Pasos para obtener credenciales:

1. Ir a https://developer.paypal.com/dashboard
2. Iniciar sesión (crear cuenta si no tienes)
3. Ir a **Sandbox → Accounts**
4. Usar la cuenta Business predeterminada (o crear una nueva)
5. Ir a **Apps & Credentials**
6. Crear una app en modo Sandbox
7. Copiar **Client ID** y **Secret** a `includes/config.php`
8. Usar la cuenta Personal de prueba para comprar

### Cuentas sandbox pre-creadas:

```
Email comprador: sb-xxxxxx@personal.example.com
Contraseña:     123456789

Email vendedor:  sb-xxxxxx@business.example.com
```

(Reemplazar con los datos reales de tu cuenta sandbox)

---

## Notas Pedagógicas

- **cURL nativo** — se usa en lugar de SDK para que los estudiantes aprendan a hacer requests HTTP desde PHP
- **Sin Composer** — todo el código es autónomo, sin dependencias externas
- **Sin namespaces** — PHP plano, tal como se vería en un proyecto real simple
- **Transacciones BD** — se usa `beginTransaction/commit/rollback` para atomicidad
- **Sandbox** — todas las transacciones son simuladas, sin dinero real
