# SDD - Plataforma E-commerce

## 1. Introducción

### 1.1 Propósito

Este documento describe el diseño de software de una plataforma e-commerce orientada a la gestión y visualización de productos mediante servicios REST.

### 1.2 Alcance

La plataforma permitirá:

* Consultar el catálogo de productos.
* Visualizar información de productos.
* Integrarse con clientes web y móviles.
* Exponer servicios mediante API REST.

### 1.3 Definiciones

| Término  | Descripción                              |
| -------- | ---------------------------------------- |
| API      | Interfaz de Programación de Aplicaciones |
| REST     | Arquitectura para servicios web          |
| JSON     | Formato de intercambio de datos          |
| Catálogo | Conjunto de productos disponibles        |

---

## 2. Arquitectura del Sistema

### 2.1 Arquitectura General

La solución seguirá una arquitectura de tres capas:

```text
+-------------------+
| Cliente Web/Móvil |
+---------+---------+
          |
          v
+-------------------+
| API REST Backend  |
+---------+---------+
          |
          v
+-------------------+
| Base de Datos     |
+-------------------+
```

### 2.2 Componentes

#### Frontend

Responsabilidades:

* Mostrar catálogo de productos.
* Consumir servicios REST.
* Gestionar navegación de usuarios.

#### Backend

Responsabilidades:

* Exponer endpoints REST.
* Procesar solicitudes.
* Gestionar lógica de negocio.

#### Base de Datos

Responsabilidades:

* Almacenar productos.
* Mantener información del catálogo.

---

## 3. Diseño de API

### 3.1 Obtener Catálogo

#### Endpoint

```http
GET /api/catalogo
```

#### Descripción

Permite consultar el catálogo disponible en la plataforma.

#### Request

No requiere parámetros.

#### Response

```json
{
  "success": true
}
```

#### Código de Respuesta

| Código | Descripción                |
| ------ | -------------------------- |
| 200    | Operación exitosa          |
| 500    | Error interno del servidor |

---

## 4. Requisitos Funcionales

### RF-01 Consultar Catálogo

**Descripción:**

El sistema debe permitir obtener el catálogo de productos mediante un servicio REST.

**Prioridad:** Alta

**Entradas:**

* Solicitud GET `/api/catalogo`

**Salidas:**

```json
{
  "success": true
}
```

---

## 5. Requisitos No Funcionales

### RNF-01 Disponibilidad

El sistema deberá mantener una disponibilidad mínima del 99%.

### RNF-02 Rendimiento

Las consultas al catálogo deberán responder en menos de 2 segundos.

### RNF-03 Escalabilidad

La arquitectura deberá soportar crecimiento horizontal del backend.

### RNF-04 Seguridad

Las comunicaciones deberán realizarse mediante HTTPS.

---

## 6. Modelo de Datos

### Entidad Producto

| Campo       | Tipo    |
| ----------- | ------- |
| id          | Integer |
| nombre      | String  |
| descripcion | String  |
| precio      | Decimal |
| stock       | Integer |
| imagen      | String  |

---

## 7. Consideraciones de Diseño

* Uso de arquitectura REST.
* Formato JSON para intercambio de datos.
* Separación de responsabilidades entre frontend y backend.
* Posibilidad de integración futura con módulos de pagos y gestión de usuarios.

---

## 8. Futuras Extensiones

* Registro e inicio de sesión.
* Carrito de compras.
* Pasarela de pagos.
* Gestión de órdenes.
* Administración de inventario.
* Sistema de recomendaciones.

## 9. Conclusión

La plataforma e-commerce proporciona una base para la gestión y consulta de productos mediante una API REST simple y escalable, permitiendo futuras ampliaciones orientadas al comercio electrónico completo.

