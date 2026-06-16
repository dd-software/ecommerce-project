# Esquema de Base de Datos — Ecommerce UCT

## Motor: MySQL 5.7+ / MariaDB 10+
## Juego de caracteres: utf8mb4
## Motor de almacenamiento: InnoDB

---

## Tabla: `usuarios`

```sql
CREATE TABLE usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL,
    apellido        VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL COMMENT 'Hash bcrypt',
    rol             ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    activo          TINYINT(1)      NOT NULL DEFAULT 1,
    fecha_registro  DATETIME        NOT NULL DEFAULT NOW(),
    ultimo_acceso   DATETIME        DEFAULT NULL,
    INDEX idx_usuarios_email (email),
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas pedagógicas:**
- `rol` es ENUM con solo `'cliente'` y `'admin'` — no hay empleados
- `password` es VARCHAR(255) para almacenar hash bcrypt (60 chars + margen)
- `fecha_registro` usa `DEFAULT NOW()` (no `CURRENT_TIMESTAMP`)

---

## Tabla: `categorias`

```sql
CREATE TABLE categorias (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL UNIQUE,
    descripcion     TEXT            DEFAULT NULL,
    activa          TINYINT(1)      NOT NULL DEFAULT 1,
    orden           INT             NOT NULL DEFAULT 0,
    INDEX idx_categorias_activa (activa),
    INDEX idx_categorias_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `productos`

```sql
CREATE TABLE productos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    sku                 VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(255)    NOT NULL,
    descripcion         TEXT            DEFAULT NULL,
    precio              DECIMAL(10,2)   NOT NULL,
    precio_descuento    DECIMAL(10,2)   DEFAULT NULL,
    categoria_id        INT             NOT NULL,
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    destacado           TINYINT(1)      NOT NULL DEFAULT 0,
    slug                VARCHAR(255)    NOT NULL UNIQUE,
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_productos_categoria (categoria_id),
    INDEX idx_productos_activo (activo),
    INDEX idx_productos_destacado (destacado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas pedagógicas:**
- `DECIMAL(10,2)` para dinero (nunca FLOAT, evita errores de redondeo)
- El precio con descuento se almacena aparte para conservar el precio original
- `slug` se usa para URLs amigables SEO

---

## Tabla: `imagenes`

```sql
CREATE TABLE imagenes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT             NOT NULL,
    url             VARCHAR(500)    NOT NULL,
    alt_text        VARCHAR(255)    DEFAULT NULL,
    es_principal    TINYINT(1)      NOT NULL DEFAULT 0,
    orden           INT             NOT NULL DEFAULT 0,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_imagenes_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `inventario`

```sql
CREATE TABLE inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    producto_id         INT         NOT NULL UNIQUE,
    cantidad            INT         NOT NULL DEFAULT 0,
    cantidad_reservada  INT         NOT NULL DEFAULT 0,
    umbral_alerta       INT         NOT NULL DEFAULT 5,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_inventario_alerta (cantidad, umbral_alerta),
    CHECK (cantidad >= 0),
    CHECK (cantidad_reservada >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `reservas_inventario`

```sql
CREATE TABLE reservas_inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    orden_id            INT             NOT NULL,
    producto_id         INT             NOT NULL,
    cantidad            INT             NOT NULL,
    estado              ENUM('activa', 'liberada', 'confirmada', 'expirada') NOT NULL DEFAULT 'activa',
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_expiracion    DATETIME        NOT NULL,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_reservas_estado (estado),
    INDEX idx_reservas_expiracion (fecha_expiracion),
    INDEX idx_reservas_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas pedagógicas:**
- `fecha_expiracion` se calcula como `DATE_ADD(NOW(), INTERVAL 10 MINUTE)` en PHP
- El stock se libera automáticamente después de 10 min sin confirmación

---

## Tabla: `movimientos_inventario`

```sql
CREATE TABLE movimientos_inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    producto_id         INT             NOT NULL,
    tipo_movimiento     ENUM('entrada', 'salida', 'reserva', 'liberacion', 'ajuste') NOT NULL,
    cantidad            INT             NOT NULL,
    referencia          VARCHAR(255)    DEFAULT NULL,
    fecha               DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_movimientos_producto (producto_id),
    INDEX idx_movimientos_tipo (tipo_movimiento),
    INDEX idx_movimientos_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `items_carrito`

```sql
CREATE TABLE items_carrito (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id           VARCHAR(255)    NOT NULL,
    usuario_id          INT             DEFAULT NULL,
    producto_id         INT             NOT NULL,
    cantidad            INT             NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(10,2)   NOT NULL,
    fecha_agregado      DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_carrito_sesion (sesion_id),
    INDEX idx_carrito_usuario (usuario_id),
    CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `pedidos`

```sql
CREATE TABLE pedidos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)     NOT NULL UNIQUE,
    usuario_id          INT             NOT NULL,
    estado              ENUM(
                            'pendiente', 'confirmado', 'en_proceso',
                            'enviado', 'entregado', 'cancelado', 'reembolsado'
                        ) NOT NULL DEFAULT 'pendiente',
    subtotal            DECIMAL(10,2)   NOT NULL,
    iva                 DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    costo_envio         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total               DECIMAL(10,2)   NOT NULL,
    direccion_envio     TEXT            NOT NULL,
    notas               TEXT            DEFAULT NULL,
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_actualizacion DATETIME        DEFAULT NULL,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pedidos_usuario (usuario_id),
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_numero (numero),
    INDEX idx_pedidos_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Notas pedagógicas:**
- `fecha_actualizacion` se actualiza desde PHP después de cada cambio de estado (no usa ON UPDATE)
- El número de orden tiene formato `ORD-YYYY-NNNNN`

---

## Tabla: `detalles_pedido`

```sql
CREATE TABLE detalles_pedido (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL,
    producto_id         INT             NOT NULL,
    nombre_producto     VARCHAR(255)    NOT NULL,
    cantidad            INT             NOT NULL,
    precio_unitario     DECIMAL(10,2)   NOT NULL,
    subtotal            DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_detalles_pedido (pedido_id),
    CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `pagos`

```sql
CREATE TABLE pagos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL UNIQUE,
    metodo              ENUM('paypal') NOT NULL DEFAULT 'paypal',
    estado              ENUM('pendiente', 'completado', 'rechazado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
    monto               DECIMAL(10,2)   NOT NULL,
    referencia_pasarela VARCHAR(255)    DEFAULT NULL,
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_pago          DATETIME        DEFAULT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pagos_estado (estado),
    INDEX idx_pagos_referencia (referencia_pasarela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `historial_pagos`

```sql
CREATE TABLE historial_pagos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pago_id             INT             NOT NULL,
    estado_anterior     VARCHAR(50)     DEFAULT NULL,
    estado_nuevo        VARCHAR(50)     NOT NULL,
    observacion         TEXT            DEFAULT NULL,
    fecha               DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_historial_pago (pago_id),
    INDEX idx_historial_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Tabla: `configuracion`

```sql
CREATE TABLE configuracion (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    clave               VARCHAR(100)    NOT NULL UNIQUE,
    valor               TEXT            NOT NULL,
    fecha_actualizacion DATETIME        NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## Diferencias con Versiones Anteriores

| Aspecto | Antes | Ahora |
|---------|-------|-------|
| IDs | UUID (VARCHAR) | INT AUTO_INCREMENT |
| Roles | cliente, empleado, supervisor, admin | cliente, admin |
| Reservas | 30 min | 10 min |
| Métodos de pago | paypal, transferencia, tarjeta | solo paypal |
| Timestamps | CURRENT_TIMESTAMP, ON UPDATE | NOW() en PHP/MySQL |
| Motor BD | InnoDB | InnoDB (sin cambios) |
