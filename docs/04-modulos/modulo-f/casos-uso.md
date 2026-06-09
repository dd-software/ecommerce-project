# Casos de Uso

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo F - Inventario**

---

# CU-F-001 Flujo Principal de Inventario

## Objetivo

Permitir la consulta, validación, reserva, actualización y control del inventario de productos, garantizando la disponibilidad y consistencia del stock en toda la plataforma.

---

## Actores

### Actor Principal

* Administrador de Inventario

### Actores Secundarios

* Sistema de Catálogo
* Sistema de Checkout
* Sistema de Pagos
* Servicio de Auditoría

---

## Descripción

El sistema administra el inventario de productos, permitiendo consultar existencias, validar disponibilidad para compras, reservar unidades durante el checkout y confirmar descuentos de stock cuando una venta es completada.

---

## Precondiciones

1. El usuario posee permisos para consultar o administrar inventario.
2. El producto existe en el catálogo.
3. El servicio de inventario está disponible.
4. La base de datos se encuentra operativa.

---

## Disparador

Se solicita una consulta o actualización de inventario desde un proceso interno o por un usuario autorizado.

---

## Flujo Principal

| Paso | Acción                                                            |
| ---- | ----------------------------------------------------------------- |
| 1    | El actor solicita consultar o validar inventario.                 |
| 2    | El sistema identifica el producto solicitado.                     |
| 3    | El sistema consulta existencias disponibles.                      |
| 4    | El sistema calcula stock disponible, reservado y total.           |
| 5    | El sistema devuelve la información solicitada.                    |
| 6    | Si corresponde a una compra, se realiza la reserva de inventario. |
| 7    | El sistema registra el evento en auditoría.                       |
| 8    | El proceso finaliza exitosamente.                                 |

---

## Flujos Alternativos

### FA-001 Producto Sin Stock

| Paso | Acción                                 |
| ---- | -------------------------------------- |
| 1    | El sistema detecta stock insuficiente. |
| 2    | Se informa la indisponibilidad.        |
| 3    | La operación es rechazada.             |

---

### FA-002 Reserva de Inventario

| Paso | Acción                                  |
| ---- | --------------------------------------- |
| 1    | Checkout solicita reservar unidades.    |
| 2    | El sistema valida disponibilidad.       |
| 3    | Se registra la reserva temporal.        |
| 4    | Se retorna el identificador de reserva. |

---

### FA-003 Liberación de Reserva

| Paso | Acción                                 |
| ---- | -------------------------------------- |
| 1    | La compra es cancelada o expira.       |
| 2    | El sistema localiza la reserva activa. |
| 3    | El stock reservado es liberado.        |
| 4    | Se registra el evento.                 |

---

## Flujos de Excepción

### FE-001 Producto Inexistente

| Paso | Acción                                             |
| ---- | -------------------------------------------------- |
| 1    | Se solicita un producto no registrado.             |
| 2    | El sistema devuelve error de producto inexistente. |
| 3    | Se registra el incidente.                          |

---

### FE-002 Error de Base de Datos

| Paso | Acción                                                |
| ---- | ----------------------------------------------------- |
| 1    | El sistema intenta consultar o actualizar inventario. |
| 2    | Ocurre un error de persistencia.                      |
| 3    | Se registra el incidente.                             |
| 4    | Se informa indisponibilidad temporal.                 |

---

### FE-003 Conflicto de Inventario

| Paso | Acción                                              |
| ---- | --------------------------------------------------- |
| 1    | Dos procesos intentan reservar las mismas unidades. |
| 2    | El sistema detecta el conflicto.                    |
| 3    | Se rechaza la operación inconsistente.              |
| 4    | Se registra el evento.                              |

---

## Postcondiciones

### Éxito

* El inventario fue consultado correctamente.
* La reserva o actualización fue realizada.
* La operación quedó registrada.

### Fallo

* No se modifican existencias.
* Se registra el incidente.
* Se devuelve mensaje de error controlado.

---

## Reglas de Negocio

### RN-INV-001

El stock disponible nunca puede ser negativo.

### RN-INV-002

No se puede reservar más inventario del disponible.

### RN-INV-003

Toda reserva debe asociarse a una orden válida.

### RN-INV-004

La confirmación de venta descuenta definitivamente las unidades vendidas.

### RN-INV-005

Las reservas vencidas deben liberarse automáticamente.

### RN-INV-006

Toda modificación de inventario debe quedar auditada.

---

## Prioridad

Alta

---

## Frecuencia de Uso

Muy Alta

---

## Requisitos Relacionados

| Código     | Descripción                  |
| ---------- | ---------------------------- |
| RF-INV-001 | Consultar inventario         |
| RF-INV-002 | Validar disponibilidad       |
| RF-INV-003 | Reservar stock               |
| RF-INV-004 | Liberar reservas             |
| RF-INV-005 | Confirmar descuento de stock |
| RF-INV-006 | Auditar operaciones          |

---

## APIs Relacionadas

### Estado del Servicio

```http
GET /api/inventario
```

### Respuesta

```json
{
  "success": true
}
```

### Consulta de Producto

```http
GET /api/inventario/{productoId}
```

### Reserva de Inventario

```http
POST /api/inventario/reservar
```

### Liberación de Reserva

```http
POST /api/inventario/liberar
```

### Confirmación de Venta

```http
POST /api/inventario/confirmar
```

---

## Criterios de Aceptación

* El inventario puede ser consultado correctamente.
* El sistema valida disponibilidad en tiempo real.
* Las reservas se crean correctamente.
* Las reservas pueden liberarse.
* El descuento de stock es consistente.
* No existen inconsistencias de inventario.
* Las operaciones quedan registradas en auditoría.
* Los contratos API son cumplidos.

---

## Dependencias

* Módulo Catálogo.
* Módulo Checkout.
* Módulo Pagos.
* Base de Datos.
* Servicio de Auditoría.

---

## Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Especificación Técnica  | spec.md                 |
| Historias de Usuario    | user-stories.md         |
| Testing                 | testing.md              |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |

---

## Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
