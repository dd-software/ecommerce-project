# Backlog Técnico

## Propósito

Este documento define las tareas técnicas necesarias para implementar la plataforma eCommerce siguiendo el enfoque SDD, asegurando trazabilidad entre especificaciones, historias de usuario, contratos API, pruebas y documentación.

---

# Diseño

## TK-DIS-001: Definición de Arquitectura

**Descripción**
Diseñar la arquitectura general de la solución.

**Actividades**

* Definir arquitectura por capas.
* Identificar módulos del sistema.
* Definir patrones de diseño.
* Elaborar diagramas arquitectónicos.

**Entregables**

* Documento de arquitectura.
* Diagrama de componentes.
* Diagrama de despliegue.

**Prioridad:** Alta

---

## TK-DIS-002: Diseño del Modelo de Datos

**Descripción**
Diseñar la estructura de almacenamiento de datos.

**Actividades**

* Definir entidades y relaciones.
* Diseñar esquema relacional.
* Crear diccionario de datos.
* Definir restricciones e índices.

**Entregables**

* Modelo entidad-relación.
* Esquema lógico de base de datos.

**Prioridad:** Alta

---

## TK-DIS-003: Diseño de APIs

**Descripción**
Definir contratos de integración entre componentes.

**Actividades**

* Diseñar endpoints REST.
* Definir payloads.
* Establecer códigos de respuesta.
* Documentar autenticación y autorización.

**Entregables**

* api-contract.md
* OpenAPI/Swagger.

**Prioridad:** Alta

---

# Desarrollo

## TK-DEV-001: Implementación de Gestión de Productos

**Descripción**
Desarrollar funcionalidades para administrar productos.

**Actividades**

* Crear productos.
* Actualizar productos.
* Eliminar productos.
* Consultar catálogo.

**Dependencias**

* TK-DIS-002
* TK-DIS-003

**Prioridad:** Alta

---

## TK-DEV-002: Implementación de Gestión de Inventario

**Descripción**
Desarrollar el control de stock de productos.

**Actividades**

* Registrar existencias.
* Actualizar stock.
* Reservar stock.
* Liberar stock.

**Dependencias**

* TK-DEV-001

**Prioridad:** Alta

---

## TK-DEV-003: Implementación de Carrito de Compras

**Descripción**
Permitir la gestión de productos seleccionados por el cliente.

**Actividades**

* Agregar productos.
* Actualizar cantidades.
* Eliminar productos.
* Calcular totales.

**Dependencias**

* TK-DEV-001
* TK-DEV-002

**Prioridad:** Alta

---

## TK-DEV-004: Implementación de Gestión de Pedidos

**Descripción**
Desarrollar el flujo completo de pedidos.

**Actividades**

* Crear pedido.
* Confirmar pedido.
* Actualizar estado.
* Consultar historial.

**Dependencias**

* TK-DEV-003

**Prioridad:** Alta

---

## TK-DEV-005: Implementación de Pasarela de Pago

**Descripción**
Integrar el sistema con proveedores de pago.

**Actividades**

* Crear transacciones.
* Procesar pagos.
* Confirmar pagos.
* Gestionar rechazos y errores.

**Dependencias**

* TK-DEV-004

**Prioridad:** Alta

---

## TK-DEV-006: Implementación de Seguridad

**Descripción**
Desarrollar mecanismos de autenticación y autorización.

**Actividades**

* Registro de usuarios.
* Inicio de sesión.
* Gestión de roles.
* Protección de endpoints.

**Prioridad:** Alta

---

# Testing

## TK-TST-001: Pruebas Unitarias

**Descripción**
Validar componentes individuales.

**Cobertura mínima**

* 80% del código.

**Casos**

* Servicios.
* Validadores.
* Repositorios.
* Controladores.

**Prioridad:** Alta

---

## TK-TST-002: Pruebas de Integración

**Descripción**
Validar interacción entre módulos.

**Escenarios**

* Productos ↔ Inventario.
* Inventario ↔ Pedidos.
* Pedidos ↔ Pagos.
* APIs ↔ Base de datos.

**Prioridad:** Alta

---

## TK-TST-003: Pruebas de Aceptación

**Descripción**
Validar cumplimiento de historias de usuario.

**Escenarios**

* Compra completa.
* Gestión de inventario.
* Gestión de pagos.
* Administración de catálogo.

**Prioridad:** Alta

---

## TK-TST-004: Pruebas de Seguridad

**Descripción**
Validar protección del sistema.

**Casos**

* Acceso no autorizado.
* Manipulación de parámetros.
* Inyección SQL.
* XSS y CSRF.

**Prioridad:** Alta

---

## TK-TST-005: Pruebas de Rendimiento

**Descripción**
Evaluar comportamiento bajo carga.

**Métricas**

* Tiempo de respuesta.
* Throughput.
* Uso de recursos.
* Concurrencia.

**Prioridad:** Media

---

# Documentación

## TK-DOC-001: Documentación Técnica

**Descripción**
Documentar componentes y arquitectura.

**Entregables**

* Diagramas actualizados.
* Modelo de datos.
* Contratos API.
* Guías técnicas.

**Prioridad:** Alta

---

## TK-DOC-002: Documentación Funcional

**Descripción**
Documentar funcionalidades de negocio.

**Entregables**

* Casos de uso.
* Flujos operativos.
* Reglas de negocio.

**Prioridad:** Media

---

## TK-DOC-003: Manual de Despliegue

**Descripción**
Documentar instalación y operación.

**Entregables**

* Requisitos.
* Configuración.
* Variables de entorno.
* Procedimiento de despliegue.

**Prioridad:** Alta

---

## TK-DOC-004: Evidencias de Calidad

**Descripción**
Registrar resultados de validación.

**Entregables**

* Reportes de pruebas.
* Métricas de cobertura.
* Registro de incidencias.
* Evidencias de corrección.

**Prioridad:** Media

---

# Estado Inicial

| ID         | Tarea                   | Categoría     | Prioridad | Estado    |
| ---------- | ----------------------- | ------------- | --------- | --------- |
| TK-DIS-001 | Arquitectura            | Diseño        | Alta      | Pendiente |
| TK-DIS-002 | Modelo de Datos         | Diseño        | Alta      | Pendiente |
| TK-DIS-003 | Diseño de APIs          | Diseño        | Alta      | Pendiente |
| TK-DEV-001 | Gestión de Productos    | Desarrollo    | Alta      | Pendiente |
| TK-DEV-002 | Gestión de Inventario   | Desarrollo    | Alta      | Pendiente |
| TK-DEV-003 | Carrito de Compras      | Desarrollo    | Alta      | Pendiente |
| TK-DEV-004 | Gestión de Pedidos      | Desarrollo    | Alta      | Pendiente |
| TK-DEV-005 | Pasarela de Pago        | Desarrollo    | Alta      | Pendiente |
| TK-DEV-006 | Seguridad               | Desarrollo    | Alta      | Pendiente |
| TK-TST-001 | Pruebas Unitarias       | Testing       | Alta      | Pendiente |
| TK-TST-002 | Pruebas de Integración  | Testing       | Alta      | Pendiente |
| TK-TST-003 | Pruebas de Aceptación   | Testing       | Alta      | Pendiente |
| TK-TST-004 | Pruebas de Seguridad    | Testing       | Alta      | Pendiente |
| TK-TST-005 | Pruebas de Rendimiento  | Testing       | Media     | Pendiente |
| TK-DOC-001 | Documentación Técnica   | Documentación | Alta      | Pendiente |
| TK-DOC-002 | Documentación Funcional | Documentación | Media     | Pendiente |
| TK-DOC-003 | Manual de Despliegue    | Documentación | Alta      | Pendiente |
| TK-DOC-004 | Evidencias de Calidad   | Documentación | Media     | Pendiente |