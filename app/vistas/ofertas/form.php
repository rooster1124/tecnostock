<?php require VISTAS . 'layout/header.php'; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Ofertas</a></li>
        <li class="breadcrumb-item active">Nueva oferta</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-1"><i class="bi bi-tag text-primary"></i> Nueva oferta</h1>
                <p class="text-muted mb-4">
                    El precio de oferta se aplica a las próximas unidades vendidas. Al venderse todas,
                    la oferta termina sola y el producto vuelve a su precio normal.
                </p>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" id="form-oferta" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select id="id_producto" name="id_producto" required
                                class="form-select<?= invalido($errores, 'id_producto') ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= (int) $p['id_producto'] ?>"
                                        data-precio="<?= (int) $p['precio'] ?>"
                                        data-stock="<?= (int) $p['stock_actual'] ?>"
                                        <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']) ?> (<?= formato_precio($p['precio']) ?> · stock <?= (int) $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        <div class="form-text">Solo aparecen productos activos, con stock y sin otra oferta activa.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="precio_oferta" class="form-label">Precio de oferta (CLP) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input type="number" id="precio_oferta" name="precio_oferta" min="1" step="1" required
                                       class="form-control<?= invalido($errores, 'precio_oferta') ?>"
                                       value="<?= e($datos['precio_oferta']) ?>">
                                <div class="invalid-feedback" id="error-precio-oferta"><?= e($errores['precio_oferta'] ?? 'Ingrese un precio mayor que 0.') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="cantidad_total" class="form-label">Unidades en oferta <span class="text-danger">*</span></label>
                            <input type="number" id="cantidad_total" name="cantidad_total" min="1" step="1" required
                                   class="form-control<?= invalido($errores, 'cantidad_total') ?>"
                                   value="<?= e($datos['cantidad_total']) ?>">
                            <div class="invalid-feedback" id="error-cantidad-oferta"><?= e($errores['cantidad_total'] ?? 'Ingrese una cantidad mayor que 0.') ?></div>
                        </div>
                    </div>

                    <div id="resumen-oferta" class="alert alert-info mt-3 d-none"></div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-tag"></i> Crear oferta</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>