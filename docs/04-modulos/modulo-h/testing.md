# Testing

## Objetivo

Definir la estrategia de pruebas para validar que la plataforma Ecommerce cumple los requisitos funcionales y no funcionales relacionados con la gestión de productos, inventario, pedidos y pagos.

---

## Pruebas Unitarias

### Objetivo

Verificar el funcionamiento correcto de cada componente de forma aislada.

### Cobertura

#### Gestión de Productos

* Creación de productos.
* Actualización de productos.
* Eliminación de productos.
* Consulta de productos.

#### Gestión de Inventario

* Registro de entradas de stock.
* Registro de salidas de stock.
* Validación de disponibilidad.
* Actualización automática de existencias.

#### Carrito de Compras

* Agregar productos.
* Modificar cantidades.
* Eliminar productos.
* Cálculo de subtotales y totales.

#### Gestión de Usuarios

* Registro de usuarios.
* Inicio de sesión.
* Recuperación de contraseña.
* Validación de permisos.

#### Pasarela de Pago

* Creación de transacciones.
* Confirmación de pagos.
* Cancelación de pagos.
* Actualización de estados.

### Criterio de Éxito

* Todas las pruebas unitarias deben ejecutarse sin errores.
* Cobertura mínima de código: 80%.

---

## Pruebas de Integración

### Objetivo

Validar la interacción entre los diferentes módulos y servicios externos.

### Escenarios

#### Inventario y Carrito

* Verificar disponibilidad de stock al agregar productos.
* Reservar stock durante el proceso de compra.

#### Carrito y Pedidos

* Generar correctamente una orden desde el carrito.
* Calcular montos y cantidades.

#### Pedidos y Pagos

* Enviar solicitudes de pago.
* Registrar respuestas de la pasarela.

#### Pagos e Inventario

* Descontar stock tras una compra exitosa.
* Liberar stock ante pagos fallidos o cancelados.

#### API y Base de Datos

* Validar operaciones CRUD.
* Verificar integridad y persistencia de datos.

### Criterio de Éxito

* Comunicación exitosa entre todos los módulos.
* Sin errores críticos de integración.

---

## Pruebas de Aceptación

### Objetivo

Confirmar que la solución satisface los requisitos del negocio.

### Escenario 1: Compra Exitosa

**Dado** un usuario autenticado

**Cuando** selecciona productos y realiza el pago

**Entonces**

* Se genera la orden.
* El pago es aprobado.
* El inventario se actualiza.
* Se notifica al usuario.

---

### Escenario 2: Producto Sin Stock

**Dado** un producto sin disponibilidad

**Cuando** el usuario intenta comprarlo

**Entonces**

* La compra no puede completarse.
* Se informa la falta de stock.

---

### Escenario 3: Pago Rechazado

**Dado** una transacción inválida

**Cuando** la pasarela rechaza el pago

**Entonces**

* El pedido queda cancelado o pendiente.
* El stock reservado se libera.
* Se informa al usuario.

---

### Escenario 4: Administración de Inventario

**Dado** un administrador autenticado

**Cuando** actualiza el stock de un producto

**Entonces**

* Los cambios se almacenan correctamente.
* Se registra la operación en auditoría.

### Criterio de Éxito

* Todos los criterios de aceptación definidos en los requisitos son cumplidos.
* No existen defectos críticos o bloqueantes.

---

## Evidencias de Prueba

Las ejecuciones deberán registrar:

* Casos de prueba ejecutados.
* Resultados obtenidos.
* Evidencias visuales (capturas).
* Logs de sistema.
* Reportes automáticos de ejecución.
* Registro de incidencias detectadas y resueltas.

---

## Resultado Esperado

La plataforma será considerada apta para liberación cuando:

* Las pruebas unitarias sean satisfactorias.
* Las integraciones funcionen correctamente.
* Los flujos de compra y pago operen sin errores.
* El inventario mantenga consistencia en todas las operaciones.
* Los criterios de aceptación del negocio sean aprobados.