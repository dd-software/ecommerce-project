# 📦 Ecommerce UCT — Especificaciones v2.0

**Plataforma de comercio electrónico con pasarela de pago (PayPal) + gestión de inventario**

> Repositorio maestro de especificaciones para el proyecto integrador de **Diseño y Desarrollo de Software + IA**
> Metodología: **SDD (Specification Driven Development)** | Arquitectura: **Módulos A–H**

---

## 🎯 Objetivo Educativo

Este documento de especificaciones ha sido diseñado con un **propósito didáctico** para que estudiantes de ingeniería puedan:

- Comprender cómo se construye un sistema desde cero partiendo de especificaciones formales
- Implementar cada módulo siguiendo contratos API bien definidos
- Aprender integración con servicios externos (PayPal)
- Practicar gestión de inventario con reservas, expiración y trazabilidad
- Trabajar en equipos paralelos con interfaces claras entre módulos

---

## 📑 Índice de Especificaciones

| # | Carpeta | Documento | Descripción |
|---|---------|-----------|-------------|
| 1 | `00-vision-producto/` | `vision-producto.md` | Visión del producto, stakeholders, objetivos |
| 2 | `01-reglas-negocio/` | `reglas-negocio.md` | 30+ reglas de negocio detalladas |
| 3 | `02-arquitectura/` | `arquitectura-general.md` | Arquitectura general, stack tecnológico, diagramas |
| 4 | `03-modelo-dominio/` | `modelo-dominio.md` | Entidades, atributos, relaciones, restricciones |
| 5 | `04-contratos-api/` | 8 archivos | Contratos API detallados por módulo |
| 6 | `05-diseno-bd/` | `esquema-bd.md` | Diseño de base de datos completo con explicaciones |
| 7 | `06-seguridad/` | `especificacion-seguridad.md` | Checklist OWASP y políticas de seguridad |
| 8 | `07-pasarela-pago/` | `integracion-paypal.md` | Integración con PayPal, sandbox, webhooks |
| 9 | `08-inventario/` | `gestion-inventario.md` | Sistema de inventario con reservas y alertas |
| 10 | `09-flujos/` | `flujo-compra.md` | Diagramas de flujo del proceso de compra completo |
| 11 | `10-ui-ux/` | `especificacion-ui.md` | Design system, componentes, pantallas |
| 12 | `11-planificacion-equipos/` | `planificacion-modulos.md` | Asignación de equipos, dependencias, sprints |

---

## 🧩 Arquitectura Modular

```
┌─────────────────────────────────────────────────────┐
│                     FRONTEND                         │
│         HTML5 · CSS3 · Bootstrap 5.3 · JS            │
├─────────────────────────────────────────────────────┤
│  ┌──────────┐  ┌─────────┐  ┌───────────────────┐   │
│  │ Módulo A │  │ Módulo B │  │    Módulo C       │   │
│  │ Catálogo │  │ Carrito  │  │ Autenticación     │   │
│  └──────────┘  └─────────┘  └───────────────────┘   │
│  ┌──────────┐  ┌─────────┐  ┌───────────────────┐   │
│  │ Módulo D │  │ Módulo E │  │    Módulo F       │   │
│  │ Checkout │  │ PayPal   │  │ Inventario        │   │
│  └──────────┘  └─────────┘  └───────────────────┘   │
│  ┌──────────┐  ┌─────────┐                           │
│  │ Módulo G │  │ Módulo H │                           │
│  │ Admin    │  │Integración│                          │
│  └──────────┘  └─────────┘                           │
├─────────────────────────────────────────────────────┤
│               BACKEND · PHP 8 REST API                │
│         PDO · MySQL 8 · JWT · Prepared Statements      │
├─────────────────────────────────────────────────────┤
│               PAYPAL REST API (Sandbox)                │
└─────────────────────────────────────────────────────┘
```

---

## 🔗 Dependencias entre Módulos

```
A (Catálogo) ──→ independiente
B (Carrito) ──→ A (lee productos), F (valida stock)
C (Auth) ────→ independiente
D (Checkout) ──→ B (lee carrito), F (reserva stock), C (sesión)
E (PayPal) ────→ D (recibe orden), F (confirma descuento)
F (Inventario) ──→ A (informa stock)
G (Admin) ────→ A, B, C, D, E, F (gestión transversal)
H (Integración) ──→ D, E, F (orquestación)
```

---

> **Metodología:** Los estudiantes trabajan en equipos (1 por módulo). Cada equipo implementa su módulo siguiendo los contratos API. El Módulo H (Integración) conecta todo.
