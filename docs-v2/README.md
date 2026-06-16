# Documentación Técnica — Ecommerce UCT

Repositorio documental maestro del proyecto **Ecommerce UCT**, plataforma educativa para la asignatura Diseño y Desarrollo de Software + IA.

## Estructura de la Documentación

| Carpeta | Contenido | Equipo responsable |
|---------|-----------|--------------------|
| `00-vision-producto/` | Visión del producto, objetivos y alcance | Todos |
| `01-reglas-negocio/` | Reglas de negocio y validaciones | Todos |
| `02-arquitectura/` | Arquitectura general y stack tecnológico | Todos |
| `03-modelo-dominio/` | Modelo de dominio (entidades y relaciones) | Todos |
| `04-contratos-api/` | Contratos API (endpoints PHP planos) | Equipos A-D |
| `05-diseno-bd/` | Esquema de base de datos MySQL | Equipo F |
| `06-seguridad/` | Especificación de seguridad | Equipo C |
| `07-pasarela-pago/` | Integración con PayPal (sandbox) | Equipo E |
| `08-inventario/` | Gestión de inventario con reservas | Equipo F |
| `09-flujos/` | Flujo de compra completo | Equipo H |
| `10-ui-ux/` | Especificación de interfaz de usuario | Todos |
| `11-planificacion-equipos/` | Planificación por módulos y equipos | Todos |

## Stack Tecnológico

- **Frontend:** HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery
- **Backend:** PHP 7.4+ (vanilla, sin frameworks, sin Composer)
- **Base de datos:** MySQL 5.7+ / MariaDB 10+
- **Pagos:** PayPal SDK (sandbox via cURL nativo)
- **Entorno:** WAMP / XAMPP (sin configuración adicional)

## Convenciones

- PHP vanilla sin namespaces, sin PSR-4, sin Composer
- Cada página es un archivo `.php` independiente
- Las APIs son archivos PHP planos en `/api/` que reciben/responden JSON
- IDs numéricos INT AUTO_INCREMENT (no UUIDs)
- Seguridad: CSRF tokens en sesión + `htmlspecialchars()` para XSS

## Metodología

- Specification Driven Development (SDD)
- Scrum con 5 equipos
- GitHub Flow (main + ramas por feature)
- Arquitectura modular A-H
