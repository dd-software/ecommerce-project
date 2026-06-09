# Testing

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**G - Administración**

## Objetivo

Definir la estrategia, alcance, casos y criterios de prueba para validar el correcto funcionamiento del módulo de Administración, garantizando calidad, seguridad, estabilidad e integración con el resto de la plataforma.

---

# Alcance de Pruebas

Las pruebas cubrirán las siguientes funcionalidades:

* Gestión de usuarios administrativos.
* Gestión de roles y permisos.
* Configuración del sistema.
* Monitoreo de servicios.
* Consulta de auditorías.
* Control de acceso.
* Integración con módulos dependientes.
* Cumplimiento de contratos API.

---

# Estrategia de Testing

## Niveles de Prueba

| Nivel       | Objetivo                             |
| ----------- | ------------------------------------ |
| Unitarias   | Validar componentes individuales     |
| Integración | Validar interacción entre módulos    |
| API         | Validar contratos y endpoints        |
| Seguridad   | Validar autenticación y autorización |
| Rendimiento | Validar tiempos de respuesta         |
| Aceptación  | Validar requisitos funcionales       |

---

# Pruebas Unitarias

## TU-ADM-001 Gestión de Usuarios

### Objetivo

Validar operaciones de usuarios administrativos.

### Casos

* Crear usuario.
* Modificar usuario.
* Desactivar usuario.
* Consultar usuario.
* Listar usuarios.

### Resultado Esperado

Las operaciones se ejecutan correctamente y respetan las reglas de negocio.

---

## TU-ADM-002 Gestión de Roles

### Objetivo

Validar administración de roles.

### Casos

* Consultar roles.
* Asignar rol.
* Modificar permisos.

### Resultado Esperado

Los permisos se aplican correctamente.

---

## TU-ADM-003 Configuración

### Objetivo

Validar configuraciones del sistema.

### Casos

* Consultar configuración.
* Actualizar configuración.
* Validar parámetros.

### Resultado Esperado

Las configuraciones son almacenadas correctamente.

---

## TU-ADM-004 Auditoría

### Objetivo

Validar registro y consulta de eventos.

### Casos

* Registrar evento.
* Consultar auditoría.
* Filtrar eventos.

### Resultado Esperado

Todos los eventos quedan registrados correctamente.

---

## TU-ADM-005 Monitoreo

### Objetivo

Validar la obtención de estados de servicios.

### Casos

* Consultar estado.
* Procesar respuestas.
* Detectar errores.

### Resultado Esperado

Los estados reflejan correctamente la disponibilidad de servicios.

---

# Pruebas de Integración

## TI-ADM-001 Integración con Autenticación

### Objetivo

Validar autenticación y autorización.

### Casos

* Usuario autenticado.
* Usuario no autenticado.
* Usuario sin permisos.

### Resultado Esperado

Solo usuarios autorizados acceden al módulo.

---

## TI-ADM-002 Integración con Auditoría

### Objetivo

Validar registro de eventos administrativos.

### Casos

* Registro exitoso.
* Consulta de registros.
* Recuperación de historial.

### Resultado Esperado

Todos los eventos son almacenados correctamente.

---

## TI-ADM-003 Integración con Catálogo

### Objetivo

Validar monitoreo del módulo Catálogo.

### Resultado Esperado

El estado reportado es consistente.

---

## TI-ADM-004 Integración con Inventario

### Objetivo

Validar monitoreo del módulo Inventario.

### Resultado Esperado

El estado reportado es consistente.

---

## TI-ADM-005 Integración con Checkout

### Objetivo

Validar monitoreo del módulo Checkout.

### Resultado Esperado

El estado reportado es consistente.

---

## TI-ADM-006 Integración con Pagos

### Objetivo

Validar monitoreo del módulo Pagos.

### Resultado Esperado

El estado reportado es consistente.

---

# Pruebas de API

## TA-ADM-001 Estado del Servicio

### Endpoint

```http
GET /api/administracion
```

### Validaciones

* Código HTTP 200.
* Respuesta JSON válida.
* Campo success presente.

### Respuesta Esperada

```json
{
  "success": true
}
```

---

## TA-ADM-002 Usuarios

### Endpoint

```http
GET /api/administracion/usuarios
```

### Validaciones

* Respuesta correcta.
* Lista de usuarios válida.
* Formato JSON correcto.

---

## TA-ADM-003 Roles

### Endpoint

```http
GET /api/administracion/roles
```

### Validaciones

* Lista de roles disponible.
* Permisos asociados válidos.

---

## TA-ADM-004 Configuración

### Endpoints

```http
GET /api/administracion/configuracion
PUT /api/administracion/configuracion
```

### Validaciones

* Consulta correcta.
* Persistencia de cambios.
* Validación de parámetros.

---

## TA-ADM-005 Auditoría

### Endpoint

```http
GET /api/administracion/auditoria
```

### Validaciones

* Consulta exitosa.
* Filtros funcionales.
* Datos consistentes.

---

## TA-ADM-006 Monitoreo

### Endpoint

```http
GET /api/administracion/estado
```

### Validaciones

* Estado de servicios disponible.
* Respuesta consistente.

---

# Pruebas de Seguridad

## TS-ADM-001 Control de Acceso

### Casos

* Usuario autorizado.
* Usuario sin permisos.
* Usuario inexistente.

### Resultado Esperado

Acceso permitido únicamente a usuarios autorizados.

---

## TS-ADM-002 Validación de Tokens

### Casos

* Token válido.
* Token expirado.
* Token inválido.

### Resultado Esperado

Solo tokens válidos permiten acceso.

---

## TS-ADM-003 Escalada de Privilegios

### Casos

* Modificación indebida de roles.
* Acceso a funciones restringidas.

### Resultado Esperado

Las operaciones son rechazadas.

---

## TS-ADM-004 Protección de Datos

### Casos

* Exposición de información sensible.
* Manipulación de parámetros.
* Entradas maliciosas.

### Resultado Esperado

Los datos sensibles permanecen protegidos.

---

# Pruebas de Rendimiento

## TR-ADM-001 Tiempo de Respuesta

### Objetivo

Medir tiempos de respuesta de APIs administrativas.

### Criterio

* Tiempo promedio menor a 2 segundos.

---

## TR-ADM-002 Carga Concurrente

### Objetivo

Validar estabilidad bajo múltiples usuarios administrativos.

### Criterio

* Sin degradación crítica del servicio.

---

## TR-ADM-003 Consulta de Auditorías

### Objetivo

Validar rendimiento sobre grandes volúmenes de registros.

### Criterio

* Consultas ejecutadas dentro de los tiempos definidos.

---

# Pruebas de Aceptación

## TP-ADM-001 Gestión de Usuarios

* Crear usuario.
* Modificar usuario.
* Desactivar usuario.
* Consultar usuario.

### Resultado Esperado

Operaciones completadas correctamente.

---

## TP-ADM-002 Gestión de Roles

* Asignar permisos.
* Actualizar roles.

### Resultado Esperado

Permisos aplicados correctamente.

---

## TP-ADM-003 Configuración

* Consultar configuración.
* Actualizar parámetros.

### Resultado Esperado

Cambios persistidos correctamente.

---

## TP-ADM-004 Auditoría

* Consultar registros.
* Filtrar eventos.

### Resultado Esperado

Información correcta y consistente.

---

## TP-ADM-005 Monitoreo

* Visualizar estado de módulos.

### Resultado Esperado

Información actualizada y confiable.

---

# Datos de Prueba

## Usuarios

| Usuario  | Rol      |
| -------- | -------- |
| admin    | ADMIN    |
| operador | OPERADOR |
| auditor  | AUDITOR  |

---

## Configuración

| Parámetro     | Valor            |
| ------------- | ---------------- |
| moneda        | USD              |
| timezone      | America/Santiago |
| mantenimiento | false            |

---

# Criterios de Éxito

## Cobertura

* Cobertura mínima de pruebas unitarias: 80%.

## Calidad

* Sin defectos críticos abiertos.
* Sin vulnerabilidades críticas abiertas.

## Integración

* Todas las dependencias funcionando correctamente.

## API

* Cumplimiento total del contrato API.

---

# Evidencias Requeridas

* Resultados de pruebas unitarias.
* Reportes de integración.
* Evidencias de seguridad.
* Reportes de rendimiento.
* Evidencias de aceptación.
* Registro de incidencias.

---

# Definición de Aprobación

El módulo será aprobado cuando:

* [ ] Todas las pruebas unitarias estén aprobadas.
* [ ] Todas las pruebas de integración estén aprobadas.
* [ ] Todas las pruebas API estén aprobadas.
* [ ] Todas las pruebas de seguridad estén aprobadas.
* [ ] Todas las pruebas de aceptación estén aprobadas.
* [ ] No existan defectos críticos abiertos.
* [ ] La documentación esté actualizada.

---

# Métricas

| Métrica                   | Objetivo     |
| ------------------------- | ------------ |
| Cobertura de pruebas      | ≥ 80%        |
| Tiempo de respuesta       | < 2 segundos |
| Disponibilidad            | ≥ 99%        |
| Defectos críticos         | 0            |
| Vulnerabilidades críticas | 0            |

---

# Estado

**Versión:** 1.0

**Estado:** Pendiente de Ejecución

**Responsable:** Equipo QA
