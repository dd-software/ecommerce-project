# Ecommerce UCT — Plataforma Educativa SDD

[![Licencia](https://img.shields.io/badge/Licencia-MIT-green)]()
[![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue)]()
[![MySQL](https://img.shields.io/badge/MySQL-5.7%2B-orange)]()
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-purple)]()

Plataforma de comercio electrónico con fines **pedagógicos** para la asignatura Diseño y Desarrollo de Software + IA. Proyecto integrador donde 5 equipos de estudiantes construyen colaborativamente un ecommerce completo usando tecnologías web fundamentales.

---

## 📚 Objetivo Pedagógico

Que los estudiantes aprendan **trabajo colaborativo** usando GitHub, desarrollando una plataforma ecommerce funcional con:

- Gestión de catálogo y carrito de compras
- Autenticación de usuarios (clientes y admin)
- Procesamiento de pedidos con pago PayPal (sandbox)
- Gestión de inventario con reservas temporales (10 min)
- Panel administrativo con dashboard y reportes

El foco no es solo el producto final, sino **el proceso**: aprender a coordinar equipos, integrar módulos, seguir contratos API y documentar decisiones técnicas.

---

## 🛠 Stack Tecnológico

| Capa | Tecnología | Versión |
|------|-----------|---------|
| Frontend | HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery | 5.3.x |
| Backend | PHP (vanilla, sin frameworks, sin Composer) | 7.4+ |
| Base de datos | MySQL / MariaDB | 5.7+ / 10+ |
| Pasarela de pago | PayPal REST API v2 (sandbox via cURL) | - |
| Servidor web | Apache (incluido en WAMP/XAMPP) | 2.4+ |
| Control de versiones | Git + GitHub | - |

**Lo que NO se usa:** Composer, namespaces PHP, PSR-4, frameworks (Laravel, Symfony), UUIDs, JWT, Vue/React/Angular.

---

## 📁 Estructura del Proyecto

```
ecommerce-project/
│
├── index.php                 # Front Controller (enrutador)
├── checkout.php              # Proceso de pago
├── carrito.php               # Vista del carrito
├── producto.php              # Detalle de producto
├── login.php                 # Inicio de sesión
├── registro.php              # Registro de clientes
├── logout.php                # Cerrar sesión
├── exito.php                 # Confirmación post-pago
├── .htaccess                 # Configuración Apache
│
├── includes/
│   ├── config.php            # Configuración global
│   ├── db.php                # Conexión PDO a MySQL
│   ├── header.php            # Template header
│   ├── footer.php            # Template footer
│   └── funciones.php         # Funciones auxiliares
│
├── admin/
│   ├── index.php             # Dashboard admin
│   ├── productos.php         # CRUD productos
│   ├── pedidos.php           # Gestión pedidos
│   ├── inventario.php        # Gestión inventario
│   ├── admin_header.php      # Header del panel admin
│   └── admin_footer.php      # Footer del panel admin
│
├── api/
│   ├── auth.php              # API autenticación
│   ├── carrito.php           # API carrito
│   ├── checkout.php          # API checkout
│   ├── pago.php              # API pagos PayPal
│   └── inventario.php        # API inventario
│
├── database/
│   └── schema.sql            # Esquema BD + datos semilla
│
├── assets/
│   ├── css/                  # Hojas de estilo
│   ├── js/                   # Scripts JS/jQuery
│   └── img/                  # Imágenes del sitio
│
├── docs-v2/                  # Documentación técnica
│   ├── 00-vision-producto/   # Visión del producto
│   ├── 01-reglas-negocio/    # Reglas de negocio
│   ├── 02-arquitectura/      # Arquitectura general
│   ├── 03-modelo-dominio/    # Modelo de dominio
│   ├── 04-contratos-api/     # Contratos API
│   ├── 05-diseno-bd/         # Esquema de BD
│   ├── 06-seguridad/         # Especificación seguridad
│   ├── 07-pasarela-pago/     # Integración PayPal
│   ├── 08-inventario/        # Gestión inventario
│   ├── 09-flujos/            # Flujo de compra
│   ├── 10-ui-ux/             # Especificación UI
│   └── 11-planificacion-equipos/  # Planificación
│
└── README.md                 # Este archivo
```

---

## 🚀 Guía de Instalación (WAMP/XAMPP)

Sigue estos pasos exactamente en orden para poner el proyecto en funcionamiento.

### Requisitos

- **WAMP** (Windows) o **XAMPP** (Windows/Linux/Mac) instalado
- Apache y MySQL corriendo
- Git instalado (opcional, pero recomendado)
- Navegador web moderno (Chrome, Firefox, Edge)

### Paso 1: Clonar el repositorio

```bash
# Desde la terminal (Git Bash, PowerShell, etc.)
cd C:\wamp64\www\          # En WAMP
# o
cd C:\xampp\htdocs\        # En XAMPP (Windows)
cd /opt/lampp/htdocs/      # En XAMPP (Linux)

# Clonar el proyecto
git clone https://github.com/TU_USUARIO/ecommerce-project.git
cd ecommerce-project
```

> Si no tienes Git, descarga el ZIP desde GitHub y extráelo en `htdocs/ecommerce-project/`.

### Paso 2: Verificar la estructura

Asegúrate de que la carpeta del proyecto esté directamente en `htdocs/` o `www/`:

```
htdocs/
└── ecommerce-project/        ← ESTA carpeta
    ├── index.php
    ├── includes/
    ├── admin/
    ├── api/
    ├── database/
    ├── assets/
    └── docs-v2/
```

### Paso 3: Importar la base de datos

1. Abre tu navegador y ve a `http://localhost/phpmyadmin/`
2. Haz clic en **"Nueva"** en el panel izquierdo
3. Nombre de la base de datos: `ecommerce_uct` (o el que prefieras)
4. Selecciona `utf8mb4_unicode_ci` como cotejamiento
5. Haz clic en **"Crear"**
6. Ve a la pestaña **"SQL"**
7. Haz clic en **"Seleccionar archivo"** y elige `database/schema.sql`
8. Haz clic en **"Continuar"**

**Alternativa — línea de comandos:**

```bash
mysql -u root -p < database/schema.sql
```

Esto creará todas las tablas y los datos de ejemplo (admin, categorías, productos, inventario inicial).

### Paso 4: Configurar la conexión a la BD

Edita el archivo `includes/config.php`:

```php
<?php
// Configuración de la Base de Datos
define('DB_HOST', 'localhost');
define('DB_USER', 'root');           // Tu usuario MySQL
define('DB_PASS', '');               // Tu contraseña MySQL (vacío en WAMP/XAMPP por defecto)
define('DB_NAME', 'ecommerce_uct');  // Debe coincidir con el nombre que usaste en phpMyAdmin

// PayPal Sandbox (obtener de https://developer.paypal.com)
define('PAYPAL_MODE', 'sandbox');
define('PAYPAL_CLIENT_ID', '');      // Reemplazar con tu Client ID
define('PAYPAL_SECRET', '');         // Reemplazar con tu Secret
```

### Paso 5: Iniciar Apache y MySQL

- **WAMP:** Haz clic en el icono de WAMP en la bandeja del sistema y selecciona "Start All Services"
- **XAMPP:** Abre el Panel de Control XAMPP y haz clic en "Start" junto a Apache y MySQL

### Paso 6: Abrir el proyecto en el navegador

```
http://localhost/ecommerce-project/
```

¡Deberías ver la página principal con el catálogo de productos!

### Credenciales de prueba

| Rol | Email | Contraseña |
|-----|-------|------------|
| Admin | `admin@ecommerce.local` | `Admin123!` |
| Cliente | (regístrate desde la página) | - |

---

## 👥 Equipos y Módulos

| # | Equipo | Integrantes | Módulo |
|---|--------|-------------|--------|
| 1 | **Los Chupa** | Christopher Aguilera, Diego Catalán, Sebastián Chinchón, Benjamín González | **A - Catálogo** + **B - Carrito** |
| 2 | **Tareasmiranda** | Sebastián Edwards, Mauricio Inostroza, Sebastián Muñoz | **C - Auth** + **D - Checkout** |
| 3 | **Los Tulachi** | Benjamín Alegría, Fernando Caullán, Cristóbal Cisterna, Benjamín Pacheco | **E - PayPal** + **F - Inventario** |
| 4 | **Team 4** | Jean Pierre Mayer, Felipe Salazar | **G - Admin** |
| 5 | **El Caleuche** | Guido Bardi Lemus, Cristóbal Escobar, Sebastián Flores, Vicente Saavedra | **H - Integración + Docs** |

---

## 📖 Documentación

Toda la documentación técnica está en la carpeta `docs-v2/`:

- **[Visión del Producto](docs-v2/00-vision-producto/vision-producto.md)** — Qué construimos y por qué
- **[Reglas de Negocio](docs-v2/01-reglas-negocio/reglas-negocio.md)** — RN-USU, RN-AUT, RN-CARRITO, RN-INV, RN-PEDIDO, RN-PAGO, RN-SEG
- **[Arquitectura General](docs-v2/02-arquitectura/arquitectura-general.md)** — Stack, estructura, front controller, APIs
- **[Modelo de Dominio](docs-v2/03-modelo-dominio/modelo-dominio.md)** — Entidades, relaciones, roles, estados
- **[Contratos API](docs-v2/04-contratos-api/index.md)** — Especificación de todos los endpoints
- **[Esquema BD](docs-v2/05-diseno-bd/esquema-bd.md)** — Tablas, columnas, relaciones
- **[Seguridad](docs-v2/06-seguridad/especificacion-seguridad.md)** — CSRF, XSS, sesiones, rate limiting
- **[PayPal](docs-v2/07-pasarela-pago/integracion-paypal.md)** — Integración con PayPal sandbox via cURL
- **[Inventario](docs-v2/08-inventario/gestion-inventario.md)** — Reservas, expiración, movimientos
- **[Flujo de Compra](docs-v2/09-flujos/flujo-compra.md)** — Diagrama y descripción paso a paso
- **[UI/UX](docs-v2/10-ui-ux/especificacion-ui.md)** — Interfaces y diseño responsive
- **[Planificación](docs-v2/11-planificacion-equipos/planificacion-modulos.md)** — Equipos, módulos, cronograma

---

## 💳 Configuración de PayPal (Sandbox)

Para probar pagos reales (simulados), necesitas configurar PayPal Sandbox:

1. Ve a [developer.paypal.com](https://developer.paypal.com) e inicia sesión
2. Ve a **Apps & Credentials**
3. Haz clic en **"Create App"** (modo Sandbox)
4. Dale un nombre y anota el **Client ID** y **Secret**
5. Copia esos valores a `includes/config.php`:

```php
define('PAYPAL_CLIENT_ID', 'AQ...client_id_aqui...');
define('PAYPAL_SECRET', 'EN...secret_aqui...');
```

6. En **Sandbox > Accounts**, usa la cuenta Personal de prueba para comprar
7. Al pagar, inicia sesión en PayPal con el email de la cuenta sandbox y la contraseña `123456789`

---

## 🔒 Seguridad

El proyecto implementa las siguientes medidas de seguridad:

- **CSRF Tokens** — Token único por sesión en toda operación POST
- **Sesiones seguras** — HttpOnly, SameSite Strict, solo cookies
- **Protección XSS** — `htmlspecialchars()` en toda salida de datos
- **SQL Injection** — Prepared statements con PDO
- **Rate limiting** — Máximo 5 intentos de login por minuto
- **Control de roles** — Funciones `require_login()` y `require_admin()`
- **Contraseñas** — Hash bcrypt con `password_hash()`

---

## 📝 Convenciones de Código

### PHP
- Archivos en `snake_case.php`
- Funciones y variables en `$snake_case`
- Sin namespaces, sin Composer, sin PSR-4
- Comentarios pedagógicos con `/* [PEDAGÓGICO] ... */`

### SQL
- Tablas en `snake_case` (plural)
- IDs siempre `INT AUTO_INCREMENT`
- Llaves foráneas con nombre `tabla_id`

### JS / jQuery
- Variables en camelCase
- Selectores jQuery con prefijo `$`

---

## 🐙 Comandos Git Útiles

```bash
# Clonar el repositorio
git clone https://github.com/TU_USUARIO/ecommerce-project.git

# Ver ramas
git branch -a

# Crear rama para una feature
git checkout -b feature/modulo-a-catalogo

# Ver cambios
git status
git diff

# Agregar y commitear cambios
git add .
git commit -m "feat: agregar catálogo con filtros y búsqueda"

# Subir cambios
git push origin feature/modulo-a-catalogo

# Actualizar tu rama con main
git checkout main
git pull origin main
git checkout feature/modulo-a-catalogo
git merge main

# Crear Pull Request (desde GitHub web)
```

---

## ⚠️ Solución de Problemas

| Problema | Causa | Solución |
|----------|-------|----------|
| Error de conexión MySQL | Credenciales incorrectas | Verificar DB_USER y DB_PASS en config.php |
| Página en blanco | Error PHP | Verificar `error_log` de Apache |
| PayPal no aparece | Client ID vacío | Configurar PAYPAL_CLIENT_ID en config.php |
| 404 en rutas | URL incorrecta | Asegurar que el proyecto está en htdocs/ecommerce-project/ |
| Carrito no guarda | Sesión no inicia | Verificar `session_start()` en config.php |

---

## 📄 Licencia

Este proyecto es con fines educativos. MIT License.

---

**Desarrollado por:** Estudiantes de la asignatura Diseño y Desarrollo de Software + IA
**Profesor:** Equipo docente SDD
**Año:** 2026
