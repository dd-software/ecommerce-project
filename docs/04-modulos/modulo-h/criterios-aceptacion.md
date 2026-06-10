# Criterios de Aceptación

## Checklist Funcional del Módulo H

### Autenticación y Control de Acceso

* [ ] El sistema permite el inicio de sesión mediante credenciales válidas.
* [ ] El sistema rechaza credenciales inválidas.
* [ ] El sistema permite cerrar sesión de forma segura.
* [ ] El sistema protege las rutas privadas contra accesos no autorizados.
* [ ] El sistema distingue correctamente entre roles de usuario.
* [ ] El sistema aplica permisos según el rol asignado.
* [ ] El sistema impide el acceso a funcionalidades restringidas.

---

### Gestión de Usuarios y Roles

* [ ] El administrador puede crear usuarios.
* [ ] El administrador puede modificar usuarios existentes.
* [ ] El administrador puede desactivar usuarios.
* [ ] El sistema registra los cambios realizados sobre usuarios.
* [ ] Los roles pueden ser asignados y modificados por usuarios autorizados.
* [ ] Los permisos asociados a cada rol son respetados por el sistema.

---

### Seguridad de Datos

* [ ] Las contraseñas se almacenan utilizando algoritmos de cifrado seguros.
* [ ] El sistema no expone información sensible en respuestas públicas.
* [ ] Los datos personales son tratados de forma segura.
* [ ] La comunicación entre cliente y servidor utiliza HTTPS.
* [ ] El sistema valida y sanitiza entradas de usuario.
* [ ] El sistema protege contra inyección SQL.
* [ ] El sistema protege contra ataques XSS.
* [ ] El sistema protege contra ataques CSRF cuando corresponda.

---

### Auditoría de Eventos

* [ ] El sistema registra inicios de sesión exitosos.
* [ ] El sistema registra intentos fallidos de autenticación.
* [ ] El sistema registra cambios de permisos y roles.
* [ ] El sistema registra operaciones críticas del sistema.
* [ ] El sistema almacena fecha y hora de cada evento auditado.
* [ ] El sistema identifica al usuario responsable de cada acción.
* [ ] Los registros de auditoría pueden ser consultados por administradores autorizados.

---

### Monitoreo y Trazabilidad

* [ ] El sistema mantiene historial de acciones relevantes.
* [ ] El sistema permite rastrear operaciones sobre pedidos.
* [ ] El sistema permite rastrear modificaciones de inventario.
* [ ] El sistema permite rastrear operaciones de pago.
* [ ] El sistema conserva evidencia suficiente para auditorías posteriores.

---

### Gestión de Sesiones

* [ ] El sistema genera sesiones únicas para cada usuario autenticado.
* [ ] Las sesiones expiran tras un período de inactividad configurable.
* [ ] El sistema invalida la sesión al cerrar sesión.
* [ ] El sistema impide reutilizar sesiones expiradas.
* [ ] El sistema protege las cookies de sesión mediante configuraciones seguras.

---

### Manejo de Incidentes

* [ ] El sistema registra errores críticos de seguridad.
* [ ] El sistema registra intentos de acceso no autorizados.
* [ ] El sistema genera alertas ante eventos sospechosos configurados.
* [ ] El sistema mantiene información suficiente para análisis posteriores.
* [ ] El sistema evita que errores internos expongan detalles técnicos al usuario final.

---

### Cumplimiento y Buenas Prácticas

* [ ] El sistema cumple las políticas de seguridad definidas para el proyecto.
* [ ] Los accesos son gestionados bajo el principio de mínimo privilegio.
* [ ] La información sensible se encuentra protegida adecuadamente.
* [ ] Los registros de auditoría son inmutables para usuarios no autorizados.
* [ ] La documentación de seguridad se encuentra actualizada.

---

## Pruebas de Aceptación

### Escenario 1: Inicio de Sesión Exitoso

**Dado** que un usuario posee credenciales válidas
**Cuando** inicia sesión en la plataforma
**Entonces** el sistema permite el acceso y registra el evento.

### Escenario 2: Intento de Acceso No Autorizado

**Dado** que un usuario intenta acceder a una funcionalidad restringida
**Cuando** no posee permisos suficientes
**Entonces** el sistema deniega el acceso y registra el intento.

### Escenario 3: Registro de Auditoría

**Dado** que un administrador modifica un usuario
**Cuando** la operación es completada
**Entonces** el sistema almacena un registro de auditoría con usuario, fecha y acción realizada.

### Escenario 4: Expiración de Sesión

**Dado** que un usuario permanece inactivo durante el tiempo configurado
**Cuando** intenta continuar utilizando la plataforma
**Entonces** el sistema solicita una nueva autenticación.

### Escenario 5: Protección de Datos Sensibles

**Dado** que un usuario consulta información del sistema
**Cuando** recibe una respuesta de la aplicación
**Entonces** no se exponen contraseñas, tokens ni datos sensibles.

---

## Criterio de Aprobación del Módulo

El módulo será considerado aceptado cuando:

* El 100% de los controles de seguridad definidos hayan sido implementados.
* Todos los mecanismos de autenticación y autorización funcionen correctamente.
* Los registros de auditoría sean completos y verificables.
* No existan vulnerabilidades críticas o altas pendientes de corrección.
* Las pruebas funcionales y de seguridad sean satisfactorias.
* La trazabilidad de eventos críticos sea demostrable.
* La documentación técnica y operativa se encuentre actualizada.