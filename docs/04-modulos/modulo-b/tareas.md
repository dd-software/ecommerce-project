# Backlog Técnico – Módulo B: Carrito de Compras

## Objetivo

Definir las actividades técnicas necesarias para el diseño, implementación, validación y documentación del módulo de carrito de compras dentro de la plataforma ecommerce.

---

## Diseño

### TAREA-B-001: Diseño de Arquitectura del Módulo

**Descripción:** Definir la arquitectura lógica y física del módulo de carrito.

**Entregables:**

* Diagramas de componentes.
* Flujo de interacción entre frontend, backend y base de datos.
* Definición de responsabilidades por capa.

**Prioridad:** Alta

---

### TAREA-B-002: Diseño del Modelo de Datos

**Descripción:** Diseñar las entidades necesarias para almacenar información del carrito.

**Entregables:**

* Tabla `carts`.
* Tabla `cart_items`.
* Relaciones con usuarios y productos.
* Diccionario de datos.

**Prioridad:** Alta

---

### TAREA-B-003: Diseño de APIs

**Descripción:** Especificar los contratos de comunicación para las operaciones del carrito.

**Entregables:**

* Endpoint agregar producto.
* Endpoint actualizar cantidad.
* Endpoint eliminar producto.
* Endpoint obtener carrito.
* Endpoint vaciar carrito.

**Prioridad:** Alta

---

## Desarrollo

### TAREA-B-004: Implementación de Persistencia

**Descripción:** Crear repositorios y consultas para gestionar la información del carrito.

**Criterios:**

* Soporte para múltiples usuarios.
* Integridad de datos.
* Manejo de concurrencia básica.

**Prioridad:** Alta

---

### TAREA-B-005: Implementación de Servicios de Negocio

**Descripción:** Desarrollar la lógica de negocio asociada al carrito.

**Funcionalidades:**

* Agregar productos.
* Modificar cantidades.
* Eliminar productos.
* Calcular subtotal.
* Calcular total.
* Validar stock disponible.

**Prioridad:** Alta

---

### TAREA-B-006: Implementación de API REST

**Descripción:** Exponer funcionalidades mediante endpoints REST.

**Entregables:**

* Controladores.
* DTOs.
* Validaciones.
* Manejo de errores.

**Prioridad:** Alta

---

### TAREA-B-007: Integración con Inventario

**Descripción:** Validar disponibilidad de productos antes de confirmar cambios en el carrito.

**Dependencias:**

* Módulo de Inventario.

**Prioridad:** Alta

---

### TAREA-B-008: Integración con Pagos

**Descripción:** Preparar la transferencia de información hacia el proceso de checkout y pago.

**Dependencias:**

* Módulo de Pagos.

**Prioridad:** Media

---

## Testing

### TAREA-B-009: Pruebas Unitarias

**Descripción:** Validar la lógica de negocio del carrito.

**Cobertura mínima:**

* Agregar producto.
* Actualizar cantidad.
* Eliminar producto.
* Calcular totales.
* Validar stock.

**Objetivo:** ≥ 80% de cobertura.

---

### TAREA-B-010: Pruebas de Integración

**Descripción:** Verificar la interacción con inventario, autenticación y pagos.

**Escenarios:**

* Producto disponible.
* Producto sin stock.
* Usuario autenticado.
* Checkout exitoso.

---

### TAREA-B-011: Pruebas de API

**Descripción:** Validar comportamiento de endpoints REST.

**Incluye:**

* Casos exitosos.
* Casos inválidos.
* Pruebas de seguridad.
* Validación de respuestas HTTP.

---

### TAREA-B-012: Pruebas de Rendimiento

**Descripción:** Evaluar el comportamiento del carrito bajo carga.

**Métricas:**

* Tiempo de respuesta.
* Consumo de memoria.
* Concurrencia de usuarios.

---

## Documentación

### TAREA-B-013: Documentación Técnica

**Descripción:** Documentar arquitectura, APIs y modelo de datos.

**Entregables:**

* Diagramas actualizados.
* Contratos API.
* Diccionario de datos.

---

### TAREA-B-014: Documentación Funcional

**Descripción:** Documentar el funcionamiento del carrito para usuarios y stakeholders.

**Entregables:**

* Casos de uso.
* Flujos funcionales.
* Reglas de negocio.

---

### TAREA-B-015: Manual de Despliegue

**Descripción:** Documentar instalación y configuración del módulo.

**Incluye:**

* Variables de entorno.
* Dependencias.
* Procedimiento de despliegue.

---

## Estado del Backlog

| ID          | Tarea                   | Categoría     | Prioridad | Estado    |
| ----------- | ----------------------- | ------------- | --------- | --------- |
| TAREA-B-001 | Diseño de Arquitectura  | Diseño        | Alta      | Pendiente |
| TAREA-B-002 | Diseño Modelo de Datos  | Diseño        | Alta      | Pendiente |
| TAREA-B-003 | Diseño APIs             | Diseño        | Alta      | Pendiente |
| TAREA-B-004 | Persistencia            | Desarrollo    | Alta      | Pendiente |
| TAREA-B-005 | Servicios de Negocio    | Desarrollo    | Alta      | Pendiente |
| TAREA-B-006 | API REST                | Desarrollo    | Alta      | Pendiente |
| TAREA-B-007 | Integración Inventario  | Desarrollo    | Alta      | Pendiente |
| TAREA-B-008 | Integración Pagos       | Desarrollo    | Media     | Pendiente |
| TAREA-B-009 | Pruebas Unitarias       | Testing       | Alta      | Pendiente |
| TAREA-B-010 | Pruebas Integración     | Testing       | Alta      | Pendiente |
| TAREA-B-011 | Pruebas API             | Testing       | Alta      | Pendiente |
| TAREA-B-012 | Pruebas Rendimiento     | Testing       | Media     | Pendiente |
| TAREA-B-013 | Documentación Técnica   | Documentación | Alta      | Pendiente |
| TAREA-B-014 | Documentación Funcional | Documentación | Media     | Pendiente |
| TAREA-B-015 | Manual de Despliegue    | Documentación | Media     | Pendiente |
