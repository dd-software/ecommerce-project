    </main>
    <!-- ============================================================
         Fin del Contenedor Principal (abierto en header.php)
         ============================================================ -->

    <!-- ============================================================
         Footer
         ============================================================ -->
    <footer class="bg-dark text-white text-center py-4 mt-5">
        <div class="container">
            <p class="mb-1">
                &copy; <?= date('Y') ?> <?= SITE_NAME ?>.
                Todos los derechos reservados.
            </p>
            <small class="text-muted">
                Proyecto académico — Universidad Católica de Temuco
            </small>
        </div>
    </footer>

    <!-- ============================================================
         Botón flotante: cambiar entre tema claro y oscuro
         ============================================================
         [PEDAGÓGICO] aria-label hace que lectores de pantalla
         anuncien la acción. aria-pressed refleja el estado actual
         para tecnología asistiva. -->
    <button id="btnTema"
            class="btn-tema"
            type="button"
            aria-label="Cambiar entre modo claro y modo oscuro"
            aria-pressed="false"
            title="Cambiar tema">
        <span id="btnTemaIcono" aria-hidden="true">🌙</span>
    </button>

    <script>
        (function () {
            var btn   = document.getElementById('btnTema');
            var icono = document.getElementById('btnTemaIcono');
            var html  = document.documentElement;

            // Sincroniza icono y aria-pressed con el tema activo.
            function reflejarEstado() {
                var oscuro = html.getAttribute('data-bs-theme') === 'dark';
                icono.textContent = oscuro ? '☀️' : '🌙';
                btn.setAttribute('aria-pressed', oscuro ? 'true' : 'false');
                btn.title = oscuro
                    ? 'Cambiar a modo claro'
                    : 'Cambiar a modo oscuro';
            }

            // El script anti-FOUC del header ya aplicó el tema —
            // aquí solo dejamos el botón en sincronía con ese estado.
            reflejarEstado();

            btn.addEventListener('click', function () {
                var nuevo = html.getAttribute('data-bs-theme') === 'dark'
                    ? 'light'
                    : 'dark';
                html.setAttribute('data-bs-theme', nuevo);
                try {
                    localStorage.setItem('tema', nuevo);
                } catch (e) { /* modo incógnito: ignorar */ }
                reflejarEstado();
            });
        })();
    </script>

    <!-- ============================================================
         Bootstrap 5.3 JS (CDN)
         ============================================================
         [PEDAGÓGICO] Popper.js es necesario para tooltips, popovers
         y dropdowns. Bootstrap.bundle.min.js lo incluye. -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
            crossorigin="anonymous">
    </script>

    <!-- ============================================================
         Scripts del carrito (AJAX)
         ============================================================
         [PEDAGÓGICO] carrito.js maneja agregar/eliminar productos
         del carrito mediante peticiones AJAX, sin recargar la
         página. Al cargarlo al final del body aseguramos que
         todo el DOM ya está disponible (no necesita DOMContentLoaded
         obligatoriamente, aunque lo usamos igual por buena práctica). -->
    <script src="<?= SITE_URL ?>/assets/js/carrito.js"></script>

    <!-- ============================================================
         Scripts adicionales (inyectados por cada página si es necesario)
         ============================================================ -->
    <?php if (!empty($scripts_adicionales)): ?>
        <?= $scripts_adicionales ?>
    <?php endif; ?>
</body>
</html>
