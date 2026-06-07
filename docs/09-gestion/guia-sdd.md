# Guía SDD — Software Design Document

## 1. Objetivos y Propósito

Establecer el estándar de documentación técnica que cada equipo debe seguir para
especificar los componentes del sistema ecommerce. Cada archivo `.md` y `.yaml`
debe ser suficientemente preciso y detallado para que un agente de inteligencia
artificial pueda comprender, interpretar y generar el componente de software sin
requerir información adicional.

## 2. Responsabilidades Funcionales

- Definir la estructura obligatoria que debe seguir cada especificación técnica.
- Establecer el lenguaje, formato y nivel de detalle esperado en cada documento.
- Servir como referencia única para validar la completitud de una especificación.
- Garantizar consistencia terminológica entre todos los documentos del proyecto.
- Proveer criterios claros para aprobar o rechazar una especificación durante revisión.

## 3. Actores Involucrados

| Actor | Rol |
|---|---|
| Desarrollador | Redacta la especificación SDD del componente asignado |
| Líder técnico | Revisa y aprueba las especificaciones antes del desarrollo |
| Agente IA | Consume la documentación para generar componentes de software |
| QA | Valida que los criterios de aceptación sean verificables |

## 4. Entradas y Salidas

### Entradas
- Visión del producto (`00-producto/vision-producto.md`)
- Reglas de negocio globales (`00-producto/reglas-negocio.md`)
- Arquitectura general del sistema (`01-arquitectura/arquitectura-general.md`)
- Contratos API del componente (`03-contratos-api/*.yaml`)
- Asignación de componente por equipo

### Salidas
- Archivo `.md` con especificación SDD completa por cada componente asignado
- Documento aprobado y mergeado en el branch del equipo (`team-3-tulachi`)

## 5. Reglas de Negocio

- **RN-SDD-001** Toda especificación debe incluir las 11 secciones obligatorias definidas en esta guía.
- **RN-SDD-002** El lenguaje debe ser técnico, preciso y libre de ambigüedades.
- **RN-SDD-003** Cada regla de negocio debe tener un identificador único formato `RN-XXX`.
- **RN-SDD-004** Los criterios de aceptación deben ser verificables y testeables.
- **RN-SDD-005** La terminología debe ser consistente con `vision-producto.md` y `arquitectura-general.md`.
- **RN-SDD-006** Ninguna especificación puede ser implementada sin aprobación previa del líder técnico.
- **RN-SDD-007** Las reglas de negocio globales (RN-001 a RN-005) aplican a todos los componentes.

## 6. Flujos Operacionales

### Flujo Principal — Redacción y aprobación de especificación SDD

1. Desarrollador recibe componente asignado
2. Desarrollador lee vision-producto.md y reglas-negocio.md
3. Desarrollador revisa el contrato API correspondiente en 03-contratos-api/
4. Desarrollador redacta el archivo .md siguiendo las 11 secciones obligatorias
5. Desarrollador crea branch feature/sdd-[nombre-componente]
6. Desarrollador hace commit y push del archivo
7. Desarrollador abre Pull Request hacia team-3-tulachi
8. Líder técnico revisa la especificación:
   - Si aprueba → merge al branch del equipo
   - Si rechaza → desarrollador corrige y vuelve al paso 6
9. Especificación disponible como fuente de verdad para implementación


## 7. Casos de Uso

### CU-SDD-001: Redactar especificación de componente
- **Actor**: Desarrollador
- **Precondición**: El componente ha sido asignado al desarrollador en Jira.
- **Flujo**: El desarrollador accede a los archivos de contexto, redacta el `.md` con las 11 secciones y lo sube en un PR.
- **Resultado esperado**: Archivo `.md` completo y aprobado disponible en el repositorio.

### CU-SDD-002: Revisar especificación
- **Actor**: Líder técnico
- **Precondición**: El desarrollador ha abierto un Pull Request con la especificación.
- **Flujo**: El líder revisa cada sección, verifica completitud y coherencia, aprueba o solicita cambios.
- **Resultado esperado**: Especificación aprobada y mergeada, o devuelta con observaciones.

### CU-SDD-003: Consumir especificación para generación
- **Actor**: Agente IA
- **Precondición**: La especificación está aprobada y disponible en el repositorio.
- **Flujo**: El agente lee el archivo `.md`, interpreta las secciones y genera el componente de software.
- **Resultado esperado**: Componente generado sin requerir información adicional.

## 8. Entidades y Relaciones
```text
EspecificacionSDD
├── componente: string         # Nombre del componente documentado
├── version: string            # Versión del documento
├── estado: enum               # [borrador, en_revision, aprobado]
├── autor: string              # Desarrollador responsable
├── fecha_creacion: date
├── fecha_aprobacion: date
└── secciones: Seccion[]
Seccion
├── id: string                 # Identificador (ej: "RN-001")
├── titulo: string
├── contenido: string
└── es_obligatoria: boolean
```
**Relaciones:**
- Una EspecificacionSDD documenta exactamente un componente del sistema.
- Una EspecificacionSDD referencia uno o más contratos API en `03-contratos-api/`.
- Una EspecificacionSDD debe ser coherente con las reglas de negocio globales.

## 9. Restricciones, Validaciones y Dependencias

### Restricciones
- El archivo debe estar en formato Markdown (`.md`).
- El nombre del archivo debe coincidir con el componente documentado.
- No se puede iniciar implementación sin especificación en estado `aprobado`.

### Validaciones
- Todas las secciones obligatorias deben estar presentes y no vacías.
- Los identificadores de reglas de negocio no pueden repetirse entre secciones.
- Los actores mencionados deben pertenecer al conjunto: Visitante, Cliente, Administrador, Sistema.

### Dependencias
- `00-producto/vision-producto.md`
- `00-producto/reglas-negocio.md`
- `01-arquitectura/arquitectura-general.md`
- `03-contratos-api/*.yaml`
- `09-gestion/workflow-git.md`

## 10. Criterios de Aceptación

- **CA-SDD-001** Dado un archivo `.md`, cuando se revisa, entonces contiene las 11 secciones obligatorias completas y no vacías.
- **CA-SDD-002** Dado un criterio de aceptación, cuando se evalúa, entonces es verificable mediante una prueba concreta.
- **CA-SDD-003** Dado un término técnico, cuando se contrasta con `vision-producto.md`, entonces es consistente.
- **CA-SDD-004** Dada una regla de negocio, cuando se identifica, entonces tiene un código único formato `RN-XXX`.
- **CA-SDD-005** Dado el documento completo, cuando lo lee un agente IA, entonces puede generar el componente sin información adicional.

## 11. Consideraciones de Integración

- Cada especificación SDD es prerequisito directo para la implementación en `04-modulos/`.
- Las entidades definidas deben ser coherentes con el modelo de datos en `02-modelado/`.
- Los endpoints descritos deben coincidir exactamente con los contratos en `03-contratos-api/`.
- Los criterios de aceptación alimentan directamente los casos de prueba en `07-testing/`.
- El estado de cada especificación debe estar reflejado en el tablero Jira del equipo.