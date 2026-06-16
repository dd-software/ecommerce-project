# Modelo de Dominio — Ecommerce UCT

## Entidades Principales

```
┌─────────────┐     ┌─────────────┐     ┌─────────────┐
│   Usuario   │     │  Producto   │     │  Categoria  │
├─────────────┤     ├─────────────┤     ├─────────────┤
│ id (INT)    │◄────│ id (INT)    │────►│ id (INT)    │
│ nombre      │     │ sku         │     │ nombre      │
│ apellido    │     │ nombre      │     │ descripcion │
│ email       │     │ descripcion │     │ activa      │
│ password    │     │ precio      │     │ orden       │
│ rol (ENUM)  │     │ precio_desc │     └─────────────┘
│ activo      │     │ categoria_id│
│ fecha_reg   │     │ activo      │
│ ultimo_ac   │     │ destacado   │
└─────────────┘     │ slug        │
       │            │ fecha_crea  │
       │            └──────┬──────┘
       │                   │
       ▼                   ▼
┌─────────────┐     ┌─────────────┐
│   Pedido    │     │ Inventario  │
├─────────────┤     ├─────────────┤
│ id (INT)    │     │ id (INT)    │
│ numero      │     │ producto_id │
│ usuario_id  │────►│ cantidad    │
│ estado      │     │ cant_reserv │
│ subtotal    │     │ umbral_alte │
│ iva         │     └──────┬──────┘
│ costo_envio │            │
│ total       │            ▼
│ dir_envio   │     ┌──────────────────┐
│ notas       │     │ Reservas_inv     │
│ fecha_crea  │────►├──────────────────┤
└──────┬──────┘     │ id (INT)         │
       │            │ orden_id         │
       ▼            │ producto_id      │
┌─────────────┐     │ cantidad         │
│ Detalle_Ped │     │ estado (ENUM)    │
├─────────────┤     │ fecha_creacion   │
│ id (INT)    │     │ fecha_expiracion │
│ pedido_id   │     └──────────────────┘
│ producto_id │
│ nom_producto│     ┌──────────────┐
│ cantidad    │     │ Movimientos  │
│ precio_unit │     ├──────────────┤
│ subtotal    │     │ id (INT)     │
└─────────────┘     │ producto_id  │
                    │ tipo_mov     │
┌─────────────┐     │ cantidad     │
│    Pago     │     │ referencia   │
├─────────────┤     │ fecha        │
│ id (INT)    │     └──────────────┘
│ pedido_id   │
│ metodo      │     ┌────────────────┐
│ estado      │     │ Configuracion  │
│ monto       │     ├────────────────┤
│ ref_pasarela│     │ id (INT)       │
│ fecha_crea  │     │ clave (UNIQUE) │
│ fecha_pago  │     │ valor          │
└─────────────┘     └────────────────┘
```

## Roles de Usuario

Solo existen 2 roles representados como ENUM en la BD:

| Rol | Descripción |
|-----|-------------|
| `cliente` | Usuario registrado que puede comprar y ver su historial |
| `admin` | Administrador del sistema con acceso al panel de gestión |

No hay roles `empleado`, `supervisor` ni ningún otro.

## Tipos de IDs

Todos los IDs primarios usan **INT AUTO_INCREMENT**:

```sql
id INT AUTO_INCREMENT PRIMARY KEY
```

No se usan UUIDs, ni VARCHAR como clave primaria, ni claves compuestas.

## Expiración de Reservas

Las reservas de inventario expiran en **10 minutos**. El campo `fecha_expiracion` se calcula como:

```sql
fecha_expiracion = DATE_ADD(NOW(), INTERVAL 10 MINUTE)
```

## Estados de Reservas

| Estado | Descripción |
|--------|-------------|
| `activa` | Reserva vigente, esperando confirmación de pago |
| `liberada` | Reserva cancelada, stock devuelto al inventario |
| `confirmada` | Pago confirmado, stock descontado definitivamente |
| `expirada` | Pasaron 10 min sin confirmar, stock liberado automáticamente |

## Estados de Pedido

```
pendiente → confirmado → en_proceso → enviado → entregado
                                                           
  └── cancelado     └── reembolsado                         
```

## Notas Pedagógicas Clave

- Los **INT AUTO_INCREMENT** son más simples que UUIDs para aprender JOINs y relaciones
- El **ENUM** para roles muestra cómo restringir valores en MySQL
- La **reserva de 10 min** enseña el concepto de transacciones temporales y expiración
- La separación de tablas (productos, inventario, movimientos) muestra normalización y auditoría
