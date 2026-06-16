    </main>
    <!-- ============================================================
         Fin del contenido principal (abierto en admin_header.php)
         ============================================================ -->

    <!-- ============================================================
         Footer del panel de administración
         ============================================================ -->
    <footer class="border-top bg-white py-3 px-4 text-center text-muted small">
        <p class="mb-0">
            &copy; <?= date('Y') ?> <?= SITE_NAME ?> — Panel de Administración.
            Todos los derechos reservados.
        </p>
        <p class="mb-0">
            Desarrollado con PHP puro, PDO y Bootstrap 5.3
        </p>
    </footer>

</div>
<!-- ============================================================
     Fin del contenedor admin-main
     ============================================================ -->

<!-- ============================================================
     Bootstrap 5.3 JS (bundle con Popper)
     ============================================================ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous">
</script>

<!-- ============================================================
     Scripts adicionales (inyectados por cada página si es necesario)
     ============================================================ -->
<?php if (!empty($scripts_adicionales)): ?>
    <?= $scripts_adicionales ?>
<?php endif; ?>

</body>
</html>
