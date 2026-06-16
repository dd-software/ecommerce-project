# Visión del Producto — Ecommerce UCT

## 🎯 Propósito del Proyecto

Este documento define la visión del producto **Plataforma Ecommerce UCT**, un proyecto pedagógico de la asignatura Diseño y Desarrollo de Software + IA. Sirve como guía maestra para que los 5 equipos de desarrollo comprendan **qué** se construye y **por qué**.

**Objetivo pedagógico:** Que los estudiantes aprendan trabajo colaborativo usando GitHub, desarrollando una plataforma ecommerce funcional con gestión de inventario y pasarela de pagos PayPal.

**Stack:** HTML5, CSS3, Bootstrap 5.3, JavaScript, jQuery, JSON, PHP, MySQL.

---

## 1. Problema

Las pequeñas y medianas tiendas en Chile no cuentan con una plataforma de ventas online **simple**, **accesible** y **administrable** que les permita:

- Publicar su catálogo de productos
- Recibir pedidos con pago en línea
- Gestionar su inventario en tiempo real
- Dar trazabilidad a sus clientes sobre el estado de los pedidos

Las soluciones existentes son caras (Mercado Shops, Shopify), complejas (Magento) o no tienen soporte en español y pesos chilenos.

---

## 2. Solución Propuesta

Una plataforma **web ecommerce autogestionable** donde:

**Para el cliente:**
- Navegar catálogo con fotos y precios
- Agregar productos al carrito
- Comprar con PayPal (sandbox)
- Ver historial de pedidos
- Registrarse e iniciar sesión

**Para el administrador:**
- Dashboard con métricas (ventas, productos, pedidos)
- CRUD de productos y categorías
- Gestionar stock e inventario
- Ver pedidos y cambiar estados
- Ver reportes de ventas

---

## 3. Usuarios y Roles

Solo **2 roles**: Cliente y Administrador.

### 👤 Visitante (No registrado)
- Navega el catálogo de productos
- Busca productos por nombre/categoría
- Agrega productos a carrito local (se pierde al cerrar sesión)

### 👤 Cliente (Registrado)
- Todo lo del visitante
- Carrito persistente en servidor (sesión PHP)
- Realiza compras completas con PayPal
- Ve su historial de pedidos

### 👤 Administrador
- Acceso total al sistema
- CRUD de productos, categorías e inventario
- Gestión de pedidos (cambiar estados)
- Dashboard con reportes y métricas

---

## 4. Objetivos Específicos

- **OBJ-01** — Explorar catálogo con filtros y búsqueda — Módulo A (Catálogo) — 🔴 Crítica
- **OBJ-02** — Agregar/quitar productos del carrito — Módulo B (Carrito) — 🔴 Crítica
- **OBJ-03** — Registrarse e iniciar sesión (solo clientes) — Módulo C (Auth) — 🔴 Crítica
- **OBJ-04** — Realizar checkout completo — Módulo D (Checkout) — 🔴 Crítica
- **OBJ-05** — Pagar con PayPal (sandbox) — Módulo E (PayPal) — 🔴 Crítica
- **OBJ-06** — Gestionar inventario con reservas (10 min) — Módulo F (Inventario) — 🔴 Crítica
- **OBJ-07** — Administrar toda la plataforma — Módulo G (Admin) — 🟡 Alta
- **OBJ-08** — Orquestar flujo completo de compra — Módulo H (Integración) — 🟡 Alta

---

## 5. KPIs de Éxito (para estudiantes)

- **Cobertura de especificaciones:** 100% de funcionalidades implementadas (checklist interno)
- **Flujo de compra completo:** Registrar pedido → PayPal → confirmar → descontar stock (prueba E2E)
- **Sin bugs críticos:** 0 bugs de seguridad o corrupción de datos (code review)
- **Código legible:** Nombres claros, comentarios pedagógicos, estructura consistente (peer review)

---

## 6. Alcance — Lo que SÍ incluye

- Catálogo de productos con nombre, descripción, imágenes, categorías y precios
- Carrito de compras (servidor con sesión + localStorage para invitados)
- Autenticación con roles: admin y cliente
- Checkout con resumen de compra
- Pago mediante PayPal (sandbox — sin dinero real)
- Gestión de inventario con reservas temporales (10 min de expiración)
- Panel administrativo con dashboard, gestión de productos, pedidos
- Seguridad: CSRF tokens, sesiones PHP seguras (HttpOnly, SameSite)

## 7. Alcance — Lo que NO incluye (futuras versiones)

- ⏳ OAuth social (Google, Facebook)
- ⏳ Autenticación de dos factores (2FA)
- ⏳ Cupones de descuento
- ⏳ Notificaciones push
- ⏳ App móvil nativa
- ⏳ Multi-idioma
- ⏳ Pasarelas adicionales (Webpay, MercadoPago, Transbank)
- ⏳ Facturación electrónica (SII)
