# MÓDULO INVENTARIO

## 1. Objetivo

El módulo de Inventario es responsable de administrar, consultar y mantener la disponibilidad de productos dentro de la plataforma e-commerce.

Su propósito es garantizar que el sistema disponga de información precisa y actualizada sobre las existencias de cada producto para soportar los procesos de venta, reserva, despacho y reposición.

---

# 2. Alcance

El módulo permite:

* Consultar inventario disponible.
* Consultar inventario por SKU.
* Actualizar niveles de stock.
* Mantener información de disponibilidad.
* Gestionar estados de inventario.
* Exponer información de stock a otros módulos.

No forma parte de este módulo:

* Gestión de precios.
* Gestión de pedidos.
* Gestión de pagos.
* Gestión logística.

---

# 3. Actores

## Administrador de Inventario

Responsable de actualizar y controlar las existencias.

## Sistema de Ventas

Consulta disponibilidad antes de confirmar una compra.

## Sistema de Pedidos

Reserva unidades disponibles.

## Sistema de Logística

Consulta existencias para preparación de despachos.

## API Cliente

Consumidor externo autorizado que consulta información de inventario.

---

# 4. Responsabilidades Funcionales

## RF-01 Consultar Inventario

Permitir la consulta del inventario completo.

## RF-02 Consultar Inventario por SKU

Permitir obtener el detalle de un producto específico.

## RF-03 Actualizar Stock

Permitir modificar la cantidad disponible de un producto.

## RF-04 Validar Disponibilidad

Determinar si existe stock suficiente para una operación.

## RF-05 Exponer Estado del Inventario

Informar si un producto se encuentra disponible, agotado o descontinuado.

---

# 5. Entidades de Negocio

## Inventario

Representa la disponibilidad física y lógica de un producto.

| Campo              | Tipo     | Obligatorio |
| ------------------ | -------- | ----------- |
| sku                | String   | Sí          |
| nombre             | String   | Sí          |
| descripcion        | String   | No          |
| stockDisponible    | Integer  | Sí          |
| stockReservado     | Integer  | Sí          |
| stockTotal         | Integer  | Sí          |
| estado             | Enum     | Sí          |
| fechaActualizacion | DateTime | Sí          |

---

## Estados de Inventario

### DISPONIBLE

Producto con unidades disponibles para venta.

### AGOTADO

Producto sin existencias disponibles.

### DESCONTINUADO

Producto retirado permanentemente del catálogo.

---

# 6. Reglas de Negocio

## RN-01

El SKU debe ser único dentro del sistema.

## RN-02

El stock disponible nunca puede ser negativo.

## RN-03

El stock reservado nunca puede ser negativo.

## RN-04

El stock total debe ser igual a:

stockDisponible + stockReservado

## RN-05

Cuando el stock disponible sea cero, el estado deberá actualizarse automáticamente a AGOTADO.

## RN-06

Los productos descontinuados no podrán recibir nuevas reservas.

## RN-07

Toda modificación de stock deberá registrar fecha de actualización.

## RN-08

No se permitirá actualizar stock para productos inexistentes.

---

# 7. Entradas

## Consulta General

Método: GET

Ruta:

/api/inventario

Parámetros opcionales:

| Parámetro  | Tipo    |
| ---------- | ------- |
| sku        | String  |
| disponible | Boolean |

---

## Consulta por SKU

Método: GET

Ruta:

/api/inventario/{sku}

Parámetro obligatorio:

| Parámetro | Tipo   |
| --------- | ------ |
| sku       | String |

---

## Actualización de Stock

Método: PUT

Ruta:

/api/inventario/{sku}

Body:

{
"stockDisponible": 50
}

---

# 8. Salidas

## Respuesta Exitosa

Código HTTP: 200

{
"sku": "SKU-001",
"nombre": "Laptop Lenovo ThinkPad",
"stockDisponible": 25,
"stockReservado": 5,
"stockTotal": 30,
"estado": "DISPONIBLE"
}

---

## Producto No Encontrado

Código HTTP: 404

{
"codigo": "INV-404",
"mensaje": "Producto no encontrado"
}

---

## Solicitud Inválida

Código HTTP: 400

{
"codigo": "INV-400",
"mensaje": "Datos inválidos"
}

---

# 9. Flujos Operacionales

## Flujo 1: Consulta de Inventario

1. El consumidor invoca el endpoint.
2. El sistema valida la solicitud.
3. El sistema consulta la información.
4. Se construye la respuesta.
5. Se retorna HTTP 200.

---

## Flujo 2: Consulta por SKU

1. Se recibe SKU.
2. Se valida existencia.
3. Se consulta inventario.
4. Se retorna información detallada.

---

## Flujo 3: Actualización de Stock

1. El administrador envía nueva cantidad.
2. El sistema valida datos.
3. Verifica existencia del producto.
4. Actualiza stock.
5. Recalcula estado.
6. Registra fecha de actualización.
7. Devuelve respuesta exitosa.

---

# 10. Casos de Uso

## CU-01 Consultar Inventario

Actor: Sistema de Ventas

Precondición:

* Usuario autenticado.

Postcondición:

* Inventario entregado correctamente.

---

## CU-02 Consultar Producto por SKU

Actor: Sistema de Ventas

Precondición:

* SKU válido.

Postcondición:

* Información del producto obtenida.

---

## CU-03 Actualizar Stock

Actor: Administrador

Precondición:

* Producto existente.

Postcondición:

* Stock actualizado.

---

# 11. Validaciones

## SKU

* Obligatorio.
* Máximo 50 caracteres.
* Debe existir para consultas individuales.

## Stock Disponible

* Obligatorio.
* Entero positivo.
* Valor mínimo: 0.

## Estado

Valores permitidos:

* DISPONIBLE
* AGOTADO
* DESCONTINUADO

---

# 12. Dependencias

El módulo depende de:

* Base de Datos de Productos.
* Servicio de Autenticación.
* Servicio de Auditoría.
* Sistema de Pedidos.
* Sistema de Ventas.

---

# 13. Integraciones

## Módulo Productos

Obtiene información descriptiva del producto.

## Módulo Pedidos

Actualiza reservas de inventario.

## Módulo Ventas

Consulta disponibilidad antes de confirmar compras.

## Módulo Logística

Consulta existencias para preparación de envíos.

---

# 14. Criterios de Aceptación

## CA-01

Debe permitir consultar inventario completo.

## CA-02

Debe permitir consultar inventario por SKU.

## CA-03

Debe actualizar stock correctamente.

## CA-04

Debe impedir valores negativos de stock.

## CA-05

Debe retornar HTTP 404 para productos inexistentes.

## CA-06

Debe actualizar automáticamente el estado AGOTADO cuando el stock llegue a cero.

## CA-07

Debe registrar fecha de actualización después de cada modificación.

## CA-08

Debe responder utilizando el contrato OpenAPI definido para el módulo.
