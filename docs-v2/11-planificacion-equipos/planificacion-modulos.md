# Planificación para Equipos

> **Propósito educativo:** Este documento organiza el trabajo en equipos paralelos siguiendo la metodología SDD + Scrum. Cada equipo implementa un módulo siguiendo los contratos API.

---

## 1. Equipos y Asignación

| Módulo | Equipo | Descripción | Depende de |
|--------|--------|-------------|------------|
| **A** - Catálogo | Team 1 | Productos, categorías, imágenes, búsqueda | — |
| **B** - Carrito | Team 2 | CRUD carrito, fusionar, cálculo totales | Módulo A, F |
| **C** - Autenticación | Team 3 | Login, registro, sesiones, roles | — |
| **D** - Checkout | Team 4 | Crear orden, validar, resumen | Módulo B, F, C |
| **E** - PayPal | Team 5 | Integración PayPal, webhooks | Módulo D |
| **F** - Inventario | Team 6 | Stock, reservas, movimientos, alertas | Módulo A |
| **G** - Admin | Team 7 | Dashboard, CRUDs, auditoría, config | Todos los módulos |
| **H** - Integración | Todos | Orquestar flujo completo | Todos los módulos |

---

## 2. Dependencias por Sprint

### Sprint 1: Base (2 semanas)
| Equipo | Tareas | Entregable |
|--------|--------|------------|
| **A** | Catálogo: listar, detalle, categorías, búsqueda | API catálogo funcional |
| **C** | Auth: login, registro, sesiones, logout | API auth funcional |
| **Core** | Router, Database, Response, View, Env | Framework base listo |
| **BD** | Schema completo con seed data | Base de datos creada |

### Sprint 2: Carrito + Inventario (2 semanas)
| Equipo | Tareas | Entregable |
|--------|--------|------------|
| **A** | Frontend catálogo (HTML + Bootstrap) | Catálogo visual |
| **B** | Carrito: agregar, ver, eliminar, actualizar | API carrito funcional |
| **F** | Inventario: consultar, validar, reservar, liberar | API inventario funcional |
| **C** | Frontend auth (login/registro forms) | Login/registro visual |

### Sprint 3: Compra + Pago (2 semanas)
| Equipo | Tareas | Entregable |
|--------|--------|------------|
| **B** | Fusionar carrito, frontend carrito | Carrito visual completo |
| **D** | Checkout: crear orden, procesar, validar stock | API checkout funcional |
| **E** | PayPal: crear orden, capturar, confirmar | API pago PayPal funcional |
| **F** | Frontend stock en catálogo | Stock visible en cards |

### Sprint 4: Administración + Integración (2 semanas)
| Equipo | Tareas | Entregable |
|--------|--------|------------|
| **D+E** | Webhook PayPal, confirmación asíncrona | Flujo pago completo |
| **D+E+F** | Transacción completa: orden → pago → descuento | Flujo E2E funcional |
| **G** | Dashboard, CRUD productos, usuarios, pedidos | Admin funcional |
| **G** | Auditoría, configuración, alertas stock | Admin completo |
| **H** | Orquestación: health check, integración módulos | Todo conectado |

---

## 3. Sprint Backlog Detallado

### Sprint 1 — Tareas por Equipo

**Team 1 (Catálogo):**
1. Implementar `GET /api/catalogo` con paginación (12/page)
2. Implementar `GET /api/producto/{slug}` con imágenes
3. Implementar `GET /api/categorias` con subcategorías
4. Implementar `GET /api/productos/destacados`
5. Búsqueda FULLTEXT en nombre y descripción
6. Vista HTML del catálogo (cards con Bootstrap)
7. Vista HTML del detalle de producto (galería)
8. Filtro por categoría y ordenamiento

**Team 3 (Auth):**
1. Implementar `POST /api/auth/registro` con validaciones
2. Implementar `POST /api/auth/login` con bcrypt
3. Implementar `GET /api/auth/status`
4. Implementar `POST /api/auth/logout`
5. Rate limiting: 5 intentos, 15 min lockout
6. Session management: regenerar ID, cookies seguras
7. Vista HTML login y registro

**Core Team:**
1. Implementar Router con regex para slugs
2. Implementar Database singleton con PDO
3. Implementar Request con sanitización
4. Implementar Response (JSON + HTML helpers)
5. Implementar Session con auth helpers
6. Implementar View con layout y componentes
7. Implementar Env parser
8. Implementar Logger con rotación diaria
9. Implementar Validator (email, uuid, precio, password)
10. Implementar Auditoria (log de eventos)

### Sprint 2 — Tareas por Equipo

**Team 2 (Carrito):**
1. Implementar `GET /api/carrito` con resumen
2. Implementar `POST /api/carrito/agregar` con validación stock
3. Implementar `POST /api/carrito/actualizar` (cantidad)
4. Implementar `POST /api/carrito/eliminar`
5. Implementar `POST /api/carrito/vaciar`
6. Implementar `POST /api/carrito/fusionar` (localStorage → servidor)
7. Cálculo de totales: subtotal + IVA(19%) + envío($4.990)
8. Vista HTML del carrito

**Team 6 (Inventario):**
1. Implementar `GET /api/inventario/{productoId}`
2. Implementar `POST /api/inventario/validar` (1 y N productos)
3. Implementar `POST /api/inventario/reservar` con expiración 30 min
4. Implementar `POST /api/inventario/liberar`
5. Implementar `POST /api/inventario/confirmar` (descuento definitivo)
6. Implementar cron job de expiración de reservas
7. Alertas de stock bajo

### Sprint 3 — Tareas por Equipo

**Team 4 (Checkout):**
1. Implementar `GET /api/checkout` (resumen + direcciones)
2. Implementar `POST /api/checkout/procesar` (transaccional)
3. Validación de stock antes de crear orden
4. Generación de número de orden: ORD-YYYY-NNNNN
5. Reserva de inventario al crear orden
6. Vista HTML del checkout
7. Vista HTML de éxito/cancelación

**Team 5 (PayPal):**
1. Configurar cuenta PayPal Developer (sandbox)
2. Obtener y cachear access token
3. Implementar creación de orden PayPal
4. Implementar captura de orden PayPal
5. Implementar endpoint de confirmación (return URL)
6. Implementar webhook handler
7. Verificación de firma de webhook
8. Manejo de errores: rechazo, expiración, duplicados

### Sprint 4 — Tareas por Equipo

**Team 7 (Admin):**
1. Dashboard con métricas (productos, pedidos, usuarios, ingresos)
2. CRUD de productos (crear, editar, desactivar)
3. CRUD de categorías (con protección de integridad)
4. Gestión de usuarios (listar, toggle activo)
5. Gestión de pedidos (cambiar estado con máquina de estados)
6. Gestión de inventario (listar, ajustar stock)
7. Auditoría (filtros por módulo/fecha)
8. Configuración del sistema (CRUD clave-valor)
9. Alertas de stock bajo en dashboard
10. Vue admin layout con sidebar

---

## 4. Criterios de Aceptación por Historia de Usuario

Cada historia de usuario debe cumplir:

| Criterio | Descripción |
|----------|-------------|
| **CR-01** | Responde con JSON válido según contrato API |
| **CR-02** | Responde con código HTTP correcto (200, 201, 400, 401, 403, 404, 409) |
| **CR-03** | Valida todas las entradas (server-side) |
| **CR-04** | Usa prepared statements (sin SQL injection) |
| **CR-05** | Verifica permisos si el endpoint es protegido |
| **CR-06** | Registra auditoría si la operación es una mutación |
| **CR-07** | Maneja errores gracefulmente (try-catch, mensaje amigable) |
| **CR-08** | Tiene al menos 1 test funcional en `tests/` |

---

## 5. Formato de Commits

```
[Modulo] Tipo: Descripción breve

Ejemplos:
[Catalogo] feat: agregar filtro por categoría con paginación
[Carrito] fix: validar stock al actualizar cantidad
[Auth] security: implementar rate limiting en login
[Core] refactor: extraer validación UUID a Validator
[Admin] feat: dashboard con métricas del mes
[PayPal] feat: integración con webhook de confirmación
[Inventario] test: test de reserva y liberación de stock
```

**Tipos:** `feat`, `fix`, `refactor`, `security`, `test`, `docs`, `style`

---

## 6. Definición de Hecho (Definition of Done)

Para que una historia se considere COMPLETA:

- [ ] Código implementado según especificación
- [ ] Contrato API respeta el formato definido
- [ ] Validaciones server-side implementadas
- [ ] Errores manejados con try-catch
- [ ] Auditoría registrada (si aplica)
- [ ] Prueba manual exitosa (al menos 1 escenario feliz)
- [ ] Sin errores de sintaxis PHP (comprobado con `php -l`)
- [ ] Pull Request creado con descripción de cambios
- [ ] Code review realizado por otro equipo
