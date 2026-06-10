-- Ecommerce Platform - Complete Schema
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLES
-- ============================================================

-- Users & Auth
CREATE TABLE usuarios (
    id CHAR(36) NOT NULL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL,
    apellido VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('cliente','empleado','supervisor','admin') NOT NULL DEFAULT 'cliente',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    email_verificado TINYINT(1) NOT NULL DEFAULT 0,
    avatar_url VARCHAR(500) DEFAULT NULL,
    fecha_registro DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ultimo_acceso DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_rol (rol),
    INDEX idx_activo (activo)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Addresses
CREATE TABLE direcciones (
    id CHAR(36) NOT NULL PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    alias VARCHAR(50) DEFAULT NULL,
    calle VARCHAR(200) NOT NULL,
    ciudad VARCHAR(100) NOT NULL,
    estado VARCHAR(100) NOT NULL,
    codigo_postal VARCHAR(20) NOT NULL,
    pais VARCHAR(3) NOT NULL DEFAULT 'CL',
    referencia VARCHAR(300) DEFAULT NULL,
    es_predeterminada TINYINT(1) NOT NULL DEFAULT 0,
    archivada TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Categories
CREATE TABLE categorias (
    id CHAR(36) NOT NULL PRIMARY KEY,
    nombre VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(150) NOT NULL UNIQUE,
    descripcion VARCHAR(500) DEFAULT NULL,
    imagen_url VARCHAR(500) DEFAULT NULL,
    categoria_padre_id CHAR(36) DEFAULT NULL,
    activa TINYINT(1) NOT NULL DEFAULT 1,
    orden INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_padre_id) REFERENCES categorias(id) ON DELETE SET NULL,
    INDEX idx_activa (activa),
    INDEX idx_padre (categoria_padre_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Products
CREATE TABLE productos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    sku VARCHAR(20) NOT NULL UNIQUE,
    nombre VARCHAR(200) NOT NULL,
    descripcion TEXT NOT NULL,
    precio DECIMAL(10,2) NOT NULL,
    precio_descuento DECIMAL(10,2) DEFAULT NULL,
    categoria_id CHAR(36) NOT NULL,
    activo TINYINT(1) NOT NULL DEFAULT 1,
    destacado TINYINT(1) NOT NULL DEFAULT 0,
    es_nuevo TINYINT(1) NOT NULL DEFAULT 1,
    slug VARCHAR(250) NOT NULL UNIQUE,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (categoria_id) REFERENCES categorias(id),
    INDEX idx_slug (slug),
    INDEX idx_sku (sku),
    INDEX idx_categoria (categoria_id),
    INDEX idx_activo (activo),
    INDEX idx_destacado (destacado),
    FULLTEXT INDEX ft_nombre_desc (nombre, descripcion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Product Images
CREATE TABLE imagenes (
    id CHAR(36) NOT NULL PRIMARY KEY,
    producto_id CHAR(36) NOT NULL,
    url VARCHAR(500) NOT NULL,
    alt_text VARCHAR(200) DEFAULT NULL,
    es_principal TINYINT(1) NOT NULL DEFAULT 0,
    orden INT DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id) ON DELETE CASCADE,
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory
CREATE TABLE inventario (
    id CHAR(36) NOT NULL PRIMARY KEY,
    producto_id CHAR(36) NOT NULL UNIQUE,
    cantidad INT NOT NULL DEFAULT 0,
    cantidad_reservada INT NOT NULL DEFAULT 0,
    umbral_alerta INT DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_producto (producto_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Reservations
CREATE TABLE reservas_inventario (
    id CHAR(36) NOT NULL PRIMARY KEY,
    orden_id CHAR(36) DEFAULT NULL,
    producto_id CHAR(36) NOT NULL,
    cantidad INT NOT NULL,
    estado ENUM('activa','liberada','confirmada','expirada') NOT NULL DEFAULT 'activa',
    fecha_reserva DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_expiracion DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_orden (orden_id),
    INDEX idx_producto (producto_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Inventory Movements
CREATE TABLE movimientos_inventario (
    id CHAR(36) NOT NULL PRIMARY KEY,
    producto_id CHAR(36) NOT NULL,
    tipo_movimiento ENUM('entrada','salida','reserva','liberacion','ajuste') NOT NULL,
    cantidad INT NOT NULL,
    referencia VARCHAR(200) DEFAULT NULL,
    usuario_id CHAR(36) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_producto (producto_id),
    INDEX idx_tipo (tipo_movimiento),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart
CREATE TABLE carritos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL UNIQUE,
    estado ENUM('activo','pendiente','comprado','abandonado') NOT NULL DEFAULT 'activo',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Cart Items
CREATE TABLE items_carrito (
    id CHAR(36) NOT NULL PRIMARY KEY,
    carrito_id CHAR(36) NOT NULL,
    producto_id CHAR(36) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (carrito_id) REFERENCES carritos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    UNIQUE KEY uk_carrito_producto (carrito_id, producto_id),
    INDEX idx_carrito (carrito_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Orders
CREATE TABLE pedidos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    numero VARCHAR(30) NOT NULL UNIQUE,
    usuario_id CHAR(36) NOT NULL,
    estado ENUM('pendiente','confirmado','en_preparacion','enviado','entregado','cancelado') NOT NULL DEFAULT 'pendiente',
    subtotal DECIMAL(10,2) NOT NULL DEFAULT 0,
    descuento_total DECIMAL(10,2) NOT NULL DEFAULT 0,
    costo_envio DECIMAL(10,2) NOT NULL DEFAULT 0,
    total DECIMAL(10,2) NOT NULL DEFAULT 0,
    direccion_envio JSON NOT NULL,
    notas VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id),
    INDEX idx_usuario (usuario_id),
    INDEX idx_estado (estado),
    INDEX idx_numero (numero),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Order Details
CREATE TABLE detalles_pedido (
    id CHAR(36) NOT NULL PRIMARY KEY,
    pedido_id CHAR(36) NOT NULL,
    producto_id CHAR(36) NOT NULL,
    nombre_producto VARCHAR(200) NOT NULL,
    sku_producto VARCHAR(20) NOT NULL,
    cantidad INT NOT NULL DEFAULT 1,
    precio_unitario DECIMAL(10,2) NOT NULL,
    descuento_unitario DECIMAL(10,2) NOT NULL DEFAULT 0,
    subtotal DECIMAL(10,2) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id) ON DELETE CASCADE,
    FOREIGN KEY (producto_id) REFERENCES productos(id),
    INDEX idx_pedido (pedido_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments
CREATE TABLE pagos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    pedido_id CHAR(36) NOT NULL UNIQUE,
    metodo ENUM('tarjeta_credito','tarjeta_debito','transferencia','efectivo_contra_entrega') NOT NULL,
    estado ENUM('pendiente','aprobado','rechazado','reembolsado') NOT NULL DEFAULT 'pendiente',
    monto DECIMAL(10,2) NOT NULL,
    moneda VARCHAR(3) NOT NULL DEFAULT 'CLP',
    referencia_pasarela VARCHAR(200) DEFAULT NULL,
    fecha_pago DATETIME DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (pedido_id) REFERENCES pedidos(id),
    INDEX idx_pedido (pedido_id),
    INDEX idx_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transaction History
CREATE TABLE historial_transacciones (
    id CHAR(36) NOT NULL PRIMARY KEY,
    pago_id CHAR(36) NOT NULL,
    estado_anterior VARCHAR(20) DEFAULT NULL,
    estado_nuevo VARCHAR(20) NOT NULL,
    observacion TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (pago_id) REFERENCES pagos(id) ON DELETE CASCADE,
    INDEX idx_pago (pago_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Log
CREATE TABLE auditoria (
    id CHAR(36) NOT NULL PRIMARY KEY,
    usuario_id CHAR(36) DEFAULT NULL,
    modulo VARCHAR(50) NOT NULL,
    accion VARCHAR(100) NOT NULL,
    entidad_tipo VARCHAR(50) DEFAULT NULL,
    entidad_id VARCHAR(36) DEFAULT NULL,
    detalles JSON DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_modulo (modulo),
    INDEX idx_usuario (usuario_id),
    INDEX idx_fecha (created_at),
    INDEX idx_accion (accion)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions
CREATE TABLE sesiones (
    id CHAR(36) NOT NULL PRIMARY KEY,
    usuario_id CHAR(36) NOT NULL,
    token VARCHAR(255) NOT NULL UNIQUE,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(500) DEFAULT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_token (token),
    INDEX idx_usuario (usuario_id),
    INDEX idx_expires (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Configuration
CREATE TABLE configuracion (
    id CHAR(36) NOT NULL PRIMARY KEY,
    clave VARCHAR(100) NOT NULL UNIQUE,
    valor TEXT NOT NULL,
    descripcion VARCHAR(255) DEFAULT NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_clave (clave)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Roles & Permissions
CREATE TABLE roles (
    id CHAR(36) NOT NULL PRIMARY KEY,
    nombre VARCHAR(50) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE permisos (
    id CHAR(36) NOT NULL PRIMARY KEY,
    codigo VARCHAR(100) NOT NULL UNIQUE,
    descripcion VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE rol_permisos (
    rol_id CHAR(36) NOT NULL,
    permiso_id CHAR(36) NOT NULL,
    PRIMARY KEY (rol_id, permiso_id),
    FOREIGN KEY (rol_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Integration Log
CREATE TABLE integracion_logs (
    id CHAR(36) NOT NULL PRIMARY KEY,
    servicio_origen VARCHAR(100) NOT NULL,
    servicio_destino VARCHAR(100) NOT NULL,
    operacion VARCHAR(100) NOT NULL,
    estado ENUM('exitoso','fallido','pendiente') NOT NULL DEFAULT 'pendiente',
    codigo_respuesta VARCHAR(20) DEFAULT NULL,
    mensaje TEXT DEFAULT NULL,
    payload JSON DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_estado (estado),
    INDEX idx_servicio (servicio_origen, servicio_destino),
    INDEX idx_fecha (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- DEFAULT DATA
-- ============================================================

-- Default Roles
INSERT INTO roles (id, nombre, descripcion) VALUES
(UUID(), 'admin', 'Administrador del sistema con control total'),
(UUID(), 'supervisor', 'Supervisor de operaciones'),
(UUID(), 'empleado', 'Empleado de tienda'),
(UUID(), 'cliente', 'Cliente registrado');

-- Default Permissions
INSERT INTO permisos (id, codigo, descripcion) VALUES
(UUID(), 'admin.access', 'Acceso al panel de administración'),
(UUID(), 'admin.users', 'Gestión de usuarios'),
(UUID(), 'admin.roles', 'Gestión de roles'),
(UUID(), 'admin.config', 'Configuración del sistema'),
(UUID(), 'admin.audit', 'Consulta de auditoría'),
(UUID(), 'products.manage', 'Gestión de productos'),
(UUID(), 'inventory.manage', 'Gestión de inventario'),
(UUID(), 'orders.manage', 'Gestión de pedidos'),
(UUID(), 'orders.view', 'Visualización de pedidos');

-- Default Admin User (password: Admin123!)
INSERT INTO usuarios (id, nombre, apellido, email, password_hash, rol, activo, email_verificado) VALUES
(UUID(), 'Admin', 'Sistema', 'admin@ecommerce.local', '$2y$12$LJ3m4ys3Gql.ZhkBARVOYeQOaXVKzXnXLXvGFCNrqhBIbLhR1HXRa', 'admin', 1, 1);

-- Default Configuration
INSERT INTO configuracion (id, clave, valor, descripcion) VALUES
(UUID(), 'moneda', 'CLP', 'Moneda del sistema'),
(UUID(), 'timezone', 'America/Santiago', 'Zona horaria'),
(UUID(), 'modo_mantenimiento', 'false', 'Modo mantenimiento del sitio'),
(UUID(), 'impuesto_porcentaje', '19', 'Porcentaje de IVA'),
(UUID(), 'envio_costo_base', '4990', 'Costo base de envío'),
(UUID(), 'reserva_expiracion_minutos', '30', 'Minutos antes de expirar reservas');

-- Sample Categories
INSERT INTO categorias (id, nombre, slug, descripcion, activa, orden) VALUES
(UUID(), 'Electrónica', 'electronica', 'Productos electrónicos y tecnología', 1, 1),
(UUID(), 'Hogar', 'hogar', 'Artículos para el hogar', 1, 2),
(UUID(), 'Ropa', 'ropa', 'Vestimenta y accesorios', 1, 3),
(UUID(), 'Deportes', 'deportes', 'Artículos deportivos', 1, 4);

SET FOREIGN_KEY_CHECKS = 1;
