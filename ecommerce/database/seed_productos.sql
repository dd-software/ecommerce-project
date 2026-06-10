-- ============================================================
-- Seed: 5 productos por categoría + inventario + imágenes
-- Plataforma Ecommerce UCT - SDD Academic
-- ============================================================

-- Electrónica (5 productos)
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
