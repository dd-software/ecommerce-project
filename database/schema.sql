-- =============================================================================
-- ESQUEMA DE BASE DE DATOS PARA ECOMMERCE PEDAGÓGICO
-- CON SOPORTE PARA PAYPAL Y TRANSFERENCIA BANCARIA
-- Motor: MySQL 5.7+ / MariaDB 10+
-- =============================================================================

-- ============================================================
-- 1. CREAR BASE DE DATOS (si no existe)
-- ============================================================
CREATE DATABASE IF NOT EXISTS ecommerce_uct
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE ecommerce_uct;

-- Desactivar verificaciones para creación ordenada
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 2. TABLA: usuarios
-- ============================================================
DROP TABLE IF EXISTS usuarios;
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

-- ============================================================
-- 3. TABLA: categorias
-- ============================================================
DROP TABLE IF EXISTS categorias;
CREATE TABLE categorias (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL UNIQUE,
    descripcion     TEXT            DEFAULT NULL,
    activa          TINYINT(1)      NOT NULL DEFAULT 1,
    orden           INT             NOT NULL DEFAULT 0,
    INDEX idx_categorias_activa (activa),
    INDEX idx_categorias_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. TABLA: productos
-- ============================================================
DROP TABLE IF EXISTS productos;
CREATE TABLE productos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    sku                 VARCHAR(50)     NOT NULL UNIQUE,
    nombre              VARCHAR(255)    NOT NULL,
    descripcion         TEXT            DEFAULT NULL,
    precio              DECIMAL(10,2)   NOT NULL,
    precio_descuento    DECIMAL(10,2)   DEFAULT NULL,
    categoria_id        INT             NOT NULL,
    stock               INT             NOT NULL DEFAULT 0 COMMENT 'Stock actual del producto',
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    destacado           TINYINT(1)      NOT NULL DEFAULT 0,
    slug                VARCHAR(255)    NOT NULL UNIQUE,
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_productos_categoria (categoria_id),
    INDEX idx_productos_activo (activo),
    INDEX idx_productos_destacado (destacado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. TABLA: imagenes
-- ============================================================
DROP TABLE IF EXISTS imagenes;
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

-- ============================================================
-- 6. TABLA: inventario (para control de stock detallado)
-- ============================================================
DROP TABLE IF EXISTS inventario;
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

-- ============================================================
-- 7. TABLA: reservas_inventario
-- ============================================================
DROP TABLE IF EXISTS reservas_inventario;
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

-- ============================================================
-- 8. TABLA: movimientos_inventario
-- ============================================================
DROP TABLE IF EXISTS movimientos_inventario;
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

-- ============================================================
-- 9. TABLA: items_carrito
-- ============================================================
DROP TABLE IF EXISTS items_carrito;
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

-- ============================================================
-- 10. TABLA: pedidos (CON PAYPAL Y DIRECCIÓN)
-- ============================================================
DROP TABLE IF EXISTS pedidos;
CREATE TABLE pedidos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)     NOT NULL UNIQUE,
    usuario_id          INT             NOT NULL,
    estado              ENUM(
                            'pendiente',
                            'pagado',
                            'confirmado',
                            'en_proceso',
                            'enviado',
                            'entregado',
                            'cancelado',
                            'reembolsado'
                        ) NOT NULL DEFAULT 'pendiente',
    subtotal            DECIMAL(10,2)   NOT NULL,
    iva                 DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    envio               DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total               DECIMAL(10,2)   NOT NULL,
    
    -- Dirección de envío (desnormalizada)
    calle               VARCHAR(255)    NOT NULL,
    ciudad              VARCHAR(100)    NOT NULL,
    region              VARCHAR(100)    NOT NULL,
    codigo_postal       VARCHAR(20)     DEFAULT NULL,
    notas               TEXT            DEFAULT NULL,
    direccion_envio     TEXT            DEFAULT NULL COMMENT 'Dirección completa (legado)',
    
    -- Método de pago
    metodo_pago         ENUM('paypal', 'transferencia', 'tarjeta', 'webpay', 'efectivo') NOT NULL DEFAULT 'transferencia',
    
    -- PayPal
    paypal_order_id     VARCHAR(100)    DEFAULT NULL,
    paypal_payer_id     VARCHAR(100)    DEFAULT NULL,
    paypal_payment_id   VARCHAR(100)    DEFAULT NULL,
    
    -- Fechas
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_actualizacion DATETIME        DEFAULT NULL,
    fecha_pago          DATETIME        DEFAULT NULL,
    
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pedidos_usuario (usuario_id),
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_numero (numero),
    INDEX idx_pedidos_fecha (fecha_creacion),
    INDEX idx_pedidos_paypal_order (paypal_order_id),
    INDEX idx_pedidos_metodo_pago (metodo_pago)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. TABLA: detalles_pedido (ÍTEMS DEL PEDIDO)
-- ============================================================
DROP TABLE IF EXISTS detalles_pedido;
CREATE TABLE detalles_pedido (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL,
    producto_id         INT             NOT NULL,
    nombre_producto     VARCHAR(255)    NOT NULL COMMENT 'Nombre congelado al momento de la compra',
    cantidad            INT             NOT NULL,
    precio_unitario     DECIMAL(10,2)   NOT NULL,
    subtotal            DECIMAL(10,2)   NOT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_detalles_pedido (pedido_id),
    CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. TABLA: pagos
-- ============================================================
DROP TABLE IF EXISTS pagos;
CREATE TABLE pagos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL UNIQUE,
    metodo              ENUM('paypal', 'transferencia', 'tarjeta', 'webpay', 'efectivo') NOT NULL,
    estado              ENUM('pendiente', 'completado', 'rechazado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
    monto               DECIMAL(10,2)   NOT NULL,
    referencia_pasarela VARCHAR(255)    DEFAULT NULL,
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_pago          DATETIME        DEFAULT NULL,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pagos_estado (estado),
    INDEX idx_pagos_referencia (referencia_pasarela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 13. TABLA: historial_pagos
-- ============================================================
DROP TABLE IF EXISTS historial_pagos;
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

-- ============================================================
-- 14. TABLA: configuracion
-- ============================================================
DROP TABLE IF EXISTS configuracion;
CREATE TABLE configuracion (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    clave               VARCHAR(100)    NOT NULL UNIQUE,
    valor               TEXT            NOT NULL,
    fecha_actualizacion DATETIME        NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- REACTIVAR VERIFICACIONES
-- ============================================================
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 15. DATOS SEMILLA (SEED DATA)
-- ============================================================

-- ---------------------------------------------------------------------------
-- 15.1 Usuario Administrador
-- Email: admin@ecommerce.local
-- Password: Admin123!
-- ---------------------------------------------------------------------------
INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_registro)
VALUES (
    'Admin',
    'Sistema',
    'admin@ecommerce.local',
    '$2y$12$3H4k5L6mN7oP8qR9sT0uV1wX2yZ3A4B5C6D7E8F9G0H1I2J3K4L5M6N7O8P',
    'admin',
    1,
    NOW()
);

-- ---------------------------------------------------------------------------
-- 15.2 Usuario de Prueba
-- Email: usuario@ecommerce.local
-- Password: Usuario123!
-- ---------------------------------------------------------------------------
INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_registro)
VALUES (
    'Usuario',
    'Prueba',
    'usuario@ecommerce.local',
    '$2y$12$4I5J6K7L8M9N0O1P2Q3R4S5T6U7V8W9X0Y1Z2A3B4C5D6E7F8G9H0I1J2K',
    'cliente',
    1,
    NOW()
);

-- ---------------------------------------------------------------------------
-- 15.3 Categorías
-- ---------------------------------------------------------------------------
INSERT INTO categorias (nombre, descripcion, activa, orden) VALUES
('Electrónica',   'Productos electrónicos, gadgets y accesorios tecnológicos', 1, 1),
('Hogar',         'Artículos para el hogar, decoración y menaje',             1, 2),
('Ropa',          'Vestimenta, calzado y accesorios de moda',                 1, 3),
('Deportes',      'Equipamiento deportivo y artículos para actividad física',  1, 4);

-- ---------------------------------------------------------------------------
-- 15.4 Productos de Ejemplo (precios en CLP)
-- ---------------------------------------------------------------------------
INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, categoria_id, stock, activo, destacado, slug)
VALUES
('TEC-001', 'Audífonos Bluetooth Pro',
 'Audífonos inalámbricos con cancelación de ruido activa y 30 horas de batería. Cómodos y ligeros.',
 29990, 24990,
 (SELECT id FROM categorias WHERE nombre = 'Electrónica'), 50, 1, 1, 'audifonos-bluetooth-pro'),

('TEC-002', 'Cargador USB-C 65W GaN',
 'Cargador compacto con tecnología GaN, 3 puertos (2 USB-C + 1 USB-A). Carga rápida para laptops y smartphones.',
 15990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Electrónica'), 100, 1, 0, 'cargador-usb-c-65w-gan'),

('HOG-001', 'Lámpara LED Inteligente',
 'Lámpara de mesa con WiFi integrado, compatible con Alexa y Google Home. 16 millones de colores y temperatura ajustable.',
 19990, 17990,
 (SELECT id FROM categorias WHERE nombre = 'Hogar'), 30, 1, 1, 'lampara-led-inteligente'),

('HOG-002', 'Set de Sartenes Antiadherentes',
 'Juego de 3 sartenes (20cm, 24cm, 28cm) con recubrimiento cerámico antiadherente. Aptas para todo tipo de cocinas.',
 34990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Hogar'), 20, 1, 0, 'set-sartenes-antiadherentes'),

('DEP-001', 'Botella Térmica Acero 750ml',
 'Botella de acero inoxidable al vacío. Mantiene bebidas frías 24h o calientes 12h. Diseño deportivo.',
 12990, 9990,
 (SELECT id FROM categorias WHERE nombre = 'Deportes'), 80, 1, 1, 'botella-termica-acero-750ml'),

('ROP-001', 'Polera Algodón Orgánico',
 'Polera de manga corta 100% algodón orgánico certificado. Corte regular, disponible en 5 colores.',
 14990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Ropa'), 60, 1, 0, 'polera-algodon-organico');

-- ---------------------------------------------------------------------------
-- 15.5 Imágenes de Productos (Ejemplo)
-- ---------------------------------------------------------------------------
INSERT INTO imagenes (producto_id, url, alt_text, es_principal, orden)
SELECT id, '/assets/img/productos/audifonos.jpg', 'Audífonos Bluetooth Pro', 1, 1 FROM productos WHERE sku = 'TEC-001'
UNION ALL
SELECT id, '/assets/img/productos/cargador.jpg', 'Cargador USB-C 65W', 1, 1 FROM productos WHERE sku = 'TEC-002'
UNION ALL
SELECT id, '/assets/img/productos/lampara.jpg', 'Lámpara LED Inteligente', 1, 1 FROM productos WHERE sku = 'HOG-001'
UNION ALL
SELECT id, '/assets/img/productos/sartenes.jpg', 'Set de Sartenes', 1, 1 FROM productos WHERE sku = 'HOG-002'
UNION ALL
SELECT id, '/assets/img/productos/botella.jpg', 'Botella Térmica', 1, 1 FROM productos WHERE sku = 'DEP-001'
UNION ALL
SELECT id, '/assets/img/productos/polera.jpg', 'Polera Algodón', 1, 1 FROM productos WHERE sku = 'ROP-001';

-- ---------------------------------------------------------------------------
-- 15.6 Inventario Inicial
-- ---------------------------------------------------------------------------
INSERT INTO inventario (producto_id, cantidad, cantidad_reservada, umbral_alerta)
SELECT id, 50, 0, 5 FROM productos WHERE sku = 'TEC-001'
UNION ALL
SELECT id, 100, 0, 10 FROM productos WHERE sku = 'TEC-002'
UNION ALL
SELECT id, 30, 0, 5 FROM productos WHERE sku = 'HOG-001'
UNION ALL
SELECT id, 20, 0, 3 FROM productos WHERE sku = 'HOG-002'
UNION ALL
SELECT id, 80, 0, 10 FROM productos WHERE sku = 'DEP-001'
UNION ALL
SELECT id, 60, 0, 10 FROM productos WHERE sku = 'ROP-001';

-- ---------------------------------------------------------------------------
-- 15.7 Configuración del Sistema
-- ---------------------------------------------------------------------------
INSERT INTO configuracion (clave, valor) VALUES
('moneda',                  'CLP'),
('moneda_simbolo',          '$'),
('impuesto_porcentaje',     '19'),
('envio_costo_base',        '4990'),
('envio_gratis_desde',      '50000'),
('reserva_expiracion_minutos', '10'),
('sitio_nombre',            'Mi Ecommerce UCT'),
('sitio_descripcion',       'Tienda en línea pedagógica - Proyecto de aprendizaje'),

-- PayPal
('pago_paypal_cliente_id',  'sb'),
('pago_paypal_secreto',     ''),
('pago_paypal_modo',        'sandbox'),
('pago_paypal_currency',    'CLP'),

-- Transferencia Bancaria
('transferencia_banco',     'Banco de Chile'),
('transferencia_cuenta',    '123456789'),
('transferencia_titular',   'Mi Ecommerce UCT'),
('transferencia_rut',       '76.123.456-7'),
('transferencia_email',     'pagos@ecommerce.local');

-- ============================================================
-- 16. VERIFICACIÓN FINAL
-- ============================================================
-- Mostrar todas las tablas creadas
SHOW TABLES;

-- Mostrar usuarios
SELECT id, nombre, email, rol FROM usuarios;

-- Mostrar productos
SELECT id, sku, nombre, precio, stock FROM productos;

-- Mostrar configuración
SELECT clave, valor FROM configuracion;

-- ============================================================
-- FIN DEL SCRIPT
-- ============================================================