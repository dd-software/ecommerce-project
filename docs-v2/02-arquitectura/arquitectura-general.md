# Arquitectura General — Ecommerce UCT

## Stack Tecnológico

| Componente | Tecnología | Versión |
|------------|-----------|---------|
| Frontend | HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery | 5.3.x |
| Backend | PHP (vanilla, sin frameworks) | 7.4+ |
| Base de datos | MySQL / MariaDB | 5.7+ / 10+ |
| Pasarela de pago | PayPal REST API (sandbox vía cURL) | v2 |
| Servidor web | Apache (incluido en WAMP/XAMPP) | 2.4+ |
| Entorno | WAMP / XAMPP | Cualquier versión reciente |

## Principios Arquitectónicos

1. **Sin Composer, sin namespaces, sin PSR-4** — PHP vanilla puro con `include`/`require`
2. **Sin frameworks** — Cada página es un archivo `.php` independiente
3. **Front Controller simple** — `index.php` redirige según parámetro `?page=` usando `switch`/`if`
4. **APIs planas** — Los endpoints en `/api/` son archivos PHP que devuelven JSON
5. **IDs INT AUTO_INCREMENT** — Sin UUIDs, sin claves compuestas complejas
6. **Separación de responsabilidades** — Archivos en carpetas por funcionalidad
7. **jQuery sí, librerías adicionales no** — Solo jQuery via CDN, sin Vue/React/Angular

## Estructura del Proyecto

```
ecommerce-project/
├── index.php              ← Front Controller (enrutador)
├── checkout.php           ← Página de checkout
├── carrito.php            ← Página del carrito
├── producto.php           ← Detalle de producto
├── login.php              ← Inicio de sesión
├── registro.php           ← Registro de usuarios
├── logout.php             ← Cerrar sesión
├── exito.php              ← Página de éxito post-pago
│
├── includes/
│   ├── config.php         ← Configuración global (DB, PayPal, sesión)
│   ├── db.php             ← Conexión PDO a MySQL
│   ├── header.php         ← <head> + navbar (include en cada página)
│   ├── footer.php         ← Cierre HTML + scripts (include en cada página)
│   └── funciones.php      ← Funciones auxiliares globales
│
├── admin/
│   ├── index.php          ← Dashboard admin
│   ├── productos.php      ← CRUD productos
│   ├── pedidos.php        ← Gestión de pedidos
│   ├── inventario.php     ← Gestión de inventario
│   ├── admin_header.php   ← Header específico del panel admin
│   └── admin_footer.php   ← Footer del panel admin
│
├── api/
│   ├── auth.php           ← Login/register/logout API
│   ├── carrito.php        ← CRUD carrito API
│   ├── checkout.php       ← Procesar checkout API
│   ├── pago.php           ← PayPal API
│   └── inventario.php     ← Inventario API (admin)
│
├── database/
│   └── schema.sql         ← Esquema completo + datos semilla
│
├── assets/
│   ├── css/               ← CSS personalizado
│   ├── js/                ← JavaScript personalizado
│   └── img/               ← Imágenes del sitio
│
├── docs-v2/               ← Documentación técnica
└── README.md              ← Guía de inicio rápido
```

## Front Controller (index.php)

```php
<?php
// index.php — Enrutador simple
require_once 'includes/config.php';
require_once 'includes/funciones.php';

$page = isset($_GET['page']) ? $_GET['page'] : 'home';

switch ($page) {
    case 'home':
        include 'includes/header.php';
        // mostrar productos destacados
        include 'includes/footer.php';
        break;
    case 'producto':
        include 'includes/header.php';
        include 'producto.php';
        include 'includes/footer.php';
        break;
    case 'carrito':
        include 'includes/header.php';
        include 'carrito.php';
        include 'includes/footer.php';
        break;
    case 'checkout':
        require_login();
        include 'includes/header.php';
        include 'checkout.php';
        include 'includes/footer.php';
        break;
    case 'login':
        include 'includes/header.php';
        include 'login.php';
        include 'includes/footer.php';
        break;
    case 'admin':
        require_admin();
        // Redirige al dashboard admin (archivos separados)
        include 'admin/index.php';
        break;
    default:
        http_response_code(404);
        include 'includes/header.php';
        echo '<h1>Página no encontrada</h1>';
        include 'includes/footer.php';
}
```

## Patrón de las APIs

Cada API en `/api/` sigue esta estructura:

```php
<?php
// api/ejemplo.php
require_once '../includes/config.php';
require_once '../includes/funciones.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // obtener datos
        break;
    case 'POST':
        // crear/modificar datos
        break;
    case 'DELETE':
        // eliminar datos
        break;
    default:
        http_response_code(405);
        echo json_encode(['error' => 'Método no permitido']);
}
```

## Comunicación Frontend-Backend

- Las páginas renderizadas con PHP (HTML + includes) se comunican con las APIs via AJAX (jQuery)
- Las APIs reciben/responden JSON
- El carrito de invitados usa `localStorage` y se sincroniza al iniciar sesión
- PayPal se integra via JavaScript SDK del lado del cliente + verificación server-side con cURL

## CORS y Headers

Para desarrollo local (mismo dominio/origen), no se requiere configuración CORS especial. Los headers se definen en cada API:

```php
header('Content-Type: application/json');
header('X-Content-Type-Options: nosniff');
```
