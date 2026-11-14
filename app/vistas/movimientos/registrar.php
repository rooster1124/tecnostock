<?php require VISTAS . 'layout/header.php'; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Movimientos</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3" id="titulo-movimiento"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" id="form-movimiento" class="needs-validation" novalidate
                      data-motivos='<?= e(json_encode($motivos, JSON_UNESCAPED_UNICODE)) ?>'>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label class="form-label d-block">Tipo de movimiento <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipo" id="tipo-salida" value="SALIDA" required
                                   <?= $datos['tipo'] === 'SALIDA' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-danger" for="tipo-salida"><i class="bi bi-box-arrow-up"></i> Salida</label>

                            <input type="radio" class="btn-check" name="tipo" id="tipo-entrada" value="ENTRADA" required
                                   <?= $datos['tipo'] === 'ENTRADA' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="tipo-entrada"><i class="bi bi-box-arrow-in-down"></i> Entrada</label>
                        </div>
                        <?php if (isset($errores['tipo'])): ?>
                            <div class="text-danger small mt-1"><?= e($errores['tipo']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <select id="motivo" name="motivo" required
                                class="form-select<?= invalido($errores, 'motivo') ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($motivos[isset($motivos[$datos['tipo']]) ? $datos['tipo'] : 'SALIDA'] as $m): ?>
                                <option value="<?= e($m) ?>" <?= $datos['motivo'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['motivo'] ?? 'Seleccione un motivo.') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select id="id_producto" name="id_producto" required
                                class="form-select<?= invalido($errores, 'id_producto') ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= (int) $p['id_producto'] ?>"
                                        data-stock="<?= (int) $p['stock_actual'] ?>"
                                        data-minimo="<?= (int) $p['stock_minimo'] ?>"
                                        data-precio="<?= (int) $p['precio'] ?>"
                                        data-oferta-precio="<?= (int) $p['precio_oferta'] ?>"
                                        data-oferta-restante="<?= (int) $p['oferta_restante'] ?>"
                                        <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']) ?> (stock: <?= (int) $p['stock_actual'] ?>)<?= $p['precio_oferta'] ? ' · EN OFERTA' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        <div id="info-producto" class="form-text"></div>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="100000" step="1" required
                               class="form-control<?= invalido($errores, 'cantidad') ?>"
                               value="<?= e($datos['cantidad']) ?>">
                        <div class="invalid-feedback" id="error-cantidad"><?= e($errores['cantidad'] ?? 'Ingrese una cantidad mayor que 0.') ?></div>
                    </div>

                    <div id="resumen-venta" class="alert alert-success d-none"></div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación</label>
                        <input type="text" id="observacion" name="observacion" maxlength="255"
                               class="form-control<?= invalido($errores, 'observacion') ?>"
                               value="<?= e($datos['observacion']) ?>" placeholder="Opcional. Ej: boleta 1234, proveedor X">
                        <div class="invalid-feedback"><?= e($errores['observacion'] ?? 'Máximo 255 caracteres.') ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Registrar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>