# Testing

## 1. Objetivo

Definir la estrategia de pruebas para garantizar la calidad, estabilidad, seguridad y correcto funcionamiento de la plataforma Ecommerce, validando cada componente de manera individual y su comportamiento integrado dentro del sistema.

---

## 2. Alcance

Las pruebas cubren los siguientes módulos:

* Gestión de Usuarios
* Catálogo de Productos
* Gestión de Inventario
* Carrito de Compras
* Gestión de Pedidos
* Pasarela de Pagos
* Administración del Sistema
* API REST
* Base de Datos

---

## 3. Estrategia de Pruebas

La validación del sistema se realizará mediante:

### Pruebas Unitarias

Verifican el correcto funcionamiento de componentes individuales.

### Pruebas de Integración

Validan la comunicación entre módulos internos y servicios externos.

### Pruebas de Aceptación

Comprueban que el sistema cumple los requerimientos funcionales definidos por el cliente.

---

# 4. Pruebas Unitarias

## Objetivo

Validar cada función, servicio y componente de manera aislada.

## Casos de Prueba

### Usuarios

| ID         | Caso                       |
| ---------- | -------------------------- |
| UT-USR-001 | Registro de usuario        |
| UT-USR-002 | Inicio de sesión           |
| UT-USR-003 | Recuperación de contraseña |
| UT-USR-004 | Validación de roles        |

### Productos

| ID          | Caso                |
| ----------- | ------------------- |
| UT-PROD-001 | Crear producto      |
| UT-PROD-002 | Actualizar producto |
| UT-PROD-003 | Eliminar producto   |
| UT-PROD-004 | Consultar producto  |

### Inventario

| ID         | Caso                   |
| ---------- | ---------------------- |
| UT-INV-001 | Incrementar stock      |
| UT-INV-002 | Reducir stock          |
| UT-INV-003 | Validar disponibilidad |
| UT-INV-004 | Bloquear stock         |

### Carrito

| ID          | Caso                |
| ----------- | ------------------- |
| UT-CART-001 | Agregar producto    |
| UT-CART-002 | Eliminar producto   |
| UT-CART-003 | Actualizar cantidad |
| UT-CART-004 | Calcular total      |

### Pagos

| ID         | Caso                |
| ---------- | ------------------- |
| UT-PAY-001 | Crear transacción   |
| UT-PAY-002 | Confirmar pago      |
| UT-PAY-003 | Cancelar pago       |
| UT-PAY-004 | Registrar respuesta |

---

# 5. Pruebas de Integración

## Objetivo

Verificar la correcta interacción entre componentes y servicios.

## Escenarios

### Inventario ↔ Carrito

**Caso:** Agregar producto al carrito.

**Resultado esperado:**

* Se consulta el stock.
* Se valida disponibilidad.
* El producto se agrega correctamente.

---

### Carrito ↔ Pedidos

**Caso:** Confirmar compra.

**Resultado esperado:**

* Se genera una orden.
* Se almacenan los productos seleccionados.
* Se calcula el monto total.

---

### Pedidos ↔ Pagos

**Caso:** Procesar pago.

**Resultado esperado:**

* Se envía la transacción.
* Se recibe respuesta del proveedor.
* Se actualiza el estado del pedido.

---

### Pagos ↔ Inventario

**Caso:** Pago aprobado.

**Resultado esperado:**

* Se descuenta el stock.
* Se confirma la compra.
* Se registra la transacción.

---

### API ↔ Base de Datos

**Caso:** Consultar productos.

**Resultado esperado:**

* La API retorna información válida.
* Los datos coinciden con la base de datos.

---

# 6. Pruebas de Aceptación

## Objetivo

Validar que la plataforma satisface los requerimientos del negocio.

---

## Escenario A1: Compra Exitosa

### Dado

Un usuario autenticado.

### Cuando

Selecciona productos y realiza el pago.

### Entonces

* Se genera la orden.
* El pago es aprobado.
* El inventario se actualiza.
* Se envía confirmación al usuario.

---

## Escenario A2: Producto Sin Stock

### Dado

Un producto sin existencias.

### Cuando

El usuario intenta comprarlo.

### Entonces

* La compra es rechazada.
* Se informa disponibilidad insuficiente.

---

## Escenario A3: Pago Rechazado

### Dado

Una transacción inválida.

### Cuando

Se procesa el pago.

### Entonces

* El pedido queda pendiente o cancelado.
* El stock reservado se libera.
* Se notifica al usuario.

---

## Escenario A4: Administración de Inventario

### Dado

Un administrador autenticado.

### Cuando

Actualiza el stock de un producto.

### Entonces

* El cambio se guarda correctamente.
* Se registra la auditoría correspondiente.

---

# 7. Pruebas de Seguridad

## Autenticación

Validar:

* Inicio de sesión.
* Gestión de sesiones.
* Expiración de tokens.
* Recuperación de contraseña.

## Autorización

Validar:

* Acceso por roles.
* Restricción de funciones administrativas.
* Protección de recursos privados.

## Protección de Datos

Validar:

* Cifrado de contraseñas.
* Uso de HTTPS.
* Protección contra SQL Injection.
* Protección contra XSS.
* Protección contra CSRF.

---

# 8. Pruebas de Rendimiento

## Objetivo

Evaluar el comportamiento del sistema bajo carga.

## Escenarios

### Catálogo

* Consulta masiva de productos.

### Inventario

* Actualizaciones concurrentes de stock.

### Carrito

* Múltiples usuarios agregando productos.

### Pagos

* Procesamiento simultáneo de transacciones.

## Métricas

* Tiempo de respuesta menor a 2 segundos.
* Disponibilidad superior al 99%.
* Capacidad mínima de 500 usuarios concurrentes.

---

# 9. Criterios de Aprobación

La versión será aceptada cuando:

* Todas las pruebas críticas sean exitosas.
* No existan errores bloqueantes.
* Las integraciones funcionen correctamente.
* Los procesos de pago operen sin fallos.
* El inventario mantenga consistencia.
* Se cumplan los criterios de aceptación definidos.

---

# 10. Evidencias

Las pruebas deberán generar:

* Casos ejecutados.
* Resultados obtenidos.
* Capturas de pantalla.
* Logs de ejecución.
* Reportes automáticos.
* Registro de incidencias y correcciones.