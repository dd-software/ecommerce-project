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

