# Setup Local

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Objetivo

Configurar un entorno de desarrollo local que permita ejecutar, desarrollar, probar y depurar la aplicación de forma consistente entre todos los miembros del equipo.

---

# Requisitos Previos

## Software Requerido

| Herramienta                   | Versión Recomendada    |
| ----------------------------- | ---------------------- |
| PHP                           | 8.2 o superior         |
| Apache                        | 2.4 o superior         |
| MySQL                         | 8.0 o superior         |
| Git                           | 2.40 o superior        |
| Composer                      | 2.x                    |
| Navegador Web                 | Última versión estable |
| Visual Studio Code (Opcional) | Última versión         |

---

# Estructura del Proyecto

```text
ecommerce/
├── app/
├── config/
├── database/
├── docs/
├── public/
├── routes/
├── storage/
├── tests/
├── vendor/
├── .env
├── composer.json
└── README.md
```

---

# Instalación de PHP

## Verificar Instalación

```bash
php -v
```

## Resultado Esperado

```text
PHP 8.2.x
```

---

# Instalación de Apache

## Verificar Servicio

### Linux

```bash
sudo systemctl status apache2
```

### Windows

Verificar desde el panel de servicios o desde XAMPP/WAMP.

## Configuración Recomendada

Habilitar:

* mod_rewrite
* SSL
* Headers

---

# Instalación de MySQL

## Verificar Instalación

```bash
mysql --version
```

## Resultado Esperado

```text
mysql  Ver 8.x
```

---

# Configuración de Base de Datos

## Crear Base de Datos

```sql
CREATE DATABASE ecommerce;
```

## Crear Usuario

```sql
CREATE USER 'ecommerce_user'@'localhost'
IDENTIFIED BY 'password_segura';
```

## Asignar Permisos

```sql
GRANT ALL PRIVILEGES ON ecommerce.*
TO 'ecommerce_user'@'localhost';

FLUSH PRIVILEGES;
```

---

# Instalación de Git

## Verificar Instalación

```bash
git --version
```

## Resultado Esperado

```text
git version 2.x
```

---

# Clonar Repositorio

```bash
git clone <repository-url>
```

```bash
cd ecommerce
```

---

# Instalación de Dependencias

## Composer

Instalar dependencias del proyecto:

```bash
composer install
```

## Resultado Esperado

* Carpeta `vendor/` creada.
* Dependencias descargadas correctamente.

---

# Configuración de Variables de Entorno

## Crear Archivo .env

Copiar archivo base:

```bash
cp .env.example .env
```

### Windows

```powershell
copy .env.example .env
```

---

## Configuración Recomendada

```env
APP_NAME=Ecommerce
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=ecommerce
DB_USERNAME=ecommerce_user
DB_PASSWORD=password_segura
```

---

# Migraciones de Base de Datos

Ejecutar migraciones:

```bash
php artisan migrate
```

## Resultado Esperado

* Tablas creadas correctamente.
* Sin errores de ejecución.

---

# Datos Iniciales

Ejecutar seeders:

```bash
php artisan db:seed
```

## Resultado Esperado

* Datos de prueba disponibles.
* Catálogo inicial cargado.

---

# Configuración de Apache

## Virtual Host Ejemplo

```apache
<VirtualHost *:80>
    ServerName ecommerce.local

    DocumentRoot "/var/www/ecommerce/public"

    <Directory "/var/www/ecommerce/public">
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

---

# Ejecución Local

## Iniciar Aplicación

```bash
php artisan serve
```

## Resultado Esperado

```text
Server running on:
http://127.0.0.1:8000
```

---

# Verificaciones Iniciales

## Aplicación

* [ ] La aplicación inicia correctamente.
* [ ] No existen errores en consola.
* [ ] El entorno es accesible desde el navegador.

## Base de Datos

* [ ] La conexión a MySQL funciona.
* [ ] Las migraciones fueron ejecutadas.
* [ ] Los datos iniciales están disponibles.

## API

### Endpoint de Verificación

```http
GET /api/checkout
```

### Respuesta Esperada

```json
{
  "success": true
}
```

---

# Testing Local

## Ejecutar Pruebas Unitarias

```bash
php artisan test
```

o

```bash
vendor/bin/phpunit
```

## Resultado Esperado

```text
PASS
```

---

# Solución de Problemas

## Error de Conexión a MySQL

Verificar:

* Servicio MySQL activo.
* Credenciales correctas.
* Puerto configurado correctamente.

---

## Error de Dependencias

Ejecutar:

```bash
composer install
```

o

```bash
composer update
```

---

## Error de Permisos

Linux:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
```

---

# Checklist de Setup

## Herramientas

* [ ] PHP instalado.
* [ ] Apache instalado.
* [ ] MySQL instalado.
* [ ] Git instalado.
* [ ] Composer instalado.

## Proyecto

* [ ] Repositorio clonado.
* [ ] Dependencias instaladas.
* [ ] Variables de entorno configuradas.
* [ ] Base de datos creada.
* [ ] Migraciones ejecutadas.
* [ ] Seeders ejecutados.

## Validación

* [ ] Aplicación accesible.
* [ ] API operativa.
* [ ] Pruebas exitosas.

---

# Trazabilidad

| Artefacto              | Referencia      |
| ---------------------- | --------------- |
| Especificación Técnica | spec.md         |
| API Contract           | api-contract.md |
| Casos de Uso           | casos-uso.md    |
| Testing                | testing.md      |
| Checklist              | checklist.md    |

---

# Estado

**Versión:** 1.0

**Estado:** Aprobado para Desarrollo Local
