/**
 * ============================================================
 * carrito.js - Funcionalidad del Carrito de Compras (AJAX)
 * ============================================================
 * [PEDAGÓGICO] Este archivo maneja TODAS las operaciones del
 * carrito de compras vía AJAX, permitiendo que el usuario
 * agregue, actualice o elimine productos SIN recargar la página.
 *
 * Requisitos:
 *   - jQuery 3.x (cargado vía CDN en header.php)
 *   - Meta tag con CSRF token en <head> (header.php lo incluye)
 *
 * Flujo general:
 *   1. El usuario hace clic en un botón o cambia un input
 *   2. Se captura el evento con jQuery
 *   3. Se arma la petición AJAX al endpoint api/carrito.php
 *   4. Se actualiza el DOM con la respuesta (badge, cantidad, etc.)
 * ============================================================
 */

// ============================================================
// Esperar a que el DOM esté listo
// ============================================================
// [PEDAGÓGICO] $(document).ready() asegura que el código se
// ejecute solo después de que todos los elementos HTML estén
// cargados. Sin esto, los selectores no encontrarían los botones.
$(document).ready(function () {

    // ============================================================
    // Obtener token CSRF desde el meta tag
    // ============================================================
    // [PEDAGÓGICO] En lugar de hardcodear el token en cada petición
    // AJAX, lo leemos dinámicamente del <meta name="csrf-token">
    // que se genera en header.php. Así:
    //   - Cada sesión tiene su propio token único
    //   - Si el token expira, solo se actualiza el meta tag
    //   - El JS no necesita cambios cuando se regenera el token
    var csrfToken = $('meta[name="csrf-token"]').attr('content');

    // ============================================================
    // ACTUALIZAR BADGE DEL CARRITO (contador en la navbar)
    // ============================================================
    // [PEDAGÓGICO] Esta función se llama después de CADA operación
    // AJAX exitosa (agregar, actualizar, eliminar). Consulta el
    // endpoint 'obtener' del api/carrito.php y actualiza el badge
    // con el número total de ítems.
    //
    // El badge está en header.php con id="carrito-contador".
    // Es un span con clase badge bg-danger dentro del enlace
    // del carrito en la navbar.
    //
    // @param {boolean} mostrarMensaje - Si debe mostrar toast/alert (opcional)
    // ============================================================
    function actualizarBadgeCarrito(mostrarMensaje) {
        mostrarMensaje = mostrarMensaje || false;

        $.ajax({
            url: 'api/carrito.php',
            method: 'POST',
            data: {
                action: 'obtener',
                // [PEDAGÓGICO] La acción 'obtener' es de solo lectura,
                // pero enviamos CSRF igual por si en el futuro cambia.
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success) {
                    // Actualizar el badge con el total de items
                    var totalItems = respuesta.data.total_items || 0;
                    $('#carrito-contador').text(totalItems);

                    // [PEDAGÓGICO] Si el carrito está vacío, ocultamos
                    // el badge. Si tiene items, lo mostramos.
                    if (totalItems > 0) {
                        $('#carrito-contador').show();
                    } else {
                        $('#carrito-contador').hide();
                    }

                    // Opcional: mostrar mensaje flotante
                    if (mostrarMensaje && respuesta.message) {
                        mostrarMensajeExito(respuesta.message);
                    }
                }
            },
            error: function (xhr, status, error) {
                console.error('Error actualizando badge:', error);
            }
        });
    }

    // [PEDAGÓGICO] Al cargar la página por primera vez, sincronizamos
    // el badge con el estado real del carrito (útil si se agregaron
    // items en otra pestaña o vía POST tradicional).
    actualizarBadgeCarrito(false);

    // ============================================================
    // MOSTRAR MENSAJES TEMPORALES (Toast/Alert)
    // ============================================================
    // [PEDAGÓGICO] En vez de recargar la página para ver mensajes,
    // creamos alertas de Bootstrap que desaparecen solas después
    // de unos segundos. Usamos un contenedor flotante fijo.
    // ============================================================

    /**
     * Muestra un mensaje de éxito temporal en la esquina superior derecha.
     * @param {string} mensaje - Texto del mensaje a mostrar
     */
    function mostrarMensajeExito(mensaje) {
        var toast = $(
            '<div class="alert alert-success alert-dismissible fade show position-fixed" ' +
            'style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;" role="alert">' +
            mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
        $('body').append(toast);

        // [PEDAGÓGICO] Auto-cerrar el mensaje después de 3 segundos
        // usando remove() de jQuery para eliminar el elemento del DOM.
        setTimeout(function () {
            toast.remove();
        }, 3000);
    }

    /**
     * Muestra un mensaje de error temporal.
     * @param {string} mensaje - Texto del error
     */
    function mostrarMensajeError(mensaje) {
        var toast = $(
            '<div class="alert alert-danger alert-dismissible fade show position-fixed" ' +
            'style="top: 20px; right: 20px; z-index: 9999; max-width: 400px;" role="alert">' +
            mensaje +
            '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
            '</div>'
        );
        $('body').append(toast);
        // [PEDAGÓGICO] Auto-cerrar el mensaje después de 3 segundos
        // usando remove() de jQuery para eliminar el elemento del DOM.
        setTimeout(function () {
            toast.remove();
        }, 3000);
    }

    // ============================================================
    // 1. AGREGAR AL CARRITO (Desde producto.php)
    // ============================================================
    // [PEDAGÓGICO] Capturamos el submit del formulario de producto.php
    // y lo enviamos vía AJAX en vez de la navegación tradicional.
    // Esto evita recargar la página y permite mantener al usuario
    // en la vista del producto.
    //
    // Selector: El formulario en producto.php tiene action="carrito.php"
    // y contiene un input hidden con name="accion" value="agregar".
    // ============================================================
    $('form[action="carrito.php"]').on('submit', function (e) {
        // [PEDAGÓGICO] Prevenimos el envío normal del formulario
        // (que recargaría la página). Vamos a manejarlo con AJAX.
        var $form = $(this);
        var accion = $form.find('input[name="accion"]').val();

        // Solo interceptamos cuando la acción es 'agregar'
        // (el carrito.php también tiene actualizar/eliminar con POST)
        if (accion !== 'agregar') {
            return; // Dejar que el formulario se envíe normalmente
        }

        e.preventDefault();

        var productoId = $form.find('input[name="producto_id"]').val();
        var cantidad   = $form.find('input[name="cantidad"]').val() || 1;

        // Validación básica en cliente
        if (!productoId || productoId <= 0) {
            mostrarMensajeError('Error: ID de producto inválido.');
            return;
        }

        // [PEDAGÓGICO] Deshabilitamos el botón para evitar doble clic
        var $btn = $form.find('button[type="submit"]');
        $btn.prop('disabled', true).text('⏳ Agregando...');

        $.ajax({
            url: 'api/carrito.php',
            method: 'POST',
            data: {
                action: 'agregar',
                producto_id: productoId,
                cantidad: cantidad,
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success) {
                    // Actualizar badge sin recargar
                    actualizarBadgeCarrito(true);
                } else {
                    mostrarMensajeError(respuesta.message || 'Error al agregar producto.');
                }
            },
            error: function (xhr, status, error) {
                mostrarMensajeError('Error de conexión. Intenta de nuevo.');
                console.error('Error agregar carrito:', error);
            },
            complete: function () {
                // [PEDAGÓGICO] Siempre restaurar el botón, haya
                // funcionado o no (el callback complete se ejecuta
                // siempre, success o error).
                $btn.prop('disabled', false).text('🛒 Agregar al carrito');
            }
        });
    });

    // ============================================================
// AGREGAR AL CARRITO DESDE CATÁLOGO (index.php)
// ============================================================

$('.agregar-carrito').on('click', function (e) {

    console.log("BOTON AGREGAR PRESIONADO");
    
    e.preventDefault();

    var $btn = $(this);

    var productoId = $btn.data('producto-id');
    var cantidad = $btn.data('cantidad') || 1;

    if (!productoId || productoId <= 0) {
        mostrarMensajeError('Producto inválido.');
        return;
    }

    $btn.prop('disabled', true).text('⏳ Agregando...');

    $.ajax({
        url: 'api/carrito.php',
        method: 'POST',
        data: {
            action: 'agregar',
            producto_id: productoId,
            cantidad: cantidad,
            _csrf_token: csrfToken
        },
        dataType: 'json',

        success: function(respuesta) {
            if (respuesta.success) {
                actualizarBadgeCarrito(true);
            } else {
                mostrarMensajeError(
                    respuesta.message || 'No se pudo agregar'
                );
            }
        },

        error: function() {
            mostrarMensajeError('Error de conexión.');
        },

        complete: function() {
            $btn.prop('disabled', false)
                .text('🛒 Agregar');
        }
    });

});



    // ============================================================
    // 2. ACTUALIZAR CANTIDAD (Desde carrito.php - input change)
    // ============================================================
    // [PEDAGÓGICO] En la página carrito.php, cuando el usuario cambia
    // la cantidad en el input numérico, queremos actualizar el
    // carrito sin recargar la página.
    //
    // Escuchamos el evento 'change' del input (se dispara cuando
    // el usuario termina de escribir o presiona Enter).
    // También escuchamos 'blur' (pierde el foco) por si el usuario
    // escribe y hace clic fuera sin presionar Enter.
    //
    // Selector: buscamos inputs type="number" dentro de formularios
    // que tengan accion=actualizar en carrito.php
    // ============================================================
    $('form:has(input[name="accion"][value="actualizar"]) input[name="cantidad"]').on('change blur',
        function (e) {
            // [PEDAGÓGICO] 'change' se dispara al modificar y salir del input.
            // 'blur' se dispara al perder el foco. Si solo fue blur sin cambio,
            // el valor es el mismo, así que verificamos.
            var $input = $(this);
            var $form = $input.closest('form');
            var valorActual = parseInt($input.val());
            var valorAnterior = parseInt($input.data('valor-anterior'));

            // Si no ha cambiado, no hacer nada
            if (valorActual === valorAnterior) {
                return;
            }

            var productoId = $form.find('input[name="producto_id"]').val();
            var cantidad   = valorActual;

            // Validar mínimo 1
            if (cantidad < 1) {
                cantidad = 1;
                $input.val(1);
            }

            // Actualizar valor anterior
            $input.data('valor-anterior', cantidad);

            $.ajax({
                url: 'api/carrito.php',
                method: 'POST',
                data: {
                    action: 'actualizar',
                    producto_id: productoId,
                    cantidad: cantidad,
                    _csrf_token: csrfToken
                },
                dataType: 'json',
                success: function (respuesta) {
                    if (respuesta.success) {
                        // Actualizar badge
                        actualizarBadgeCarrito(false);

                        // [PEDAGÓGICO] También actualizamos el subtotal
                        // de la fila (precio_unitario * cantidad) sin
                        // recargar la página.
                        actualizarSubtotalFila($form, cantidad);

                        // Actualizar el resumen de totales
                        actualizarTotalesCarrito();
                    } else {
                        mostrarMensajeError(respuesta.message || 'Error al actualizar cantidad.');
                        // Restaurar valor anterior
                        $input.val(valorAnterior);
                    }
                },
                error: function () {
                    mostrarMensajeError('Error de conexión. Intenta de nuevo.');
                    $input.val(valorAnterior);
                }
            });
        }
    );

    // Guardar valor inicial de cada input de cantidad al cargar la página
    $('form:has(input[name="accion"][value="actualizar"]) input[name="cantidad"]').each(function () {
        $(this).data('valor-anterior', parseInt($(this).val()));
    });

    // ============================================================
    // 3. ACTUALIZAR CANTIDAD (Botones +/- en producto.php)
    // ============================================================
    // [PEDAGÓGICO] En producto.php hay botones − y + que cambian
    // la cantidad mediante la función inline cambiarCantidad().
    // Nuestro evento 'agregar al carrito' captura el valor actual
    // del input, así que no necesitamos lógica adicional aquí.
    // Los botones +/- ya están manejados por el onclick en el HTML.
    // Simplemente nos aseguramos de que el formulario se capture
    // correctamente (ya lo hicimos arriba).
    // ============================================================

    // ============================================================
    // 4. ELIMINAR ITEM DEL CARRITO (Desde carrito.php)
    // ============================================================
    // [PEDAGÓGICO] Reemplazamos la confirmación nativa de JavaScript
    // (confirm()) y el POST tradicional con una petición AJAX.
    // Así el usuario no sale de la página.
    //
    // Selector: formularios con accion=eliminar dentro de carrito.php
    // Tienen un botón rojo 🗑️ que originalmente usaba onsubmit.
    // ============================================================
    $('form:has(input[name="accion"][value="eliminar"])').on('submit', function (e) {
        e.preventDefault();

        var $form = $(this);
        var productoId = $form.find('input[name="producto_id"]').val();
        var nombreProducto = $form.closest('tr').find('td:nth-child(2)').text().trim() || 'producto';

        // [PEDAGÓGICO] Confirmación visual con confirm() nativo.
        // Podría reemplazarse por un modal de Bootstrap más elegante.
        if (!confirm('¿Eliminar "' + nombreProducto + '" del carrito?')) {
            return;
        }

        $.ajax({
            url: 'api/carrito.php',
            method: 'POST',
            data: {
                action: 'eliminar',
                producto_id: productoId,
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success) {
                    // [PEDAGÓGICO] Animación: desvanecemos la fila
                    // antes de eliminarla del DOM
                    $form.closest('tr').fadeOut(300, function () {
                        $(this).remove();

                        // Actualizar badge
                        actualizarBadgeCarrito(true);

                        // Actualizar totales
                        actualizarTotalesCarrito();

                        // [PEDAGÓGICO] Si no quedan items, mostrar
                        // el mensaje de carrito vacío.
                        if ($('table tbody tr').length === 0) {
                            location.reload(); // Recargar para mostrar vista vacía
                        }
                    });
                } else {
                    mostrarMensajeError(respuesta.message || 'Error al eliminar producto.');
                }
            },
            error: function () {
                mostrarMensajeError('Error de conexión. Intenta de nuevo.');
            }
        });
    });

    // ============================================================
    // 5. VACIAR CARRITO COMPLETO (Desde carrito.php)
    // ============================================================
    // [PEDAGÓGICO] Reemplazamos el POST tradicional de "vaciar"
    // con una petición AJAX. Mostramos confirmación antes.
    //
    // Selector: formulario con accion=vaciar
    // ============================================================
    $('form:has(input[name="accion"][value="vaciar"])').on('submit', function (e) {
        e.preventDefault();

        // [PEDAGÓGICO] Confirmación antes de vaciar todo el carrito
        if (!confirm('¿Estás seguro de vaciar TODO el carrito? Esta acción no se puede deshacer.')) {
            return;
        }

        $.ajax({
            url: 'api/carrito.php',
            method: 'POST',
            data: {
                action: 'vaciar',
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success) {
                    mostrarMensajeExito(respuesta.message || '🗑️ Carrito vaciado.');

                    // Recargar la página para mostrar la vista de carrito vacío
                    setTimeout(function () {
                        location.reload();
                    }, 500);
                } else {
                    mostrarMensajeError(respuesta.message || 'Error al vaciar carrito.');
                }
            },
            error: function () {
                mostrarMensajeError('Error de conexión. Intenta de nuevo.');
            }
        });
    });

    // ============================================================
    // FUNCIONES AUXILIARES
    // ============================================================

    /**
     * Actualiza el subtotal de una fila individual en el carrito.
     *
     * [PEDAGÓGICO] Cuando el usuario cambia la cantidad de un producto,
     * recalculamos el subtotal en el cliente sin recargar la página.
     * Esto mejora la experiencia de usuario.
     *
     * @param {jQuery} $form - El formulario que contiene el input de cantidad
     * @param {number} nuevaCantidad - La cantidad actualizada
     */
    function actualizarSubtotalFila($form, nuevaCantidad) {
        var $row = $form.closest('tr');
        var precioTexto = $row.find('td:nth-child(3)').text().trim();

        // [PEDAGÓGICO] El precio está formateado como $1.234 (peso chileno).
        // Extraemos solo los dígitos para el cálculo numérico.
        var precioNumerico = parseInt(precioTexto.replace(/[^0-9]/g, '')) || 0;
        var nuevoSubtotal = precioNumerico * nuevaCantidad;

        // Formatear el subtotal (sin usar number_format de PHP)
        var subtotalFormateado = '$' + nuevoSubtotal.toLocaleString('es-CL');
        $row.find('td:nth-child(5)').text(subtotalFormateado);
    }

    /**
     * Actualiza el resumen de totales en la columna derecha del carrito.
     *
     * [PEDAGÓGICO] Después de modificar cantidades o eliminar items,
     * recalculamos los totales consultando el endpoint 'obtener'.
     * Esto asegura que los cálculos sean correctos (usando la lógica
     * del servidor) y no solo estimaciones del cliente.
     */
    function actualizarTotalesCarrito() {
        // Solo si existe el bloque de totales en la página
        if ($('.card:has(.card-title:contains("Resumen"))').length === 0) {
            return;
        }

        $.ajax({
            url: 'api/carrito.php',
            method: 'POST',
            data: {
                action: 'obtener',
                _csrf_token: csrfToken
            },
            dataType: 'json',
            success: function (respuesta) {
                if (respuesta.success && respuesta.data.totales) {
                    var totales = respuesta.data.totales;

                    // [PEDAGÓGICO] Actualizamos los valores en la card
                    // de resumen. Buscamos por los textos fijos de cada
                    // línea y actualizamos el span siguiente.
                    actualizarValorResumen('Subtotal', totales.subtotal);
                    actualizarValorResumen('IVA', totales.iva);
                    actualizarValorResumen('Envío', totales.envio);
                    actualizarValorResumen('TOTAL', totales.total);
                }
            },
            error: function () {
                // Silencio — no mostrar error si falla la actualización
                console.warn('No se pudieron actualizar los totales.');
            }
        });
    }

    /**
     * Actualiza un valor específico en la card de resumen.
     *
     * @param {string} label - Texto de la etiqueta a buscar (ej: "Subtotal")
     * @param {number} valor - Valor numérico a mostrar
     */
    function actualizarValorResumen(label, valor) {
        // [PEDAGÓGICO] Buscamos un div que contenga el texto label
        // y actualizamos el span hermano (siguiente elemento con fw-semibold)
        $('.card:has(.card-title:contains("Resumen")) .d-flex:has(span:contains("' + label + '"))')
            .find('span.fw-semibold, span.fw-bold')
            .text('$' + Math.round(valor).toLocaleString('es-CL'));
    }

    // ============================================================
    // INICIALIZACIÓN: ocultar badge si carrito vacío
    // ============================================================
    // [PEDAGÓGICO] Al cargar la página, si el badge muestra 0,
    // lo ocultamos automáticamente para no confundir al usuario.
    if ($('#carrito-contador').text() === '0') {
        $('#carrito-contador').hide();
    }

}); // Fin de $(document).ready()
