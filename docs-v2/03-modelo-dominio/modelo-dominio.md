# Modelo de Dominio

> **Propósito educativo:** Este documento define las entidades del sistema, sus atributos y relaciones. Los estudiantes deben usar este modelo como guía para diseñar la base de datos y las clases del backend.

---

## 1. Diagrama de Clases

```
┌─────────────────────────────────────────────────────────────────────────────┐
│                                                                             │
│  ┌──────────────┐     ┌────────────────┐     ┌──────────────────────┐      │
│  │   Usuario    │1──N→│   Dirección    │     │      Carrito        │      │
│  │──────────────│     │────────────────│     │──────────────────────│      │
│  │ id (UUID)    │     │ id (UUID)      │     │ id (UUID)            │      │
│  │ nombre       │     │ usuario_id FK  │     │ usuario_id FK (UQ)   │      │
│  │ apellido     │     │ alias          │ 1   │ estado               │      │
│  │ email (UQ)   │     │ calle          │──────│ (activo/pendiente/   │      │
│  │ password_hash│     │ ciudad         │     │  comprado/abandonado)│      │
│  │ rol          │     │ estado         │     │ created_at           │      │
│  │ activo       │     │ codigo_postal  │     └────────┬─────────────┘      │
│  │ email_verif. │     │ pais           │              │ 1                  │
│  │ avatar_url   │     │ referencia     │              │                    │
│  │ fecha_reg.   │     │ predeterminada │              ▼ N                  │
│  │ ult_acceso   │     │ archivada      │     ┌──────────────────────┐      │
│  └──────┬───────┘     └────────────────┘     │    ItemCarrito      │      │
│         │                                    │──────────────────────│      │
│         │ 1                                  │ id (UUID)            │      │
│         │                                    │ carrito_id FK        │      │
│         ▼ N                                  │ producto_id FK       │      │
│  ┌──────────────┐                            │ cantidad             │      │
│  │   Pedido     │                            │ precio_unitario      │      │
│  │──────────────│                            └──────────────────────┘      │
│  │ id (UUID)    │                                                          │
│  │ numero (UQ)  │     ┌────────────────┐     ┌──────────────────────┐      │
│  │ usuario_id   │1──N→│ DetallePedido  │     │      Producto        │      │
│  │ estado       │     │────────────────│     │──────────────────────│      │
│  │ subtotal     │     │ id (UUID)      │     │ id (UUID)            │      │
│  │ descuento    │     │ pedido_id FK   │     │ sku (UQ)             │      │
│  │ costo_envio  │     │ producto_id FK │     │ nombre               │      │
│  │ total        │     │ nombre_prod.   │     │ descripcion          │      │
│  │ direccion    │     │ sku_prod.      │     │ precio               │      │
│  │ notas        │     │ cantidad       │     │ precio_descuento     │      │
│  │ created_at   │     │ precio_unit.   │     │ categoria_id FK      │      │
│  └──────┬───────┘     │ descuento_uni. │     │ activo               │      │
│         │             │ subtotal       │     │ destacado            │      │
│         │ 1           └────────────────┘     │ slug (UQ)            │      │
│         ▼                                    └──────┬───────────────┘      │
│  ┌──────────────┐                                  │ 1                     │
│  │    Pago      │                                  │                       │
│  │──────────────│                                  │ N                     │
│  │ id (UUID)    │     ┌────────────────┐     ┌──────────────────────┐      │
│  │ pedido_id (UQ)│    │   Imagen       │     │    Inventario        │      │
│  │ metodo       │     │────────────────│     │──────────────────────│      │
│  │ estado       │     │ id (UUID)      │     │ id (UUID)            │      │
│  │ monto        │     │ producto_id FK │     │ producto_id FK (UQ)  │      │
│  │ moneda       │     │ url            │     │ cantidad             │      │
│  │ ref_pasarela │     │ alt_text       │     │ cantidad_reservada   │      │
│  │ fecha_pago   │     │ es_principal   │     │ umbral_alerta        │      │
│  └──────┬───────┘     │ orden          │     └──────────────────────┘      │
│         │             └────────────────┘                                   │
│         ▼ N                                                                │
│  ┌──────────────────┐              ┌──────────────────────┐                │
│  │ HistorialTransac.│              │  Categoría           │                │
│  │──────────────────│              │──────────────────────│                │
│  │ id (UUID)        │              │ id (UUID)            │                │
│  │ pago_id FK       │              │ nombre (UQ)          │                │
│  │ estado_anterior  │              │ slug (UQ)            │                │
│  │ estado_nuevo     │              │ descripcion          │                │
│  │ observacion      │              │ imagen_url           │                │
│  │ created_at       │              │ categoria_padre_id FK│                │
│  └──────────────────┘              │ activa               │                │
│                                    │ orden                │                │
│  ┌──────────────┐                  └──────────────────────┘                │
│  │Roles/Permisos│                                                          │
│  │──────────────│     ┌────────────────┐     ┌──────────────────────┐      │
│  │ roles        │     │ MovInventario │     │   Auditoría          │      │
│  │ permisos     │     │────────────────│     │──────────────────────│      │
│  │ rol_permisos │     │ id (UUID)      │     │ id (UUID)            │      │
│  └──────────────┘     │ producto_id FK │     │ usuario_id FK        │      │
│                       │ tipo_movimiento│     │ modulo               │      │
│  ┌──────────────┐     │ cantidad       │     │ accion               │      │
│  │Configuración │     │ referencia     │     │ entidad_tipo         │      │
│  │──────────────│     │ usuario_id FK  │     │ entidad_id           │      │
│  │ clave (UQ)   │     │ created_at     │     │ detalles (JSON)      │      │
│  │ valor        │     └────────────────┘     │ ip_address           │      │
│  │ descripcion  │                            │ created_at           │      │
│  └──────────────┘                            └──────────────────────┘      │
│                                                                             │
└─────────────────────────────────────────────────────────────────────────────┘
```

---

## 2. Entidades Detalladas

### 2.1 Usuario (`usuarios`)

| Atributo | Tipo | Restricción | Descripción |
|----------|------|-------------|-------------|
| id | CHAR(36) PK | UUID | Identificador único universal |
| nombre | VARCHAR(100) | NOT NULL | Nombre del usuario |
| apellido | VARCHAR(100) | NOT NULL | Apellido del usuario |
| email | VARCHAR(255) | UNIQUE, NOT NULL | Email de inicio de sesión |
| password_hash | VARCHAR(255) | NOT NULL | Hash bcrypt de la contraseña |
| rol | ENUM | cliente/empleado/supervisor/admin | Rol del usuario |
| activo | TINYINT(1) | DEFAULT 1 | Si el usuario está habilitado |
| email_verificado | TINYINT(1) | DEFAULT 0 | Si verificó su email |
| avatar_url | VARCHAR(500) | NULL | URL del avatar |
| fecha_registro | DATETIME | DEFAULT NOW | Cuándo se registró |
| ultimo_acceso | DATETIME | NULL | Último inicio de sesión |
| created_at | DATETIME | DEFAULT NOW | Fecha de creación del registro |
| updated_at | DATETIME | ON UPDATE NOW | Fecha de última modificación |

### 2.2 Producto (`productos`)

| Atributo | Tipo | Restricción | Descripción |
|----------|------|-------------|-------------|
| id | CHAR(36) PK | UUID | Identificador único |
| sku | VARCHAR(20) | UNIQUE, NOT NULL | Código interno del producto |
| nombre | VARCHAR(200) | NOT NULL | Nombre visible en tienda |
| descripcion | TEXT | NOT NULL | Descripción detallada |
| precio | DECIMAL(10,2) | > 0 | Precio normal |
| precio_descuento | DECIMAL(10,2) | NULL, < precio | Precio con descuento |
| categoria_id | CHAR(36) FK | NOT NULL | Categoría a la que pertenece |
| activo | TINYINT(1) | DEFAULT 1 | Visible en catálogo |
| destacado | TINYINT(1) | DEFAULT 0 | Aparece en sección destacados |
| es_nuevo | TINYINT(1) | DEFAULT 1 | Marca de producto nuevo |
| slug | VARCHAR(250) | UNIQUE, NOT NULL | URL amigable |
| created_at | DATETIME | DEFAULT NOW | |
| updated_at | DATETIME | ON UPDATE NOW | |
| **Índices:** idx_slug, idx_sku, idx_categoria, idx_activo, idx_destacado, FULLTEXT(nombre, descripcion) | | | |

### 2.3 Inventario (`inventario`)

| Atributo | Tipo | Restricción | Descripción |
|----------|------|-------------|-------------|
| id | CHAR(36) PK | UUID | |
| producto_id | CHAR(36) FK | UNIQUE, NOT NULL | Producto asociado |
| cantidad | INT | DEFAULT 0, >= 0 | Stock total |
| cantidad_reservada | INT | DEFAULT 0, <= cantidad | Stock apartado por compras en curso |
| umbral_alerta | INT | NULL | Stock mínimo para alertar |
| updated_at | DATETIME | ON UPDATE NOW | |
| created_at | DATETIME | DEFAULT NOW | |

**Cálculos derivados:**
- `stock_disponible = cantidad - cantidad_reservada`
- `stock_bajo = stock_disponible <= umbral_alerta`

### 2.4 Pedido (`pedidos`)

| Atributo | Tipo | Restricción | Descripción |
|----------|------|-------------|-------------|
| id | CHAR(36) PK | UUID | |
| numero | VARCHAR(30) | UNIQUE, NOT NULL | ORD-YYYY-NNNNN |
| usuario_id | CHAR(36) FK | NOT NULL | Quién compra |
| estado | ENUM | ver máquina de estados | Estado del pedido |
| subtotal | DECIMAL(10,2) | DEFAULT 0 | Suma de items |
| descuento_total | DECIMAL(10,2) | DEFAULT 0 | Descuentos aplicados |
| costo_envio | DECIMAL(10,2) | DEFAULT 0 | Costo de envío |
| total | DECIMAL(10,2) | DEFAULT 0 | Subtotal + IVA + envío |
| direccion_envio | JSON | NOT NULL | Dirección de entrega completa |
| notas | VARCHAR(500) | NULL | Comentarios del cliente |
| created_at | DATETIME | DEFAULT NOW | |
| updated_at | DATETIME | ON UPDATE NOW | |

**Máquina de estados de Pedido:**
```
pendiente ──→ confirmado ──→ en_preparacion ──→ enviado ──→ entregado
     │                                                          
     └──→ cancelado (solo desde pendiente o confirmado)
```

### 2.5 Pago (`pagos`)

| Atributo | Tipo | Restricción | Descripción |
|----------|------|-------------|-------------|
| id | CHAR(36) PK | UUID | |
| pedido_id | CHAR(36) FK | UNIQUE, NOT NULL | Pedido asociado (1:1) |
| metodo | ENUM | tarjeta/paypal/transferencia/contra_entrega | Método de pago |
| estado | ENUM | pendiente/aprobado/rechazado/reembolsado | Estado del pago |
| monto | DECIMAL(10,2) | NOT NULL | Monto pagado |
| moneda | VARCHAR(3) | DEFAULT 'CLP' | Código ISO 4217 |
| referencia_pasarela | VARCHAR(200) | NULL | ID de transacción PayPal |
| fecha_pago | DATETIME | NULL | Cuándo se completó |
| created_at | DATETIME | DEFAULT NOW | |
| updated_at | DATETIME | ON UPDATE NOW | |

---

## 3. Relaciones Clave

| Desde | Hasta | Tipo | Explicación |
|-------|-------|------|-------------|
| Usuario | Dirección | 1:N | Un usuario puede tener varias direcciones |
| Usuario | Carrito | 1:1 | Un usuario tiene un carrito activo |
| Carrito | ItemCarrito | 1:N | Un carrito tiene muchos items |
| Producto | ItemCarrito | 1:N | Un producto puede estar en varios carritos |
| Producto | Imagen | 1:N | Un producto tiene varias imágenes |
| Producto | Inventario | 1:1 | Un producto tiene un registro de inventario |
| Producto | Categoría | N:1 | Muchos productos pertenecen a una categoría |
| Usuario | Pedido | 1:N | Un usuario tiene muchos pedidos |
| Pedido | DetallePedido | 1:N | Un pedido tiene muchos detalles |
| Pedido | Pago | 1:1 | Un pedido tiene un pago asociado |
| Pago | HistorialTransaccion | 1:N | Un pago tiene historial de cambios |

---

## 4. Restricciones de Integridad

1. **TODO FK debe ser válido** — No huérfanos (ON DELETE CASCADE/RESTRICT)
2. **stock_disponible >= 0** — CHECK en BD y validación en backend
3. **precio > 0** — CHECK en BD
4. **email UNIQUE** — No emails duplicados
5. **sku UNIQUE** — No SKUs duplicados
6. **pedido_id en pagos es UNIQUE** — Un pago por pedido
7. **producto_id en inventario es UNIQUE** — Un inventario por producto
8. **Transacciones ACID** — Todo el flujo compra usa BEGIN/COMMIT/ROLLBACK
