# Casos de Prueba

---

## CP-001 Compra exitosa


### **Precondiciones:**

* El usuario puede ser **anónimo o autenticado**
* Existe al menos un producto con **stock disponible > 0**
* El carrito funciona con persistencia en **localStorage/cache del cliente**
* El sistema de pagos **Transbank (modo prueba)** está operativo
* El sistema de correo está configurado correctamente

### **Pasos:**

1. El usuario ingresa a la página principal
2. Navega por el catálogo de productos
3. Selecciona un producto con stock disponible
4. Hace clic en Agregar al carrito
5. El sistema valida stock y agrega el producto al carrito
6. El usuario accede al icono del carrito
7. Visualiza los productos agregados
8. Hace clic en Checkout
9. El sistema muestra modal con opciones:

   * Iniciar sesión
   * Registrarse
   * Continuar como invitado
10. El usuario selecciona una opción 
11. El usuario confirma la compra
12. El sistema redirige a Transbank
13. El usuario completa el pago
14. El sistema recibe confirmación exitosa
15. El sistema:

* Registra la orden como pagado
* Descuenta el stock del producto
* Guarda la compra 

16. El sistema envía correo de confirmación
17. El usuario vuelve a la página con mensaje de éxito

## CP-002 Login exitoso

### **Precondiciones:**

* El usuario se encuentra **registrado en el sistema**
* El usuario está en estado **habilitado**
* La contraseña está almacenada con **hash seguro**
* El sistema de autenticación está operativo

### **Pasos:**

1. El usuario accede a la opción Iniciar sesión
2. El sistema muestra el formulario de login
3. El usuario ingresa:

   * Correo válido 
   * Contraseña
4. Hace clic en Iniciar sesión
5. El sistema valida:

   * Formato del correo
   * Credenciales 
6. El sistema verifica que el usuario esté habilitado
7. El sistema crea una **sesión temporal no persistente
8. El sistema identifica el rol del usuario
9. El sistema redirige según rol:

   * Admin / Supervisor / Empleado → **Dashboard**
   * Cliente → **Página principal**

## CP-003 Compra con producto sin stock.

**Precondiciones:**

* Existe un producto en el catálogo con stock igual a 0
* El usuario se encuentra en el catálogo 

**Pasos:**

1. El usuario navega al catálogo de productos
2. Identifica un producto sin stock disponible
3. Intenta hacer clic en “Agregar al carrito”
4. El sistema valida el stock del producto
5. El sistema bloquea la acción de agregar al carrito

---

## CP-004 Login con usuario deshabilitado.

**Precondiciones:**

* Existe un usuario registrado en el sistema
* El usuario tiene estado "deshabilitado"
* El sistema de autenticación está operativo

**Pasos:**

1. El usuario accede a la página de inicio de sesión
2. Ingresa su correo electrónico
3. Ingresa su contraseña
4. Hace clic en “Iniciar sesión”
5. El sistema valida las credenciales
6. El sistema verifica el estado del usuario
7. Acceso denegado, mensaje visible "Tu cuenta ha sido deshabilitada, contacta al administrador".

---

## CP-005 Cambio de estado de pedido.

**Precondiciones:**

* Existe un pedido registrado en el sistema
* El pedido tiene estado inicial "pendiente"
* Existe un usuario con rol admin o supervisor autenticado

**Pasos:**

1. El usuario accede al dashboard
2. Navega a la gestión de pedidos
3. Selecciona un pedido existente
4. Cambia el estado del pedido 
5. Guarda los cambios
6. Repite el proceso para los siguientes estados 
7. El sistema registra cada cambio realizado
