# Testing

## Objetivo

Definir la estrategia de validación y aseguramiento de calidad para la plataforma Ecommerce, garantizando el correcto funcionamiento de los módulos de catálogo, inventario, carrito de compras, pagos, usuarios y administración.

---

## Alcance

Las pruebas abarcan:

- Funcionalidades individuales de cada módulo.
- Integración entre servicios y APIs.
- Procesos críticos de negocio.
- Seguridad básica de transacciones.
- Experiencia de usuario en flujos principales.
- Validación de requisitos funcionales y no funcionales.

---

# 1. Pruebas Unitarias

## Objetivo

Validar que cada componente funcione correctamente de manera aislada.

## Cobertura

### Gestión de Productos

- Crear producto.
- Actualizar producto.
- Eliminar producto.
- Validar campos obligatorios.
- Validar precios y stock.

### Gestión de Inventario

- Incrementar stock.
- Reduccir stock.
- Bloquear stock durante compra.
- Liberar stock en cancelaciones.

### Carrito de Compras

- Agregar productos.
- Eliminar productos.
- Actualizar cantidades.
- Calcular subtotal.
- Calcular total.

### Gestión de Usuarios

- Registro.
- Inicio de sesión.
- Recuperación de contraseña.
- Validación de roles.

### Pagos

- Creación de orden.
- Confirmación de pago.
- Cancelación de pago.
- Actualización de estado.

## Herramientas

- PHPUnit
- Jest (Frontend)
- Mock Services

---

# 2. Pruebas de Integración

## Objetivo

Verificar la interacción entre módulos internos y servicios externos.

## Escenarios

### Inventario + Carrito

**Caso:** Agregar producto al carrito.

**Resultado esperado:**

- Se consulta stock disponible.
- Se reserva inventario.
- Se actualiza el carrito correctamente.

---

### Carrito + Pagos

**Caso:** Confirmar compra.

**Resultado esperado:**

- Se genera orden.
- Se calcula total.
- Se envía solicitud al proveedor de pago.

---

### Pagos + Inventario

**Caso:** Pago exitoso.

**Resultado esperado:**

- Se descuenta stock.
- Se actualiza estado de orden.
- Se registra transacción.

---

### Usuarios + Órdenes

**Caso:** Consulta historial.

**Resultado esperado:**

- El usuario visualiza únicamente sus pedidos.
- Los datos coinciden con la base de datos.

---

### API REST

Validar:

- Métodos HTTP.
- Códigos de respuesta.
- Formato JSON.
- Manejo de errores.
- Autenticación JWT.

---

# 3. Pruebas de Aceptación

## Objetivo

Validar que el sistema cumple los requerimientos del negocio y las expectativas del cliente.

---

## Escenario 1: Compra Completa

### Dado

Un cliente autenticado.

### Cuando

Selecciona productos y realiza el pago.

### Entonces

- La compra se registra.
- El pago es aprobado.
- El inventario se actualiza.
- Se genera una confirmación de compra.

---

## Escenario 2: Pago Rechazado

### Dado

Un cliente intenta pagar.

### Cuando

El proveedor rechaza la transacción.

### Entonces

- La orden queda pendiente o cancelada.
- El stock reservado se libera.
- Se muestra mensaje informativo.

---

## Escenario 3: Administración de Inventario

### Dado

Un administrador autenticado.

### Cuando

Actualiza el stock de un producto.

### Entonces

- El inventario refleja el nuevo valor.
- Se registra la auditoría correspondiente.

---

## Escenario 4: Registro de Usuario

### Dado

Un visitante.

### Cuando

Completa el formulario de registro.

### Entonces

- Se crea la cuenta.
- Se envía correo de confirmación.
- Puede iniciar sesión.

---

# 4. Pruebas de Seguridad

## Autenticación

Validar:

- Inicio de sesión seguro.
- Expiración de tokens.
- Recuperación de contraseña.

## Autorización

Validar:

- Roles Administrador.
- Roles Cliente.
- Restricción de accesos.

## Protección de Datos

Validar:

- Cifrado de contraseñas.
- Uso de HTTPS.
- Protección contra SQL Injection.
- Protección contra XSS.
- Protección contra CSRF.

---

# 5. Pruebas de Rendimiento

## Objetivos

Verificar estabilidad bajo carga.

### Métricas

- Tiempo de respuesta < 2 segundos.
- Disponibilidad > 99%.
- Soporte mínimo de 500 usuarios concurrentes.

### Escenarios

- Navegación del catálogo.
- Consultas de inventario.
- Creación de pedidos.
- Procesamiento de pagos.

---

# 6. Criterios de Éxito

La versión será aceptada cuando:

- 100% de pruebas críticas aprobadas.
- 95% de pruebas funcionales aprobadas.
- Sin errores bloqueantes.
- Integraciones funcionando correctamente.
- Procesos de pago completados exitosamente.
- Inventario sincronizado correctamente.

---

# 7. Evidencias

Las evidencias de prueba deberán incluir:

- Casos ejecutados.
- Resultados obtenidos.
- Capturas de pantalla.
- Logs de ejecución.
- Reportes automáticos de testing.
- Registro de incidencias y correcciones.