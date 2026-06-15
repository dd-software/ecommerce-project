# Especificación UI/UX

> **Propósito educativo:** Este documento define la interfaz de usuario. No es un diseño pixel-perfect, sino una guía de componentes y pantallas que los estudiantes deben implementar con Bootstrap 5.3.

---

## 1. Design System

### 1.1 Paleta de Colores

| Variable | Color | Hex | Uso |
|----------|-------|-----|-----|
| Primary | Azul institucional | `#0d6efd` | Botones principales, links, header |
| Secondary | Gris oscuro | `#6c757d` | Texto secundario, badges |
| Success | Verde | `#198754` | Stock disponible, pago exitoso |
| Danger | Rojo | `#dc3545` | Errores, stock agotado, alertas |
| Warning | Amarillo | `#ffc107` | Alertas de stock bajo, advertencias |
| Info | Celeste | `#0dcaf0` | Información, badges de "nuevo" |
| Dark | Negro | `#212529` | Texto principal, footer |
| Light | Gris claro | `#f8f9fa` | Fondo de secciones |

### 1.2 Tipografía

- **Principal:** System font stack (Bootstrap default):
  `system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", sans-serif`
- **Tamaños:** Bootstrap estándar (h1-h6, fs-1 a fs-6)

### 1.3 Espaciado

- Usar sistema de espaciado Bootstrap (p-*, m-*, gap-*)
- Márgenes internos de cards: `p-3` a `p-4`

---

## 2. Componentes UI

### 2.1 Navbar

```
┌──────────────────────────────────────────────────────────────┐
│ 🛒 Mi Tienda    [Catálogo]  [Categorías ▼]  [🔍]  [🛒 (3)] │
│                                            [👤 Juan ▼]      │
└──────────────────────────────────────────────────────────────┘
```

**Comportamiento:**
- Logo/nombre del sitio → link a home
- Menú desplegable de categorías
- Barra de búsqueda (submit → catálogo filtrado)
- Badge del carrito con contador de items
- Dropdown de usuario: "Mis pedidos", "Mi perfil", "Cerrar sesión"
- Responsive: colapsar a hamburguesa en mobile

### 2.2 Hero / Banner Principal

```
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│   🎉  ¡Bienvenido a Mi Tienda!                               │
│   Los mejores productos al mejor precio                      │
│                                                              │
│   [Ver Catálogo 🛍️]  [Ofertas 🔥]                          │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 2.3 Card de Producto

```
┌───────────────────┐
│  ┌─────────────┐  │
│  │    Imagen    │  │
│  │             │  │
│  └─────────────┘  │
│                    │
│  Categoría         │
│  Nombre Producto   │
│  ★★★★☆ (12)       │
│                    │
│  $7.999           │
│  $6.999           │
│  [🔥 25% OFF]      │
│                    │
│  [▬]  1  [▬]       │
│  [🛒 Agregar]      │
│                    │
│  ✅ Stock disponible│
│  ⚠️ Últimas 3 uds  │
└───────────────────┘
```

**Grid:** 3 columnas (desktop), 2 (tablet), 1 (mobile).

### 2.4 Detalle de Producto

```
┌──────────────────────────────────────────────────────────────┐
│  Inicio > Electrónica > Laptops                              │
│                                                              │
│  ┌────────────┐  ┌────────────┐  ┌────────────┐             │
│  │  Imagen    │  │  Imagen    │  │  Imagen    │  ⋯          │
│  │  principal │  │  thumb 1   │  │  thumb 2   │             │
│  └────────────┘  └────────────┘  └────────────┘             │
│                                                              │
│  Laptop Gamer RTX 4060                                       │
│  SKU: LPT-001                                                │
│                                                              │
│  $799.990            $699.990   🔥 Ahorras $100.000          │
│  ───────             ───────                                 │
│  (precio normal)     (precio oferta)                         │
│                                                              │
│  🏷️ Categoría: Electrónica                                   │
│  ✅ Stock disponible (15 uds)                                 │
│                                                              │
│  [▬]  1  [▬]   [🛒 Agregar al carrito]                      │
│                                                              │
│  Descripción:                                                 │
│  Laptop con procesador i7 13th gen, 16GB RAM...              │
│                                                              │
│  ── Productos Relacionados ──                                 │
│  [Card]  [Card]  [Card]  [Card]                              │
└──────────────────────────────────────────────────────────────┘
```

### 2.5 Carrito

```
┌──────────────────────────────────────────────────────────────┐
│  🛒  Mi Carrito  (3 productos)                               │
│                                                              │
│  ┌──────────────────────────────────────┐                    │
│  │ 📷  Laptop Gamer        $699.990     │                    │
│  │     [−]  2  [+]   =  $1.399.980      │                    │
│  │     🗑️ Eliminar         ✅ Stock OK   │                    │
│  ├──────────────────────────────────────┤                    │
│  │ 📷  Mouse RGB            $29.990      │                    │
│  │     [−]  1  [+]   =  $29.990         │                    │
│  │     🗑️ Eliminar         ✅ Stock OK   │                    │
│  └──────────────────────────────────────┘                    │
│                                                              │
│  Resumen:                                                     │
│  Subtotal:            $1.429.970                              │
│  IVA (19%):           $271.694                               │
│  Envío:               $4.990                                 │
│  ─────────────────────────────────────                       │
│  TOTAL:               $1.706.654                              │
│                                                              │
│  [← Seguir comprando]        [📍 Ir a pagar →]              │
└──────────────────────────────────────────────────────────────┘
```

### 2.6 Checkout

```
┌──────────────────────────────────────────────────────────────┐
│  📋  Resumen de Compra                                       │
│                                                              │
│  Dirección de envío:                                         │
│  ┌──────────────────────────────────────────────┐            │
│  │ ○ Casa: Av. Alemania 123, Temuco             │            │
│  │ ○ Oficina: Av. España 456, Temuco            │            │
│  │ [+ Agregar nueva dirección]                  │            │
│  └──────────────────────────────────────────────┘            │
│                                                              │
│  Productos:                                                   │
│  Laptop Gamer x1     $699.990                                │
│  Mouse RGB x2         $59.980                                │
│                                                              │
│  Total: $956.978                                              │
│                                                              │
│  💳 Método de pago: PayPal                                   │
│                                                              │
│  [✏️ Volver al carrito]    [✅ Confirmar compra]             │
└──────────────────────────────────────────────────────────────┘
```

### 2.7 Pago PayPal

```
┌──────────────────────────────────────────────────────────────┐
│                                                              │
│     🔄  Redirigiendo a PayPal...                             │
│                                                              │
│     Haz clic en el botón para ir a la pasarela segura:       │
│                                                              │
│     [💳 Pagar con PayPal $956.978]                          │
│                                                              │
│     ⚠️ Estás siendo redirigido a un sitio seguro (sandbox)  │
│                                                              │
└──────────────────────────────────────────────────────────────┘
```

### 2.8 Panel Admin - Dashboard

```
┌──────────────────────────────────────────────────────────────┐
│  ⚙️ Panel de Administración    [👤 Admin ▼]                 │
├──────────────────────────────────────────────────────────────┤
│  ┌──────────┐ ┌──────────┐ ┌──────────┐ ┌──────────┐       │
│  │ 150      │ │ 45       │ │ 230      │ │ $15M     │       │
│  │ 📦 Prods │ │ 📋 Ped.  │ │ 👥 Usrs  │ │ 💰 Ing.  │       │
│  └──────────┘ └──────────┘ └──────────┘ └──────────┘       │
│                                                              │
│  ⚠️ Alertas de Stock (3)                                     │
│  Mouse Inalámbrico  ───  stock: 2  (umbral: 10)  [Ajustar]│
│  Teclado Mecánico  ───  stock: 5  (umbral: 10)  [Ajustar]│
│                                                              │
│  📋 Pedidos Recientes                                        │
│  ORD-2024-00045 │ Juan Pérez │ $956.978 │ ⏳ Pendiente     │
│  ORD-2024-00044 │ María Soto│ $29.990  │ 📦 Enviado       │
│                                                              │
│  Menú:                                                        │
│  [📊 Dashboard] [👥 Usuarios] [📋 Pedidos]                  │
│  [📦 Productos] [🏷️ Categorías] [📦 Inventario]            │
│  [📝 Auditoría] [⚙️ Configuración]                          │
└──────────────────────────────────────────────────────────────┘
```

### 2.9 Formulario Admin - Producto

```
┌──────────────────────────────────────────────────────────────┐
│  ✏️ Editar Producto: Laptop Gamer                            │
│                                                              │
│  SKU:          [LPT-001            ]                         │
│  Nombre:       [Laptop Gamer RTX4060       ]                 │
│  Slug:         [laptop-gamer-rtx4060       ]                 │
│  Descripción:  [_______________________________]              │
│                [_______________________________]              │
│  Precio:       [799990           ]                           │
│  Descuento:    [699990           ] (opcional)                │
│  Categoría:    [Electrónica ▼  ]                             │
│  Activo:       [✅]  Destacado: [  ]  Nuevo: [✅]          │
│                                                              │
│  Imágenes:                                                    │
│  [📷 img1.jpg] [📷 img2.jpg] [+ Agregar]                   │
│                                                              │
│  [🗑️ Eliminar]  [💾 Guardar cambios]                       │
└──────────────────────────────────────────────────────────────┘
```

---

## 3. Pantallas (Vistas)

| # | Ruta | Vista | Módulo |
|---|------|-------|--------|
| 1 | `/` | Home: hero + categorías + productos destacados | A |
| 2 | `/catalogo` | Listado de productos con filtros | A |
| 3 | `/producto/{slug}` | Detalle de producto | A |
| 4 | `/carrito` | Carrito de compras | B |
| 5 | `/auth/login` | Formulario de inicio de sesión | C |
| 6 | `/auth/registro` | Formulario de registro | C |
| 7 | `/checkout` | Resumen de compra + dirección | D |
| 8 | `/checkout/pago` | Redirección a PayPal | E |
| 9 | `/checkout/exito` | Confirmación de compra exitosa | D+E |
| 10 | `/checkout/cancelado` | Pago cancelado/rechazado | D+E |
| 11 | `/admin` | Dashboard admin | G |
| 12 | `/admin/usuarios` | Gestión de usuarios | G |
| 13 | `/admin/pedidos` | Gestión de pedidos | G |
| 14 | `/admin/productos` | CRUD de productos | G |
| 15 | `/admin/inventario` | Gestión de stock | G+F |
| 16 | `/admin/auditoria` | Log de eventos | G |
| 17 | `/admin/configuracion` | Configuración del sistema | G |

---

## 4. Responsive Design

| Breakpoint | Ancho | Columnas Catálogo | Navbar |
|-----------|-------|-------------------|--------|
| XS | <576px | 1 columna | Hamburger |
| SM | ≥576px | 2 columnas | Hamburger |
| MD | ≥768px | 2 columnas | Expandido |
| LG | ≥992px | 3 columnas | Expandido |
| XL | ≥1200px | 3-4 columnas | Expandido |

---

## 5. Estados de Carga y Vacío

### Loading State
```html
<div class="text-center py-5">
  <div class="spinner-border text-primary" role="status">
    <span class="visually-hidden">Cargando...</span>
  </div>
  <p class="mt-2">Cargando productos...</p>
</div>
```

### Empty State
```html
<div class="text-center py-5">
  <h2 class="text-muted">🛒 Tu carrito está vacío</h2>
  <p>Agrega productos desde el catálogo</p>
  <a href="/catalogo" class="btn btn-primary">Ver Catálogo</a>
</div>
```

### Error State
```html
<div class="alert alert-danger">
  <strong>⚠️ Error:</strong> No se pudieron cargar los productos.
  <button onclick="location.reload()">Reintentar</button>
</div>
```
