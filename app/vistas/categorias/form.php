<?php require VISTAS . 'layout/header.php'; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Categorías</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="nombre" name="nombre" maxlength="60" required
                               class="form-control<?= invalido($errores, 'nombre') ?>"
                               value="<?= e($datos['nombre']) ?>">
                        <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese el nombre de la categoría.') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" maxlength="255" rows="3"
                                  class="form-control<?= invalido($errores, 'descripcion') ?>"><?= e($datos['descripcion']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'Máximo 255 caracteres.') ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>