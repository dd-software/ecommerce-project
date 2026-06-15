# Diseño de Base de Datos

> **Propósito educativo:** Este documento explica el diseño de la base de datos tabla por tabla. Cada decisión tiene una justificación didáctica.

---

## 1. Estrategia General

| Decisión | Elección | ¿Por qué? |
|----------|----------|-----------|
| Motor | InnoDB | Transacciones ACID, Foreign Keys, Row-level locking |
| Charset | utf8mb4 | Soportar emojis, caracteres especiales (ñ, tildes) |
| Collation | utf8mb4_unicode_ci | Ordenamiento correcto en español |
| IDs | UUID (CHAR 36) | No secuenciales, seguros en URLs, distribuibles |
| Fechas | DATETIME | Mayor rango que TIMESTAMP, sin problemas de zona horaria |

---

## 2. Tablas (20 totales)

### 2.1 `usuarios` — Usuarios del sistema
**Propósito:** Almacenar cuentas de todos los roles (cliente, empleado, supervisor, admin)

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | UUID |
| nombre | VARCHAR(100) | Obligatorio |
| apellido | VARCHAR(100) | Obligatorio |
| email | VARCHAR(255) UNIQUE | Usado para login |
| password_hash | VARCHAR(255) | bcrypt cost=12 |
| rol | ENUM('cliente','empleado','supervisor','admin') | Control de acceso |
| activo | TINYINT(1) DEFAULT 1 | Para deshabilitar sin eliminar |
| email_verificado | TINYINT(1) DEFAULT 0 | Para futuro flujo de verificación |
| avatar_url | VARCHAR(500) NULL | URL externa |
| fecha_registro | DATETIME | Generado automáticamente |
| ultimo_acceso | DATETIME | Se actualiza en cada login |
| created_at / updated_at | DATETIME | Auditoría de registro |

**Índices:** email, rol, activo

---

### 2.2 `direcciones` — Direcciones de envío
**Relación:** N direcciones por 1 usuario

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | UUID |
| usuario_id | CHAR(36) FK → usuarios | Dueño de la dirección |
| alias | VARCHAR(50) | "Casa", "Oficina", etc. |
| calle | VARCHAR(200) | Dirección completa |
| ciudad | VARCHAR(100) | |
| estado | VARCHAR(100) | Región en Chile |
| codigo_postal | VARCHAR(20) | |
| pais | VARCHAR(3) DEFAULT 'CL' | Código ISO |
| referencia | VARCHAR(300) | Puntos de referencia |
| es_predeterminada | TINYINT(1) | Seleccionada por defecto en checkout |
| archivada | TINYINT(1) | Soft-delete |

**FK:** `usuario_id → usuarios(id) ON DELETE CASCADE`

---

### 2.3 `categorias` — Categorías de productos
**Relación:** Auto-referencia para subcategorías (categoria_padre_id)

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | UUID |
| nombre | VARCHAR(100) UNIQUE | "Electrónica" |
| slug | VARCHAR(150) UNIQUE | "electronica" (URL amigable) |
| descripcion | VARCHAR(500) | |
| imagen_url | VARCHAR(500) | Opcional |
| categoria_padre_id | CHAR(36) FK → categorias | NULL si es categoría raíz |
| activa | TINYINT(1) DEFAULT 1 | |
| orden | INT DEFAULT 0 | Para ordenar en UI |

**FK:** `categoria_padre_id → categorias(id) ON DELETE SET NULL`

---

### 2.4 `productos` — Catálogo de productos
**Relaciones:** N productos → 1 categoría, 1 producto → N imágenes, 1 producto → 1 inventario

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | UUID |
| sku | VARCHAR(20) UNIQUE | Código interno (ej: LPT-001) |
| nombre | VARCHAR(200) | Nombre visible |
| descripcion | TEXT | Descripción larga (con HTML básico) |
| precio | DECIMAL(10,2) | CHECK > 0 |
| precio_descuento | DECIMAL(10,2) NULL | NULL = sin descuento, CHECK < precio |
| categoria_id | CHAR(36) FK → categorias | |
| activo | TINYINT(1) DEFAULT 1 | Soft-delete |
| destacado | TINYINT(1) DEFAULT 0 | Para sección de destacados |
| es_nuevo | TINYINT(1) DEFAULT 1 | Marca temporal de novedad |
| slug | VARCHAR(250) UNIQUE | URL amigable |

**FK:** `categoria_id → categorias(id)`
**FULLTEXT INDEX:** `(nombre, descripcion)` — para búsqueda rápida

---

### 2.5 `imagenes` — Imágenes de productos
**Relación:** N imágenes por 1 producto

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| producto_id | CHAR(36) FK → productos ON DELETE CASCADE | |
| url | VARCHAR(500) | URL externa o ruta local |
| alt_text | VARCHAR(200) | Texto alternativo (accesibilidad) |
| es_principal | TINYINT(1) DEFAULT 0 | Solo 1 principal por producto |
| orden | INT DEFAULT 0 | Para galería |

---

### 2.6 `inventario` — Stock de productos
**Relación:** 1 inventario → 1 producto (UNIQUE)

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| producto_id | CHAR(36) FK → productos UNIQUE | |
| cantidad | INT DEFAULT 0 | Stock total (CHECK >= 0) |
| cantidad_reservada | INT DEFAULT 0 | Stock apartado (CHECK <= cantidad) |
| umbral_alerta | INT DEFAULT NULL | Stock mínimo para alertar |

---

### 2.7 `reservas_inventario` — Reservas activas
**Propósito:** Trazabilidad de qué órdenes tienen stock reservado

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| orden_id | CHAR(36) NULL | Orden asociada |
| producto_id | CHAR(36) FK → productos | Producto reservado |
| cantidad | INT | Cantidad reservada |
| estado | ENUM('activa','liberada','confirmada','expirada') | |
| fecha_expiracion | DATETIME | = created_at + 30 min |

**Índices:** orden_id, producto_id, estado

---

### 2.8 `movimientos_inventario` — Trazabilidad de stock
**Propósito:** Registrar cada cambio de inventario

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| producto_id | CHAR(36) FK → productos | |
| tipo_movimiento | ENUM('entrada','salida','reserva','liberacion','ajuste') | |
| cantidad | INT | Positiva o negativa |
| referencia | VARCHAR(200) | "Orden ORD-2024-00001" |
| usuario_id | CHAR(36) FK → usuarios NULL | Quién hizo el cambio |

---

### 2.9 `carritos` — Carritos de usuario
**Relación:** 1 carrito → 1 usuario (UNIQUE)

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| usuario_id | CHAR(36) FK → usuarios UNIQUE | |
| estado | ENUM('activo','pendiente','comprado','abandonado') | |

---

### 2.10 `items_carrito` — Items dentro del carrito

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| carrito_id | CHAR(36) FK → carritos ON DELETE CASCADE | |
| producto_id | CHAR(36) FK → productos | |
| cantidad | INT DEFAULT 1 | |
| precio_unitario | DECIMAL(10,2) | Precio al momento de agregar |
| **UNIQUE:** | (carrito_id, producto_id) | No duplicados |

---

### 2.11 `pedidos` — Órdenes de compra

| Columna | Tipo | Detalle |
|---------|------|---------|
| id | CHAR(36) PK | |
| numero | VARCHAR(30) UNIQUE | ORD-YYYY-NNNNN |
| usuario_id | CHAR(36) FK → usuarios | |
| estado | ENUM('pendiente','confirmado','en_preparacion','enviado','entregado','cancelado') | |
| subtotal | DECIMAL(10,2) | |
| descuento_total | DECIMAL(10,2) | |
| costo_envio | DECIMAL(10,2) | |
| total | DECIMAL(10,2) | subtotal + IVA + envío |
| direccion_envio | JSON | Snapshot de la dirección al momento de compra |
| notas | VARCHAR(500) | |

---

### 2.12-2.19 Resto de tablas

Ver `database/schema.sql` para el DDL completo con:
- `detalles_pedido` — Items de cada orden
- `pagos` — Pago asociado a orden
- `historial_transacciones` — Trazabilidad de pagos
- `auditoria` — Log de auditoría general
- `sesiones` — Sesiones activas
- `configuracion` — Configuración del sistema
- `roles`, `permisos`, `rol_permisos` — RBAC
- `integracion_logs` — Logs de integración entre módulos

---

## 3. Transacciones Críticas

### Flujo de Compra (transacción completa)

```sql
START TRANSACTION;

-- 1. Validar stock de cada producto
SELECT cantidad, cantidad_reservada FROM inventario WHERE producto_id = ? FOR UPDATE;

-- 2. Crear orden
INSERT INTO pedidos (...) VALUES (...);

-- 3. Crear detalles de pedido
INSERT INTO detalles_pedido (...) VALUES (...);

-- 4. Reservar inventario
UPDATE inventario SET cantidad_reservada = cantidad_reservada + ? WHERE producto_id = ?;
INSERT INTO reservas_inventario (...) VALUES (...);
INSERT INTO movimientos_inventario (...) VALUES (...);

-- 5. Registrar auditoría
INSERT INTO auditoria (...) VALUES (...);

COMMIT;  -- o ROLLBACK si algo falla
```
