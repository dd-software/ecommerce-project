# Gestión de Inventario — Ecommerce UCT

## Visión General

El sistema de inventario maneja el stock de productos, las reservas temporales durante el proceso de compra y la auditoría de todos los movimientos. Usa **IDs INT AUTO_INCREMENT** y tiempos de expiración de **10 minutos**.

---

## Modelo de Datos

### Tabla: `inventario`
Guarda el stock actual y reservado de cada producto.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | ID único |
| producto_id | INT (FK→productos) | Relación 1:1 con producto |
| cantidad | INT | Stock disponible actual (>= 0) |
| cantidad_reservada | INT | Stock apartado por compras en progreso (>= 0) |
| umbral_alerta | INT | Mínimo antes de notificar reposición |

### Tabla: `reservas_inventario`
Registra las reservas temporales durante el checkout.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | ID único |
| orden_id | INT | ID del pedido asociado |
| producto_id | INT (FK→productos) | Producto reservado |
| cantidad | INT | Unidades reservadas |
| estado | ENUM | activa, liberada, confirmada, expirada |
| fecha_creacion | DATETIME | Momento de la reserva |
| fecha_expiracion | DATETIME | La reserva expira a esta hora |

### Tabla: `movimientos_inventario`
Registro de auditoría de todos los cambios de stock.

| Campo | Tipo | Descripción |
|-------|------|-------------|
| id | INT (PK) | ID único |
| producto_id | INT (FK→productos) | Producto afectado |
| tipo_movimiento | ENUM | entrada, salida, reserva, liberacion, ajuste |
| cantidad | INT | Cantidad del movimiento |
| referencia | VARCHAR | Documento/orden que originó el movimiento |
| fecha | DATETIME | Momento del movimiento |

---

## Flujo de Reserva de Inventario

```
1. Usuario hace checkout
   → Verificar stock disponible (cantidad - cantidad_reservada >= solicitado)
   → Crear reserva (estado: 'activa', expira en 10 min)
   → Incrementar cantidad_reservada en inventario

2. Usuario paga con PayPal (éxito)
   → Cambiar reserva a 'confirmada'
   → Descontar cantidad definitiva: cantidad = cantidad - cantidad_reservada
   → Resetear cantidad_reservada a 0
   → Registrar movimiento 'salida'

3. Usuario cancela / pago rechazado / expira 10 min
   → Cambiar reserva a 'liberada' o 'expirada'
   → Decrementar cantidad_reservada
   → Registrar movimiento 'liberacion'
```

---

## Cálculo de Stock Disponible

```sql
-- Stock disponible para la venta
SELECT 
    p.id, p.nombre, 
    i.cantidad, 
    i.cantidad_reservada,
    (i.cantidad - i.cantidad_reservada) AS disponible
FROM productos p
JOIN inventario i ON p.id = i.producto_id
WHERE p.activo = 1;
```

En PHP:

```php
<?php
function stock_disponible($pdo, $producto_id) {
    $stmt = $pdo->prepare("
        SELECT cantidad - cantidad_reservada AS disponible
        FROM inventario WHERE producto_id = ?
    ");
    $stmt->execute([$producto_id]);
    return (int)$stmt->fetchColumn();
}
```

---

## Liberación de Reservas Expiradas

### Función PHP para liberar reservas vencidas

```php
<?php
function liberar_reservas_expiradas($pdo) {
    $stmt = $pdo->prepare("
        UPDATE reservas_inventario r
        JOIN inventario i ON r.producto_id = i.producto_id
        SET r.estado = 'expirada',
            i.cantidad_reservada = i.cantidad_reservada - r.cantidad
        WHERE r.estado = 'activa'
          AND r.fecha_expiracion <= NOW()
    ");
    $stmt->execute();

    // Registrar movimientos
    $stmt = $pdo->prepare("
        INSERT INTO movimientos_inventario (producto_id, tipo_movimiento, cantidad, referencia, fecha)
        SELECT r.producto_id, 'liberacion', r.cantidad, CONCAT('EXP-', r.id), NOW()
        FROM reservas_inventario r
        WHERE r.estado = 'expirada'
          AND r.fecha_expiracion <= NOW()
    ");
    // Usar una subconsulta corregida...

    return $stmt->rowCount();
}

// Llamar desde admin o automáticamente al iniciar un checkout
liberar_reservas_expiradas($pdo);
```

### Alternativa: Consulta SQL directa

```sql
-- Liberar todas las reservas expiradas
UPDATE reservas_inventario r
JOIN inventario i ON r.producto_id = i.producto_id
SET r.estado = 'expirada',
    i.cantidad_reservada = i.cantidad_reservada - r.cantidad
WHERE r.estado = 'activa'
  AND r.fecha_expiracion <= NOW();
```

---

## Estado de Alerta de Stock

```php
<?php
function productos_con_stock_bajo($pdo) {
    $stmt = $pdo->query("
        SELECT p.id, p.nombre, p.sku,
               i.cantidad, i.umbral_alerta
        FROM productos p
        JOIN inventario i ON p.id = i.producto_id
        WHERE (i.cantidad - i.cantidad_reservada) <= i.umbral_alerta
          AND p.activo = 1
        ORDER BY (i.cantidad - i.cantidad_reservada) ASC
    ");
    return $stmt->fetchAll();
}
```

---

## Notas Pedagógicas

- Las **IDs INT** simplifican JOINs y son más fáciles de entender que UUIDs
- La **reserva de 10 min** enseña gestión de estado temporal y expiración en sistemas transaccionales
- La **tabla de movimientos** demuestra auditoría — nunca perder el rastro de cambios de stock
- Las **transacciones SQL** (`BEGIN/COMMIT/ROLLBACK`) son esenciales para la consistencia
- El **cálculo `cantidad - cantidad_reservada`** muestra stock en tiempo real sin bloqueos largos
