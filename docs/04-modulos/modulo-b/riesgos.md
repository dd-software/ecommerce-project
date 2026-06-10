# Riesgos

## Objetivo

Identificar, evaluar y mitigar los riesgos asociados al desarrollo, despliegue y operación de la plataforma Ecommerce con gestión de inventarios y procesamiento de pagos.

---

# 1. Riesgos de Dependencias

## R-DEP-001: Dependencia de proveedores de pago

**Descripción:**
La plataforma depende de servicios externos para procesar transacciones electrónicas.

**Impacto:** Alto

**Probabilidad:** Media

**Consecuencias:**

* Imposibilidad de realizar compras.
* Pérdida de ventas.
* Mala experiencia de usuario.

**Mitigación:**

* Implementar mecanismos de reintento.
* Utilizar proveedores con alta disponibilidad.
* Diseñar una capa de abstracción para facilitar el cambio de proveedor.

---

## R-DEP-002: Dependencia de servicios de correo electrónico

**Descripción:**
Los correos de confirmación de compra, recuperación de contraseña y notificaciones dependen de servicios externos.

**Impacto:** Medio

**Probabilidad:** Media

**Mitigación:**

* Configurar múltiples proveedores SMTP.
* Implementar colas de envío.
* Registrar errores y reintentos automáticos.

---

## R-DEP-003: Dependencia de infraestructura cloud

**Descripción:**
La disponibilidad del sistema depende de servicios de hosting, almacenamiento y bases de datos administradas.

**Impacto:** Alto

**Probabilidad:** Baja

**Mitigación:**

* Respaldos automáticos.
* Monitoreo continuo.
* Plan de recuperación ante desastres.

---

# 2. Riesgos de Integración

## R-INT-001: Fallas de sincronización de inventario

**Descripción:**
Errores durante la actualización de stock pueden provocar diferencias entre inventario real y disponible.

**Impacto:** Alto

**Probabilidad:** Media

**Consecuencias:**

* Sobreventa de productos.
* Cancelaciones de pedidos.
* Reclamos de clientes.

**Mitigación:**

* Uso de transacciones ACID.
* Bloqueo de registros críticos.
* Validación de stock antes de confirmar compras.

---

## R-INT-002: Incompatibilidad entre módulos

**Descripción:**
Cambios en interfaces internas pueden afectar la comunicación entre módulos.

**Impacto:** Medio

**Probabilidad:** Media

**Mitigación:**

* Definición de contratos API.
* Versionamiento de servicios.
* Pruebas de integración continuas.

---

## R-INT-003: Errores en integración con pasarelas de pago

**Descripción:**
Cambios en APIs externas pueden generar interrupciones en el proceso de pago.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Monitoreo de cambios de API.
* Pruebas automatizadas periódicas.
* Adaptadores desacoplados.

---

# 3. Riesgos de Seguridad

## R-SEG-001: Acceso no autorizado

**Descripción:**
Usuarios maliciosos podrían intentar acceder a información sensible o funciones administrativas.

**Impacto:** Crítico

**Probabilidad:** Media

**Mitigación:**

* Autenticación segura.
* Hashing de contraseñas con bcrypt.
* Control de acceso basado en roles (RBAC).
* Políticas de contraseñas robustas.

---

## R-SEG-002: Inyección SQL

**Descripción:**
Ataques dirigidos a la base de datos mediante entradas maliciosas.

**Impacto:** Crítico

**Probabilidad:** Media

**Mitigación:**

* Uso de consultas preparadas.
* ORM o Query Builder.
* Validación y sanitización de entradas.

---

## R-SEG-003: Cross-Site Scripting (XSS)

**Descripción:**
Inserción de código malicioso en formularios o campos de entrada.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Escape de caracteres especiales.
* Validación de contenido.
* Implementación de Content Security Policy (CSP).

---

## R-SEG-004: Cross-Site Request Forgery (CSRF)

**Descripción:**
Ejecución de acciones no autorizadas mediante solicitudes falsificadas.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Tokens CSRF.
* Validación de origen.
* Protección en formularios críticos.

---

## R-SEG-005: Exposición de datos sensibles

**Descripción:**
Divulgación accidental de información de clientes o transacciones.

**Impacto:** Crítico

**Probabilidad:** Baja

**Mitigación:**

* Cifrado de datos sensibles.
* Uso obligatorio de HTTPS.
* Restricción de acceso a información confidencial.

---

## R-SEG-006: Pérdida de información

**Descripción:**
Fallas de hardware, software o errores humanos pueden causar pérdida de datos.

**Impacto:** Crítico

**Probabilidad:** Baja

**Mitigación:**

* Backups automáticos diarios.
* Estrategias de recuperación.
* Replicación de bases de datos.

---

# 4. Matriz de Riesgos

| ID        | Riesgo                               | Impacto | Probabilidad | Nivel   |
| --------- | ------------------------------------ | ------- | ------------ | ------- |
| R-DEP-001 | Dependencia de pasarela de pago      | Alto    | Media        | Alto    |
| R-DEP-002 | Dependencia de correo electrónico    | Medio   | Media        | Medio   |
| R-DEP-003 | Dependencia de infraestructura cloud | Alto    | Baja         | Medio   |
| R-INT-001 | Sincronización de inventario         | Alto    | Media        | Alto    |
| R-INT-002 | Incompatibilidad entre módulos       | Medio   | Media        | Medio   |
| R-INT-003 | Integración con APIs externas        | Alto    | Media        | Alto    |
| R-SEG-001 | Acceso no autorizado                 | Crítico | Media        | Crítico |
| R-SEG-002 | Inyección SQL                        | Crítico | Media        | Crítico |
| R-SEG-003 | XSS                                  | Alto    | Media        | Alto    |
| R-SEG-004 | CSRF                                 | Alto    | Media        | Alto    |
| R-SEG-005 | Exposición de datos sensibles        | Crítico | Baja         | Alto    |
| R-SEG-006 | Pérdida de información               | Crítico | Baja         | Alto    |

---

# 5. Estrategia General de Mitigación

1. Aplicar principios de seguridad desde el diseño (Security by Design).
2. Realizar pruebas unitarias, de integración y seguridad en cada sprint.
3. Implementar monitoreo y alertas en tiempo real.
4. Mantener respaldos automáticos y planes de recuperación.
5. Actualizar periódicamente dependencias y componentes externos.
6. Ejecutar revisiones de código y análisis de vulnerabilidades.
7. Documentar incidentes y acciones correctivas.