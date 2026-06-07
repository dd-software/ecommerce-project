# Modelo de Dominio — Especificación SDD

## 1. Propósito y Alcance

Este documento define las especificaciones completas del modelo de dominio del sistema de ecommerce. Describe cada entidad del dominio con sus atributos, invariantes, relaciones, restricciones y reglas de negocio. Constituye la fuente de verdad para el diseño de la base de datos, la capa de dominio de la aplicación y la lógica de negocio.

El modelo cubre las siguientes entidades principales:

- `Usuario`
- `Dirección`
- `Carrito` / `ItemCarrito`
- `Producto`
- `Categoría`
- `Inventario`
- `Imagen`
- `Pedido`
- `DetallePedido`
- `Pago`

---

## 2. Diagrama de Relaciones (Descripción Textual)

```
Usuario
 ├── tiene muchas → Dirección
 ├── tiene uno    → Carrito (activo en todo momento)
 └── tiene muchos → Pedido

Carrito
 └── contiene muchos → ItemCarrito → referencia → Producto

Pedido
 ├── tiene una        → Dirección (de envío, copiada al momento del pedido)
 ├── tiene muchos     → DetallePedido → referencia → Producto
 └── tiene uno        → Pago

Producto
 ├── pertenece a uno  → Categoría
 ├── tiene uno        → Inventario
 └── tiene muchas     → Imagen
```

---

## 3. Entidades del Dominio

### 3.1 Usuario

**Descripción:** Representa a cualquier persona registrada en el sistema. Puede actuar como cliente o como administrador según su rol.

#### 3.1.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable; generado por el sistema al crear |
| `nombre` | string | Sí | 2–100 caracteres; solo letras, espacios y guiones |
| `apellido` | string | Sí | 2–100 caracteres; solo letras, espacios y guiones |
| `email` | string | Sí | Único en el sistema; formato RFC 5322 válido |
| `passwordHash` | string | Sí | Hash bcrypt (cost ≥ 12); nunca almacenar en texto plano |
| `rol` | enum | Sí | Valores: `cliente`, `admin`; default: `cliente` |
| `activo` | boolean | Sí | Default: `true`; si `false`, el usuario no puede autenticarse |
| `emailVerificado` | boolean | Sí | Default: `false`; se activa al verificar el correo |
| `fechaRegistro` | datetime | Sí | Generado automáticamente; inmutable |
| `ultimoAcceso` | datetime | No | Actualizado en cada autenticación exitosa |
| `avatarUrl` | string | No | URL válida; imagen de perfil del usuario |

#### 3.1.2 Invariantes y Reglas de Negocio

1. El `email` es único en el sistema; no puede registrarse dos usuarios con el mismo correo.
2. Un usuario con `activo === false` no puede iniciar sesión ni realizar compras.
3. Un usuario con `emailVerificado === false` puede navegar pero no completar pedidos.
4. Solo los usuarios con `rol === 'admin'` pueden acceder al panel de administración.
5. El `passwordHash` nunca se expone en respuestas de API.
6. El `id` no puede modificarse una vez creado.

#### 3.1.3 Relaciones

| Relación | Tipo | Entidad relacionada | Descripción |
|---|---|---|---|
| `direcciones` | 1:N | `Dirección` | Un usuario puede tener múltiples direcciones guardadas |
| `carrito` | 1:1 | `Carrito` | Cada usuario tiene exactamente un carrito activo |
| `pedidos` | 1:N | `Pedido` | Un usuario puede tener múltiples pedidos |

---

### 3.2 Dirección

**Descripción:** Dirección postal asociada a un usuario, usada para el envío de pedidos.

#### 3.2.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `usuarioId` | UUID | Sí | FK a `Usuario.id` |
| `alias` | string | No | Nombre descriptivo (ej. "Casa", "Trabajo"); máx. 50 caracteres |
| `calle` | string | Sí | Nombre de calle y número; máx. 200 caracteres |
| `ciudad` | string | Sí | Máx. 100 caracteres |
| `estado` | string | Sí | Estado o provincia; máx. 100 caracteres |
| `codigoPostal` | string | Sí | Formato válido según el país configurado |
| `pais` | string | Sí | Código ISO 3166-1 alpha-2 (ej. `MX`, `CO`) |
| `referencia` | string | No | Indicaciones adicionales; máx. 300 caracteres |
| `esPredeterminada` | boolean | Sí | Default: `false`; solo una dirección por usuario puede ser `true` |

#### 3.2.2 Invariantes y Reglas de Negocio

1. Un usuario puede tener máximo 5 direcciones guardadas.
2. Solo una dirección por usuario puede tener `esPredeterminada === true`.
3. Al eliminar la dirección predeterminada, ninguna otra se convierte automáticamente en predeterminada; el usuario debe designar una nueva.
4. Las direcciones asociadas a pedidos ya realizados no pueden eliminarse; se marcan como archivadas.

---

### 3.3 Carrito

**Descripción:** Contenedor temporal de productos que el usuario desea comprar. Existe uno por usuario; persiste entre sesiones.

#### 3.3.1 Atributos del Carrito

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `usuarioId` | UUID | Sí | FK a `Usuario.id`; único (un carrito por usuario) |
| `creadoEn` | datetime | Sí | Generado automáticamente |
| `actualizadoEn` | datetime | Sí | Actualizado en cada modificación |

#### 3.3.2 Atributos de ItemCarrito

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `carritoId` | UUID | Sí | FK a `Carrito.id` |
| `productoId` | UUID | Sí | FK a `Producto.id` |
| `cantidad` | integer | Sí | Mínimo: 1; máximo: limitado por stock disponible |
| `precioUnitario` | decimal | Sí | Precio del producto al momento de agregar; 2 decimales |
| `agregadoEn` | datetime | Sí | Generado automáticamente |

#### 3.3.3 Invariantes y Reglas de Negocio

1. Un `ItemCarrito` no puede tener `cantidad` mayor que el stock disponible del producto en el momento del checkout.
2. Si un mismo producto se agrega dos veces, se incrementa la cantidad del `ItemCarrito` existente.
3. El carrito no expira automáticamente, pero los precios se recalculan al iniciar el checkout con los precios actuales.
4. Un usuario puede tener máximo 50 ítems distintos en el carrito.
5. Al completar un pedido, los ítems del carrito se eliminan.
6. El carrito de usuarios visitantes (no autenticados) es temporal (localStorage); al autenticarse, se fusiona con el carrito del servidor.

#### 3.3.4 Atributos Calculados

- `subtotal`: suma de `(precioUnitario * cantidad)` por cada `ItemCarrito`.
- `totalItems`: suma de `cantidad` de todos los ítems.

---

### 3.4 Producto

**Descripción:** Artículo disponible para la venta en el catálogo de la tienda.

#### 3.4.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `sku` | string | Sí | Único en el sistema; 4–20 caracteres alfanuméricos y guiones |
| `nombre` | string | Sí | 3–200 caracteres |
| `descripcion` | string | Sí | Texto enriquecido (HTML sanitizado); mín. 20 caracteres |
| `precio` | decimal | Sí | Mayor que 0; hasta 2 decimales |
| `precioDescuento` | decimal | No | Debe ser menor que `precio` si se provee; mayor que 0 |
| `categoriaId` | UUID | Sí | FK a `Categoria.id` |
| `activo` | boolean | Sí | Default: `true`; si `false`, no aparece en el catálogo público |
| `destacado` | boolean | Sí | Default: `false`; si `true`, aparece en secciones especiales |
| `esNuevo` | boolean | Sí | Default: `true`; se puede desactivar manualmente |
| `slug` | string | Sí | Único; generado desde `nombre`; usado en URLs amigables |
| `creadoEn` | datetime | Sí | Generado automáticamente |
| `actualizadoEn` | datetime | Sí | Actualizado en cada modificación |

#### 3.4.2 Invariantes y Reglas de Negocio

1. El `sku` es único e inmutable una vez publicado el producto.
2. El `slug` se genera automáticamente desde `nombre` (lowercase, espacios → guiones, caracteres especiales eliminados).
3. Si el `slug` generado ya existe, se le agrega un sufijo numérico incremental.
4. Si `activo === false`, el producto no aparece en búsquedas ni listados del catálogo público, pero sí en el panel de administración.
5. `precioDescuento` debe ser estrictamente menor que `precio`; de lo contrario, se rechaza.
6. Un producto no puede eliminarse si tiene pedidos asociados; se desactiva (`activo = false`) en su lugar.

#### 3.4.3 Atributos Calculados

- `tieneDescuento`: `precioDescuento !== null && precioDescuento < precio`
- `porcentajeDescuento`: si `tieneDescuento`, `Math.round((1 - precioDescuento / precio) * 100)`
- `stockDisponible`: derivado de `Inventario.cantidad`

#### 3.4.4 Relaciones

| Relación | Tipo | Entidad relacionada |
|---|---|---|
| `categoria` | N:1 | `Categoría` |
| `inventario` | 1:1 | `Inventario` |
| `imagenes` | 1:N | `Imagen` |

---

### 3.5 Categoría

**Descripción:** Clasificación jerárquica de productos que organiza el catálogo.

#### 3.5.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `nombre` | string | Sí | Único; 2–100 caracteres |
| `slug` | string | Sí | Único; generado desde `nombre` |
| `descripcion` | string | No | Máx. 500 caracteres |
| `imagenUrl` | string | No | URL de imagen representativa de la categoría |
| `categoriaPadreId` | UUID | No | FK a `Categoria.id`; permite jerarquía de dos niveles |
| `activa` | boolean | Sí | Default: `true`; si `false`, no aparece en el menú |
| `orden` | integer | No | Posición en el menú; menor número aparece primero |

#### 3.5.2 Invariantes y Reglas de Negocio

1. La jerarquía de categorías es de máximo 2 niveles (categoría padre → subcategoría).
2. Una categoría con subcategorías activas no puede desactivarse sin antes desactivar o reasignar sus subcategorías.
3. Una categoría no puede eliminarse si tiene productos activos asociados.
4. Las categorías inactivas no se muestran en el Navbar ni en filtros del catálogo.

---

### 3.6 Inventario

**Descripción:** Registro de stock disponible para cada producto. Gestiona la cantidad física en almacén.

#### 3.6.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `productoId` | UUID | Sí | FK a `Producto.id`; único (un inventario por producto) |
| `cantidad` | integer | Sí | Mínimo: 0; no puede ser negativo |
| `cantidadReservada` | integer | Sí | Default: 0; cantidad bloqueada en pedidos en proceso |
| `umbralAlerta` | integer | No | Si `cantidad <= umbralAlerta`, se notifica al admin |
| `actualizadoEn` | datetime | Sí | Actualizado en cada modificación |

#### 3.6.2 Invariantes y Reglas de Negocio

1. `cantidad` nunca puede ser negativa; cualquier operación que lo lleve por debajo de 0 es rechazada.
2. `cantidadDisponible = cantidad - cantidadReservada` es la cantidad real disponible para nuevas compras.
3. Al confirmar un pedido, la `cantidadReservada` aumenta y la `cantidad` se reduce al confirmar el envío.
4. Al cancelar un pedido, la `cantidadReservada` se libera y `cantidad` se restaura.
5. Si `cantidadDisponible === 0`, el producto se considera sin stock y no puede agregarse al carrito.

#### 3.6.3 Atributos Calculados

- `cantidadDisponible`: `cantidad - cantidadReservada`
- `enAlerta`: `cantidad <= umbralAlerta`

---

### 3.7 Imagen

**Descripción:** Recurso visual asociado a un producto. Un producto puede tener múltiples imágenes.

#### 3.7.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `productoId` | UUID | Sí | FK a `Producto.id` |
| `url` | string | Sí | URL absoluta; HTTPS obligatorio |
| `altText` | string | No | Texto alternativo para accesibilidad; máx. 200 caracteres |
| `esPrincipal` | boolean | Sí | Default: `false`; solo una imagen por producto puede ser `true` |
| `orden` | integer | No | Orden de aparición en la galería del producto |

#### 3.7.2 Invariantes y Reglas de Negocio

1. Solo una imagen por producto puede tener `esPrincipal === true`.
2. Un producto debe tener al menos 1 imagen para ser activado (`activo = true`).
3. Un producto puede tener máximo 10 imágenes.
4. Las imágenes se almacenan en el servicio de almacenamiento externo (CDN); la entidad solo guarda la URL.

---

### 3.8 Pedido

**Descripción:** Registro de una compra realizada por un usuario. Captura el estado de la transacción desde la creación hasta la entrega.

#### 3.8.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `numero` | string | Sí | Único; código legible generado por el sistema (ej. `ORD-2026-00123`) |
| `usuarioId` | UUID | Sí | FK a `Usuario.id` |
| `estado` | enum | Sí | Ver tabla de estados abajo |
| `subtotal` | decimal | Sí | Suma de `(precioUnitario * cantidad)` de todos los detalles; 2 decimales |
| `descuentoTotal` | decimal | Sí | Default: 0; monto de descuentos aplicados |
| `costoEnvio` | decimal | Sí | Default: 0; costo del envío |
| `total` | decimal | Sí | `subtotal - descuentoTotal + costoEnvio`; 2 decimales |
| `direccionEnvio` | JSON | Sí | Copia de la dirección al momento del pedido (inmutable) |
| `notas` | string | No | Instrucciones del cliente; máx. 500 caracteres |
| `creadoEn` | datetime | Sí | Generado automáticamente |
| `actualizadoEn` | datetime | Sí | Actualizado en cada cambio de estado |

#### 3.8.2 Estados del Pedido

| Estado | Descripción | Transiciones permitidas |
|---|---|---|
| `pendiente` | Pedido creado; esperando confirmación de pago | → `confirmado`, `cancelado` |
| `confirmado` | Pago recibido y validado | → `en_preparacion`, `cancelado` |
| `en_preparacion` | Pedido siendo preparado en almacén | → `enviado` |
| `enviado` | Pedido despachado al transportista | → `entregado` |
| `entregado` | Pedido recibido por el cliente | Estado final |
| `cancelado` | Pedido cancelado | Estado final |

#### 3.8.3 Invariantes y Reglas de Negocio

1. El `numero` de pedido es único e inmutable; generado por el sistema con el formato `ORD-{AÑO}-{SECUENCIA_5_DÍGITOS}`.
2. La `direccionEnvio` se copia como JSON al momento de crear el pedido; no se actualiza si el usuario modifica su dirección posteriormente.
3. El `total` siempre se calcula como `subtotal - descuentoTotal + costoEnvio`; nunca se acepta un total negativo.
4. Solo el administrador puede cambiar el estado del pedido; el usuario solo puede cancelar pedidos en estado `pendiente`.
5. Un pedido `entregado` o `cancelado` no puede volver a ningún estado anterior.
6. Al cancelar un pedido, el inventario reservado se libera automáticamente.

#### 3.8.4 Relaciones

| Relación | Tipo | Entidad relacionada |
|---|---|---|
| `detalles` | 1:N | `DetallePedido` |
| `pago` | 1:1 | `Pago` |

---

### 3.9 DetallePedido

**Descripción:** Línea individual de un pedido que representa la compra de un producto específico en una cantidad determinada.

#### 3.9.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `pedidoId` | UUID | Sí | FK a `Pedido.id` |
| `productoId` | UUID | Sí | FK a `Producto.id` |
| `nombreProducto` | string | Sí | Copia del nombre del producto al momento de la compra |
| `skuProducto` | string | Sí | Copia del SKU del producto al momento de la compra |
| `cantidad` | integer | Sí | Mínimo: 1 |
| `precioUnitario` | decimal | Sí | Precio del producto al momento de la compra; 2 decimales |
| `descuentoUnitario` | decimal | Sí | Default: 0; descuento aplicado por unidad |
| `subtotal` | decimal | Sí | `(precioUnitario - descuentoUnitario) * cantidad`; 2 decimales |

#### 3.9.2 Invariantes y Reglas de Negocio

1. `nombreProducto` y `skuProducto` se copian al crear el detalle; no se actualizan si el producto cambia posteriormente.
2. `precioUnitario` es el precio efectivo al momento de la compra (con o sin descuento según aplique).
3. El `subtotal` se recalcula automáticamente si se modifica `cantidad` (solo en estado `pendiente`).
4. Un `DetallePedido` no puede eliminarse; si hay un error, el pedido completo se cancela.

---

### 3.10 Pago

**Descripción:** Registro de la transacción financiera asociada a un pedido.

#### 3.10.1 Atributos

| Atributo | Tipo | Requerido | Restricciones |
|---|---|---|---|
| `id` | UUID | Sí | Inmutable |
| `pedidoId` | UUID | Sí | FK a `Pedido.id`; único (un pago por pedido) |
| `metodo` | enum | Sí | Valores: `tarjeta_credito`, `tarjeta_debito`, `transferencia`, `efectivo_contra_entrega` |
| `estado` | enum | Sí | Ver tabla de estados abajo |
| `monto` | decimal | Sí | Debe coincidir con `Pedido.total`; 2 decimales |
| `moneda` | string | Sí | Código ISO 4217 (ej. `MXN`, `USD`); 3 caracteres |
| `referenciaPasarela` | string | No | ID de transacción provisto por la pasarela de pago |
| `fechaPago` | datetime | No | Fecha y hora en que el pago fue confirmado |
| `creadoEn` | datetime | Sí | Generado automáticamente |

#### 3.10.2 Estados del Pago

| Estado | Descripción |
|---|---|
| `pendiente` | Pago iniciado; esperando confirmación de la pasarela |
| `aprobado` | Pago confirmado por la pasarela; fondos capturados |
| `rechazado` | Pago rechazado por la pasarela (fondos insuficientes, datos inválidos) |
| `reembolsado` | Fondos devueltos al cliente (tras cancelación de pedido) |

#### 3.10.3 Invariantes y Reglas de Negocio

1. El `monto` del pago debe ser exactamente igual a `Pedido.total`; diferencias de cualquier valor son rechazadas.
2. Al transicionar a `aprobado`, el pedido asociado pasa automáticamente a estado `confirmado`.
3. Al transicionar a `rechazado`, el pedido permanece en `pendiente` para permitir un nuevo intento.
4. Un pago `aprobado` solo puede pasar a `reembolsado`; nunca a `rechazado`.
5. El `referenciaPasarela` es inmutable una vez registrado.

---

## 4. Restricciones Globales del Modelo

1. Todos los `id` son UUID v4 generados por el sistema; nunca por el cliente.
2. Los campos `creadoEn` son inmutables; solo el sistema los asigna en la creación.
3. Los campos `actualizadoEn` son gestionados automáticamente por el ORM/repositorio.
4. Ninguna entidad se elimina físicamente si tiene relaciones activas; se aplica soft-delete o desactivación.
5. Las operaciones monetarias (`precio`, `total`, `monto`) usan tipo `DECIMAL(10, 2)` en base de datos para evitar errores de punto flotante.

---

## 5. Índices Recomendados

| Tabla | Campo(s) | Tipo de Índice | Justificación |
|---|---|---|---|
| `Usuario` | `email` | UNIQUE | Búsqueda y unicidad en autenticación |
| `Producto` | `slug` | UNIQUE | URLs amigables |
| `Producto` | `sku` | UNIQUE | Búsqueda por código |
| `Producto` | `categoriaId` | INDEX | Filtrado por categoría |
| `Producto` | `activo` | INDEX | Filtrado de catálogo público |
| `Pedido` | `usuarioId` | INDEX | Historial de pedidos por usuario |
| `Pedido` | `estado` | INDEX | Filtrado por estado en admin |
| `Pedido` | `numero` | UNIQUE | Búsqueda por número de pedido |
| `DetallePedido` | `pedidoId` | INDEX | Carga de detalles de un pedido |
| `ItemCarrito` | `carritoId` | INDEX | Carga del carrito activo |

---

## 6. Consideraciones de Integración

| Entidad | Integra con | Descripción |
|---|---|---|
| `Usuario` | Módulo de Autenticación | Gestión de sesión, JWT, verificación de email |
| `Inventario` | Módulo de Carrito | Validación de stock al agregar ítems |
| `Pedido` | Módulo de Notificaciones | Email de confirmación en cada cambio de estado |
| `Pago` | Pasarela de Pago Externa | Webhook de confirmación; referencia de transacción |
| `Imagen` | Servicio CDN/Almacenamiento | Carga y gestión de archivos de imagen |
| `Producto` | Motor de Búsqueda | Indexación para búsqueda full-text |
