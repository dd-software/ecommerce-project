# Gestión de Inventario

> **Propósito educativo:** Este documento detalla el diseño del sistema de inventario con reservas, expiración y trazabilidad. Es el módulo más complejo de la plataforma y crítico para la integridad del negocio.

---

## 1. Conceptos Clave

### Stock Total
Cantidad física disponible en bodega. Solo el admin puede modificarlo.

### Stock Reservado
Stock apartado para una compra en curso (no pagada aún). No está disponible para otros compradores.

### Stock Disponible
`stock_disponible = stock_total - stock_reservado`
Es el stock que ven los clientes en el catálogo.

### Reserva
Bloqueo temporal de stock por 30 minutos mientras el usuario completa el pago.

---

## 2. Ciclo de Vida del Inventario

```
                    ┌──────────────┐
                    │ Stock Total  │  (Admin define cantidad inicial)
                    └──────┬───────┘
                           │
                    ┌──────▼───────┐
                    │  Disponible  │  (Visible para clientes)
                    └──────┬───────┘
                           │
              ┌────────────┴────────────┐
              │                         │
      ┌───────▼───────┐         ┌───────▼───────┐
      │   Reservado   │         │  No aplica    │
      │  (30 min máx) │         │  (Catálogo)   │
      └───────┬───────┘         └───────────────┘
              │
    ┌─────────┴──────────┐
    │                    │
┌───▼───┐          ┌─────▼─────┐
│Pago OK│          │Pago falla │
│       │          │o expira   │
└───┬───┘          └─────┬─────┘
    │                    │
┌───▼─────────┐   ┌─────▼─────────┐
│  Descuento  │   │   Liberación  │
│  Definitivo │   │  (vuelve a    │
│  (salida)   │   │  disponible)  │
└─────────────┘   └───────────────┘
```

---

## 3. Operaciones de Inventario

### 3.1 Reservar Stock

**Cuándo:** Al crear la orden (checkout exitoso)

```sql
START TRANSACTION;

-- Bloquear fila para evitar race conditions
SELECT cantidad, cantidad_reservada 
FROM inventario 
WHERE producto_id = ? 
FOR UPDATE;

-- Validar que hay suficiente stock disponible
-- disponible = cantidad - cantidad_reservada
-- Si disponible >= solicitado → continuar
-- Si no → ROLLBACK y error

UPDATE inventario 
SET cantidad_reservada = cantidad_reservada + ? 
WHERE producto_id = ?;

INSERT INTO reservas_inventario (id, orden_id, producto_id, cantidad, 
                                  estado, fecha_expiracion)
VALUES (UUID(), ?, ?, ?, 'activa', DATE_ADD(NOW(), INTERVAL 30 MINUTE));

INSERT INTO movimientos_inventario (id, producto_id, tipo_movimiento, 
                                     cantidad, referencia, usuario_id)
VALUES (UUID(), ?, 'reserva', ?, ?, ?);

COMMIT;
```

### 3.2 Liberar Reserva

**Cuándo:** Pago rechazado, expiración, cancelación de orden

```sql
START TRANSACTION;

SELECT cantidad FROM reservas_inventario 
WHERE orden_id = ? AND estado = 'activa' FOR UPDATE;

UPDATE inventario i
JOIN reservas_inventario r ON i.producto_id = r.producto_id
SET i.cantidad_reservada = i.cantidad_reservada - r.cantidad
WHERE r.orden_id = ? AND r.estado = 'activa';

UPDATE reservas_inventario 
SET estado = 'liberada' 
WHERE orden_id = ? AND estado = 'activa';

INSERT INTO movimientos_inventario (...)
VALUES (?, ?, 'liberacion', -?, ?, ?);

COMMIT;
```

### 3.3 Confirmar Descuento

**Cuándo:** PayPal confirma el pago exitosamente

```sql
START TRANSACTION;

-- Mover de reservado a salida definitiva
UPDATE inventario i
JOIN reservas_inventario r ON i.producto_id = r.producto_id
SET i.cantidad = i.cantidad - r.cantidad,
    i.cantidad_reservada = i.cantidad_reservada - r.cantidad
WHERE r.orden_id = ? AND r.estado = 'activa';

UPDATE reservas_inventario 
SET estado = 'confirmada' 
WHERE orden_id = ? AND estado = 'activa';

INSERT INTO movimientos_inventario (...)
VALUES (?, ?, 'salida', -?, ?, ?);

COMMIT;
```

---

## 4. Expiración Automática de Reservas

### 4.1 Mecanismo

Las reservas expiran a los 30 minutos. Se implementa mediante:

1. **Cron job** (recomendado para producción):
   ```bash
   # Cada 5 minutos: libera reservas expiradas
   */5 * * * * php /ruta/ecommerce/cron/liberar-reservas.php
   ```

2. **Lazy expiration** (alternativa simple):
   - Cada vez que se consulta el stock por un producto
   - Se verifica si hay reservas expiradas para ese producto
   - Se liberan automáticamente

### 4.2 SQL de Liberación

```sql
-- Buscar reservas expiradas (más de 30 min)
SELECT * FROM reservas_inventario 
WHERE estado = 'activa' 
  AND fecha_expiracion < NOW();

-- Para cada una, liberar stock (ver 3.2)
```

---

## 5. Alertas de Stock Bajo

Se genera alerta cuando `stock_disponible <= umbral_alerta`.

**Visibilidad:**
- Dashboard admin: tarjeta "Alertas de Stock" con count
- Catálogo público: badge "Últimas unidades" si `stock_disponible <= 5`

---

## 6. Trazabilidad (Movimientos)

Cada operación de inventario se registra en `movimientos_inventario`:

| Tipo | Cuándo | Cantidad |
|------|--------|----------|
| `entrada` | Admin agrega stock | + |
| `salida` | Pago confirmado (venta) | - |
| `reserva` | Checkout | + (aumenta reservado) |
| `liberacion` | Expira/cancelación | - (disminuye reservado) |
| `ajuste` | Admin corrige stock | +/- |

---

## 7. Políticas Anti-Race Condition

| Riesgo | Solución |
|--------|----------|
| Dos usuarios compran el último producto | `FOR UPDATE` en la transacción (lock de fila) |
| Reserva se libera mientras se confirma | Validar estado de reserva antes de confirmar |
| Admin ajusta stock durante una compra | Transacciones aíslan las operaciones |
| Webhook duplicado de PayPal | Verificar `event_id` único antes de procesar |
