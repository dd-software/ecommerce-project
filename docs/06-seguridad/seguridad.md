## 1. Objetivo

Este documento establece los controles de seguridad que deberian implementarse a la plataforma de eCommerce

---

## 1. Validación de Entradas

### Objetivo

Prevenir ataques derivados de datos maliciosos o incorrectos ingresados por los usuarios.

### Controles Implementados

* [ ] Validación de todos los datos recibidos desde formularios.
* [ ] Validación tanto en cliente como en servidor.
* [ ] Restricción de longitud mínima y máxima de campos.
* [ ] Validación de tipos de datos (texto, números, fechas, correos electrónicos).
* [ ] Uso de listas blancas (Whitelist Validation).
* [ ] Rechazo de caracteres no permitidos cuando corresponda.
* [ ] Registro y limite de intentos de entrada inválida.

---

## 2. Protección contra XSS (Cross-Site Scripting)

### Objetivo

Evitar la ejecución de código JavaScript malicioso en el navegador de los usuarios.

### Controles Implementados

- Escape automático de datos mostrados en vistas.
- Sanitización de contenido generado por usuarios.
- Implementación de Content Security Policy (CSP).
- Prohibición de uso de funciones inseguras como:
  * innerHTML
  * document.write()
  * eval()
- Codificación de caracteres especiales antes de renderizar contenido.

### Ejemplos

* Comentarios de productos.
* Reseñas de clientes.
* Formularios de contacto.

---

## 3. Protección contra CSRF (Cross-Site Request Forgery)

### Objetivo

Evitar que un atacante ejecute acciones en nombre de un usuario autenticado.

### Controles Implementados

- Uso de tokens CSRF en formularios.
- Validación del token en cada solicitud sensible.
- Regeneración periódica de tokens.
- Configuración SameSite para cookies.
- Protección de operaciones críticas:

  * Cambio de contraseña
  * Actualización de perfil
  * Creación de pedidos
  * Eliminación de cuentas

### Ejemplos

* Actualización de dirección de envío.
* Modificación de métodos de pago.
* Confirmación de compras.

---

## 4. Uso de Prepared Statements

### Objetivo

Prevenir ataques de Inyección SQL.

### Controles Implementados

- Uso exclusivo de consultas parametrizadas.
- Uso de ORM (Object-Relational Mapping o Mapeo Objeto-Relacional) cuando sea posible.
- Validación de parámetros antes de ejecutar consultas.
- Uso de cuentas de base de datos con privilegios mínimos.

## 5. Gestión Segura de Sesiones

### Objetivo

Proteger la autenticación y la identidad de los usuarios.

### Controles Implementados

- Cookies marcadas como HttpOnly.
- Cookies marcadas como Secure.
- Configuración SameSite=Strict o Lax.
- Regeneración del identificador de sesión tras autenticación.
- Invalidación de sesión al cerrar sesión.
- Tiempo de expiración configurado.
- Detección de sesiones inactivas.
- Protección frente a Session Fixation.

### Ejemplos

* Cierre automático después de 30 minutos de inactividad.
* Renovación de sesión tras cambio de contraseña.

---

## 6. Hash de Contraseñas

### Objetivo

Proteger credenciales almacenadas en la base de datos.

### Controles Implementados

- Nunca almacenar contraseñas en texto plano.
- Uso de algoritmos seguros:

  * Argon2
  * bcrypt
  * PBKDF2
  * SHA-2
- Uso obligatorio de salt aleatorio.
- Configuración adecuada del factor de trabajo.
- Política de contraseñas robustas.
- Validación contra contraseñas comunes o comprometidas.

### Requisitos de Contraseña

* Mínimo 8 caracteres.
* Al menos una mayúscula.
* Al menos una minúscula.
* Al menos un número.
* Al menos un carácter especial.

---

## 7. Control de Acceso Basado en Roles (RBAC)

### Objetivo

Garantizar que cada usuario acceda únicamente a los recursos autorizados.

### Roles Definidos

### Cliente

* Ver productos.
* Gestionar carrito.
* Realizar compras.
* Consultar historial de pedidos.

### Administrador

* Gestionar productos.
* Gestionar inventario.
* Gestionar usuarios.
* Visualizar reportes.

### Super Administrador

* Administrar permisos.
* Configurar sistema.
* Gestionar administradores.

### Controles Implementados

- Verificación de permisos en backend.
- Principio de mínimo privilegio.
- Validación de acceso en cada solicitud.
- Registro de acciones administrativas.
- Restricción de acceso a paneles internos.
- Revisión periódica de permisos.

---

# Auditoría y Monitoreo

## Eventos Registrados

* Inicio de sesión.
* Cierre de sesión.
* Cambios de contraseña.
* Creación de pedidos.
* Modificaciones de productos.
* Eliminación de registros.
* Intentos de acceso no autorizados.

## Retención de Logs

* Conservación mínima: 90 días.
* Acceso restringido a administradores autorizados.
* Protección contra alteración de registros.

---

# Revisión de Seguridad

## Frecuencia

* Revisión de código en cada despliegue.
* Escaneo de vulnerabilidades mensual.
* Pruebas de penetración semestrales.
* Actualización de dependencias de seguridad de forma continua.

## Referencias

* OWASP Top 10
* OWASP ASVS
* OWASP Cheat Sheet Series
* NIST Cybersecurity Framework

---

# Estado de Cumplimiento

| Control                           | Estado |
| --------------------------------- | ------ |
| Validación de Entradas            | ☐      |
| Protección XSS                    | ☐      |
| Protección CSRF                   | ☐      |
| Prepared Statements               | ☐      |
| Gestión Segura de Sesiones        | ☐      |
| Hash de Contraseñas               | ☐      |
| Control de Acceso Basado en Roles | ☐      |
