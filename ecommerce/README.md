# Plataforma E-commerce

Plataforma de comercio electrónico completa construida con PHP 8.2+, MySQL 8.0+ y Apache.

## Módulos

| Módulo | Descripción |
|--------|-------------|
| A - Catálogo | Listado/detalle de productos, disponibilidad, precios |
| B - Carrito | Agregar/modificar/eliminar productos, validar stock, cálculos |
| C - Autenticación | Login, sesiones, registro de eventos, validación credenciales |
| D - Checkout | Validar compra, datos de entrega, generar orden, cálculos |
| E - Pasarela de Pago | Procesar pagos, proveedores externos, seguridad |
| F - Inventario | Consultar stock, reservar, liberar, confirmar descuento |
| G - Administración | Dashboard admin, usuarios, roles, configuración, auditoría |
| H - Integración | Orquestación entre módulos, APIs, eventos, servicios externos |

## Requisitos

- PHP 8.2+
- MySQL 8.0+
- Apache con mod_rewrite
- Composer 2.x

## Instalación

```bash
# 1. Clonar repositorio
git clone <repository-url>
cd ecommerce-project/ecommerce

# 2. Instalar dependencias
composer install

# 3. Configurar entorno
cp .env.example .env
# Editar .env con credenciales de BD

# 4. Crear base de datos
mysql -u root -p < database/schema.sql

# 5. Iniciar servidor
php -S localhost:8000 -t public
```

## Endpoints API

### Catálogo
- `GET /api/catalogo` — Listar productos
- `GET /api/producto/{slug}` — Detalle de producto

### Carrito
- `GET /api/carrito` — Ver carrito (auth)
- `POST /api/carrito/agregar` — Agregar producto
- `POST /api/carrito/actualizar` — Actualizar cantidad (auth)
- `POST /api/carrito/eliminar` — Eliminar producto (auth)
- `POST /api/carrito/vaciar` — Vaciar carrito (auth)
- `POST /api/carrito/fusionar` — Fusionar carrito local (auth)

### Autenticación
- `GET /api/autenticacion` — Estado del servicio
- `POST /api/auth/login` — Iniciar sesión
- `POST /api/auth/registro` — Registrar usuario
- `GET /api/auth/status` — Estado de sesión

### Checkout
- `GET /api/checkout` — Estado del servicio
- `POST /api/checkout/procesar` — Procesar checkout (auth)

### Pasarela de Pago
- `GET /api/pasarela-de-pago` — Estado de pasarela (auth)
- `POST /api/pagos/procesar` — Procesar pago (auth)
- `GET /api/pagos/confirmar/{ordenId}` — Confirmar pago
- `POST /api/pagos/reembolsar` — Reembolsar pago (auth)

### Inventario
- `GET /api/inventario/{productoId}` — Consultar stock
- `POST /api/inventario/validar` — Validar disponibilidad
- `POST /api/inventario/reservar` — Reservar stock (auth)
- `POST /api/inventario/liberar` — Liberar reserva (auth)
- `POST /api/inventario/confirmar` — Confirmar venta (auth)

### Administración
- `GET /api/administracion` — Estado del servicio
- `GET /admin` — Dashboard admin
- `GET /api/administracion/usuarios` — Listar usuarios
- `POST /api/admin/pedidos/estado` — Cambiar estado pedido
- `POST /api/admin/inventario/ajustar` — Ajustar stock

### Integración
- `GET /api/integracion` — Estado de integraciones
- `POST /api/integracion/orquestar` — Orquestar compra
- `GET /api/health` — Health check completo

## Seguridad
- PDO con prepared statements
- Password hashing con bcrypt (cost ≥ 12)
- Sesiones seguras (httponly, samesite)
- Validación de entradas
- Sanitización HTML

## Estructura

```
ecommerce/
├── app/              # Lógica de negocio por módulo
│   ├── Catalogo/
│   ├── Carrito/
│   ├── Autenticacion/
│   ├── Checkout/
│   ├── PasarelaPago/
│   ├── Inventario/
│   ├── Administracion/
│   ├── Integracion/
│   └── Core/         # Router, DB, Session, etc.
├── config/
├── database/         # Schema SQL
├── public/           # index.php, assets
├── routes/           # router.php
├── storage/          # logs, cache
└── tests/
```
