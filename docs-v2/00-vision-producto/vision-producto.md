# Visión del Producto

## 🎯 Propósito del Proyecto

Este documento define la visión del producto **Plataforma Ecommerce UCT**. Sirve como guía maestra para que todos los equipos de desarrollo comprendan **qué** se construye y **por qué**. Cualquier decisión técnica debe alinearse con la visión aquí descrita.
Este proyecto es con fines pedagógicos, por lo tanto es un ejercicio didáctico y el eje central es que los estudiantes aprendan a trabajar de forma 
colaborativa usando giuthub, notion, jira, además permitirá obtener como resultado una plataforma ecommerce con gestión de inventaio y pasarela de pagos paypal. Usando HTML5, CSS3, javascript, Jquery, JSON, PHP y MYSQL.

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

| Para el cliente | Para el administrador |
|----------------|----------------------|
| Navegar catálogo con fotos y precios | Dashboard con métricas |
| Agregar productos al carrito | CRUD de productos y categorías |
| Comprar con PayPal | Gestionar stock |
| Ver historial de pedidos | Ver pedidos y cambiar estados |
| Recibir confirmaciones | Configurar tienda |
| Registrarse e iniciar sesión | Ver reportes de ventas |

---

## 3. Usuarios y Roles

### 👤 Visitante (No registrado)
- Navega el catálogo de productos
- Busca productos por nombre/categoría
- Agrega productos a carrito local (se pierde al cerrar sesión)

### 👤 Cliente (Registrado)
- Todo lo del visitante +
- Carrito persistente en servidor
- Realiza compras completas con PayPal
- Ve historial de pedidos
- Gestiona su perfil y direcciones

### 👤 Administrador
- Acceso total al sistema
- Gestión de usuarios y roles
- Configuración global (IVA, envío, moneda)
- Auditoría y logs
- Modo mantenimiento

---

## 4. Objetivos Específicos

| ID | Objetivo | Módulo | Prioridad |
|----|----------|--------|-----------|
| OBJ-01 | Explorar catálogo con filtros y búsqueda | A - Catálogo | 🔴 Crítica |
| OBJ-02 | Agregar/quitar productos del carrito | B - Carrito | 🔴 Crítica |
| OBJ-03 | Registrarse e iniciar sesión (sólo clientes) | C - Auth | 🔴 Crítica |
| OBJ-04 | Realizar checkout completo | D - Checkout | 🔴 Crítica |
| OBJ-05 | Pagar con PayPal (sandbox) | E - PayPal | 🔴 Crítica |
| OBJ-06 | Gestionar inventario con reservas | F - Inventario | 🔴 Crítica |
| OBJ-07 | Administrar toda la plataforma | G - Admin | 🟡 Alta |
| OBJ-08 | Orquestar flujo completo de compra | H - Integración | 🟡 Alta |
| OBJ-10 | Reportes | G - Admin | 🟢 Media |

---

## 5. KPIs de Éxito (para estudiantes)

| KPI | Meta | Cómo medirlo |
|-----|------|-------------|
| Cobertura de especificaciones | 100% de endpoints implementados | Revisión contra contratos API |
| Flujo de compra completo | Registrar pedido → PayPal → confirmar → descontar stock | Prueba manual E2E |
| Sin bugs críticos | 0 bugs de seguridad o corrupción de datos | Checklist de code review |
| Código legible | Nombres claros, comentarios, PSR-4 | Peer review entre equipos |
| Tests funcionales | Al menos 1 test por endpoint | Carpeta `tests/` con PHPUnit |

---

## 6. Alcance — Lo que SÍ incluye

- Catálogo de productos con nombre del preoducto, descripción breve, imágenes, categorías y precios
- Carrito de compras persistente (servidor) y temporal (localStorage)
- Autenticación con roles (admin, cliente)
- Checkout con selección de dirección y resumen
- Pago mediante PayPal (sandbox — sin dinero real)
- Gestión de inventario con reservas (10 min de expiración)
- Panel administrativo con dashboard, usuarios, pedidos, auditoría
- Trazabilidad completa (auditoría de todas las operaciones)
- Seguridad: CSRF token, sesiones seguras

## 7. Alcance — Lo que NO incluye (futuras versiones)

- ⏳ OAuth social (Google, Facebook)
- ⏳ Autenticación de dos factores (2FA)
- ⏳ Cupones de descuento
- ⏳ Notificaciones push
- ⏳ App móvil nativa
- ⏳ Multi-idioma
- ⏳ Dropshipping
- ⏳ Pasarelas adicionales (Webpay, MercadoPago, Transbank)
- ⏳ Facturación electrónica (SII)
- ⏳ Bodega física / múltiples ubicaciones
