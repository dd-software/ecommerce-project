# Casos de Uso

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

---

# CU-D-001 Flujo Principal de Checkout

## Objetivo

Permitir que el usuario finalice el proceso de compra validando los productos seleccionados, la disponibilidad de inventario, la información de entrega y los datos necesarios para el pago.

---

## Actores

### Actor Principal

* Usuario

### Actores Secundarios

* Sistema de Checkout
* Módulo de Carrito
* Módulo de Inventario
* Módulo de Pagos
* Sistema de Auditoría

---

## Descripción

El usuario inicia el proceso de checkout para revisar su pedido y confirmar la compra. El sistema valida la información requerida y prepara la orden para el procesamiento del pago.

---

## Precondiciones

1. El usuario posee una sesión válida.
2. Existe al menos un producto en el carrito.
3. Los productos están activos.
4. Los servicios dependientes se encuentran disponibles.

---

## Disparador

El usuario selecciona la opción **Finalizar Compra** desde el carrito.

---

## Flujo Principal

| Paso | Acción                                                         |
| ---- | -------------------------------------------------------------- |
| 1    | El usuario inicia el proceso de checkout.                      |
| 2    | El sistema recupera los productos del carrito.                 |
| 3    | El sistema valida la disponibilidad de inventario.             |
| 4    | El sistema calcula subtotales, impuestos y total de la compra. |
| 5    | El usuario confirma la información de entrega.                 |
| 6    | El sistema valida los datos ingresados.                        |
| 7    | El sistema genera una orden preliminar.                        |
| 8    | El sistema registra el evento de checkout.                     |
| 9    | La orden queda lista para el procesamiento del pago.           |

---

## Flujos Alternativos

### FA-001 Producto Sin Stock

| Paso | Acción                                                |
| ---- | ----------------------------------------------------- |
| 1    | Durante la validación se detecta falta de inventario. |
| 2    | El sistema informa el problema al usuario.            |
| 3    | Se solicita actualizar el carrito.                    |
| 4    | El proceso de checkout se detiene.                    |

---

### FA-002 Información de Entrega Inválida

| Paso | Acción                                                    |
| ---- | --------------------------------------------------------- |
| 1    | El usuario proporciona información incompleta o inválida. |
| 2    | El sistema muestra errores de validación.                 |
| 3    | El usuario corrige la información.                        |
| 4    | El proceso continúa.                                      |

---

## Flujos de Excepción

### FE-001 Error de Inventario

| Paso | Acción                                                                 |
| ---- | ---------------------------------------------------------------------- |
| 1    | El sistema consulta disponibilidad de productos.                       |
| 2    | El servicio de inventario no responde.                                 |
| 3    | Se registra el incidente.                                              |
| 4    | Se informa al usuario que el proceso no puede continuar temporalmente. |

---

### FE-002 Error de Generación de Orden

| Paso | Acción                                     |
| ---- | ------------------------------------------ |
| 1    | El sistema intenta crear la orden.         |
| 2    | Ocurre un error interno.                   |
| 3    | El incidente es registrado.                |
| 4    | Se muestra un mensaje de error controlado. |

---

## Postcondiciones

### Éxito

* La orden preliminar ha sido creada.
* La información de compra ha sido validada.
* El proceso queda preparado para el pago.
* El evento queda registrado.

### Fallo

* No se genera la orden.
* No se inicia el proceso de pago.
* El incidente queda registrado.

---

## Reglas de Negocio

### RN-CHK-001

El checkout solo puede iniciarse con productos en el carrito.

### RN-CHK-002

Todos los productos deben tener inventario disponible.

### RN-CHK-003

La información de entrega debe ser válida antes de generar la orden.

### RN-CHK-004

Los montos deben calcularse correctamente antes del pago.

### RN-CHK-005

Toda actividad relevante debe registrarse para auditoría.

---

## Prioridad

Alta

---

## Frecuencia de Uso

Alta

---

## Requisitos Relacionados

| Código     | Descripción                     |
| ---------- | ------------------------------- |
| RF-CHK-001 | Validar contenido del carrito   |
| RF-CHK-002 | Verificar inventario disponible |
| RF-CHK-003 | Calcular totales de compra      |
| RF-CHK-004 | Validar datos de entrega        |
| RF-CHK-005 | Generar orden preliminar        |

---

## API Relacionada

### Verificación del Servicio de Checkout

```http
GET /api/checkout
```

### Respuesta

```json
{
  "success": true
}
```

---

## Criterios de Aceptación

* El usuario puede iniciar el checkout desde el carrito.
* Se valida correctamente el inventario.
* Los cálculos de compra son correctos.
* La información de entrega es validada.
* Se genera una orden preliminar válida.
* Los errores son manejados de forma controlada.
* El servicio cumple el contrato API definido.

---

## Dependencias

* Módulo de Autenticación.
* Módulo de Carrito.
* Módulo de Inventario.
* Módulo de Pagos.
* Base de Datos de Órdenes.

---

## Trazabilidad

| Artefacto               | Referencia              |
| ----------------------- | ----------------------- |
| API Contract            | api-contract.md         |
| Especificación          | spec.md                 |
| Historias de Usuario    | user-stories.md         |
| Testing                 | testing.md              |
| Checklist               | checklist.md            |
| Criterios de Aceptación | criterios-aceptacion.md |
| Riesgos                 | riesgos.md              |

---

## Estado

**Versión:** 1.0

**Estado:** En Diseño

**Aprobación:** Pendiente
