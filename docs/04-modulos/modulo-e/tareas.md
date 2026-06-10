# Backlog Técnico

## Objetivo

Definir las tareas técnicas necesarias para diseñar, desarrollar, probar y documentar la plataforma eCommerce, garantizando la correcta implementación de los módulos de catálogo, carrito de compras, inventario, pedidos, pagos y administración.

---

# Diseño

## TAREA-DIS-001: Arquitectura del Sistema

**Descripción:** Diseñar la arquitectura general de la plataforma.

**Actividades:**

* Definir arquitectura por capas.
* Identificar módulos funcionales.
* Definir integración entre servicios.
* Elaborar diagramas de componentes.

**Entregables:**

* Documento de arquitectura.
* Diagrama de componentes.
* Diagrama de despliegue.

**Prioridad:** Alta

---

## TAREA-DIS-002: Diseño de Base de Datos

**Descripción:** Diseñar el modelo de datos de la aplicación.

**Actividades:**

* Identificar entidades.
* Definir relaciones.
* Diseñar índices.
* Elaborar diccionario de datos.

**Entregables:**

* Modelo entidad-relación.
* Script inicial de base de datos.

**Prioridad:** Alta

---

## TAREA-DIS-003: Diseño de APIs

**Descripción:** Especificar los contratos de comunicación entre frontend y backend.

**Actividades:**

* Definir endpoints.
* Establecer formatos JSON.
* Definir códigos de respuesta.
* Diseñar mecanismos de autenticación.

**Entregables:**

* API Contract.
* Documentación OpenAPI/Swagger.

**Prioridad:** Alta

---

# Desarrollo

## TAREA-DEV-001: Gestión de Usuarios

**Descripción:** Implementar registro, autenticación y administración de usuarios.

**Actividades:**

* Registro de usuarios.
* Inicio de sesión.
* Recuperación de contraseña.
* Gestión de perfiles.

**Prioridad:** Alta

---

## TAREA-DEV-002: Gestión de Catálogo

**Descripción:** Implementar administración de productos y categorías.

**Actividades:**

* CRUD de productos.
* CRUD de categorías.
* Gestión de imágenes.
* Búsqueda y filtrado.

**Prioridad:** Alta

---

## TAREA-DEV-003: Gestión de Carrito

**Descripción:** Implementar funcionalidad de carrito de compras.

**Actividades:**

* Agregar productos.
* Modificar cantidades.
* Eliminar productos.
* Calcular subtotales y totales.

**Prioridad:** Alta

---

## TAREA-DEV-004: Gestión de Inventario

**Descripción:** Implementar control de stock.

**Actividades:**

* Registro de existencias.
* Actualización automática.
* Validación de disponibilidad.
* Alertas de stock bajo.

**Prioridad:** Alta

---

## TAREA-DEV-005: Gestión de Pedidos

**Descripción:** Implementar ciclo de vida de pedidos.

**Actividades:**

* Creación de pedidos.
* Confirmación.
* Seguimiento.
* Historial de compras.

**Prioridad:** Alta

---

## TAREA-DEV-006: Integración de Pagos

**Descripción:** Implementar procesamiento de pagos electrónicos.

**Actividades:**

* Integración con pasarela de pago.
* Confirmación de transacciones.
* Gestión de estados de pago.
* Manejo de errores y reintentos.

**Prioridad:** Alta

---

## TAREA-DEV-007: Panel Administrativo

**Descripción:** Implementar herramientas de administración.

**Actividades:**

* Gestión de productos.
* Gestión de inventario.
* Gestión de pedidos.
* Gestión de usuarios.

**Prioridad:** Media

---

# Testing

## TAREA-TEST-001: Pruebas Unitarias

**Descripción:** Verificar el funcionamiento individual de cada componente.

**Cobertura mínima:**

* 80% de código.

**Actividades:**

* Servicios.
* Repositorios.
* Controladores.
* Validaciones.

**Prioridad:** Alta

---

## TAREA-TEST-002: Pruebas de Integración

**Descripción:** Validar la interacción entre módulos.

**Escenarios:**

* Catálogo → Carrito.
* Carrito → Inventario.
* Pedido → Pago.
* Pedido → Inventario.

**Prioridad:** Alta

---

## TAREA-TEST-003: Pruebas Funcionales

**Descripción:** Verificar cumplimiento de requisitos funcionales.

**Escenarios:**

* Compra completa.
* Gestión de inventario.
* Administración de productos.
* Gestión de usuarios.

**Prioridad:** Alta

---

## TAREA-TEST-004: Pruebas de Seguridad

**Descripción:** Evaluar vulnerabilidades de la plataforma.

**Actividades:**

* Validación de autenticación.
* Control de acceso.
* Protección contra inyección SQL.
* Protección contra XSS.

**Prioridad:** Alta

---

## TAREA-TEST-005: Pruebas de Rendimiento

**Descripción:** Validar comportamiento bajo carga.

**Métricas:**

* Tiempo de respuesta.
* Consumo de recursos.
* Usuarios concurrentes.

**Prioridad:** Media

---

# Documentación

## TAREA-DOC-001: Documentación Técnica

**Descripción:** Documentar la arquitectura y componentes del sistema.

**Entregables:**

* Arquitectura.
* Modelo de datos.
* APIs.
* Diagramas técnicos.

**Prioridad:** Alta

---

## TAREA-DOC-002: Manual de Usuario

**Descripción:** Crear guía de uso para clientes y administradores.

**Contenido:**

* Registro.
* Compra de productos.
* Gestión de pedidos.
* Administración.

**Prioridad:** Media

---

## TAREA-DOC-003: Manual de Despliegue

**Descripción:** Documentar el proceso de instalación y despliegue.

**Contenido:**

* Requisitos.
* Configuración.
* Variables de entorno.
* Procedimiento de despliegue.

**Prioridad:** Alta

---

## TAREA-DOC-004: Evidencias de Pruebas

**Descripción:** Registrar resultados de validación.

**Contenido:**

* Casos de prueba.
* Resultados.
* Incidencias detectadas.
* Evidencias de corrección.

**Prioridad:** Media

---

# Resumen del Backlog

| ID             | Tarea                    | Categoría     | Prioridad | Estado    |
| -------------- | ------------------------ | ------------- | --------- | --------- |
| TAREA-DIS-001  | Arquitectura del Sistema | Diseño        | Alta      | Pendiente |
| TAREA-DIS-002  | Diseño Base de Datos     | Diseño        | Alta      | Pendiente |
| TAREA-DIS-003  | Diseño APIs              | Diseño        | Alta      | Pendiente |
| TAREA-DEV-001  | Gestión de Usuarios      | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-002  | Gestión de Catálogo      | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-003  | Gestión de Carrito       | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-004  | Gestión de Inventario    | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-005  | Gestión de Pedidos       | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-006  | Integración de Pagos     | Desarrollo    | Alta      | Pendiente |
| TAREA-DEV-007  | Panel Administrativo     | Desarrollo    | Media     | Pendiente |
| TAREA-TEST-001 | Pruebas Unitarias        | Testing       | Alta      | Pendiente |
| TAREA-TEST-002 | Pruebas de Integración   | Testing       | Alta      | Pendiente |
| TAREA-TEST-003 | Pruebas Funcionales      | Testing       | Alta      | Pendiente |
| TAREA-TEST-004 | Pruebas de Seguridad     | Testing       | Alta      | Pendiente |
| TAREA-TEST-005 | Pruebas de Rendimiento   | Testing       | Media     | Pendiente |
| TAREA-DOC-001  | Documentación Técnica    | Documentación | Alta      | Pendiente |
| TAREA-DOC-002  | Manual de Usuario        | Documentación | Media     | Pendiente |
| TAREA-DOC-003  | Manual de Despliegue     | Documentación | Alta      | Pendiente |
| TAREA-DOC-004  | Evidencias de Pruebas    | Documentación | Media     | Pendiente |