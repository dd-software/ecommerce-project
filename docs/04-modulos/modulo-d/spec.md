# Módulo D - Checkout

## Información General

| Campo       | Valor                                                    |
| ----------- | -------------------------------------------------------- |
| Proyecto    | Plataforma E-commerce con Gestión de Inventarios y Pagos |
| Módulo      | Checkout                                                 |
| Código      | Módulo D                                                 |
| Versión     | 1.0                                                      |
| Metodología | Software Design Description (SDD)                        |

---

# Objetivo

Implementar la funcionalidad de checkout que permita al usuario validar su compra, verificar disponibilidad de inventario, registrar información de entrega y generar una orden lista para ser procesada por el módulo de pagos.

---

# Alcance

El módulo contempla las siguientes funcionalidades:

* Inicio del proceso de checkout.
* Recuperación de productos desde el carrito.
* Validación de disponibilidad de inventario.
* Cálculo de subtotales, impuestos, descuentos y total.
* Validación de datos de entrega.
* Generación de órdenes preliminares.
* Integración con el módulo de pagos.
* Registro de eventos para auditoría.

## Fuera de Alcance

* Procesamiento del pago.
* Gestión de devoluciones.
* Gestión de inventario.
* Administración de productos.
* Gestión de usuarios.

---

# Dependencias

## Dependencias Funcionales

* Módulo de Autenticación.
* Módulo de Carrito.
* Módulo de Inventario.
* Módulo de Pagos.
* Servicio de Auditoría.

## Dependencias Técnicas

* API REST.
* Base de datos de órdenes.
* Sistema de monitoreo.
* Infraestructura HTTPS.

## Contrato API

### Verificación del Servicio

```http
GET /api/checkout
```

### Respuesta

```json
{
  "success": true
}
```

---

# Requisitos Funcionales

## RF-CHK-001 Recuperar Carrito

El sistema debe recuperar los productos seleccionados por el usuario desde el carrito activo.

### Entradas

* Identificador de usuario.
* Identificador de sesión.

### Salidas

* Lista de productos.
* Cantidades seleccionadas.

---

## RF-CHK-002 Validar Inventario

El sistema debe verificar la disponibilidad de stock antes de generar una orden.

### Resultado Esperado

* Confirmación de disponibilidad.
* Identificación de productos sin stock.

---

## RF-CHK-003 Calcular Totales

El sistema debe calcular correctamente:

* Subtotal.
* Impuestos.
* Descuentos.
* Costos de envío.
* Total final.

---

## RF-CHK-004 Validar Información de Entrega

El sistema debe validar los datos requeridos para la entrega del pedido.

### Validaciones

* Campos obligatorios.
* Formatos válidos.
* Información consistente.

---

## RF-CHK-005 Generar Orden

El sistema debe generar una orden preliminar antes de iniciar el pago.

### Resultado Esperado

* Número de orden.
* Productos asociados.
* Totales calculados.
* Estado inicial de la orden.

---

## RF-CHK-006 Registrar Eventos

El sistema debe registrar eventos relevantes del proceso de checkout para fines de auditoría.

---

# Requisitos No Funcionales

## RNF-CHK-001 Rendimiento

El tiempo promedio de respuesta debe ser inferior a 2 segundos.

---

## RNF-CHK-002 Disponibilidad

El servicio debe mantener una disponibilidad mínima del 99%.

---

## RNF-CHK-003 Escalabilidad

El sistema debe soportar múltiples procesos de checkout concurrentes.

---

## RNF-CHK-004 Seguridad

Todas las comunicaciones deben realizarse mediante HTTPS.

---

## RNF-CHK-005 Auditoría

Los eventos críticos del proceso deben registrarse para trazabilidad.

---

# Arquitectura del Módulo

```text
Usuario
   |
   v
Servicio Checkout
   |
   +------> Carrito
   |
   +------> Inventario
   |
   +------> Órdenes
   |
   +------> Auditoría
   |
   +------> Pagos
```

---

# Flujo Principal

1. El usuario inicia el checkout.
2. El sistema recupera los productos del carrito.
3. Se valida la disponibilidad de inventario.
4. Se calculan subtotales, descuentos, impuestos y total.
5. El usuario confirma información de entrega.
6. El sistema valida los datos ingresados.
7. Se genera una orden preliminar.
8. Se registra el evento en auditoría.
9. La orden queda disponible para el procesamiento del pago.

---

# Reglas de Negocio

## RN-CHK-001

Solo usuarios autenticados pueden iniciar un checkout.

---

## RN-CHK-002

El carrito debe contener al menos un producto.

---

## RN-CHK-003

Todos los productos deben tener stock disponible.

---

## RN-CHK-004

Los cálculos monetarios deben realizarse en el servidor.

---

## RN-CHK-005

La orden debe generarse antes del procesamiento del pago.

---

## RN-CHK-006

Toda operación relevante debe quedar registrada para auditoría.

---

# Modelo de Datos Conceptual

## Orden

| Campo         | Tipo     |
| ------------- | -------- |
| id            | UUID     |
| numeroOrden   | String   |
| usuarioId     | UUID     |
| subtotal      | Decimal  |
| impuestos     | Decimal  |
| descuentos    | Decimal  |
| costoEnvio    | Decimal  |
| total         | Decimal  |
| estado        | String   |
| fechaCreacion | DateTime |

---

## DetalleOrden

| Campo          | Tipo    |
| -------------- | ------- |
| id             | UUID    |
| ordenId        | UUID    |
| productoId     | UUID    |
| cantidad       | Integer |
| precioUnitario | Decimal |
| subtotal       | Decimal |

---

# Manejo de Errores

## Error de Inventario

### Condición

Producto sin disponibilidad.

### Acción

* Detener checkout.
* Informar al usuario.
* Registrar evento.

---

## Error de Integración

### Condición

Fallo de comunicación con inventario o pagos.

### Acción

* Registrar incidente.
* Mostrar mensaje controlado.
* Permitir reintento.

---

## Error de Generación de Orden

### Condición

No es posible almacenar la orden.

### Acción

* Revertir operación.
* Registrar incidente.
* Informar indisponibilidad temporal.

---

# Consideraciones de Seguridad

* Uso obligatorio de HTTPS.
* Validación de autenticación y sesión activa.
* Validación de entradas.
* Protección contra manipulación de precios.
* Recalcular montos del lado servidor.
* Registro de auditoría.
* Gestión segura de errores.
* Protección de información sensible.

---

# Criterios de Aceptación

* El usuario puede iniciar el checkout.
* El inventario es validado correctamente.
* Los cálculos son correctos.
* La orden es generada exitosamente.
* Los errores son gestionados adecuadamente.
* El endpoint cumple el contrato API.
* Los requisitos de seguridad son satisfechos.

---

# Riesgos Relacionados

| ID        | Riesgo                              |
| --------- | ----------------------------------- |
| R-CHK-001 | Indisponibilidad del servicio       |
| R-CHK-003 | Fallo de integración con inventario |
| R-CHK-004 | Integración incorrecta con pagos    |
| R-CHK-006 | Error en cálculos                   |
| R-CHK-009 | Manipulación de datos               |

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
| Testing                 | testing.md              |
| Tareas                  | tareas.md               |

---

# Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
