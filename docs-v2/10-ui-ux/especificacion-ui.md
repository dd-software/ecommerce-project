# Especificación de UI/UX — Ecommerce UCT

## Principios de Diseño

- **Responsive** — Bootstrap 5.3 garantiza adaptación a móvil, tablet y desktop
- **Minimalista** — Interfaces limpias, con foco en la tarea principal
- **Consistente** — Mismo navbar, footer y estilos en todas las páginas
- **Feedback** — Mensajes claros de éxito/error en cada acción

---

## Estructura General de la Página

```
┌─────────────────────────────────────┐
│              Navbar                  │
│  [Logo]  [Catálogo] [Carrito] [User] │
├─────────────────────────────────────┤
│                                     │
│          Contenido Principal         │
│          (varía por página)          │
│                                     │
├─────────────────────────────────────┤
│              Footer                  │
│   © 2026 Ecommerce UCT - SDD        │
└─────────────────────────────────────┘
```

## Navbar — Por Rol

### Visitante (no autenticado)
```
[Logo]  [Catálogo ▼]  [Carrito (0)]  [Iniciar sesión]  [Registrarse]
```

### Cliente
```
[Logo]  [Catálogo ▼]  [Carrito (3)]  [Mis pedidos]  [Cerrar sesión]
```

### Admin
```
[Logo]  [Catálogo ▼]  [Carrito]  [Admin ▼]  [Cerrar sesión]
         Admin menu: [Dashboard] [Productos] [Pedidos] [Inventario]
```

---

## Pantallas del Sistema

### 1. Home / Catálogo (`index.php`)
```
┌─────────────────────────────────────────┐
│  Buscar productos...  [🔍]              │
│  Categorías: [Todas] [Electrónica] ...  │
├─────────────────────────────────────────┤
│ ┌────────┐ ┌────────┐ ┌────────┐       │
│ │  Foto  │ │  Foto  │ │  Foto  │       │
│ │ Nombre │ │ Nombre │ │ Nombre │       │
│ │ $9.990 │ │$15.990 │ │$29.990 │       │
│ │[Carrito]│ │[Carrito]│ │[Carrito]│       │
│ └────────┘ └────────┘ └────────┘       │
│ ┌────────┐ ┌────────┐ ┌────────┐       │
│ │  ...   │ │  ...   │ │  ...   │       │
│ └────────┘ └────────┘ └────────┘       │
│         [←] 1 2 3 ... 5 [→]           │
└─────────────────────────────────────────┘
```

### 2. Detalle de Producto (`producto.php?id=N`)
```
┌─────────────────────────────────────────┐
│  ← Volver al catálogo                   │
│                                         │
│  ┌──────────┐  Nombre del Producto      │
│  │  Foto    │  SKU: TEC-001            │
│  │  grande  │  Precio: $29.990          │
│  │          │  Precio oferta: $24.990   │
│  │          │                           │
│  │ [img1]   │  Descripción:             │
│  │ [img2]   │  Lorem ipsum dolor...     │
│  └──────────┘                           │
│            Cantidad: [-] 1 [+]          │
│            [Agregar al carrito]         │
└─────────────────────────────────────────┘
```

### 3. Carrito (`carrito.php`)
```
┌─────────────────────────────────────────┐
│  Mi Carrito (3 items)                   │
│                                         │
│  ┌─────────────────────────────────┐    │
│  │ Foto | Audífonos Bluetooth      │    │
│  │      | $24.990 x 2 = $49.980   │    │
│  │      | [-] 2 [+]  [🗑]         │    │
│  ├─────────────────────────────────┤    │
│  │ Foto | Cargador USB-C           │    │
│  │      | $15.990 x 1 = $15.990   │    │
│  │      | [-] 1 [+]  [🗑]         │    │
│  └─────────────────────────────────┘    │
│                                         │
│  Subtotal: $65.970                      │
│  Envío: $4.990                          │
│  Total: $70.960                         │
│                                         │
│  [Seguir comprando]  [Ir al checkout]   │
└─────────────────────────────────────────┘
```

### 4. Checkout (`checkout.php`)
```
┌─────────────────────────────────────────┐
│  Checkout                               │
│                                         │
│  ┌────────── Resumen ───────────┐       │
│  │ Producto A        2 x $XX    │       │
│  │ Producto B        1 x $XX    │       │
│  │ Subtotal           $65.970   │       │
│  │ IVA (19%)          $12.534   │       │
│  │ Envío              $4.990    │       │
│  │ Total              $83.494   │       │
│  └──────────────────────────────┘       │
│                                         │
│  Dirección de envío:                    │
│  [___________________________]          │
│                                         │
│  [Confirmar pedido y pagar]             │
│                                         │
│  (Al hacer clic, se abre PayPal)        │
└─────────────────────────────────────────┘
```

### 5. Inicio de Sesión / Registro
```
┌─────────────────────────────────────────┐
│  Iniciar Sesión                         │
│                                         │
│  Email:    [________________]           │
│  Contraseña: [________________]          │
│                                         │
│  [Iniciar sesión]                       │
│                                         │
│  ¿No tienes cuenta? [Registrarse]       │
│                                         │
│  ─────────────────────────────────      │
│                                         │
│  Registrarse                            │
│  Nombre:    [________________]          │
│  Apellido:  [________________]          │
│  Email:     [________________]          │
│  Password:  [________________]          │
│                                         │
│  [Crear cuenta]                         │
└─────────────────────────────────────────┘
```

### 6. Panel Admin — Dashboard (`admin/index.php`)
```
┌─────────────────────────────────────────┐
│  Admin Dashboard                        │
│  ┌──────┐ ┌──────┐ ┌──────┐ ┌──────┐  │
│  │$1.2M │ │ 45   │ │ 12   │ │ 5    │  │
│  │Ventas│ │Ped.  │ │Prod. │ │Alert │  │
│  │      │ │hoy   │ │nuevos│ │stock │  │
│  └──────┘ └──────┘ └──────┘ └──────┘  │
│                                         │
│  Pedidos Recientes                      │
│  ┌─────────────────────────────────┐    │
│  │ #ORD-001 | Juan P. | $83.494   │    │
│  │ #ORD-002 | María L. | $29.990  │    │
│  └─────────────────────────────────┘    │
│                                         │
│  Stock Bajo                             │
│  ┌─────────────────────────────────┐    │
│  │ Cargador USB-C     Stock: 2     │    │
│  │ Botella térmica    Stock: 1     │    │
│  └─────────────────────────────────┘    │
└─────────────────────────────────────────┘
```

### 7. Admin — Productos (`admin/productos.php`)
```
┌─────────────────────────────────────────┐
│  Gestión de Productos  [+ Nuevo]        │
│                                         │
│  [Buscar...]                            │
│                                         │
│  ┌────┬──────────┬───────┬─────┬────┐   │
│  │ID  │Nombre    │Precio │Stk  │Acc │   │
│  ├────┼──────────┼───────┼─────┼────┤   │
│  │1   │Audífonos │$24.990│ 48  │✏️🗑│   │
│  │2   │Cargador  │$15.990│ 100 │✏️🗑│   │
│  └────┴──────────┴───────┴─────┴────┘   │
└─────────────────────────────────────────┘
```

### 8. Admin — Pedidos (`admin/pedidos.php`)
```
┌─────────────────────────────────────────┐
│  Gestión de Pedidos                     │
│                                         │
│  Filtro: [Todos] [Pendientes] [Envío]   │
│                                         │
│  ┌──────┬───────┬──────┬───────┬─────┐  │
│  │N°    │Cliente│Total │Estado  │Acc  │  │
│  ├──────┼───────┼──────┼───────┼─────┤  │
│  │ORD-01│Juan   │$83K  │Pend.  │[▼]  │  │
│  │ORD-02│María  │$30K  │Envío  │[▼]  │  │
│  └──────┴───────┴──────┴───────┴─────┘  │
│                                         │
│  Al cambiar estado: [Confirmar] [Enviar]│
└─────────────────────────────────────────┘
```

---

## Diseño Mobile

En móvil, el navbar se colapsa en un menú hamburguesa (Bootstrap navbar). La cuadrícula de productos pasa de 3 columnas a 2, y luego a 1 columna en pantallas muy pequeñas.

```html
<!-- Bootstrap grid adaptativo -->
<div class="row">
    <div class="col-lg-4 col-md-6 col-12">
        <!-- cada producto -->
    </div>
</div>
```

## Colores y Estilos

- **Color primario:** `#0d6efd` (Bootstrap primary)
- **Color éxito:** `#198754` (verde Bootstrap)
- **Color peligro:** `#dc3545` (rojo Bootstrap)
- **Fondo:** `#f8f9fa` (gris muy claro)
- **Tipografía:** Bootstrap default (sistema sans-serif)

Los estilos personalizados van en `assets/css/estilos.css` y se cargan después de Bootstrap.
