# Riesgos

## Objetivo

Identificar los riesgos técnicos, operativos y de seguridad que pueden afectar el desarrollo, integración, despliegue y operación de la plataforma Ecommerce.

---

## Riesgos de Dependencias

### R-DEP-001: Dependencia de Pasarela de Pago

**Descripción:**
La plataforma depende de servicios externos para procesar pagos electrónicos.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Implementar manejo de errores y reintentos.
* Mantener compatibilidad con múltiples proveedores.
* Monitorear disponibilidad del servicio.

---

### R-DEP-002: Dependencia de Servicios de Correo

**Descripción:**
Las notificaciones de pedidos, pagos y recuperación de cuentas dependen de proveedores externos.

**Impacto:** Medio

**Probabilidad:** Media

**Mitigación:**

* Configurar mecanismos de respaldo.
* Utilizar colas de procesamiento.
* Registrar y monitorear fallos de envío.

---

### R-DEP-003: Dependencia de Infraestructura Cloud

**Descripción:**
La disponibilidad del sistema depende de servicios de alojamiento, almacenamiento y bases de datos.

**Impacto:** Alto

**Probabilidad:** Baja

**Mitigación:**

* Respaldos automáticos.
* Monitoreo de infraestructura.
* Plan de recuperación ante desastres.

---

## Riesgos de Integración

### R-INT-001: Desincronización de Inventario

**Descripción:**
Actualizaciones simultáneas pueden provocar inconsistencias en el stock disponible.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Uso de transacciones atómicas.
* Bloqueo de registros críticos.
* Validación previa a la confirmación de compra.

---

### R-INT-002: Incompatibilidad de Contratos API

**Descripción:**
Cambios en interfaces o servicios pueden afectar la comunicación entre módulos.

**Impacto:** Medio

**Probabilidad:** Media

**Mitigación:**

* Versionamiento de APIs.
* Validación automática de contratos.
* Pruebas de integración continuas.

---

### R-INT-003: Fallos en Confirmación de Pagos

**Descripción:**
Errores de comunicación con la pasarela pueden generar estados inconsistentes de pago.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Uso de webhooks confiables.
* Reconciliación periódica de transacciones.
* Registro completo de eventos de pago.

---

## Riesgos de Seguridad

### R-SEG-001: Acceso No Autorizado

**Descripción:**
Usuarios maliciosos pueden intentar acceder a funciones restringidas o información sensible.

**Impacto:** Crítico

**Probabilidad:** Media

**Mitigación:**

* Autenticación segura.
* Control de acceso basado en roles (RBAC).
* Auditoría de accesos.

---

### R-SEG-002: Inyección SQL

**Descripción:**
Manipulación de consultas mediante entradas maliciosas.

**Impacto:** Crítico

**Probabilidad:** Media

**Mitigación:**

* Consultas parametrizadas.
* Validación de entradas.
* Uso de ORM.

---

### R-SEG-003: Cross-Site Scripting (XSS)

**Descripción:**
Inserción de código malicioso en páginas visualizadas por otros usuarios.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Sanitización de datos.
* Escape de caracteres especiales.
* Content Security Policy (CSP).

---

### R-SEG-004: Cross-Site Request Forgery (CSRF)

**Descripción:**
Ejecución de acciones no autorizadas mediante solicitudes fraudulentas.

**Impacto:** Alto

**Probabilidad:** Media

**Mitigación:**

* Tokens CSRF.
* Validación de origen.
* Protección de formularios sensibles.

---

### R-SEG-005: Exposición de Información Sensible

**Descripción:**
Divulgación accidental o maliciosa de datos de clientes, pedidos o pagos.

**Impacto:** Crítico

**Probabilidad:** Baja

**Mitigación:**

* Cifrado de datos sensibles.
* Uso obligatorio de HTTPS.
* Restricciones de acceso a datos críticos.

---

### R-SEG-006: Pérdida de Datos

**Descripción:**
Fallos de hardware, software o errores humanos pueden ocasionar pérdida de información.

**Impacto:** Crítico

**Probabilidad:** Baja

**Mitigación:**

* Backups automáticos.
* Replicación de bases de datos.
* Procedimientos de recuperación documentados.

---

## Matriz de Riesgos

| ID        | Riesgo                               | Impacto | Probabilidad |
| --------- | ------------------------------------ | ------- | ------------ |
| R-DEP-001 | Dependencia de pasarela de pago      | Alto    | Media        |
| R-DEP-002 | Dependencia de correo electrónico    | Medio   | Media        |
| R-DEP-003 | Dependencia de infraestructura cloud | Alto    | Baja         |
| R-INT-001 | Desincronización de inventario       | Alto    | Media        |
| R-INT-002 | Incompatibilidad de contratos API    | Medio   | Media        |
| R-INT-003 | Fallos en confirmación de pagos      | Alto    | Media        |
| R-SEG-001 | Acceso no autorizado                 | Crítico | Media        |
| R-SEG-002 | Inyección SQL                        | Crítico | Media        |
| R-SEG-003 | XSS                                  | Alto    | Media        |
| R-SEG-004 | CSRF                                 | Alto    | Media        |
| R-SEG-005 | Exposición de información sensible   | Crítico | Baja         |
| R-SEG-006 | Pérdida de datos                     | Crítico | Baja         |

---

## Estrategia General de Mitigación

1. Aplicar principios de Security by Design.
2. Definir contratos API estables y versionados.
3. Automatizar pruebas unitarias, integración y seguridad.
4. Implementar monitoreo, alertas y trazabilidad.
5. Mantener dependencias actualizadas.
6. Ejecutar auditorías periódicas de seguridad.
7. Establecer políticas de respaldo y recuperación.
8. Documentar incidentes y acciones correctivas.