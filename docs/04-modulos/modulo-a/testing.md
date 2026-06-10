# Testing

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo A - Catálogo**

## Objetivo

Definir la estrategia, alcance, casos y criterios de pruebas para garantizar la calidad, confiabilidad e integración del módulo de catálogo.

---

# Alcance de Pruebas

Las pruebas cubren:

* Consulta de catálogo.
* Visualización de productos.
* Consulta de detalle de producto.
* Integración con inventario.
* Validación de APIs.
* Manejo de errores.
* Rendimiento básico.
* Seguridad de acceso.

---

# Estrategia de Testing

| Tipo de Prueba | Objetivo                                       |
| -------------- | ---------------------------------------------- |
| Unitarias      | Validar componentes individuales               |
| Integración    | Verificar interacción entre módulos            |
| Funcionales    | Validar requisitos y casos de uso              |
| Aceptación     | Confirmar cumplimiento de criterios de negocio |
| Rendimiento    | Medir tiempos de respuesta                     |
| Seguridad      | Validar controles de protección                |

---

# Pruebas Unitarias

## Objetivo

Verificar el correcto funcionamiento de los componentes internos del módulo.

### Casos de Prueba

#### UT-001 Obtener Catálogo

**Descripción:** Validar la recuperación de productos.

**Resultado Esperado:**

* La lista de productos es obtenida correctamente.
* No se generan excepciones.

---

#### UT-002 Validar Producto Activo

**Descripción:** Verificar que solo se devuelvan productos activos.

**Resultado Esperado:**

* Los productos inactivos son excluidos.

---

#### UT-003 Validar Disponibilidad

**Descripción:** Verificar el cálculo y presentación del stock.

**Resultado Esperado:**

* El stock mostrado coincide con el inventario.

---

#### UT-004 Manejo de Error

**Descripción:** Validar respuesta ante excepciones internas.

**Resultado Esperado:**

* El sistema retorna error controlado.

---

# Pruebas de Integración

## Objetivo

Verificar la comunicación entre el catálogo y sistemas relacionados.

### Casos de Prueba

#### IT-001 Integración Catálogo-Inventario

**Descripción:** Consultar productos y disponibilidad.

**Resultado Esperado:**

* El catálogo refleja correctamente el stock disponible.

---

#### IT-002 Sincronización de Inventario

**Descripción:** Modificar stock y consultar catálogo.

**Resultado Esperado:**

* Los cambios son visibles inmediatamente.

---

#### IT-003 Disponibilidad de API

**Descripción:** Consumir servicio REST de catálogo.

**Endpoint:**

```http
GET /api/catalogo
```

**Resultado Esperado:**

```json
{
  "success": true
}
```

---

#### IT-004 Error de Servicio Externo

**Descripción:** Simular indisponibilidad de inventario.

**Resultado Esperado:**

* El sistema registra el incidente.
* Se informa adecuadamente al usuario.

---

# Pruebas de Aceptación

## Objetivo

Validar que el módulo cumple los requisitos funcionales definidos.

### AT-001 Acceso al Catálogo

**Dado** que existen productos registrados

**Cuando** el usuario accede al catálogo

**Entonces** el sistema muestra los productos disponibles.

---

### AT-002 Consulta de Producto

**Dado** que existe un producto activo

**Cuando** el usuario selecciona el producto

**Entonces** el sistema muestra el detalle correspondiente.

---

### AT-003 Producto Agotado

**Dado** que un producto no tiene stock

**Cuando** el usuario visualiza el catálogo

**Entonces** el producto aparece identificado como agotado.

---

### AT-004 Tiempo de Respuesta

**Dado** que el sistema está operativo

**Cuando** el usuario consulta el catálogo

**Entonces** la respuesta se obtiene en menos de 2 segundos.

---

# Pruebas de Rendimiento

## Objetivo

Validar el comportamiento bajo carga.

### PT-001 Consulta Concurrente

**Escenario:**

* 100 usuarios concurrentes.

**Resultado Esperado:**

* Tiempo de respuesta menor a 2 segundos.
* Sin errores críticos.

---

### PT-002 Carga Sostenida

**Escenario:**

* Operación continua durante 1 hora.

**Resultado Esperado:**

* Disponibilidad superior al 99%.

---

# Pruebas de Seguridad

## Objetivo

Validar la protección de los servicios expuestos.

### ST-001 Comunicación Segura

**Validación:**

* Uso obligatorio de HTTPS.

---

### ST-002 Validación de Entradas

**Validación:**

* Rechazo de datos inválidos o malformados.

---

### ST-003 Protección contra Enumeración

**Validación:**

* No exposición de información sensible en respuestas de error.

---

# Criterios de Éxito

Las pruebas serán consideradas satisfactorias cuando:

* [ ] 100% de las pruebas unitarias sean exitosas.
* [ ] 100% de las pruebas de integración sean exitosas.
* [ ] 100% de las pruebas de aceptación sean exitosas.
* [ ] No existan defectos críticos abiertos.
* [ ] El rendimiento cumpla los requisitos definidos.
* [ ] Los controles de seguridad sean aprobados.

---

# Evidencias

Las siguientes evidencias deberán almacenarse:

* Resultados de pruebas unitarias.
* Reportes de integración.
* Evidencias de pruebas funcionales.
* Resultados de pruebas de rendimiento.
* Resultados de validaciones de seguridad.
* Registro de incidencias detectadas.

---

# Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| Especificación          | spec.md                 |
| Casos de Uso            | casos-uso.md            |
| API Contract            | api-contract.md         |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |

---

# Aprobación

| Rol           | Estado    |
| ------------- | --------- |
| QA            | Pendiente |
| Líder Técnico | Pendiente |
| Product Owner | Pendiente |

**Estado del Plan de Testing:** Pendiente de ejecución.
