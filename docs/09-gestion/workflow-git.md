# Workflow Git

**Versión:** 1.0
**Autor:** Equipo 3 Tulachi

---

## 1. Objetivos y Propósito

Definir el flujo de trabajo Git oficial del proyecto Ecommerce para gestionar el desarrollo colaborativo, el control de versiones, la revisión de código y la integración de cambios de forma segura y trazable.

Este documento establece las reglas y procedimientos que todos los integrantes del equipo deben seguir al trabajar sobre el repositorio del proyecto.

---

## 2. Responsabilidades Funcionales

* Gestionar el ciclo de vida de las ramas del proyecto.
* Garantizar la trazabilidad de cambios mediante commits descriptivos.
* Controlar la integración de nuevas funcionalidades.
* Facilitar revisiones de código mediante Pull Requests.
* Proteger la estabilidad de las ramas principales.
* Mantener un historial verificable de modificaciones realizadas durante el desarrollo.

---

## 3. Actores Involucrados

| Actor         | Rol                                                |
| ------------- | -------------------------------------------------- |
| Desarrollador | Implementa funcionalidades y realiza commits       |
| Líder Técnico | Revisa y aprueba Pull Requests                     |
| GitHub        | Plataforma de control de versiones                 |
| Sistema CI/CD | Ejecuta validaciones automáticas sobre los cambios |

---

## 4. Entradas y Salidas

### Entradas

* Tareas asignadas en Jira.
* Historias de usuario.
* Requerimientos funcionales.
* Correcciones de errores.
* Solicitudes de mejora.

### Salidas

* Commits registrados en el repositorio.
* Branches de desarrollo.
* Pull Requests revisados.
* Código integrado en la rama del equipo.
* Historial de cambios auditables.

---

## 5. Reglas de Negocio

### RN-GIT-001

Todo desarrollo debe realizarse en una rama independiente.

### RN-GIT-002

Está prohibido realizar commits directamente sobre la rama principal.

### RN-GIT-003

Toda integración debe realizarse mediante Pull Request.

### RN-GIT-004

Todo Pull Request debe ser aprobado por el líder técnico antes de ser fusionado.

### RN-GIT-005

Los mensajes de commit deben seguir una convención estandarizada.

### RN-GIT-006

Cada tarea debe estar asociada a una rama específica.

### RN-GIT-007

Los conflictos de merge deben resolverse antes de aprobar un Pull Request.

---

## 6. Flujos Operacionales

### Flujo Principal — Desarrollo de Funcionalidad

1. El desarrollador actualiza su repositorio local.
2. Crea una rama feature basada en el branch del equipo.
3. Implementa la funcionalidad asignada.
4. Realiza commits descriptivos.
5. Publica la rama en GitHub.
6. Crea un Pull Request.
7. El líder técnico revisa los cambios.
8. Si existen observaciones:

   * Se solicitan correcciones.
   * El desarrollador actualiza la rama.
9. Si es aprobado:

   * Se realiza merge hacia el branch del equipo.
10. La funcionalidad queda disponible para integración.

### Flujo Alternativo — Corrección de Error

1. Se identifica un error.
2. Se crea una rama de corrección.
3. Se implementa la solución.
4. Se realizan pruebas.
5. Se crea Pull Request.
6. Se aprueba e integra la corrección.

---

## 7. Casos de Uso

### CU-GIT-001 Crear Rama de Funcionalidad

**Actor:** Desarrollador

**Precondición:** Existe una tarea asignada.

**Flujo:**

1. Actualizar repositorio local.
2. Crear rama feature.
3. Asociar la rama a la tarea.

**Resultado esperado:**

La funcionalidad se desarrolla de forma aislada.

---

### CU-GIT-002 Crear Pull Request

**Actor:** Desarrollador

**Precondición:** Los cambios fueron publicados en GitHub.

**Flujo:**

1. Seleccionar rama origen.
2. Crear Pull Request.
3. Solicitar revisión.

**Resultado esperado:**

Los cambios quedan disponibles para revisión.

---

### CU-GIT-003 Aprobar Integración

**Actor:** Líder Técnico

**Precondición:** Existe un Pull Request pendiente.

**Flujo:**

1. Revisar cambios.
2. Verificar cumplimiento de estándares.
3. Aprobar o rechazar.

**Resultado esperado:**

Los cambios son integrados o devueltos para corrección.

---

## 8. Entidades y Relaciones

```text
Repositorio
├── nombre
├── url
└── ramas

Rama
├── nombre
├── tipo
├── creador
└── fecha_creacion

Commit
├── hash
├── mensaje
├── autor
└── fecha

PullRequest
├── id
├── rama_origen
├── rama_destino
├── estado
└── aprobador
```

### Relaciones

* Un Repositorio contiene múltiples Ramas.
* Una Rama contiene múltiples Commits.
* Un Pull Request conecta una rama origen con una rama destino.
* Un Pull Request puede contener múltiples Commits.

---

## 9. Restricciones, Validaciones y Dependencias

### Restricciones

* No se permiten commits directos a ramas protegidas.
* Todo Pull Request debe ser revisado.
* Toda rama debe tener un nombre descriptivo.

### Validaciones

* Los commits deben seguir la convención establecida.
* La rama debe compilar correctamente antes del merge.
* No deben existir conflictos pendientes al momento de integrar cambios.

### Dependencias

* Git.
* GitHub.
* Jira.
* Branch del equipo `team-3-tulachi`.

---

## 10. Criterios de Aceptación

### CA-GIT-001

Dada una nueva funcionalidad, cuando se desarrolla, entonces existe una rama dedicada para su implementación.

### CA-GIT-002

Dado un Pull Request, cuando se revisa, entonces debe contar con aprobación antes del merge.

### CA-GIT-003

Dado un commit, cuando se registra, entonces posee un mensaje descriptivo y trazable.

### CA-GIT-004

Dado un cambio integrado, cuando se consulta el historial, entonces puede identificarse su origen y responsable.

### CA-GIT-005

Dado el repositorio, cuando se inspeccionan las ramas principales, entonces no existen commits directos realizados por desarrolladores.

---

## 11. Consideraciones de Integración

* El workflow Git es utilizado por todos los módulos del sistema Ecommerce.
* Los cambios documentados en `04-modulos/` deben seguir este flujo.
* Las tareas definidas en Jira deben vincularse con ramas y Pull Requests.
* Los criterios de aceptación definidos en `07-testing/` se validarán sobre el código integrado.
* El estado de cada Pull Request debe reflejarse en el tablero de gestión del proyecto.
