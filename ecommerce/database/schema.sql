-- Ecommerce Platform - Complete Schema
-- MySQL 8.0+

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- TABLES
-- ============================================================
use ecommerce;
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

INSERT INTO productos (id, sku, nombre, descripcion, precio, precio_descuento, categoria_id, activo, destacado, es_nuevo, slug)
VALUES
(UUID(), 'ELEC-001', 'Audífonos Bluetooth Pro', 'Audífonos inalámbricos con cancelación de ruido activa, 30 horas de batería y micrófono incorporado. Sonido envolvente de alta fidelidad.', 49990, 39990, (SELECT id FROM categorias WHERE slug = 'electronica'), 1, 1, 1, 'audifonos-bluetooth-pro'),
(UUID(), 'ELEC-002', 'Teclado Mecánico RGB', 'Teclado mecánico con switches Cherry MX Red, retroiluminación RGB personalizable por tecla, reposamuñecas incluido. USB-C desmontable.', 89990, 74990, (SELECT id FROM categorias WHERE slug = 'electronica'), 1, 0, 1, 'teclado-mecanico-rgb'),
(UUID(), 'ELEC-003', 'Monitor 27" 4K UHD', 'Monitor IPS 4K de 27 pulgadas, 99% sRGB, HDR10, FreeSync, puertos HDMI 2.1 y DisplayPort 1.4. Ideal para diseño y gaming.', 349990, NULL, (SELECT id FROM categorias WHERE slug = 'electronica'), 1, 1, 1, 'monitor-27-4k-uhd'),
(UUID(), 'ELEC-004', 'Cargador Inalámbrico Rápido', 'Base de carga rápida 15W compatible con Qi. Carga tu teléfono sin cables. Diseño compacto con indicador LED.', 19990, 15990, (SELECT id FROM categorias WHERE slug = 'electronica'), 1, 0, 1, 'cargador-inalambrico-rapido'),
(UUID(), 'ELEC-005', 'Mouse Gamer 16000 DPI', 'Mouse óptico gaming con sensor de 16.000 DPI, 8 botones programables, iluminación RGB y cable paracord ultraligero.', 45990, 35990, (SELECT id FROM categorias WHERE slug = 'electronica'), 1, 1, 1, 'mouse-gamer-16000-dpi');

-- Hogar (5 productos)
INSERT INTO productos (id, sku, nombre, descripcion, precio, precio_descuento, categoria_id, activo, destacado, es_nuevo, slug)
VALUES
(UUID(), 'HOG-001', 'Lámpara LED Inteligente', 'Lámpara de escritorio LED con control por app, temperatura de color ajustable (3000K-6500K), 5 niveles de brillo y temporizador.', 34990, 29990, (SELECT id FROM categorias WHERE slug = 'hogar'), 1, 1, 1, 'lampara-led-inteligente'),
(UUID(), 'HOG-002', 'Set de Sartenes Antiadherentes', 'Set de 3 sartenes (20/24/28 cm) con recubrimiento de titanio antiadherente, aptas para inducción. Mangos ergonómicos soft-touch.', 59990, 49990, (SELECT id FROM categorias WHERE slug = 'hogar'), 1, 0, 1, 'set-sartenes-antiadherentes'),
(UUID(), 'HOG-003', 'Organizador de Escritorio', 'Organizador modular de bambú con 5 compartimentos, soporte para celular y bandeja para accesorios. Montaje sin herramientas.', 24990, NULL, (SELECT id FROM categorias WHERE slug = 'hogar'), 1, 0, 1, 'organizador-escritorio-bambu'),
(UUID(), 'HOG-004', 'Humidificador Ultrasónico', 'Humidificador de aire 4L con difusor de aromas, LED de 7 colores, apagado automático y funcionamiento silencioso. Hasta 30 horas continuas.', 39990, 34990, (SELECT id FROM categorias WHERE slug = 'hogar'), 1, 1, 1, 'humidificador-ultrasonico'),
(UUID(), 'HOG-005', 'Estantería Modular', 'Estantería metálica de 5 niveles con capacidad de 150 kg por repisa. Acabado en polvo epóxico resistente a rayones. Fácil montaje.', 69990, NULL, (SELECT id FROM categorias WHERE slug = 'hogar'), 1, 0, 1, 'estanteria-modular-metalica');

-- Ropa (5 productos)
INSERT INTO productos (id, sku, nombre, descripcion, precio, precio_descuento, categoria_id, activo, destacado, es_nuevo, slug)
VALUES
(UUID(), 'ROP-001', 'Chaqueta Impermeable', 'Chaqueta cortaviento con membrana impermeable 10K/10K, capucha ajustable, costuras termoselladas y bolsillos con cremallera. Ideal para trekking.', 89990, 69990, (SELECT id FROM categorias WHERE slug = 'ropa'), 1, 1, 1, 'chaqueta-impermeable-trekking'),
(UUID(), 'ROP-002', 'Zapatillas Running Ultraliv', 'Zapatillas de running con amortiguación reactiva, malla transpirable, suela Vibram y drop de 8mm. Peso: 240g.', 79990, NULL, (SELECT id FROM categorias WHERE slug = 'ropa'), 1, 1, 1, 'zapatillas-running-ultraliv'),
(UUID(), 'ROP-003', 'Mochila Urbana 25L', 'Mochila impermeable con compartimento acolchado para laptop 15.6", puerto USB externo, espalda ergonómica y correas ajustables.', 45990, 39990, (SELECT id FROM categorias WHERE slug = 'ropa'), 1, 0, 1, 'mochila-urbana-25l'),
(UUID(), 'ROP-004', 'Camisa de Lino Premium', 'Camisa de lino 100% peinado, corte slim fit, cuello semi-italiano y botones de nácar. Transpirable y elegante para verano.', 49990, NULL, (SELECT id FROM categorias WHERE slug = 'ropa'), 1, 0, 1, 'camisa-lino-premium-slim'),
(UUID(), 'ROP-005', 'Jeans Denim Japonés', 'Jeans de denim japonés 14oz con costuras reforzadas, tinte índigo natural y ajuste recto. Fabricación artesanal con detalles en cobre.', 69990, 59990, (SELECT id FROM categorias WHERE slug = 'ropa'), 1, 1, 1, 'jeans-denim-japones');

-- Deportes (5 productos)
INSERT INTO productos (id, sku, nombre, descripcion, precio, precio_descuento, categoria_id, activo, destacado, es_nuevo, slug)
VALUES
(UUID(), 'DEP-001', 'Colchoneta Yoga Premium', 'Colchoneta de yoga 6mm de grosor, superficie antideslizante, material TPE ecológico y correa de transporte incluida. 183x61cm.', 34990, 29990, (SELECT id FROM categorias WHERE slug = 'deportes'), 1, 1, 1, 'colchoneta-yoga-premium'),
(UUID(), 'DEP-002', 'Smartwatch Deportivo', 'Reloj inteligente con GPS integrado, monitor de frecuencia cardíaca, +100 modos deportivos, resistencia 5ATM y batería de 14 días.', 169990, 139990, (SELECT id FROM categorias WHERE slug = 'deportes'), 1, 1, 1, 'smartwatch-deportivo-gps'),
(UUID(), 'DEP-003', 'Pesas Ajustables 24kg', 'Kit de mancuernas ajustables de 2-24 kg por unidad con mecanismo de cambio rápido. Base de almacenamiento incluida. Ahorra espacio.', 249990, 199990, (SELECT id FROM categorias WHERE slug = 'deportes'), 1, 0, 1, 'pesas-ajustables-24kg'),
(UUID(), 'DEP-004', 'Botella Térmica 750ml', 'Botella de acero inoxidable doble pared, mantiene frío 24h y caliente 12h. Boca ancha para hielo, tapa hermética y mosquetón.', 19990, NULL, (SELECT id FROM categorias WHERE slug = 'deportes'), 1, 0, 1, 'botella-termica-750ml'),
(UUID(), 'DEP-005', 'Cuerda de Saltar Profesional', 'Cuerda de velocidad con cable de acero recubierto, rodamientos de bolas suizos, mangos ergonómicos y longitud ajustable. Incluye bolsa.', 14990, 9990, (SELECT id FROM categorias WHERE slug = 'deportes'), 1, 0, 1, 'cuerda-saltar-profesional');

-- ============================================================
-- Inventario para todos los productos
-- ============================================================
INSERT INTO inventario (id, producto_id, cantidad, cantidad_reservada, umbral_alerta)
SELECT UUID(), id, cantidad, 0, umbral
FROM (
    -- Electrónica
    SELECT id, 35 AS cantidad, 5 AS umbral FROM productos WHERE sku = 'ELEC-001'
    UNION ALL SELECT id, 20, 5 FROM productos WHERE sku = 'ELEC-002'
    UNION ALL SELECT id, 12, 3 FROM productos WHERE sku = 'ELEC-003'
    UNION ALL SELECT id, 50, 10 FROM productos WHERE sku = 'ELEC-004'
    UNION ALL SELECT id, 28, 5 FROM productos WHERE sku = 'ELEC-005'
    -- Hogar
    UNION ALL SELECT id, 40, 5 FROM productos WHERE sku = 'HOG-001'
    UNION ALL SELECT id, 25, 5 FROM productos WHERE sku = 'HOG-002'
    UNION ALL SELECT id, 60, 10 FROM productos WHERE sku = 'HOG-003'
    UNION ALL SELECT id, 18, 3 FROM productos WHERE sku = 'HOG-004'
    UNION ALL SELECT id, 15, 3 FROM productos WHERE sku = 'HOG-005'
    -- Ropa
    UNION ALL SELECT id, 30, 5 FROM productos WHERE sku = 'ROP-001'
    UNION ALL SELECT id, 45, 10 FROM productos WHERE sku = 'ROP-002'
    UNION ALL SELECT id, 55, 10 FROM productos WHERE sku = 'ROP-003'
    UNION ALL SELECT id, 22, 5 FROM productos WHERE sku = 'ROP-004'
    UNION ALL SELECT id, 18, 3 FROM productos WHERE sku = 'ROP-005'
    -- Deportes
    UNION ALL SELECT id, 50, 10 FROM productos WHERE sku = 'DEP-001'
    UNION ALL SELECT id, 15, 3 FROM productos WHERE sku = 'DEP-002'
    UNION ALL SELECT id, 10, 2 FROM productos WHERE sku = 'DEP-003'
    UNION ALL SELECT id, 70, 10 FROM productos WHERE sku = 'DEP-004'
    UNION ALL SELECT id, 65, 10 FROM productos WHERE sku = 'DEP-005'
) AS t;

-- ============================================================
-- Imágenes para cada producto (1 imagen principal por producto)
-- ============================================================
INSERT INTO imagenes (id, producto_id, url, alt_text, es_principal, orden)
SELECT UUID(), id, url, nombre, 1, 0
FROM (
    -- Electrónica
    SELECT id, 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=600' AS url, nombre FROM productos WHERE sku = 'ELEC-001'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?w=600', nombre FROM productos WHERE sku = 'ELEC-002'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1527443224154-c4a3942d3acf?w=600', nombre FROM productos WHERE sku = 'ELEC-003'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1662947995689-ec5165848bae?w=600', nombre FROM productos WHERE sku = 'ELEC-004'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1615663245857-ac93bb7c39e7?w=600', nombre FROM productos WHERE sku = 'ELEC-005'
    -- Hogar
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1507473885765-e6ed057ab6fe?w=600', nombre FROM productos WHERE sku = 'HOG-001'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1590794056226-79ef3a8147e1?w=600', nombre FROM productos WHERE sku = 'HOG-002'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1544816155-12df9643f363?w=600', nombre FROM productos WHERE sku = 'HOG-003'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1585771724684-38269d6639fd?w=600', nombre FROM productos WHERE sku = 'HOG-004'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1558618666-fcd25c85f82e?w=600', nombre FROM productos WHERE sku = 'HOG-005'
    -- Ropa
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1544022613-e87ca75a784a?w=600', nombre FROM productos WHERE sku = 'ROP-001'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1542291026-7eec264c27ff?w=600', nombre FROM productos WHERE sku = 'ROP-002'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1553062407-98eeb64c6a62?w=600', nombre FROM productos WHERE sku = 'ROP-003'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1598033129183-c4f50d736b10?w=600', nombre FROM productos WHERE sku = 'ROP-004'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1542272454315-4c01d7abdf4a?w=600', nombre FROM productos WHERE sku = 'ROP-005'
    -- Deportes
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1601925260368-ae2f83cf8b7f?w=600', nombre FROM productos WHERE sku = 'DEP-001'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=600', nombre FROM productos WHERE sku = 'DEP-002'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1534438327276-14e5300c3a48?w=600', nombre FROM productos WHERE sku = 'DEP-003'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1602143407151-7111542de6fc?w=600', nombre FROM productos WHERE sku = 'DEP-004'
    UNION ALL SELECT id, 'https://images.unsplash.com/photo-1571019613454-1cb2f99b2d8b?w=600', nombre FROM productos WHERE sku = 'DEP-005'
) AS t;