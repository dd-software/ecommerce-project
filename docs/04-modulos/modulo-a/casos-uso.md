# Casos de Uso - Plataforma E-commerce

## Información del Documento

| Campo       | Valor                             |
| ----------- | --------------------------------- |
| Proyecto    | Plataforma E-commerce             |
| Versión     | 1.0                               |
| Documento   | Casos de Uso                      |
| Metodología | Software Design Description (SDD) |

---

# CU-A-001 Flujo Principal de Catálogo

## Objetivo

Permitir a los clientes visualizar y explorar el catálogo de productos disponibles para su compra.

## Actores

### Actor Principal

* Cliente

### Actores Secundarios

* Sistema de Inventario
* Base de Datos

---

## Descripción

El cliente accede al catálogo de productos para consultar los artículos disponibles, sus características, precios y disponibilidad en inventario.

---

## Precondiciones

1. La plataforma se encuentra operativa.
2. Existen productos registrados en el sistema.
3. El servicio de catálogo está disponible.

---

## Disparador

El cliente ingresa a la sección "Catálogo" desde la tienda virtual.

---

## Flujo Principal

| Paso | Acción                                                 |
| ---- | ------------------------------------------------------ |
| 1    | El cliente accede al catálogo.                         |
| 2    | El sistema solicita la lista de productos disponibles. |
| 3    | El módulo de inventario verifica disponibilidad.       |
| 4    | El sistema recupera la información de productos.       |
| 5    | El sistema muestra el catálogo al cliente.             |
| 6    | El cliente navega entre productos.                     |
| 7    | El cliente selecciona un producto para ver detalles.   |
| 8    | El sistema muestra información detallada del producto. |

---

## Flujos Alternativos

### FA-001 Catálogo sin productos

| Paso | Acción                                                                    |
| ---- | ------------------------------------------------------------------------- |
| 1    | El sistema consulta productos disponibles.                                |
| 2    | No existen productos registrados.                                         |
| 3    | El sistema muestra un mensaje indicando que no hay productos disponibles. |

---

### FA-002 Producto sin stock

| Paso | Acción                                         |
| ---- | ---------------------------------------------- |
| 1    | El cliente consulta un producto.               |
| 2    | El sistema detecta stock igual a cero.         |
| 3    | El sistema muestra el producto como "Agotado". |

---

## Flujos de Excepción

### FE-001 Error de conexión

| Paso | Acción                                     |
| ---- | ------------------------------------------ |
| 1    | El sistema intenta obtener el catálogo.    |
| 2    | Ocurre un error de comunicación.           |
| 3    | El sistema registra el incidente.          |
| 4    | Se muestra un mensaje de error al cliente. |

---

## Postcondiciones

1. El catálogo es mostrado correctamente.
2. La disponibilidad de productos es visible para el cliente.
3. No se realizan modificaciones sobre el inventario.

---

## Reglas de Negocio

### RN-001

Solo se mostrarán productos activos.

### RN-002

Los productos agotados deben identificarse visualmente.

### RN-003

Los precios mostrados deben corresponder al valor vigente.

### RN-004

La disponibilidad deberá sincronizarse con el inventario en tiempo real.

---

## Prioridad

Alta

---

## Frecuencia de Uso

Alta

---

## Requisitos Relacionados

* RF-001 Consultar catálogo.
* RF-002 Visualizar detalle de producto.
* RF-003 Consultar disponibilidad de inventario.

---

## APIs Relacionadas

### Obtener Catálogo

```http
GET /api/catalogo
```

#### Respuesta

```json
{
  "success": true
}
```

---

## Criterios de Aceptación

* El catálogo debe cargarse en menos de 2 segundos.
* Deben mostrarse únicamente productos activos.
* Debe visualizarse el stock disponible.
* Los errores deben notificarse adecuadamente al usuario.
