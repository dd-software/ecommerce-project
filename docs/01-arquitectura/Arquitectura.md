# Arquitectura del Software - Plataforma E-Commerce

Este documento detalla las especificaciones técnicas, decisiones de diseño, flujo de datos y arquitectura del sistema para el proyecto e-commerce.

>**Stack principal:** PHP · Bootstrap 5.3.8 · AJAX/JSON · MySQL  
> **Localización:** `es-CL` · `UTF-8` · Precios en pesos chilenos (CLP)

---

# Visión General

La plataforma es una aplicación web tipo **Single Page Application (SPA)** parcial, donde la estructura HTML base (navbar, footer, sidebar) permanece fija y únicamente el **área de contenido principal** se reemplaza dinámicamente mediante AJAX, sin recargar la página completa.

```
┌─────────────────────────────────────────────────────────┐
│                     NAVEGADOR                           │
│  ┌──────────────────────────────────────────────────┐  │
│  │  Navbar  (logo · búsqueda · carrito · sesión)    │  │
│  ├──────────────────────────────────────────────────┤  │
│  │                                                  │  │
│  │          #app-content  ← ZONA DINÁMICA           │  │
│  │          (reemplazada por AJAX)                  │  │
│  │                                                  │  │
│  ├──────────────────────────────────────────────────┤  │
│  │                    Footer                        │  │
│  └──────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────┘
```

## 1. Stack Tecnológico y Arquitectura del Sistema

### 1.1. Frontend
* **Framework CSS:** Bootstrap v5.3.8 (Look & Feel minimalista utilizando exclusivamente la paleta de colores nativa/básica de Bootstrap: `primary`, `secondary`, `light`, `dark`).
* **Comportamiento:** Single Page Application (SPA). El cambio de vistas se realiza de manera dinámica sin recargar el navegador completa, utilizando **AJAX** (vía `fetch` API o `XMLHttpRequest`).
* **Mecanismo SPA (Rutado Frontend):** Un contenedor principal (`#main-content`) intercepta las interacciones de navegación. El backend en PHP sirve fragmentos de HTML o JSON que el motor JS del frontend renderiza dinámicamente.

### 1.2. Backend
* **Lenguaje:** PHP (v8.x recomendado).
* **Codificación del Sistema:** Estricta en `UTF-8` tanto en el envío de cabeceras HTTP (`Content-Type: text/html; charset=utf-8`) como en la colación de la Base de Datos (`utf8mb4_unicode_ci`).
* **Compatibilidad de textos y caracteres:** Todo el sistema está configurado para que los acentos, la letra "ñ" y los caracteres especiales se guarden y se muestren correctamente. Esto evita que aparezcan símbolos extraños (como signos de interrogación o caracteres rotos) tanto en la base de datos como en la pantalla del usuario.

---

## 2. Internacionalización y Localización

* **Configuración Regional:** Configurado y estructurado para la región `es-CL` (Español - Chile).
* **Moneda y Precios:** Todos los precios se procesan y muestran en **Pesos Chilenos (CLP)**.
  * Formateo obligatorio en Frontend/Backend: `$X.XXX` (Uso de la clase `NumberFormatter` de PHP con el locale `es_CH`).
  * Las transacciones no manejan decimales (en cumplimiento con las reglas financieras de la moneda local).

---

## 3. Seguridad y Control de Acceso

### 3.1. Autenticación y Cifrado
* **Contraseñas:** El almacenamiento de credenciales en la base de datos se realiza aplicando hashing mediante el algoritmo contemporáneo nativo de PHP: `password_hash($password, PASSWORD_ARGON2ID)` o en su defecto `PASSWORD_DEFAULT` (Bcrypt).
* **Sesión:** Manejo de sesiones seguras mediante PHP Sessions o JWT (Json Web Tokens) con flag `HttpOnly` y `Secure`.

### 3.2. Roles y Autorización (Dashboards Separados)
El sistema implementa un Control de Acceso Basado en Roles (RBAC) con dos perfiles críticos que acceden a paneles de control totalmente diferenciados:

[ Login Único ]
|
v
[ Verificación de Rol ]
|
+---> (Rol: Administrador) ---> Redirección a /admin/dashboard
|
+---> (Rol: Cliente)       ---> Redirección a /account/dashboard

* **Dashboard Cliente:** Interfaz limpia para gestionar datos personales, lista de deseos e historial.
* **Dashboard Admin:** Panel administrativo para control de stock, gestión de órdenes y configuraciones globales.

---

## 4. Estrategia de Carrito y Persistencia

| Rol | Descripción |
|-----|-------------|
| **Invitado** | Sin sesión. Puede navegar y añadir al carrito (caché/localStorage). |
| **Cliente** | Registrado y autenticado. Carrito persistente en BD, lista de deseos, historial. |
| **Administrador** | Gestión completa: productos, pedidos, stock, usuarios, reportes. |

El sistema cuenta con un comportamiento híbrido y diferenciado para la persistencia del carrito de compras dependiendo del estado de autenticación del usuario:

| Tipo de Usuario | Mecanismo de Persistencia | Justificación Técnica |
| :--- | :--- | :--- |
| **Invitado (Guest)** | Memoria Caché del Cliente (LocalStorage / SessionStorage) | Evita la sobrecarga de escrituras en la base de datos por usuarios anónimos. |
| **Cliente Autenticado** | Base de Datos Relacional | **NO utiliza memoria caché.** Al iniciar sesión o añadir productos, los datos se sincronizan directamente en la DB para garantizar persistencia multi-dispositivo. |

* **Regla de Negocio (Sincronización):** Al momento del Login exitoso de un Invitado, el frontend debe enviar el contenido del carrito en caché vía AJAX al backend para guardarlo en la DB del Cliente y posteriormente limpiar la caché del navegador.

---

## 5. Arquitectura de Módulos y Flujos Clave

### 5.1. Módulo de Cuenta de Cliente
Exclusivo para usuarios autenticados con rol `Cliente`. Consiste en componentes AJAX que renderizan:
* **Historial de Compras:** Listado histórico de órdenes, estados de pago y despacho correspondientes al ID del usuario.
* **Lista de Deseos (Wishlist):** Persistida en base de datos. Permite al cliente almacenar productos para futuras compras.

### 5.2. Módulo de Checkout
La pasarela interna de recolección de datos sigue una estructura de URL estricta y limpia:
* **Ruta Crítica:** `/checkout/personal-details`
* **Flujo:** Procesado asíncronamente mediante AJAX para validar los datos del cliente (RUT, Dirección en Chile, Teléfono) antes de proceder a la pasarela de pago (Ej: Webpay Plus / Transbank).

### 5.3. Módulo de Gestión de Inventario (Stock)
Lógica crítica del backend para evitar la sobreventa (*overselling*):

* **Control Transaccional:** Cada proceso de pago exitoso ejecuta una consulta de actualización con bloqueo (`SELECT ... FOR UPDATE` en SQL) para decrementar el stock físico.
* **Alertas de Stock Configurable:** El Administrador puede definir dos campos numéricos por cada producto en la base de datos:
  * `stock_actual` (Cantidad real disponible).
  * `stock_minimo` (Umbral límite de seguridad).
* **Motor de Alertas:** Cuando un proceso de compra o actualización manual provoca que `stock_actual <= stock_minimo`, el sistema gatilla automáticamente una bandera de alerta perceptible en el Dashboard del Administrador y (opcionalmente) un envío de notificación por correo.

---

## 6. Lineamientos de Interfaz y UI (Look & Feel)

* **Estilo General:** Minimalista. Espaciados amplios (`padding` y `margin` balanceados), tipografía limpia (propia de Bootstrap por defecto como Segoe UI / Roboto).
* **Paleta de Colores Básica:** * Fondos primarios: Nativos claros (`bg-light`).
  * Textos y Botones principales: Combinación de `btn-dark` y `btn-outline-dark` para mantener el contraste minimalista.
  * Alertas de stock: `text-danger` o `bg-warning` para indicadores de atención.
  