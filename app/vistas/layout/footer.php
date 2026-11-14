</main>

<footer class="text-center text-muted small py-4">
    TecnoStock &copy; <?= date('Y') ?> · Sistema de inventario
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= BASE_URL ?>assets/js/validaciones.js"></script>
<?php foreach ($scriptsExtra ?? [] as $script): ?>
<script src="<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>