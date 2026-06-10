# Módulo F - Inventario

## Información General

| Campo       | Valor                                                    |
| ----------- | -------------------------------------------------------- |
| Proyecto    | Plataforma E-commerce con Gestión de Inventarios y Pagos |
| Módulo      | Inventario                                               |
| Código      | F                                                        |
| Versión     | 1.0                                                      |
| Metodología | SDD (Software Design Description)                        |

---

# Objetivo

Implementar la funcionalidad de gestión de inventario que permita consultar existencias, validar disponibilidad de productos, reservar stock durante el proceso de compra y actualizar inventario tras la confirmación de una venta.

---

# Alcance

El módulo incluye las siguientes funcionalidades:

* Consulta de inventario por producto.
* Consulta masiva de existencias.
* Validación de disponibilidad de stock.
* Reserva temporal de inventario.
* Liberación de reservas.
* Confirmación de descuento de stock.
* Auditoría de movimientos.
* Integración con Catálogo.
* Integración con Checkout.
* Integración con Pagos.

## Fuera de Alcance

* Administración de productos.
* Gestión de precios.
* Procesamiento de pagos.
* Gestión de usuarios.
* Logística y despacho.

---

# Dependencias

## Dependencias Funcionales

* Módulo Catálogo.
* Módulo Checkout.
* Módulo Pagos.
* Servicio de Auditoría.

## Dependencias Técnicas

* API REST.
* Base de Datos MySQL.
* Apache HTTP Server.
* PHP 8.2+.
* Sistema de Logs.

---

# Contrato API

## Estado del Servicio

```http
GET /api/inventario
```

### Respuesta

```json
{
  "success": true
}
```

---

# Requisitos Funcionales

## RF-INV-001 Consultar Inventario

El sistema debe permitir consultar las existencias de un producto.

### Entradas

* productoId

### Salidas

* stockDisponible
* stockReservado
* stockTotal

---

## RF-INV-002 Validar Disponibilidad

El sistema debe validar si existe inventario suficiente para una compra.

### Entradas

* productoId
* cantidad

### Salidas

* disponible (true/false)

---

## RF-INV-003 Reservar Inventario

El sistema debe reservar temporalmente unidades asociadas a una orden.

### Entradas

* ordenId
* productoId
* cantidad

### Salidas

* reservaId

---

## RF-INV-004 Liberar Reserva

El sistema debe liberar inventario reservado cuando una compra es cancelada o expira.

### Entradas

* reservaId

### Salidas

* confirmación de liberación

---

## RF-INV-005 Confirmar Venta

El sistema debe descontar definitivamente las unidades vendidas.

### Entradas

* ordenId

### Salidas

* confirmación de actualización

---

## RF-INV-006 Auditoría

El sistema debe registrar todas las operaciones relevantes de inventario.

---

# Requisitos No Funcionales

## RNF-INV-001 Rendimiento

* Tiempo promedio de respuesta menor a 2 segundos.

---

## RNF-INV-002 Disponibilidad

* Disponibilidad mínima del servicio: 99%.

---

## RNF-INV-003 Escalabilidad

* Soportar operaciones concurrentes de consulta y reserva.

---

## RNF-INV-004 Seguridad

* Comunicación mediante HTTPS.
* Validación de autenticación y autorización.

---

## RNF-INV-005 Integridad

* No debe existir inventario negativo.
* Las operaciones deben ser transaccionales.

---

# Arquitectura del Módulo

```text
Catálogo
    |
    |
Checkout -----> Inventario <----- Pagos
                    |
                    |
               Base de Datos
                    |
                    |
                Auditoría
```

---

# Modelo de Datos Conceptual

## Inventario

| Campo              | Tipo     |
| ------------------ | -------- |
| id                 | UUID     |
| productoId         | UUID     |
| stockDisponible    | Integer  |
| stockReservado     | Integer  |
| stockTotal         | Integer  |
| fechaActualizacion | DateTime |

---

## ReservaInventario

| Campo           | Tipo     |
| --------------- | -------- |
| id              | UUID     |
| ordenId         | UUID     |
| productoId      | UUID     |
| cantidad        | Integer  |
| fechaReserva    | DateTime |
| fechaExpiracion | DateTime |
| estado          | String   |

---

## MovimientoInventario

| Campo           | Tipo     |
| --------------- | -------- |
| id              | UUID     |
| productoId      | UUID     |
| tipoMovimiento  | String   |
| cantidad        | Integer  |
| referencia      | String   |
| fechaMovimiento | DateTime |

---

# Flujo Principal

1. El sistema recibe una solicitud de consulta o validación.
2. Se identifica el producto solicitado.
3. Se consulta el inventario actual.
4. Se devuelve la información requerida.
5. Si existe una compra:

   * Se valida disponibilidad.
   * Se genera una reserva.
6. Cuando el pago es confirmado:

   * Se descuenta el inventario.
   * Se registra el movimiento.
7. El sistema registra la operación en auditoría.

---

# Reglas de Negocio

## RN-INV-001

El stock disponible nunca puede ser negativo.

## RN-INV-002

No se puede reservar más stock del disponible.

## RN-INV-003

Toda reserva debe asociarse a una orden válida.

## RN-INV-004

Las reservas tienen tiempo de expiración configurable.

## RN-INV-005

La confirmación de venta descuenta definitivamente las unidades vendidas.

## RN-INV-006

Toda modificación de inventario debe quedar registrada.

---

# Manejo de Errores

## Error de Producto No Encontrado

### Condición

El producto solicitado no existe.

### Acción

* Retornar error controlado.
* Registrar incidente.

---

## Error de Inventario Insuficiente

### Condición

La cantidad solicitada supera el stock disponible.

### Acción

* Rechazar operación.
* Informar disponibilidad actual.

---

## Error de Reserva

### Condición

No es posible generar la reserva.

### Acción

* Revertir cambios.
* Registrar incidente.

---

## Error de Persistencia

### Condición

Fallo de base de datos.

### Acción

* Cancelar transacción.
* Registrar error.
* Informar indisponibilidad temporal.

---

# Seguridad

## Autenticación

* Acceso restringido a usuarios autenticados y sistemas autorizados.

## Autorización

* Control basado en roles.

## Auditoría

* Registro obligatorio de operaciones críticas.

## Protección de Datos

* Validación de entradas.
* Sanitización de parámetros.
* Gestión segura de errores.

---

# Integraciones

## Catálogo

* Consulta disponibilidad de productos.

## Checkout

* Valida disponibilidad.
* Reserva inventario.

## Pagos

* Confirma venta.
* Actualiza existencias.

---

# Métricas

| Métrica              | Objetivo     |
| -------------------- | ------------ |
| Tiempo de respuesta  | < 2 segundos |
| Disponibilidad       | ≥ 99%        |
| Errores críticos     | 0            |
| Cobertura de pruebas | ≥ 80%        |

---

# Criterios de Aceptación

* Consulta de inventario funcional.
* Validación de stock funcional.
* Reservas operativas.
* Liberación de reservas operativa.
* Confirmación de ventas operativa.
* Integraciones funcionando correctamente.
* Contratos API cumplidos.
* Auditoría implementada.

---

# Riesgos Relacionados

| ID        | Riesgo                      |
| --------- | --------------------------- |
| R-INV-001 | Base de datos no disponible |
| R-INV-003 | Error con catálogo          |
| R-INV-004 | Error con checkout          |
| R-INV-005 | Error con pagos             |
| R-INV-006 | Sobreventa                  |
| R-INV-009 | Acceso no autorizado        |

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
