-- =============================================================================
-- ESQUEMA DE BASE DE DATOS PARA ECOMMERCE PEDAGÓGICO
-- Motor: MySQL 5.7+ / MariaDB 10+
-- Juego de caracteres: utf8mb4
-- Motor de almacenamiento: InnoDB (transaccional, integridad referencial)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS ecommerce_uct
    DEFAULT CHARACTER SET utf8mb4
    DEFAULT COLLATE utf8mb4_unicode_ci;

USE ecommerce_uct;

-- Desactivar verificaciones para creación ordenada
SET FOREIGN_KEY_CHECKS = 0;

-- =============================================================================
-- TABLA: usuarios
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Gestión de autenticación con password_hash (bcrypt)
-- 2. Control de acceso basado en roles (ENUM)
-- 3. Soft-delete con campo activo (no borrar registros)
-- 4. Trazabilidad con fecha_registro y ultimo_acceso
-- 5. Por qué el password es VARCHAR(255): bcrypt genera hashes de 60 caracteres,
--    pero se deja margen para futuros algoritmos
-- =============================================================================
CREATE TABLE usuarios (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL,
    apellido        VARCHAR(100)    NOT NULL,
    email           VARCHAR(255)    NOT NULL UNIQUE,
    password        VARCHAR(255)    NOT NULL COMMENT 'Hash bcrypt - nunca almacenar contraseñas en texto plano',
    rol             ENUM('cliente', 'admin') NOT NULL DEFAULT 'cliente',
    activo          TINYINT(1)      NOT NULL DEFAULT 1 COMMENT '1=activo, 0=desactivado (soft-delete)',
    fecha_registro  DATETIME        NOT NULL DEFAULT NOW(),
    ultimo_acceso   DATETIME        DEFAULT NULL,
    INDEX idx_usuarios_email (email),
    INDEX idx_usuarios_rol (rol)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: categorias
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Por qué las categorías son una tabla separada y no un campo fijo
-- 2. Normalización: evitar repetición de nombres de categoría en productos
-- 3. Ordenamiento explícito con campo orden (no depender del orden de inserción)
-- 4. Soft-delete con activa para ocultar sin perder integridad referencial
-- =============================================================================
CREATE TABLE categorias (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    nombre          VARCHAR(100)    NOT NULL UNIQUE,
    descripcion     TEXT            DEFAULT NULL COMMENT 'Descripción visible para el cliente',
    activa          TINYINT(1)      NOT NULL DEFAULT 1,
    orden           INT             NOT NULL DEFAULT 0 COMMENT 'Orden de visualización (menor = primero)',
    INDEX idx_categorias_activa (activa),
    INDEX idx_categorias_orden (orden)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: productos
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Diferencia entre SKU (propio del negocio) e ID (autonumérico interno)
-- 2. Precio con descuento: cómo manejar ofertas sin borrar el precio original
-- 3. Slugs URL-friendly para SEO vs IDs numéricos en URLs
-- 4. Clave foránea a categorias: integridad referencial y JOINs
-- 5. DECIMAL(10,2) vs FLOAT: precisión monetaria sin errores de redondeo
-- =============================================================================
CREATE TABLE productos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    sku                 VARCHAR(50)     NOT NULL UNIQUE COMMENT 'Stock Keeping Unit - código interno del producto',
    nombre              VARCHAR(255)    NOT NULL,
    descripcion         TEXT            DEFAULT NULL,
    precio              DECIMAL(10,2)   NOT NULL COMMENT 'Precio normal en CLP (ej: 29990.00)',
    precio_descuento    DECIMAL(10,2)   DEFAULT NULL COMMENT 'Precio con oferta (NULL = sin descuento)',
    categoria_id        INT             NOT NULL,
    activo              TINYINT(1)      NOT NULL DEFAULT 1,
    destacado           TINYINT(1)      NOT NULL DEFAULT 0 COMMENT 'Producto destacado en homepage',
    slug                VARCHAR(255)    NOT NULL UNIQUE COMMENT 'URL amigable: nombre-del-producto',
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (categoria_id) REFERENCES categorias(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_productos_categoria (categoria_id),
    INDEX idx_productos_activo (activo),
    INDEX idx_productos_destacado (destacado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: imagenes
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Relación 1:N: un producto tiene múltiples imágenes
-- 2. Imagen principal vs secundarias (campo es_principal booleano)
-- 3. Nunca almacenar archivos binarios en BD - solo la URL/ruta
-- 4. Orden explícito para galerías
-- =============================================================================
CREATE TABLE imagenes (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    producto_id     INT             NOT NULL,
    url             VARCHAR(500)    NOT NULL COMMENT 'Ruta relativa o URL absoluta de la imagen',
    alt_text        VARCHAR(255)    DEFAULT NULL COMMENT 'Texto alternativo para accesibilidad SEO',
    es_principal    TINYINT(1)      NOT NULL DEFAULT 0 COMMENT '1=imagen principal del producto',
    orden           INT             NOT NULL DEFAULT 0,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_imagenes_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: inventario
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Separación de responsabilidades: stock no va en productos
-- 2. Control de stock con cantidad_reservada (stock temporalmente apartado)
-- 3. Umbral de alerta para notificar reposición
-- 4. Relación 1:1 con productos (UNIQUE en producto_id)
-- =============================================================================
CREATE TABLE inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    producto_id         INT         NOT NULL UNIQUE,
    cantidad            INT         NOT NULL DEFAULT 0 COMMENT 'Stock disponible actual',
    cantidad_reservada  INT         NOT NULL DEFAULT 0 COMMENT 'Stock apartado por carritos/órdenes en progreso',
    umbral_alerta       INT         NOT NULL DEFAULT 5 COMMENT 'Cantidad mínima antes de alertar reposición',
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_inventario_alerta (cantidad, umbral_alerta),
    CHECK (cantidad >= 0),
    CHECK (cantidad_reservada >= 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: reservas_inventario
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Reserva temporal de stock mientras el usuario paga
-- 2. Estados de reserva y ciclo de vida (activa -> confirmada o expirada)
-- 3. Fecha de expiración para liberar stock automáticamente
-- 4. Diferencia entre reserva (temporal) y movimiento de inventario (permanente)
-- =============================================================================
CREATE TABLE reservas_inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    orden_id            INT             NOT NULL COMMENT 'ID del pedido asociado a la reserva',
    producto_id         INT             NOT NULL,
    cantidad            INT             NOT NULL COMMENT 'Cantidad de unidades reservadas',
    estado              ENUM('activa', 'liberada', 'confirmada', 'expirada') NOT NULL DEFAULT 'activa',
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_expiracion    DATETIME        NOT NULL COMMENT 'La reserva expira si no se confirma antes',
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_reservas_estado (estado),
    INDEX idx_reservas_expiracion (fecha_expiracion),
    INDEX idx_reservas_orden (orden_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: movimientos_inventario
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Auditoría: cada cambio de stock queda registrado permanentemente
-- 2. Tipos de movimiento que afectan el stock de distintas formas
-- 3. Referencia a documento externo (factura, orden de compra, nota de ajuste)
-- 4. Trazabilidad completa: saber quién, cuándo, cuánto y por qué
-- =============================================================================
CREATE TABLE movimientos_inventario (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    producto_id         INT             NOT NULL,
    tipo_movimiento     ENUM('entrada', 'salida', 'reserva', 'liberacion', 'ajuste') NOT NULL,
    cantidad            INT             NOT NULL COMMENT 'Cantidad positiva o negativa según el tipo',
    referencia          VARCHAR(255)    DEFAULT NULL COMMENT 'Documento/orden que originó el movimiento (ej: OC-001)',
    fecha               DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_movimientos_producto (producto_id),
    INDEX idx_movimientos_tipo (tipo_movimiento),
    INDEX idx_movimientos_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: items_carrito
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Carrito de compras tanto para usuarios registrados como invitados
-- 2. Sesión identificada por VARCHAR (no requiere tabla de sesiones aparte)
-- 3. Precio_unitario congelado al agregar (el precio del producto puede cambiar)
-- 4. FK nullable a usuarios: carrito invitado vs carrito de usuario logueado
-- =============================================================================
CREATE TABLE items_carrito (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    sesion_id           VARCHAR(255)    NOT NULL COMMENT 'ID de sesión PHP para invitados',
    usuario_id          INT             DEFAULT NULL COMMENT 'NULL si es invitado, FK si está registrado',
    producto_id         INT             NOT NULL,
    cantidad            INT             NOT NULL DEFAULT 1,
    precio_unitario     DECIMAL(10,2)   NOT NULL COMMENT 'Precio al momento de agregar al carrito',
    fecha_agregado      DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_carrito_sesion (sesion_id),
    INDEX idx_carrito_usuario (usuario_id),
    CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: pedidos
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Número de orden legible y único con formato (ORD-YYYY-NNNNN)
-- 2. Desglose financiero: subtotal, IVA, costo_envio, total
-- 3. Estados que reflejan el ciclo de vida completo del pedido
-- 4. Dirección de envío almacenada en el pedido (puede cambiar del registro del usuario)
-- =============================================================================
CREATE TABLE pedidos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    numero              VARCHAR(20)     NOT NULL UNIQUE COMMENT 'Formato: ORD-2026-00001',
    usuario_id          INT             NOT NULL,
    estado              ENUM(
                            'pendiente',
                            'confirmado',
                            'en_proceso',
                            'enviado',
                            'entregado',
                            'cancelado',
                            'reembolsado'
                        ) NOT NULL DEFAULT 'pendiente',
    subtotal            DECIMAL(10,2)   NOT NULL COMMENT 'Suma de precios sin impuestos ni envío',
    iva                 DECIMAL(10,2)   NOT NULL DEFAULT 0.00 COMMENT 'Impuesto (19% en Chile)',
    costo_envio         DECIMAL(10,2)   NOT NULL DEFAULT 0.00,
    total               DECIMAL(10,2)   NOT NULL COMMENT 'subtotal + iva + costo_envio',
    direccion_envio     TEXT            NOT NULL COMMENT 'Dirección completa al momento del pedido',
    notas               TEXT            DEFAULT NULL COMMENT 'Notas del cliente o internas',
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_actualizacion DATETIME        DEFAULT NULL ON UPDATE NOW() COMMENT 'Actualizado por trigger o aplicación',
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pedidos_usuario (usuario_id),
    INDEX idx_pedidos_estado (estado),
    INDEX idx_pedidos_numero (numero),
    INDEX idx_pedidos_fecha (fecha_creacion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Nota: ON UPDATE NOW() en fecha_actualizacion funciona en MariaDB 10.5+
-- Si usas MySQL 5.7, reemplazar por manejo en PHP o trigger:
-- CREATE TRIGGER tr_pedidos_actualizar_fecha
-- BEFORE UPDATE ON pedidos
-- FOR EACH ROW
--     SET NEW.fecha_actualizacion = NOW();

-- =============================================================================
-- TABLA: detalles_pedido
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Normalización: la información del pedido se separa en cabecera y detalle
-- 2. nombre_producto congelado: si el producto se renombra, el pedido conserva
--    el nombre original (registro histórico inmutable)
-- 3. Cada línea tiene su propio subtotal (cantidad * precio_unitario)
-- 4. Relación N:N entre pedidos y productos se resuelve con tabla intermedia
-- =============================================================================
CREATE TABLE detalles_pedido (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL,
    producto_id         INT             NOT NULL COMMENT 'FK al producto (referencial, el nombre se congela aquí)',
    nombre_producto     VARCHAR(255)    NOT NULL COMMENT 'Nombre del producto al momento de la compra',
    cantidad            INT             NOT NULL,
    precio_unitario     DECIMAL(10,2)   NOT NULL COMMENT 'Precio pagado (con descuento aplicado)',
    subtotal            DECIMAL(10,2)   NOT NULL COMMENT 'cantidad * precio_unitario',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_detalles_pedido (pedido_id),
    CHECK (cantidad > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: pagos
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Relación 1:1 con pedidos (UNIQUE en pedido_id) - un pedido, un pago
-- 2. Múltiples métodos de pago manejados desde el mismo esquema
-- 3. referencia_pasarela para conciliación con PayPal/Webpay/Transbank
-- 4. Estados del pago independientes del estado del pedido
-- =============================================================================
CREATE TABLE pagos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id           INT             NOT NULL UNIQUE,
    metodo              ENUM('paypal', 'transferencia', 'tarjeta', 'webpay', 'efectivo') NOT NULL,
    estado              ENUM('pendiente', 'completado', 'rechazado', 'reembolsado') NOT NULL DEFAULT 'pendiente',
    monto               DECIMAL(10,2)   NOT NULL,
    referencia_pasarela VARCHAR(255)    DEFAULT NULL COMMENT 'ID de transacción de PayPal u otra pasarela',
    fecha_creacion      DATETIME        NOT NULL DEFAULT NOW(),
    fecha_pago          DATETIME        DEFAULT NULL COMMENT 'Momento en que se confirmó el pago',
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_pagos_estado (estado),
    INDEX idx_pagos_referencia (referencia_pasarela)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: historial_pagos
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Auditoría de cambios de estado (pista de auditoría obligatoria en finanzas)
-- 2. Trazabilidad: cada transición de estado queda registrada
-- 3. Observaciones para documentar por qué cambió el estado
-- =============================================================================
CREATE TABLE historial_pagos (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    pago_id             INT             NOT NULL,
    estado_anterior     VARCHAR(50)     DEFAULT NULL COMMENT 'NULL si es el primer estado',
    estado_nuevo        VARCHAR(50)     NOT NULL,
    observacion         TEXT            DEFAULT NULL,
    fecha               DATETIME        NOT NULL DEFAULT NOW(),
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE ON UPDATE CASCADE,
    INDEX idx_historial_pago (pago_id),
    INDEX idx_historial_fecha (fecha)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- TABLA: configuracion
-- [PEDAGÓGICO] Los estudiantes aprenden:
-- 1. Patrón clave-valor para configuración dinámica (evitar archivos .config.php)
-- 2. Flexibilidad: agregar configuraciones sin modificar esquema
-- 3. Almacenar valores como texto, convertir según contexto en PHP
-- =============================================================================
CREATE TABLE configuracion (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    clave               VARCHAR(100)    NOT NULL UNIQUE,
    valor               TEXT            NOT NULL,
    fecha_actualizacion DATETIME        NOT NULL DEFAULT NOW()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- =============================================================================
-- REACTIVAR VERIFICACIONES
-- =============================================================================
SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================================
-- DATOS SEMILLA (SEED DATA) PARA DEMO
-- =============================================================================

-- ---------------------------------------------------------------------------
-- 1. Administrador
-- Email: admin@ecommerce.local
-- Password: Admin123!
-- Hash bcrypt generado con coste 12
-- ---------------------------------------------------------------------------
INSERT INTO usuarios (nombre, apellido, email, password, rol, activo, fecha_registro)
VALUES (
    'Admin',
    'Sistema',
    'admin@ecommerce.local',
    '$2y$12$LJ3m4ys3Gql.ZhkBARVOYeQOaXVKzXnXLXvGFCNrqhBIbLhR1HXRa',
    'admin',
    1,
    NOW()
);

-- ---------------------------------------------------------------------------
-- 2. Categorías
-- ---------------------------------------------------------------------------
INSERT INTO categorias (nombre, descripcion, activa, orden) VALUES
('Electrónica',   'Productos electrónicos, gadgets y accesorios tecnológicos', 1, 1),
('Hogar',         'Artículos para el hogar, decoración y menaje',             1, 2),
('Ropa',          'Vestimenta, calzado y accesorios de moda',                 1, 3),
('Deportes',      'Equipamiento deportivo y artículos para actividad física',  1, 4);

-- ---------------------------------------------------------------------------
-- 3. Productos de ejemplo (precios en CLP)
-- ---------------------------------------------------------------------------
INSERT INTO productos (sku, nombre, descripcion, precio, precio_descuento, categoria_id, activo, destacado, slug)
VALUES
('TEC-001', 'Audífonos Bluetooth Pro',
 'Audífonos inalámbricos con cancelación de ruido activa y 30 horas de batería. Cómodos y ligeros.',
 29990, 24990,
 (SELECT id FROM categorias WHERE nombre = 'Electrónica'), 1, 1, 'audifonos-bluetooth-pro'),

('TEC-002', 'Cargador USB-C 65W GaN',
 'Cargador compacto con tecnología GaN, 3 puertos (2 USB-C + 1 USB-A). Carga rápida para laptops y smartphones.',
 15990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Electrónica'), 1, 0, 'cargador-usb-c-65w-gan'),

('HOG-001', 'Lámpara LED Inteligente',
 'Lámpara de mesa con WiFi integrado, compatible con Alexa y Google Home. 16 millones de colores y temperatura ajustable.',
 19990, 17990,
 (SELECT id FROM categorias WHERE nombre = 'Hogar'), 1, 1, 'lampara-led-inteligente'),

('HOG-002', 'Set de Sartenes Antiadherentes',
 'Juego de 3 sartenes (20cm, 24cm, 28cm) con recubrimiento cerámico antiadherente. Aptas para todo tipo de cocinas.',
 34990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Hogar'), 1, 0, 'set-sartenes-antiadherentes'),

('DEP-001', 'Botella Térmica Acero 750ml',
 'Botella de acero inoxidable al vacío. Mantiene bebidas frías 24h o calientes 12h. Diseño deportivo.',
 12990, 9990,
 (SELECT id FROM categorias WHERE nombre = 'Deportes'), 1, 1, 'botella-termica-acero-750ml'),

('ROP-001', 'Polera Algodón Orgánico',
 'Polera de manga corta 100% algodón orgánico certificado. Corte regular, disponible en 5 colores.',
 14990, NULL,
 (SELECT id FROM categorias WHERE nombre = 'Ropa'), 1, 0, 'polera-algodon-organico');

-- ---------------------------------------------------------------------------
-- 4. Inventario inicial para los productos
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
-- 5. Configuración del sistema
-- ---------------------------------------------------------------------------
INSERT INTO configuracion (clave, valor) VALUES
('moneda',                  'CLP'),
('moneda_simbolo',          '$'),
('impuesto_porcentaje',     '19'),
('envio_costo_base',        '4990'),
('envio_gratis_desde',      '50000'),
('reserva_expiracion_minutos', '10'),
('sitio_nombre',            'Mi Ecommerce'),
('sitio_descripcion',       'Tienda en línea pedagógica - Proyecto de aprendizaje'),
('pago_paypal_cliente_id',  ''),
('pago_paypal_secreto',     ''),
('pago_paypal_modo',        'sandbox');
