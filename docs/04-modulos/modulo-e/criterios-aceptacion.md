# Criterios de Aceptación

## Checklist Funcional del Módulo E

### Generación de Reportes

* [ ] El sistema permite generar reportes de ventas.
* [ ] El sistema permite generar reportes de pedidos.
* [ ] El sistema permite generar reportes de inventario.
* [ ] El sistema permite generar reportes de clientes registrados.
* [ ] El sistema muestra información actualizada al momento de la consulta.
* [ ] El usuario puede visualizar reportes directamente en la plataforma.
* [ ] El sistema presenta los datos de forma clara y estructurada.
* [ ] Los reportes incluyen fecha y hora de generación.

---

### Filtros y Consultas

* [ ] El usuario puede filtrar reportes por rango de fechas.
* [ ] El usuario puede filtrar reportes por estado de pedido.
* [ ] El usuario puede filtrar reportes por producto.
* [ ] El usuario puede filtrar reportes por categoría de producto.
* [ ] El sistema valida correctamente los parámetros de búsqueda.
* [ ] El sistema muestra mensajes cuando no existen resultados.

---

### Reportes de Ventas

* [ ] El sistema calcula correctamente el total de ventas.
* [ ] El sistema muestra cantidad de pedidos realizados.
* [ ] El sistema identifica los productos más vendidos.
* [ ] El sistema muestra ingresos por período seleccionado.
* [ ] El sistema permite consultar tendencias de ventas históricas.

---

### Reportes de Inventario

* [ ] El sistema muestra el stock actual de cada producto.
* [ ] El sistema identifica productos con bajo inventario.
* [ ] El sistema muestra movimientos de entrada y salida de stock.
* [ ] El sistema permite consultar historial de inventario.
* [ ] El sistema refleja cambios realizados en tiempo real.

---

### Reportes de Clientes

* [ ] El sistema muestra cantidad total de clientes registrados.
* [ ] El sistema identifica clientes con mayor volumen de compras.
* [ ] El sistema presenta historial de compras por cliente.
* [ ] El sistema permite consultar información consolidada de clientes.

---

### Exportación de Información

* [ ] El usuario puede exportar reportes en formato PDF.
* [ ] El usuario puede exportar reportes en formato Excel/CSV.
* [ ] Los archivos exportados contienen la información visualizada.
* [ ] El sistema conserva la estructura y formato de los datos exportados.
* [ ] El proceso de exportación finaliza sin errores.

---

### Seguridad y Control de Acceso

* [ ] Solo usuarios autorizados pueden acceder a los reportes.
* [ ] El sistema valida permisos antes de generar información.
* [ ] El sistema registra accesos a reportes críticos.
* [ ] Los datos sensibles son mostrados únicamente a perfiles autorizados.
* [ ] El sistema protege la información contra accesos no autorizados.

---

### Rendimiento

* [ ] Los reportes se generan dentro de tiempos aceptables.
* [ ] El sistema mantiene estabilidad ante grandes volúmenes de datos.
* [ ] Las consultas no afectan significativamente el rendimiento general del sistema.
* [ ] El sistema gestiona correctamente consultas concurrentes.

---

## Pruebas de Aceptación

### Escenario 1: Generación Exitosa de Reporte de Ventas

**Dado** que existen ventas registradas en el sistema
**Cuando** el administrador solicita un reporte de ventas
**Entonces** el sistema genera y muestra la información correctamente.

### Escenario 2: Filtrado por Fechas

**Dado** que existen registros históricos
**Cuando** el usuario selecciona un rango de fechas válido
**Entonces** el sistema muestra únicamente los datos correspondientes al período indicado.

### Escenario 3: Exportación de Reporte

**Dado** que un reporte ha sido generado
**Cuando** el usuario selecciona exportar a PDF o Excel
**Entonces** el sistema descarga el archivo con la información correcta.

### Escenario 4: Acceso No Autorizado

**Dado** que un usuario no posee permisos administrativos
**Cuando** intenta acceder al módulo de reportes
**Entonces** el sistema deniega el acceso y registra el intento.

### Escenario 5: Consulta de Inventario

**Dado** que existen productos registrados
**Cuando** el administrador genera un reporte de inventario
**Entonces** el sistema muestra cantidades actualizadas y alertas de stock bajo.

---

## Criterio de Aprobación del Módulo

El módulo será considerado aceptado cuando:

* El 100% de las funcionalidades definidas hayan sido implementadas.
* Todos los reportes generen información consistente y verificable.
* Los filtros funcionen correctamente en todos los escenarios previstos.
* Las exportaciones se realicen sin pérdida de información.
* No existan errores críticos pendientes.
* Las pruebas funcionales, de integración y aceptación sean satisfactorias.
* La documentación técnica y funcional se encuentre actualizada.