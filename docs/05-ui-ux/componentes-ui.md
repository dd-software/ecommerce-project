# Componentes UI — Especificación SDD

## 1. Propósito y Alcance

Este documento define las especificaciones completas de los componentes de interfaz de usuario (UI) del sistema de ecommerce. Cada componente se documenta con sus responsabilidades funcionales, propiedades, estados, comportamientos, reglas de negocio y criterios de aceptación, constituyendo la fuente de verdad para su implementación y generación automatizada.

Los componentes especificados son:

- `CardProducto`
- `TablaAdmin`
- `Navbar`
- `Footer`
- `Toast`

---

## 2. CardProducto

### 2.1 Descripción y Propósito

Tarjeta visual reutilizable que representa un producto individual en listados, carruseles y resultados de búsqueda. Muestra la información esencial del producto y permite al usuario agregar el ítem al carrito o acceder al detalle completo.

### 2.2 Actores Involucrados

| Actor | Interacción |
|---|---|
| Usuario Visitante | Visualiza el producto; puede acceder al detalle |
| Usuario Autenticado | Puede agregar al carrito y a favoritos |
| Sistema | Renderiza stock, precio y descuentos dinámicamente |

### 2.3 Propiedades (Props)

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `productoId` | `string` | Sí | Identificador único del producto |
| `nombre` | `string` | Sí | Nombre del producto (máx. 80 caracteres) |
| `precio` | `number` | Sí | Precio base en la moneda configurada |
| `precioDescuento` | `number` | No | Precio con descuento; si se provee, el precio base se muestra tachado |
| `imagenUrl` | `string` | Sí | URL de la imagen principal del producto |
| `categoria` | `string` | Sí | Categoría a la que pertenece el producto |
| `stock` | `number` | Sí | Cantidad disponible en inventario |
| `calificacion` | `number` | No | Promedio de calificación (0.0–5.0) |
| `totalResenas` | `number` | No | Número total de reseñas asociadas |
| `esNuevo` | `boolean` | No | Activa la insignia "Nuevo" si es `true` |
| `onAgregarCarrito` | `function` | No | Callback ejecutado al presionar "Agregar al carrito" |
| `onVerDetalle` | `function` | No | Callback ejecutado al presionar el nombre o imagen |

### 2.4 Estados del Componente

| Estado | Descripción |
|---|---|
| `default` | Visualización estándar con imagen, nombre y precio |
| `hover` | Eleva la sombra y muestra el botón "Agregar al carrito" |
| `sinStock` | Precio y botón deshabilitados; etiqueta "Sin stock" visible |
| `cargando` | Muestra skeleton/placeholder mientras llegan los datos |
| `agregado` | Confirmación visual breve (checkmark) tras agregar al carrito |
| `error` | Mensaje de error si falla la operación de agregar al carrito |

### 2.5 Reglas de Negocio

1. Si `stock === 0`, el botón "Agregar al carrito" se deshabilita y se muestra la etiqueta "Sin stock".
2. Si `precioDescuento` es provisto y menor a `precio`, se calcula y muestra el porcentaje de descuento: `Math.round((1 - precioDescuento / precio) * 100)` + `"%"`.
3. Si `precioDescuento >= precio`, se ignora y no se muestra descuento alguno.
4. La calificación se renderiza con estrellas (escala 0–5); fracción menor a 0.5 redondea hacia abajo.
5. Si `nombre` supera los 60 caracteres en la vista de tarjeta, se trunca con elipsis.
6. La insignia "Nuevo" solo se muestra si `esNuevo === true`.

### 2.6 Flujo Operacional

```
Usuario visualiza listado
  └── Sistema renderiza CardProducto por cada producto
        ├── [stock > 0] → Botón "Agregar al carrito" habilitado
        │     └── Usuario hace clic → onAgregarCarrito(productoId)
        │           ├── Éxito → Estado "agregado" por 2 s → regresa a "default"
        │           └── Error → Estado "error" con mensaje descriptivo
        └── [stock === 0] → Botón deshabilitado; etiqueta "Sin stock"
```

### 2.7 Criterios de Aceptación

- [ ] La tarjeta muestra imagen, nombre, precio, calificación y stock correctamente.
- [ ] El descuento se calcula y muestra solo si `precioDescuento < precio`.
- [ ] El botón "Agregar al carrito" está deshabilitado cuando `stock === 0`.
- [ ] La insignia "Nuevo" aparece solo cuando `esNuevo === true`.
- [ ] El estado `cargando` muestra un placeholder durante la carga asíncrona.
- [ ] La interacción hover revela el botón de acción en dispositivos con mouse.
- [ ] El componente es responsivo y se adapta a grillas de 1, 2, 3 y 4 columnas.

### 2.8 Dependencias

- `Toast` — notificación al agregar al carrito
- Servicio de Carrito (API REST)
- Módulo de Inventario (stock en tiempo real)

---

## 3. TablaAdmin

### 3.1 Descripción y Propósito

Componente de tabla genérico utilizado en el panel de administración para listar, filtrar, ordenar, paginar y ejecutar acciones sobre entidades del sistema (productos, pedidos, usuarios). Es el componente central de todas las vistas de gestión.

### 3.2 Actores Involucrados

| Actor | Interacción |
|---|---|
| Administrador | Opera todas las funciones de la tabla |
| Sistema | Provee datos paginados y responde a acciones CRUD |

### 3.3 Propiedades (Props)

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `columnas` | `Column[]` | Sí | Definición de columnas (ver estructura) |
| `datos` | `object[]` | Sí | Array de objetos con los datos a mostrar |
| `totalRegistros` | `number` | Sí | Total de registros para la paginación |
| `paginaActual` | `number` | Sí | Número de página activa (base 1) |
| `registrosPorPagina` | `number` | No | Filas por página (default: 10; mín: 5; máx: 100) |
| `acciones` | `Action[]` | No | Botones de acción por fila |
| `onCambiarPagina` | `function` | Sí | Callback `(pagina: number) => void` |
| `onOrdenar` | `function` | No | Callback `(campo: string, dir: 'asc' \| 'desc') => void` |
| `onFiltrar` | `function` | No | Callback `(filtros: object) => void` |
| `cargando` | `boolean` | No | Activa overlay de carga |
| `mensajeVacio` | `string` | No | Texto cuando no hay registros (default: "Sin registros") |

**Estructura `Column`:**
```typescript
{
  campo: string;
  encabezado: string;
  ordenable: boolean;
  ancho?: string;
  renderizar?: (valor: any, fila: object) => ReactNode;
}
```

**Estructura `Action`:**
```typescript
{
  etiqueta: string;
  icono?: string;
  variante: 'primary' | 'warning' | 'danger' | 'secondary';
  onClick: (fila: object) => void;
  deshabilitarSi?: (fila: object) => boolean;
}
```

### 3.4 Estados del Componente

| Estado | Descripción |
|---|---|
| `cargando` | Overlay semitransparente con spinner sobre la tabla |
| `vacio` | Fila única con `mensajeVacio` cuando `datos.length === 0` |
| `error` | Mensaje de error si la carga de datos falla |
| `normal` | Tabla con datos, paginación y controles activos |

### 3.5 Reglas de Negocio

1. La paginación muestra máximo 5 páginas visibles; usa elipsis para rangos extensos.
2. El ordenamiento es exclusivo: solo una columna puede estar ordenada a la vez.
3. Al cambiar un filtro, la paginación se reinicia a la página 1.
4. Las acciones con `deshabilitarSi` que retornan `true` se renderizan deshabilitadas, no ocultas.
5. Si `totalRegistros === 0`, el control de paginación no se muestra.

### 3.6 Flujo Operacional

```
Administrador abre vista de gestión
  └── Sistema carga datos paginados → estado "cargando"
        └── Datos recibidos → estado "normal"
              ├── Ordena columna → onOrdenar(campo, dir) → recarga
              ├── Aplica filtro → onFiltrar(filtros) → página 1 → recarga
              ├── Cambia página → onCambiarPagina(pagina) → recarga
              └── Ejecuta acción sobre fila → action.onClick(fila)
```

### 3.7 Criterios de Aceptación

- [ ] La tabla muestra correctamente todos los campos definidos en `columnas`.
- [ ] El ordenamiento alterna entre `asc` y `desc` en clics sucesivos.
- [ ] Al cambiar filtros, la paginación regresa a la página 1.
- [ ] El estado `cargando` bloquea la interacción con un overlay.
- [ ] Las acciones deshabilitadas son visibles pero no interactuables.
- [ ] La tabla es responsive: en móvil usa scroll horizontal.
- [ ] El mensaje vacío se muestra correctamente cuando `datos.length === 0`.

### 3.8 Dependencias

- Bootstrap 5.3 (estilos de tabla y paginación)
- Módulos de gestión: Productos, Pedidos, Usuarios

---

## 4. Navbar

### 4.1 Descripción y Propósito

Barra de navegación principal del sitio. Persiste en todas las vistas y provee acceso a las secciones principales, búsqueda de productos, estado de autenticación del usuario y acceso rápido al carrito.

### 4.2 Actores Involucrados

| Actor | Interacción |
|---|---|
| Usuario Visitante | Navega, busca productos, accede a login/registro |
| Usuario Autenticado | Accede a perfil, pedidos, cerrar sesión, carrito |
| Administrador | Extiende autenticado con acceso al panel de administración |

### 4.3 Propiedades (Props)

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `usuario` | `Usuario \| null` | Sí | Objeto usuario autenticado; `null` si no hay sesión |
| `cantidadCarrito` | `number` | Sí | Número de ítems en el carrito |
| `categorias` | `Categoria[]` | Sí | Lista de categorías para el menú desplegable |
| `onBuscar` | `function` | Sí | Callback `(termino: string) => void` |
| `onCerrarSesion` | `function` | No | Callback ejecutado al cerrar sesión |

### 4.4 Secciones del Componente

| Sección | Descripción |
|---|---|
| **Logo** | Logo de la tienda; enlaza a `/` |
| **Categorías** | Dropdown con categorías activas del catálogo |
| **Búsqueda** | Input + botón; ejecuta `onBuscar` al enviar |
| **Carrito** | Ícono con badge mostrando `cantidadCarrito` |
| **Menú de Usuario** | Opciones según estado de autenticación |
| **Menú Móvil** | Hamburguesa que colapsa las secciones en pantallas pequeñas |

### 4.5 Reglas de Negocio

1. Si `usuario === null`: el menú muestra "Iniciar sesión" y "Registrarse".
2. Si `usuario !== null`: el menú muestra nombre, "Mis pedidos", "Perfil" y "Cerrar sesión".
3. Si `usuario.rol === 'admin'`: se agrega el enlace "Panel de Administración".
4. El badge del carrito no se muestra si `cantidadCarrito === 0`.
5. Si `cantidadCarrito > 99`, el badge muestra "99+".
6. La búsqueda requiere al menos 2 caracteres; se activa con Enter o clic en el botón.
7. La Navbar es sticky en la parte superior de la pantalla en todo momento.

### 4.6 Estados del Componente

| Estado | Descripción |
|---|---|
| `visitante` | Sin sesión; muestra opciones de login/registro |
| `autenticado` | Con sesión activa; muestra opciones de cuenta |
| `admin` | Extiende `autenticado` con enlace al panel admin |
| `movil-expandido` | Menú hamburguesa abierto |
| `movil-colapsado` | Menú hamburguesa cerrado |

### 4.7 Criterios de Aceptación

- [ ] El logo redirecciona a `/` siempre.
- [ ] El dropdown de categorías muestra solo categorías activas.
- [ ] La búsqueda no se dispara con términos de menos de 2 caracteres.
- [ ] El badge del carrito refleja la cantidad actual en tiempo real.
- [ ] El menú de usuario cambia correctamente según el estado de autenticación.
- [ ] En pantallas < 768px el menú se colapsa en hamburguesa.
- [ ] La Navbar permanece visible al hacer scroll.

### 4.8 Dependencias

- Módulo de Autenticación (estado de sesión)
- Módulo de Carrito (cantidad de ítems)
- Módulo de Categorías (listado activo)
- Sistema de enrutamiento (React Router o equivalente)

---

## 5. Footer

### 5.1 Descripción y Propósito

Pie de página global del sitio. Presente en todas las vistas públicas. Contiene información institucional, enlaces de navegación secundarios, redes sociales y datos de contacto.

### 5.2 Actores Involucrados

| Actor | Interacción |
|---|---|
| Usuario Visitante | Consulta información y accede a enlaces |
| Usuario Autenticado | Misma interacción que el visitante |

### 5.3 Propiedades (Props)

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `nombreTienda` | `string` | Sí | Nombre de la tienda para el copyright |
| `anioFundacion` | `number` | No | Año de fundación para el rango de copyright |
| `redesSociales` | `RedSocial[]` | No | Íconos y URLs de redes sociales |
| `enlacesLegales` | `Enlace[]` | No | Términos, privacidad, cookies |
| `columnasEnlaces` | `ColumnaFooter[]` | No | Columnas de enlaces secundarios |
| `emailContacto` | `string` | No | Correo de contacto visible como `mailto:` |
| `telefonoContacto` | `string` | No | Teléfono de contacto visible |

**Estructura `RedSocial`:**
```typescript
{ nombre: string; url: string; icono: string; }
```

**Estructura `ColumnaFooter`:**
```typescript
{ titulo: string; enlaces: { texto: string; url: string; }[]; }
```

### 5.4 Reglas de Negocio

1. El año de copyright se calcula dinámicamente. Si `anioFundacion` difiere del año actual, se muestra el rango `anioFundacion–añoActual`; de lo contrario, solo el año actual.
2. Los enlaces de redes sociales se abren en nueva pestaña con `target="_blank"` y `rel="noopener noreferrer"`.
3. Si no se provee `columnasEnlaces`, el Footer muestra solo copyright y datos de contacto.
4. El Footer no se renderiza en rutas bajo `/admin/*`.

### 5.5 Criterios de Aceptación

- [ ] El año de copyright se actualiza automáticamente cada año.
- [ ] Los enlaces de redes sociales abren en nueva pestaña con atributos de seguridad.
- [ ] El Footer es responsive: en móvil las columnas se apilan verticalmente.
- [ ] El Footer no aparece en rutas `/admin/*`.
- [ ] El email de contacto es un enlace `mailto:` funcional.

### 5.6 Dependencias

- Sistema de enrutamiento (para exclusión en rutas admin)
- Bootstrap 5.3 (grid de columnas)

---

## 6. Toast

### 6.1 Descripción y Propósito

Componente de notificación no intrusiva que muestra mensajes de retroalimentación al usuario como resultado de acciones del sistema (éxito, error, advertencia, información). Desaparece automáticamente después de un tiempo configurable o puede ser cerrado manualmente.

### 6.2 Actores Involucrados

| Actor | Interacción |
|---|---|
| Sistema | Dispara el Toast como respuesta a eventos de negocio |
| Usuario | Puede cerrar el Toast manualmente |

### 6.3 Propiedades (Props)

| Prop | Tipo | Requerido | Descripción |
|---|---|---|---|
| `mensaje` | `string` | Sí | Texto del mensaje a mostrar |
| `tipo` | `'success' \| 'error' \| 'warning' \| 'info'` | Sí | Determina el color e ícono |
| `duracion` | `number` | No | Tiempo en ms antes de desaparecer (default: 3000) |
| `posicion` | `'top-right' \| 'top-left' \| 'bottom-right' \| 'bottom-left'` | No | Posición en pantalla (default: `'top-right'`) |
| `onCerrar` | `function` | No | Callback ejecutado al cerrar |
| `mostrarCerrar` | `boolean` | No | Muestra botón de cierre manual (default: `true`) |

### 6.4 Tipos y Variantes

| Tipo | Color | Ícono | Uso |
|---|---|---|---|
| `success` | Verde | ✓ | Operación completada exitosamente |
| `error` | Rojo | ✗ | Error en operación; requiere atención del usuario |
| `warning` | Amarillo | ⚠ | Advertencia; acción opcional del usuario |
| `info` | Azul | ℹ | Información general; sin acción requerida |

### 6.5 Reglas de Negocio

1. Si `duracion === 0`, el Toast no desaparece automáticamente; requiere cierre manual.
2. Pueden coexistir máximo 3 Toasts simultáneos; el más antiguo se descarta si se supera el límite.
3. Los Toasts de tipo `error` tienen duración mínima de 5000ms, sin importar el valor configurado.
4. La animación de entrada es `slide-in` y la de salida es `fade-out`.
5. Los Toasts se apilan verticalmente con un gap de 8px entre ellos.

### 6.6 Flujo Operacional

```
Sistema ejecuta acción de negocio
  ├── Éxito     → Toast "success" con duración 3000ms
  ├── Error     → Toast "error" con duración mín. 5000ms
  └── Advertencia → Toast "warning" con duración 4000ms

Toast visible
  ├── [duracion > 0] → Timer automático → fade-out → onCerrar()
  └── Usuario presiona "×" → fade-out → onCerrar()
```

### 6.7 Criterios de Aceptación

- [ ] El Toast aparece con la animación `slide-in`.
- [ ] El tipo `error` no desaparece en menos de 5000ms.
- [ ] No coexisten más de 3 Toasts simultáneamente.
- [ ] El botón de cierre manual funciona independientemente del timer.
- [ ] La posición configurable ubica el Toast en la esquina correcta de la pantalla.
- [ ] El Toast tiene `role="alert"` para accesibilidad con lectores de pantalla.

### 6.8 Dependencias

- Contexto global de notificaciones (para disparar desde cualquier módulo)
- Bootstrap 5.3 (clases de color base)

---

## 7. Consideraciones de Integración

| Componente | Integra con |
|---|---|
| `CardProducto` | Módulo de Carrito, Módulo de Inventario, `Toast` |
| `TablaAdmin` | Módulos de gestión: Productos, Pedidos, Usuarios |
| `Navbar` | Autenticación, Carrito, Categorías, Router |
| `Footer` | Router (exclusión en rutas admin) |
| `Toast` | Todos los módulos (notificaciones globales) |

## 8. Restricciones Generales

- Todos los componentes deben cumplir WCAG 2.1 nivel AA (accesibilidad).
- Los componentes no gestionan estado de servidor directamente; usan callbacks y props para comunicarse con la capa de datos.
- El diseño base usa Bootstrap 5.3; no se permiten librerías CSS adicionales sin aprobación.
- Los componentes deben ser testeables de forma unitaria e independiente.
