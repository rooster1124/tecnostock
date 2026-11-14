<?php require VISTAS . 'layout/header.php'; ?>
<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Productos</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?></label>
                            <?php if ($esEdicion): ?>
                                <input type="text" id="codigo" class="form-control" value="<?= e($datos['codigo']) ?>" readonly disabled>
                                <div class="form-text">El código no se modifica.</div>
                            <?php else: ?>
                                <input type="text" id="codigo" name="codigo" maxlength="20" required
                                       pattern="[A-Za-z0-9\-]{3,20}" data-mayusculas
                                       class="form-control<?= invalido($errores, 'codigo') ?>"
                                       value="<?= e($datos['codigo']) ?>" placeholder="Ej: AUD-003">
                                <div class="invalid-feedback"><?= e($errores['codigo'] ?? 'Entre 3 y 20 letras, números o guiones.') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="nombre" name="nombre" maxlength="100" required
                                   class="form-control<?= invalido($errores, 'nombre') ?>"
                                   value="<?= e($datos['nombre']) ?>">
                            <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese el nombre del producto.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select id="id_categoria" name="id_categoria" required
                                    class="form-select<?= invalido($errores, 'id_categoria') ?>">
                                <option value="">Seleccione…</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['id_categoria'] ?>"
                                        <?= (string) $datos['id_categoria'] === (string) $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= e($c['nombre']) ?><?= $c['estado'] ? '' : ' (inactiva)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errores['id_categoria'] ?? 'Seleccione una categoría.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio (CLP) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input type="number" id="precio" name="precio" min="0" step="1" required
                                       class="form-control<?= invalido($errores, 'precio') ?>"
                                       value="<?= e($datos['precio']) ?>">
                                <div class="invalid-feedback"><?= e($errores['precio'] ?? 'El precio debe ser un número entero mayor o igual a 0.') ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" maxlength="255" rows="2"
                                      class="form-control<?= invalido($errores, 'descripcion') ?>"><?= e($datos['descripcion']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'Máximo 255 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <?php if ($esEdicion): ?>
                                <label class="form-label">Stock actual</label>
                                <input type="text" class="form-control" value="<?= (int) $producto['stock_actual'] ?> unidad(es)" readonly disabled>
                                <div class="form-text">
                                    El stock cambia registrando
                                    <a href="../movimientos/registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor&amp;id_producto=<?= (int) $id ?>">entradas</a>
                                    o
                                    <a href="../movimientos/registrar.php?tipo=SALIDA&amp;motivo=Venta&amp;id_producto=<?= (int) $id ?>">salidas</a>.
                                </div>
                            <?php else: ?>
                                <label for="stock_inicial" class="form-label">Stock inicial <span class="text-danger">*</span></label>
                                <input type="number" id="stock_inicial" name="stock_inicial" min="0" step="1" required
                                       class="form-control<?= invalido($errores, 'stock_inicial') ?>"
                                       value="<?= e($datos['stock_inicial']) ?>">
                                <div class="invalid-feedback"><?= e($errores['stock_inicial'] ?? 'Número entero mayor o igual a 0.') ?></div>
                                <div class="form-text">Se registra como una entrada con motivo "Stock inicial".</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="stock_minimo" class="form-label">Stock mínimo <span class="text-danger">*</span></label>
                            <input type="number" id="stock_minimo" name="stock_minimo" min="0" step="1" required
                                   class="form-control<?= invalido($errores, 'stock_minimo') ?>"
                                   value="<?= e($datos['stock_minimo']) ?>">
                            <div class="invalid-feedback"><?= e($errores['stock_minimo'] ?? 'Número entero mayor o igual a 0.') ?></div>
                            <div class="form-text">Si el stock baja de este número, el sistema muestra una alerta.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>