# Planificación por Módulos — Ecommerce UCT

## Equipos de Trabajo

| Equipo | Nombre | Integrantes | Módulo asignado |
|--------|--------|-------------|-----------------|
| 1 | Los Chupa | Christopher Aguilera, Diego Catalán, Sebastián Chinchón, Benjamín González | **A - Catálogo** + **B - Carrito** |
| 2 | Tareasmiranda | Sebastián Edwards, Mauricio Inostroza, Sebastián Muñoz | **C - Auth** + **D - Checkout** |
| 3 | Los Tulachi | Benjamín Alegría, Fernando Caullán, Cristóbal Cisterna, Benjamín Pacheco | **E - PayPal** + **F - Inventario** |
| 4 | Team 4 | Jean Pierre Mayer, Felipe Salazar | **G - Admin** |
| 5 | El Caleuche | Guido Bardi Lemus, Cristóbal Escobar, Sebastián Flores, Vicente Saavedra | **H - Integración** |

---

## Stack Tecnológico por Equipo (común a todos)

| Tecnología | Uso |
|------------|-----|
| HTML5 | Estructura de páginas web |
| CSS3 + Bootstrap 5.3 | Diseño responsive y estilos |
| JavaScript + jQuery | Interactividad frontend + AJAX |
| JSON | Formato de comunicación API |
| PHP 7.4+ (vanilla) | Backend y lógica de servidor |
| MySQL 5.7+ / MariaDB 10+ | Base de datos |

**No se usa:** Composer, namespaces PHP, PSR-4, frameworks, UUIDs, JWT.

---

## Módulos y Responsabilidades

### Módulo A — Catálogo (Equipo Los Chupa)
- `index.php` — Página principal con productos destacados
- `producto.php` — Detalle de producto individual
- Catálogo con búsqueda y filtro por categorías
- Paginación de resultados
- Vista de productos en cuadrícula responsive

### Módulo B — Carrito (Equipo Los Chupa)
- `carrito.php` — Vista del carrito de compras
- `api/carrito.php` — API CRUD del carrito
- Carrito para invitados (localStorage) y usuarios registrados (BD)
- Migración de carrito invitado → usuario al iniciar sesión
- Cálculo de subtotales y totales

### Módulo C — Auth (Equipo Tareasmiranda)
- `login.php` — Inicio de sesión
- `registro.php` — Registro de nuevos clientes
- `logout.php` — Cerrar sesión
- `api/auth.php` — API de autenticación
- CSRF tokens
- Rate limiting de login
- Solo registro de clientes (no admin)

### Módulo D — Checkout (Equipo Tareasmiranda)
- `checkout.php` — Página de checkout con resumen
- `api/checkout.php` — Procesar checkout
- Creación de pedido + detalles_pedido
- Cálculo de IVA (19%) y envío ($4.990)
- Integración con PayPal (llamado a api/pago.php)

### Módulo E — PayPal (Equipo Los Tulachi)
- Integración del botón PayPal en checkout
- `api/pago.php` — API de pagos
- Verificación de transacciones con PayPal via cURL
- Manejo de aprobación, cancelación y error
- Confirmación de pago y actualización de BD

### Módulo F — Inventario (Equipo Los Tulachi)
- `api/inventario.php` — API de gestión de inventario
- Reservas de inventario (10 min de expiración)
- Liberación de reservas expiradas
- Movimientos de inventario (auditoría)
- Integración con checkout (reserva) y pago (confirmación/descuento)
- `database/schema.sql` — Responsable del esquema completo de BD

### Módulo G — Admin (Equipo Team 4)
- `admin/index.php` — Dashboard con métricas
- `admin/productos.php` — CRUD de productos y categorías
- `admin/pedidos.php` — Gestión de pedidos (cambiar estados)
- `admin/inventario.php` — Vista de inventario con alertas
- Control de acceso: solo rol admin

### Módulo H — Integración (Equipo El Caleuche)
- `index.php` como Front Controller (enrutador)
- `includes/config.php` — Configuración global
- `includes/db.php` — Conexión PDO a MySQL
- `includes/header.php` y `includes/footer.php` — Template base
- `includes/funciones.php` — Funciones compartidas
- Integración de todos los módulos (A-H)
- Pruebas de flujo completo E2E
- `docs-v2/` — Documentación y guías

---

## Dependencias entre Módulos

```
A (Catálogo) ──► B (Carrito) ──► D (Checkout) ──► E (PayPal)
                                        │               │
                                        ▼               ▼
                                   F (Inventario)   F (Inventario)
                                        │               │
                                        └───────┬───────┘
                                                ▼
                                          G (Admin)
                                                │
                                                ▼
                                          H (Integración)
```

**Regla:** Un equipo no comienza su módulo hasta que las dependencias previas estén listas (o se usen datos mock/stubs).

---

## Cronograma Tentativo

| Sprint | Semanas | Módulos | Hitos |
|--------|---------|---------|-------|
| 1 | 1-2 | A (Catálogo) + B (Carrito) + C (Auth) | Catálogo funcional, login/registro, carrito básico |
| 2 | 3-4 | D (Checkout) + F (Inventario) + E (PayPal) | Checkout completo con PayPal, inventario con reservas |
| 3 | 5-6 | G (Admin) + H (Integración + Docs) | Panel admin, integración total, documentación final |

---

## Convenciones de Código

### PHP
- Archivos en `snake_case.php`
- Funciones en `snake_case()`
- Variables en `$snake_case`
- Sin namespaces, sin PSR-4
- Comentarios pedagógicos en bloques `/* [PEDAGÓGICO] ... */`

### SQL
- Tablas en `snake_case` (plural)
- Columnas en `snake_case`
- IDs siempre `INT AUTO_INCREMENT`
- Llaves foráneas con nombre descriptivo (`producto_id`, no `pid`)

### JavaScript
- Variables en camelCase
- Funciones con nombre descriptivo
- Selectores jQuery con prefijo `$`

### CSS
- Clases Bootstrap preferidas sobre CSS personalizado
- CSS personalizado solo para lo que Bootstrap no cubre
- Nombres de clases en kebab-case

---

## Checklist de Entrega por Módulo

- [ ] Código funcional (según contratos API)
- [ ] Comentarios pedagógicos incluidos
- [ ] Validaciones de seguridad (CSRF, htmlspecialchars)
- [ ] Sin errores PHP (log de errores limpio)
- [ ] Responsive (funciona en móvil y desktop)
- [ ] README del módulo (si aplica)
- [ ] Pull request a main en GitHub
