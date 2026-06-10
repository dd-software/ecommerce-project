# Testing

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo C - Autenticación**

## Objetivo

Definir la estrategia, alcance, casos de prueba y criterios de validación para garantizar la calidad, seguridad y correcto funcionamiento del módulo de autenticación.

---

# Alcance de Pruebas

Las pruebas cubren:

* Validación de credenciales.
* Gestión de sesiones.
* Integración con base de datos de usuarios.
* Integración con auditoría.
* API de autenticación.
* Manejo de errores.
* Seguridad.
* Rendimiento.

---

# Estrategia de Testing

| Tipo de Prueba | Objetivo                                          |
| -------------- | ------------------------------------------------- |
| Unitarias      | Validar componentes individuales                  |
| Integración    | Verificar interacción entre sistemas              |
| Funcionales    | Validar requisitos del negocio                    |
| Aceptación     | Confirmar cumplimiento de criterios de aceptación |
| Seguridad      | Verificar controles de protección                 |
| Rendimiento    | Medir desempeño del servicio                      |

---

# Pruebas Unitarias

## Objetivo

Validar el correcto funcionamiento de las funciones internas del módulo.

### UT-AUT-001 Validación de Credenciales

**Descripción**

Verificar que las credenciales válidas sean aceptadas.

**Resultado Esperado**

* La autenticación es exitosa.
* Se retorna respuesta válida.

---

### UT-AUT-002 Rechazo de Credenciales Inválidas

**Descripción**

Verificar que credenciales incorrectas sean rechazadas.

**Resultado Esperado**

* Se deniega el acceso.
* Se genera mensaje controlado.

---

### UT-AUT-003 Creación de Sesión

**Descripción**

Validar la creación de una sesión autenticada.

**Resultado Esperado**

* Se genera una sesión válida.
* La sesión queda asociada al usuario.

---

### UT-AUT-004 Registro de Eventos

**Descripción**

Verificar el registro de eventos de autenticación.

**Resultado Esperado**

* Los eventos quedan almacenados correctamente.

---

### UT-AUT-005 Manejo de Errores

**Descripción**

Validar la respuesta ante excepciones internas.

**Resultado Esperado**

* Se devuelve un error controlado.
* No se expone información sensible.

---

# Pruebas de Integración

## Objetivo

Validar la interacción del módulo con componentes externos.

### IT-AUT-001 Integración con Base de Datos

**Descripción**

Verificar consulta de usuarios registrados.

**Resultado Esperado**

* Los datos son recuperados correctamente.

---

### IT-AUT-002 Integración con Auditoría

**Descripción**

Verificar registro de accesos y errores.

**Resultado Esperado**

* Los eventos son almacenados correctamente.

---

### IT-AUT-003 Integración de Sesiones

**Descripción**

Validar persistencia y recuperación de sesiones.

**Resultado Esperado**

* Las sesiones son gestionadas correctamente.

---

### IT-AUT-004 Verificación de Endpoint

**Endpoint**

```http
GET /api/autenticacion
```

**Resultado Esperado**

```json
{
  "success": true
}
```

**Validaciones**

* HTTP 200.
* JSON válido.
* Cumplimiento del contrato API.

---

### IT-AUT-005 Error de Dependencia

**Descripción**

Simular indisponibilidad de la base de datos.

**Resultado Esperado**

* Error controlado.
* Registro de incidente.
* Sin exposición de información interna.

---

# Pruebas de Aceptación

## Objetivo

Verificar que el módulo satisface las necesidades del negocio.

### AT-AUT-001 Acceso Correcto

**Dado** un usuario registrado

**Cuando** ingresa credenciales válidas

**Entonces** el sistema permite el acceso.

---

### AT-AUT-002 Acceso Denegado

**Dado** un usuario con credenciales inválidas

**Cuando** intenta autenticarse

**Entonces** el sistema rechaza el acceso.

---

### AT-AUT-003 Cuenta Inactiva

**Dado** una cuenta inactiva

**Cuando** intenta autenticarse

**Entonces** el sistema bloquea el acceso.

---

### AT-AUT-004 Registro de Auditoría

**Dado** un inicio de sesión exitoso

**Cuando** el acceso es concedido

**Entonces** el evento queda registrado.

---

### AT-AUT-005 Disponibilidad del Servicio

**Dado** el servicio operativo

**Cuando** se consulta el endpoint

**Entonces** responde correctamente según el contrato definido.

---

# Pruebas de Seguridad

## Objetivo

Validar la protección del proceso de autenticación.

### ST-AUT-001 Comunicación Segura

**Validación**

* Uso obligatorio de HTTPS.

---

### ST-AUT-002 Protección de Información

**Validación**

* No se exponen contraseñas.
* No se exponen detalles internos.

---

### ST-AUT-003 Validación de Entradas

**Validación**

* Rechazo de datos inválidos.
* Prevención de entradas malformadas.

---

### ST-AUT-004 Protección contra Fuerza Bruta

**Validación**

* Limitación de intentos fallidos.
* Registro de actividad sospechosa.

---

### ST-AUT-005 Gestión Segura de Sesiones

**Validación**

* Expiración correcta de sesiones.
* Invalidación al cerrar sesión.

---

# Pruebas de Rendimiento

## Objetivo

Validar el comportamiento bajo carga.

### PT-AUT-001 Tiempo de Respuesta

**Escenario**

100 solicitudes concurrentes.

**Resultado Esperado**

* Tiempo promedio menor a 2 segundos.

---

### PT-AUT-002 Disponibilidad

**Escenario**

Operación continua durante una hora.

**Resultado Esperado**

* Disponibilidad superior al 99%.

---

# Criterios de Éxito

Las pruebas serán consideradas satisfactorias cuando:

* [ ] 100% de las pruebas unitarias sean exitosas.
* [ ] 100% de las pruebas de integración sean exitosas.
* [ ] 100% de las pruebas de aceptación sean exitosas.
* [ ] No existan defectos críticos abiertos.
* [ ] Los requisitos de seguridad sean aprobados.
* [ ] Los requisitos de rendimiento sean cumplidos.

---

# Evidencias Requeridas

* Resultados de pruebas unitarias.
* Resultados de integración.
* Evidencias funcionales.
* Reportes de seguridad.
* Resultados de rendimiento.
* Registro de incidencias detectadas.

---

# Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Casos de Uso            | casos-uso.md            |
| Historias de Usuario    | user-stories.md         |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |
| Especificación Técnica  | spec.md                 |

---

# Aprobación

| Rol                    | Estado    |
| ---------------------- | --------- |
| QA                     | Pendiente |
| Líder Técnico          | Pendiente |
| Arquitecto de Software | Pendiente |
| Product Owner          | Pendiente |

---

# Estado del Plan

**Versión:** 1.0

**Estado:** Pendiente de Ejecución
