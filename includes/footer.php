</main> <footer class="mt-5 text-center text-muted py-3">
        <small>&copy; <?= date('Y') ?> <?= APP_NAME ?></small>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script src="assets/js/app.js"></script>

    <?php
    // Dynamisches Laden der Modul-JS
    // Wenn wir im Modul "projects" sind, suchen wir nach "assets/js/module_projects.js"
    $module = $_GET['module'] ?? 'dashboard';
    $js_file = "assets/js/module_{$module}.js";
    
    if (file_exists($js_file)) {
        echo "<script src='{$js_file}'></script>";
    }
    ?>
</body>
</html>