# Design System — Especificación SDD

## 1. Propósito y Alcance

Este documento define las especificaciones completas del Design System del sistema de ecommerce. Establece los fundamentos visuales, los tokens de diseño, los componentes base, las reglas de uso y las directrices de consistencia que deben aplicarse en toda la interfaz de usuario.

El Design System es la fuente de verdad visual del proyecto. Toda implementación de componentes UI debe derivarse exclusivamente de los elementos aquí definidos.

**Framework base:** Bootstrap 5.3  
**Alcance:** Vistas públicas del ecommerce y panel de administración.

---

## 2. Fundamentos Visuales

### 2.1 Paleta de Colores

#### 2.1.1 Colores Primarios

| Token | Nombre | Valor HEX | Uso |
|---|---|---|---|
| `--color-primary` | Azul Institucional | `#1A3C6E` | Botones principales, enlaces activos, encabezados |
| `--color-primary-dark` | Azul Oscuro | `#122B50` | Hover de botones primarios, foco |
| `--color-primary-light` | Azul Claro | `#3A6EA8` | Fondos de sección destacada, badges informativos |

#### 2.1.2 Colores Secundarios

| Token | Nombre | Valor HEX | Uso |
|---|---|---|---|
| `--color-secondary` | Naranja Acento | `#E8720C` | CTAs secundarios, precios en descuento, badges de oferta |
| `--color-secondary-dark` | Naranja Oscuro | `#C25E08` | Hover de botones secundarios |
| `--color-secondary-light` | Naranja Claro | `#F5A05A` | Fondos de alertas de advertencia |

#### 2.1.3 Colores de Estado

| Token | Nombre | Valor HEX | Uso |
|---|---|---|---|
| `--color-success` | Verde | `#198754` | Confirmaciones, stock disponible, Toast success |
| `--color-error` | Rojo | `#DC3545` | Errores de validación, Toast error, sin stock |
| `--color-warning` | Amarillo | `#FFC107` | Alertas de advertencia, Toast warning |
| `--color-info` | Azul Info | `#0DCAF0` | Información general, Toast info |

#### 2.1.4 Colores Neutros

| Token | Nombre | Valor HEX | Uso |
|---|---|---|---|
| `--color-neutral-900` | Casi Negro | `#1A1A2E` | Texto principal |
| `--color-neutral-700` | Gris Oscuro | `#495057` | Texto secundario, íconos |
| `--color-neutral-400` | Gris Medio | `#ADB5BD` | Bordes, separadores, texto deshabilitado |
| `--color-neutral-100` | Gris Claro | `#F8F9FA` | Fondos de secciones alternadas |
| `--color-white` | Blanco | `#FFFFFF` | Fondos de tarjetas, contenedores |

#### 2.1.5 Reglas de Uso de Color

1. El contraste mínimo entre texto y fondo debe ser 4.5:1 (WCAG 2.1 nivel AA).
2. El color primario no debe usarse en fondos extensos de texto; reservado para elementos de acción.
3. Los colores de estado (`success`, `error`, `warning`, `info`) solo se usan en sus contextos semánticos definidos.
4. No se permiten colores fuera de esta paleta sin aprobación del Design Lead.

---

### 2.2 Tipografía

#### 2.2.1 Familias Tipográficas

| Rol | Familia | Fallback | Uso |
|---|---|---|---|
| **Principal** | `Inter` | `system-ui, sans-serif` | Cuerpo de texto, UI general |
| **Encabezados** | `Poppins` | `Georgia, serif` | Títulos H1–H3, nombre de la tienda |
| **Monoespaciada** | `JetBrains Mono` | `monospace` | Códigos de pedido, IDs, precios en tabla admin |

#### 2.2.2 Escala Tipográfica

| Token | Tamaño | Line-height | Peso | Uso |
|---|---|---|---|---|
| `--text-xs` | 12px | 1.4 | 400 | Etiquetas pequeñas, notas al pie |
| `--text-sm` | 14px | 1.5 | 400 | Texto secundario, descripciones en tabla |
| `--text-base` | 16px | 1.6 | 400 | Cuerpo de texto principal |
| `--text-lg` | 18px | 1.5 | 500 | Texto destacado, precios |
| `--text-xl` | 20px | 1.4 | 600 | Subtítulos de sección |
| `--text-2xl` | 24px | 1.3 | 600 | Títulos de página (H2) |
| `--text-3xl` | 30px | 1.2 | 700 | Título principal de página (H1) |
| `--text-4xl` | 36px | 1.1 | 700 | Heros y banners |

#### 2.2.3 Reglas Tipográficas

1. El tamaño mínimo de texto legible es `--text-xs` (12px); no se permite texto menor.
2. Los encabezados H1 usan `Poppins` weight 700; H2–H3 usan `Poppins` weight 600.
3. El cuerpo de texto usa `Inter` weight 400; texto en negrita usa weight 600 (no 700 en cuerpo).
4. El interlineado mínimo para cuerpo de texto es 1.5 para garantizar legibilidad.

---

### 2.3 Espaciado

El sistema de espaciado se basa en múltiplos de 4px (escala de 4pt).

| Token | Valor | Uso |
|---|---|---|
| `--space-1` | 4px | Espaciado interno mínimo, gap entre íconos |
| `--space-2` | 8px | Gap entre elementos inline, padding de badges |
| `--space-3` | 12px | Padding interno de inputs pequeños |
| `--space-4` | 16px | Padding estándar de componentes, gap de grilla |
| `--space-5` | 20px | Separación entre secciones internas |
| `--space-6` | 24px | Margen entre tarjetas, padding de cards |
| `--space-8` | 32px | Separación entre secciones de página |
| `--space-10` | 40px | Margen vertical de secciones principales |
| `--space-12` | 48px | Padding de secciones hero |
| `--space-16` | 64px | Separación entre bloques de contenido mayor |

**Regla:** No se permite usar valores de espaciado fuera de esta escala (excepto valores derivados como `auto` en márgenes).

---

### 2.4 Sombras y Elevaciones

| Token | Valor CSS | Uso |
|---|---|---|
| `--shadow-sm` | `0 1px 3px rgba(0,0,0,0.08)` | Tarjetas en estado default |
| `--shadow-md` | `0 4px 12px rgba(0,0,0,0.12)` | Tarjetas en hover, dropdowns |
| `--shadow-lg` | `0 8px 24px rgba(0,0,0,0.16)` | Modales, drawers |
| `--shadow-xl` | `0 16px 48px rgba(0,0,0,0.20)` | Toasts, tooltips flotantes |

---

### 2.5 Bordes y Radio

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | 4px | Inputs, badges pequeños |
| `--radius-md` | 8px | Botones, tarjetas de producto |
| `--radius-lg` | 12px | Modales, cards de sección |
| `--radius-xl` | 20px | Elementos destacados, hero cards |
| `--radius-full` | 9999px | Avatares, badges circulares, pills |

---

## 3. Componentes Base

### 3.1 Botones

#### 3.1.1 Variantes

| Variante | Color fondo | Color texto | Borde | Uso |
|---|---|---|---|---|
| `primary` | `--color-primary` | Blanco | Ninguno | Acción principal de la página |
| `secondary` | `--color-secondary` | Blanco | Ninguno | Acción secundaria relevante |
| `outline-primary` | Transparente | `--color-primary` | `--color-primary` | Acción alternativa |
| `outline-danger` | Transparente | `--color-error` | `--color-error` | Cancelar, eliminar |
| `ghost` | Transparente | `--color-neutral-700` | Ninguno | Acciones de baja jerarquía |
| `danger` | `--color-error` | Blanco | Ninguno | Confirmar eliminación |

#### 3.1.2 Tamaños

| Tamaño | Padding | Font-size | Altura |
|---|---|---|---|
| `sm` | 6px 12px | 14px | 32px |
| `md` (default) | 10px 20px | 16px | 40px |
| `lg` | 12px 28px | 18px | 48px |

#### 3.1.3 Estados de Botón

| Estado | Descripción |
|---|---|
| `default` | Estilo base definido por variante |
| `hover` | Oscurece el color de fondo en 10%; sombra `--shadow-sm` |
| `focus` | Outline de 3px en `--color-primary-light` con offset 2px |
| `active` | Oscurece el fondo en 15%; sombra ninguna |
| `disabled` | Opacidad 0.5; cursor `not-allowed`; no interactuable |
| `loading` | Spinner inline; texto opcional; ancho fijo para evitar layout shift |

#### 3.1.4 Reglas de Uso de Botones

1. Solo puede existir un botón `primary` por área de acción visible.
2. Los botones `danger` requieren confirmación (modal o diálogo) antes de ejecutar la acción.
3. Los botones `disabled` nunca se ocultan; se muestran con opacidad reducida.
4. Los botones en estado `loading` deben mostrar feedback visual para evitar clics duplicados.

---

### 3.2 Inputs y Formularios

#### 3.2.1 Tipos de Input

| Tipo | Descripción |
|---|---|
| `text` | Campo de texto libre |
| `email` | Validación de formato de correo electrónico |
| `password` | Campo enmascarado con toggle de visibilidad |
| `number` | Solo dígitos; puede incluir controles de incremento |
| `search` | Incluye ícono de lupa; ejecuta búsqueda al presionar Enter |
| `select` | Dropdown de opciones; compatible con búsqueda interna |
| `textarea` | Texto multilínea; redimensionable verticalmente |
| `checkbox` | Selección múltiple |
| `radio` | Selección única dentro de un grupo |

#### 3.2.2 Estados de Input

| Estado | Descripción visual |
|---|---|
| `default` | Borde `--color-neutral-400`; fondo blanco |
| `focus` | Borde `--color-primary`; sombra sutil `--shadow-sm` |
| `error` | Borde `--color-error`; mensaje de error debajo en rojo |
| `success` | Borde `--color-success`; ícono de check al final |
| `disabled` | Fondo `--color-neutral-100`; cursor `not-allowed` |
| `readonly` | Fondo `--color-neutral-100`; sin indicador de foco |

#### 3.2.3 Reglas de Formularios

1. Todo campo obligatorio debe marcarse con asterisco (*) de color `--color-error` junto al label.
2. Los mensajes de error se muestran debajo del input afectado, no en alerts globales.
3. La validación se ejecuta al perder el foco (`onBlur`) y al intentar enviar el formulario.
4. Los labels siempre son visibles; no se usan placeholders como sustituto de labels.
5. El formulario no puede enviarse si existe al menos un campo en estado `error`.

---

### 3.3 Badges e Insignias

| Tipo | Color fondo | Uso |
|---|---|---|
| `nuevo` | `--color-primary` | Productos nuevos en CardProducto |
| `oferta` | `--color-secondary` | Productos con descuento activo |
| `sin-stock` | `--color-neutral-400` | Productos sin inventario disponible |
| `activo` | `--color-success` | Estado activo en tablas de admin |
| `inactivo` | `--color-error` | Estado inactivo en tablas de admin |
| `pendiente` | `--color-warning` | Pedidos en estado pendiente |

---

### 3.4 Modales y Diálogos

#### 3.4.1 Tipos

| Tipo | Propósito |
|---|---|
| **Confirmación** | Confirmar acciones destructivas (eliminar, cancelar pedido) |
| **Formulario** | Crear o editar una entidad en el panel de admin |
| **Detalle** | Mostrar información ampliada sin salir de la vista |
| **Alerta** | Comunicar un estado crítico que requiere atención del usuario |

#### 3.4.2 Reglas de Modales

1. Todo modal debe poder cerrarse con la tecla `Escape`.
2. El foco se atrapa dentro del modal mientras está abierto (accesibilidad).
3. El scroll del body se bloquea mientras un modal está abierto.
4. Los modales de confirmación de acciones destructivas tienen el botón de confirmar en variante `danger`.
5. Los modales de formulario validan los campos antes de ejecutar la acción de guardado.

---

## 4. Sistema de Grilla

El sistema de grilla se basa en el grid de Bootstrap 5.3 (12 columnas).

| Breakpoint | Prefijo | Ancho mínimo | Columnas típicas para cards |
|---|---|---|---|
| xs (móvil) | `col-` | < 576px | 1 columna |
| sm (móvil grande) | `col-sm-` | ≥ 576px | 2 columnas |
| md (tablet) | `col-md-` | ≥ 768px | 3 columnas |
| lg (desktop) | `col-lg-` | ≥ 992px | 4 columnas |
| xl (desktop grande) | `col-xl-` | ≥ 1200px | 4–5 columnas |
| xxl (pantallas grandes) | `col-xxl-` | ≥ 1400px | 5–6 columnas |

**Regla:** El contenedor máximo de contenido es de 1320px (`container-xxl`); centrado horizontalmente.

---

## 5. Iconografía

- **Librería:** Bootstrap Icons 1.11 (dependencia separada; **no** se incluye automáticamente con Bootstrap 5.3.8 y debe agregarse explícitamente como asset independiente via CDN o paquete npm `bootstrap-icons`).
- **Tamaño estándar:** 20px en navegación y botones; 16px en inputs; 24px en encabezados.
- **Color:** Hereda del color de texto del elemento contenedor, salvo especificación explícita.
- **Uso decorativo vs. semántico:** Los íconos decorativos llevan `aria-hidden="true"`; los semánticos llevan `aria-label` descriptivo.

---

## 6. Animaciones y Transiciones

| Token | Duración | Curva | Uso |
|---|---|---|---|
| `--transition-fast` | 150ms | `ease-out` | Hover de botones, cambios de color |
| `--transition-base` | 250ms | `ease-in-out` | Aparición de dropdowns, cambios de estado |
| `--transition-slow` | 400ms | `ease-in-out` | Modales, drawers, Toasts (slide-in/fade-out) |

**Reglas:**
1. No se permiten animaciones de más de 500ms en interacciones frecuentes (hover, clic).
2. Las animaciones deben respetarse con `prefers-reduced-motion`: si el usuario tiene configurada esta preferencia del sistema, todas las transiciones se reducen a 0ms.

---

## 7. Accesibilidad

| Requisito | Estándar |
|---|---|
| Contraste de color texto/fondo | WCAG 2.1 AA — mínimo 4.5:1 |
| Tamaño mínimo de área interactuable | 44×44px (WCAG 2.5.5) |
| Navegación por teclado | Todos los elementos interactuables alcanzables con Tab |
| Foco visible | Outline de 3px en `--color-primary-light` en todos los elementos |
| Atributos ARIA | Todos los componentes interactivos incluyen roles y labels ARIA apropiados |
| Lectores de pantalla | Toasts con `role="alert"`; modales con `aria-modal="true"` y `aria-labelledby` |

---

## 8. Directrices de Implementación

### 8.1 Uso de Clases de Bootstrap vs. Tokens

- Se usan las clases de Bootstrap 5.3.8 como punto de partida; se sobreescriben con los tokens CSS definidos en `:root` cuando los valores de Bootstrap no coincidan con el Design System.
- No se permite modificar el `_variables.scss` de Bootstrap directamente; se usan variables CSS personalizadas en una hoja de estilos separada (`design-tokens.css`).

### 8.2 Archivo de Tokens

Todos los tokens definidos en este documento se declaran en:

```
src/styles/design-tokens.css
```

> **Nota:** esta ruta es la propuesta dentro del repositorio frontend donde vivirá la implementación de la UI. Este repositorio de documentación no contiene código fuente; la ruta `src/` no existe aquí.

Este archivo se importa globalmente antes que cualquier otro stylesheet y nunca se modifica por componente.

### 8.3 Nombrado de Clases Personalizadas

El naming sigue la convención BEM adaptada:

```
.bloque__elemento--modificador
```

Ejemplo: `.card-producto__precio--descuento`

### 8.4 Restricciones

1. No se permiten estilos inline (`style=""`) salvo en valores dinámicos calculados en tiempo de ejecución (ej. porcentaje de progreso en una barra).
2. No se permite el uso de `!important` salvo en la hoja de reset global.
3. Los colores, tipografías y espaciados deben referenciarse siempre por token, nunca por valor hardcoded.

---

## 9. Versionado del Design System

| Versión | Fecha | Cambios |
|---|---|---|
| 1.0.0 | 2026-06-07 | Especificación inicial completa |

Cualquier cambio en tokens, componentes base o directrices requiere actualización de este documento y comunicación al equipo de desarrollo antes de su implementación.
