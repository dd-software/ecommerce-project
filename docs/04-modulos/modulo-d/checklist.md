# Checklist

## Proyecto

**Plataforma E-commerce con Gestión de Inventarios y Pagos**

## Módulo

**Módulo D - Checkout**

## Estado General

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

---

# Funcionalidad de Checkout

## Inicio del Proceso de Compra

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] El usuario puede iniciar el checkout desde el carrito.
* [ ] El sistema recupera correctamente los productos seleccionados.
* [ ] El checkout solo puede iniciarse con productos en el carrito.
* [ ] Se muestran los datos del pedido antes de la confirmación.

---

## Validación de Inventario

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Se valida la disponibilidad de todos los productos.
* [ ] Los productos sin stock son detectados correctamente.
* [ ] Se informa al usuario cuando existe falta de inventario.
* [ ] No se permite continuar con productos no disponibles.

---

## Cálculo de Totales

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Se calcula correctamente el subtotal.
* [ ] Se calculan correctamente impuestos y cargos adicionales.
* [ ] El total final es correcto.
* [ ] Los montos coinciden con la información presentada al usuario.

---

## Validación de Datos de Entrega

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Los campos obligatorios son validados.
* [ ] Se detectan datos inválidos.
* [ ] El usuario puede corregir errores.
* [ ] Solo se aceptan datos válidos.

---

## Generación de Orden

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Se genera una orden preliminar correctamente.
* [ ] La orden contiene los productos seleccionados.
* [ ] Los montos almacenados son correctos.
* [ ] La orden queda disponible para el proceso de pago.

---

## API de Checkout

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Endpoint

```http
GET /api/checkout
```

### Verificaciones

* [ ] Retorna HTTP 200 cuando la operación es exitosa.
* [ ] Retorna una respuesta JSON válida.
* [ ] Cumple el contrato API definido.
* [ ] El campo `success` es retornado correctamente.

### Respuesta Esperada

```json
{
  "success": true
}
```

---

# Integraciones

## Integración con Carrito

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Se recuperan correctamente los productos del carrito.
* [ ] Los cambios del carrito son reflejados en el checkout.

---

## Integración con Inventario

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] El stock es validado correctamente.
* [ ] Los cambios de inventario son reflejados durante el checkout.

---

## Integración con Pagos

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] La orden generada puede ser enviada al módulo de pagos.
* [ ] Los montos enviados son correctos.
* [ ] Se mantiene la consistencia de la información.

---

# Seguridad

## Protección de Datos

* [ ] Implementado
* [ ] Probado
* [ ] Documentado

### Verificaciones

* [ ] Uso obligatorio de HTTPS.
* [ ] Protección de información sensible.
* [ ] Validación de entradas.
* [ ] Registro de eventos relevantes.

---

# Calidad

## Testing

* [ ] Pruebas unitarias ejecutadas.
* [ ] Pruebas de integración ejecutadas.
* [ ] Pruebas funcionales ejecutadas.
* [ ] Pruebas de aceptación ejecutadas.
* [ ] Pruebas de seguridad ejecutadas.

---

## Resultados

* [ ] Sin defectos críticos.
* [ ] Sin defectos de alta prioridad.
* [ ] Cobertura mínima alcanzada.
* [ ] Criterios de aceptación aprobados.

---

# Documentación

* [ ] API Contract actualizado.
* [ ] Casos de Uso actualizados.
* [ ] Historias de Usuario actualizadas.
* [ ] Especificación Técnica actualizada.
* [ ] Evidencias de pruebas almacenadas.

---

# Aprobación Final

| Rol           | Estado |
| ------------- | ------ |
| Desarrollador | ☐      |
| QA            | ☐      |
| Líder Técnico | ☐      |
| Product Owner | ☐      |

---

# Resultado

El módulo de checkout será considerado completado cuando:

* [ ] Todas las funcionalidades estén implementadas.
* [ ] Todas las pruebas sean exitosas.
* [ ] Toda la documentación esté actualizada.
* [ ] Los criterios de aceptación estén aprobados.
* [ ] No existan incidencias críticas abiertas.
* [ ] Las integraciones funcionen correctamente.
