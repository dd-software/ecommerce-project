# Módulo E - Pasarela de Pago

## Objetivo

Implementar el módulo de pasarela de pago encargado de procesar transacciones financieras de manera segura, confiable y trazable, permitiendo la integración con proveedores de servicios de pago externos para completar las compras realizadas por los clientes.

---

## Alcance

El módulo contempla las siguientes funcionalidades:

### Gestión de Pagos

* Procesar pagos de órdenes de compra.
* Validar información de pago enviada por el cliente.
* Confirmar autorización de transacciones.
* Gestionar pagos aprobados y rechazados.
* Registrar resultados de procesamiento.

### Integración con Proveedores

* Conectarse con servicios externos de pago.
* Enviar solicitudes de autorización.
* Recibir respuestas de aprobación o rechazo.
* Gestionar tiempos de espera y reintentos.

### Seguridad de Transacciones

* Proteger la información sensible durante la transmisión.
* Validar autenticidad de solicitudes.
* Registrar eventos de auditoría.
* Cumplir buenas prácticas de seguridad financiera.

### Gestión de Estados

* Pendiente.
* Procesando.
* Aprobado.
* Rechazado.
* Cancelado.
* Reembolsado.

### Notificaciones

* Informar al módulo de pedidos el resultado del pago.
* Notificar al cliente sobre el estado de la transacción.
* Registrar eventos para seguimiento administrativo.

---

## Actores

### Cliente

Usuario que realiza una compra y ejecuta el pago correspondiente.

### Sistema Ecommerce

Sistema que solicita el procesamiento del pago.

### Proveedor de Pago

Servicio externo encargado de validar y autorizar la transacción.

### Administrador

Usuario responsable de monitorear pagos y resolver incidencias.

---

## Requisitos Funcionales

### RF-E-001 Procesar Pago

El sistema debe permitir procesar pagos asociados a una orden de compra.

### RF-E-002 Validar Datos

El sistema debe validar la información recibida antes de enviar la solicitud al proveedor externo.

### RF-E-003 Autorizar Transacción

El sistema debe solicitar autorización al proveedor de pago configurado.

### RF-E-004 Registrar Resultado

El sistema debe almacenar el resultado de cada transacción procesada.

### RF-E-005 Consultar Estado

El sistema debe permitir consultar el estado actual de una transacción.

### RF-E-006 Gestionar Reintentos

El sistema debe permitir reintentar transacciones fallidas cuando corresponda.

### RF-E-007 Notificar Resultado

El sistema debe informar el resultado del pago a los módulos dependientes.

### RF-E-008 Procesar Reembolsos

El sistema debe permitir la gestión de devoluciones y reembolsos autorizados.

---

## Requisitos No Funcionales

### Seguridad

* Todas las comunicaciones deberán utilizar HTTPS/TLS.
* La información sensible no deberá almacenarse en texto plano.
* Se deberán registrar eventos de auditoría.
* Se deberá validar la autenticidad de las respuestas recibidas.

### Rendimiento

* El tiempo promedio de respuesta deberá ser inferior a 5 segundos.
* El sistema deberá soportar múltiples transacciones concurrentes.

### Disponibilidad

* El servicio deberá estar disponible al menos el 99.9% del tiempo.

### Escalabilidad

* El módulo deberá soportar incrementos de carga sin afectar la estabilidad de las transacciones.

### Trazabilidad

* Cada transacción deberá contar con identificadores únicos y registros históricos.

---

## Modelo de Datos

### TransaccionPago

| Campo               | Tipo          | Descripción                 |
| ------------------- | ------------- | --------------------------- |
| id                  | UUID          | Identificador único         |
| orden_id            | UUID          | Orden asociada              |
| usuario_id          | UUID          | Cliente que realiza el pago |
| monto               | DECIMAL(10,2) | Valor de la transacción     |
| moneda              | VARCHAR(10)   | Moneda utilizada            |
| proveedor           | VARCHAR(50)   | Pasarela utilizada          |
| estado              | VARCHAR(20)   | Estado actual               |
| referencia_externa  | VARCHAR(100)  | Identificador del proveedor |
| fecha_creacion      | DATETIME      | Fecha de creación           |
| fecha_actualizacion | DATETIME      | Última actualización        |

### HistorialTransaccion

| Campo           | Tipo        | Descripción          |
| --------------- | ----------- | -------------------- |
| id              | UUID        | Identificador        |
| transaccion_id  | UUID        | Transacción asociada |
| estado_anterior | VARCHAR(20) | Estado previo        |
| estado_nuevo    | VARCHAR(20) | Estado actualizado   |
| observacion     | TEXT        | Comentario o detalle |
| fecha_evento    | DATETIME    | Fecha del cambio     |

---

## Flujo Principal

1. El cliente confirma la compra.
2. El sistema genera una solicitud de pago.
3. Se validan los datos de la transacción.
4. La solicitud es enviada al proveedor de pago.
5. El proveedor procesa la operación.
6. Se recibe la respuesta de autorización.
7. El sistema registra el resultado.
8. Se actualiza el estado de la orden.
9. Se notifica al cliente y a los módulos relacionados.

---

## Reglas de Negocio

### RN-E-001

Toda transacción deberá estar asociada a una orden válida.

### RN-E-002

No se podrán procesar pagos para órdenes canceladas.

### RN-E-003

Las transacciones aprobadas generarán actualización automática del estado de la orden.

### RN-E-004

Las transacciones rechazadas deberán registrar la causa correspondiente.

### RN-E-005

Cada transacción deberá poseer una referencia única para trazabilidad.

### RN-E-006

Los reembolsos solo podrán ejecutarse sobre pagos previamente aprobados.

### RN-E-007

Las respuestas provenientes del proveedor deberán ser verificadas antes de actualizar estados internos.

---

## Dependencias

### API de Carrito

Responsable de proporcionar el resumen de compra y monto total a pagar.

### API de Pedidos

Responsable de actualizar el estado de las órdenes según el resultado del pago.

### API de Usuarios

Responsable de proporcionar información del cliente para validaciones y notificaciones.

### API de Inventario

Responsable de confirmar disponibilidad de productos antes de completar la compra.

### API de Proveedor de Pago

Responsable de autorizar, rechazar o reembolsar transacciones.

---

## Manejo de Errores

| Código  | Descripción                         |
| ------- | ----------------------------------- |
| PAY-001 | Datos de pago inválidos             |
| PAY-002 | Orden inexistente                   |
| PAY-003 | Transacción rechazada               |
| PAY-004 | Tiempo de espera excedido           |
| PAY-005 | Error de comunicación con proveedor |
| PAY-006 | Reembolso no autorizado             |
| PAY-007 | Error interno de procesamiento      |

---

## Criterios de Éxito

* Las transacciones se procesan correctamente.
* Los estados de pago son consistentes.
* La integración con proveedores externos funciona adecuadamente.
* La información financiera se mantiene segura.
* Existe trazabilidad completa de todas las operaciones.
* Los módulos dependientes reciben notificaciones oportunas y confiables.