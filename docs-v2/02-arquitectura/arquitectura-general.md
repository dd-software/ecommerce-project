# Arquitectura General

> **Propósito educativo:** Este documento define cómo se estructura el sistema a alto nivel. Los estudiantes deben entender cada capa y cómo se comunican entre sí antes de empezar a codificar.

---

## 1. Stack Tecnológico

| Capa | Tecnología | Versión Mínima | Justificación Didáctica |
|------|-----------|----------------|-------------------------|
| **Frontend** | HTML5 + CSS3 + JavaScript | — | Fundamentos web, sin abstracciones que oculten la complejidad |
| **Framework CSS** | Bootstrap 5.3 | 5.3 | Componentes responsivos, no inventar la rueda |
| **Backend** | PHP | 8.2 | Sintaxis accesible, tipado gradual, gran ecosistema |
| **Base de Datos** | MySQL | 8.0 | SQL estándar, transacciones, índices, FullText |
| **Servidor Web** | Apache + mod_rewrite | 2.4 | URLs amigables, .htaccess |
| **Autenticación** | Sesiones PHP + bcrypt | — | Comprender sesiones vs. JWT vs. tokens |
| **Pasarela de Pago** | PayPal REST API (Sandbox) | — | Integración con API real de terceros |
| **Control de Versiones** | Git + GitHub | — | Flujo de trabajo colaborativo (GitHub Flow) |

---

## 2. Arquitectura en Capas

```
┌────────────────────────────────────────────────────────────┐
│                        CLIENTE                              │
│              Navegador Web (Chrome/Firefox)                  │
├────────────────────────────────────────────────────────────┤
│              CAPA DE PRESENTACIÓN (Frontend)                 │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  index.php (Front Controller) → Router (dispatcher)  │  │
│  │  ↓                                                    │  │
│  │  Vista: PHP + HTML + Bootstrap (View.php)            │  │
│  │  JS: Fetch API para llamadas AJAX                    │  │
│  └──────────────────────────────────────────────────────┘  │
├────────────────────────────────────────────────────────────┤
│              CAPA DE NEGOCIO (Backend REST)                  │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌───────────────┐ │
│  │ Módulo A │ │ Módulo B │ │ Módulo C │ │   Core        │ │
│  │ Catálogo │ │ Carrito  │ │ Auth     │ │ Router, DB,   │ │
│  └──────────┘ └──────────┘ └──────────┘ │ Session,      │ │
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ │ Validator,    │ │
│  │ Módulo D │ │ Módulo E │ │ Módulo F │ │ Logger,       │ │
│  │ Checkout │ │ PayPal   │ │ Invent.  │ │ Auditoría     │ │
│  └──────────┘ └──────────┘ └──────────┘ └───────────────┘ │
│  ┌──────────┐ ┌──────────┐                                 │
│  │ Módulo G │ │ Módulo H │                                 │
│  │ Admin    │ │Integración│                                 │
│  └──────────┘ └──────────┘                                 │
├────────────────────────────────────────────────────────────┤
│              CAPA DE PERSISTENCIA                            │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  PDO (PHP Data Objects) → MySQL 8.0                   │  │
│  │  Prepared Statements → SQL Injection prevention       │  │
│  │  Transacciones ACID → Commit/Rollback en operaciones  │  │
│  └──────────────────────────────────────────────────────┘  │
├────────────────────────────────────────────────────────────┤
│              CAPA DE INTEGRACIÓN EXTERNA                     │
│  ┌──────────────────────────────────────────────────────┐  │
│  │  PayPal REST API (Sandbox)                            │  │
│  │  → Orders API (crear orden de pago)                  │  │
│  │  → Webhooks (confirmación asíncrona)                 │  │
│  │  → curl PHP + JSON                                   │  │
│  └──────────────────────────────────────────────────────┘  │
└────────────────────────────────────────────────────────────┘
```

---

## 3. Patrón de Diseño: Front Controller + MVC Simplificado

```
Request HTTP                  Response HTTP
    │                              ↑
    ▼                              │
index.php (Front Controller) ──────┤
    │                              │
    ▼                              │
Router.php ───→ Controller ───→ View.php
                   │               │
                   ▼               ▼
              Database.php     HTML + Bootstrap
                   │
                   ▼
               MySQL 8.0
```

### Flujo de una petición típica:

1. **Apache** recibe la petición → `.htaccess` redirige a `public/index.php`
2. **index.php** carga autoloader, configura sesión, parsea `.env`, inicia DB
3. **Router.php** analiza la URL y el método HTTP, selecciona Controller + método
4. **Controller** valida permisos, ejecuta lógica de negocio, llama a DB
5. **Controller** devuelve:
   - **JSON** (para APIs AJAX) mediante `Response::json()`
   - **HTML** (para páginas completas) mediante `View::render()`
6. **Response** se envía al navegador

---

## 4. Estructura de Directorios

```
ecommerce/
├── app/                              # Código de la aplicación
│   ├── Core/                         # Framework base
│   │   ├── Router.php                # Enrutador de URLs
│   │   ├── Database.php              # Singleton PDO + helpers CRUD
│   │   ├── Request.php               # Wrapper de $_GET, $_POST, $_SERVER
│   │   ├── Response.php              # Helpers para respuestas JSON/HTML
│   │   ├── Session.php               # Manejo de sesiones + autenticación
│   │   ├── View.php                  # Renderizado de templates
│   │   ├── Validator.php             # Validación de datos
│   │   ├── Env.php                   # Parseo de .env
│   │   ├── Logger.php                # Logging a archivo
│   │   └── Auditoria.php             # Trazabilidad de eventos
│   │
│   ├── Catalogo/        (Módulo A)   # Catálogo de productos
│   ├── Carrito/         (Módulo B)   # Carrito de compras
│   ├── Autenticacion/   (Módulo C)   # Login, registro, sesiones
│   ├── Checkout/        (Módulo D)   # Checkout y creación de órdenes
│   ├── PasarelaPago/    (Módulo E)   # Integración PayPal
│   ├── Inventario/      (Módulo F)   # Stock, reservas, movimientos
│   ├── Administracion/  (Módulo G)   # Panel admin
│   └── Integracion/     (Módulo H)   # Orquestación entre módulos
│
├── database/                         # Esquemas SQL
│   └── schema.sql                    # DDL completo con datos iniciales
│
├── public/                           # Document root
│   └── index.php                     # Front Controller
│
├── routes/
│   └── router.php                    # Definición de rutas
│
├── storage/
│   ├── logs/                         # Logs de aplicación
│   └── cache/                        # Cache de vistas (opcional)
│
├── tests/                            # Tests unitarios y funcionales
│
├── .env                              # Configuración local (NO versionar)
├── .env.example                      # Template de .env (versionar)
├── .htaccess                         # Reglas de reescritura Apache
└── composer.json                     # PSR-4 autoload
```

---

## 5. Convenciones de Código

### Nomenclatura
- **Namespaces:** `App\Modulo` (PSR-4)
- **Clases:** PascalCase — `AuthController.php`
- **Métodos:** camelCase — `procesarPago()`
- **Variables:** camelCase — `$productoId`
- **Constantes:** UPPER_SNAKE — `MAX_LOGIN_ATTEMPTS`
- **Base de Datos:** snake_case — `items_carrito`

### Respuestas API
Todas las respuestas JSON siguen este formato:

```json
{
  "success": true,
  "data": { ... },
  "message": "Operación exitosa"
}
```

```json
{
  "success": false,
  "error": {
    "code": "STOCK_INSUFICIENTE",
    "message": "No hay stock disponible para el producto X"
  }
}
```

### Códigos de estado HTTP
| Código | Uso |
|--------|-----|
| 200 | Éxito |
| 201 | Recurso creado (registro, orden) |
| 400 | Error de validación (datos inválidos) |
| 401 | No autenticado |
| 403 | No autorizado (sin permiso) |
| 404 | Recurso no encontrado |
| 409 | Conflicto (stock insuficiente, email duplicado) |
| 422 | Error de negocio (regla violada) |
| 500 | Error interno del servidor |

---

## 6. Manejo de Errores

- **Excepciones:** Todas las excepciones se capturan en `index.php` (try-catch global)
- **Logging:** Errores se registran en `storage/logs/error-YYYY-MM-DD.log`
- **Usuario:** Errores 500 muestran página genérica (sin stack trace)
- **Debug:** Modo DEBUG en `.env` muestra stack trace en desarrollo

---

## 7. Decisiones Arquitectónicas

| Decisión | Alternativa | ¿Por qué esta opción? |
|----------|-------------|----------------------|
| PHP vanilla vs framework | Laravel/Symfony | **Didáctico:** los estudiantes aprenden cómo funciona un framework por dentro |
| Sesiones PHP vs JWT | JWT | **Didáctico:** las sesiones son más fáciles de entender y depurar |
| PDO vs MySQLi | MySQLi | **Estándar:** PDO permite cambiar de motor en el futuro |
| UUID vs auto-increment | Auto-increment | **Seguridad:** UUIDs no son predecibles, mejor para expuestos en URLs |
| HTML inline vs template engine | Twig/Blade | **Simplicidad:** PHP ya es un template engine; menos dependencias |
| PayPal vs Webpay | Ambos | **PayPal:** API REST más documentada, sandbox inmediato, sin barreras de entrada |
