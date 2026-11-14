<?php require VISTAS . 'layout/header.php'; ?>
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Historial de movimientos</h1>
    <div class="d-flex gap-2">
        <a href="registrar.php?tipo=SALIDA&amp;motivo=Venta" class="btn btn-success"><i class="bi bi-cart-check"></i> Registrar venta</a>
        <a href="registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor" class="btn btn-primary"><i class="bi bi-box-arrow-in-down"></i> Cargar stock</a>
        <?php if (es_encargado()): ?>
            <a href="registrar.php" class="btn btn-outline-dark"><i class="bi bi-plus-lg"></i> Otro movimiento</a>
        <?php endif; ?>
    </div>
</div>

<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-2">
            <label for="tipo" class="form-label small mb-1">Tipo</label>
            <select id="tipo" name="tipo" class="form-select">
                <option value="">Todos</option>
                <option value="ENTRADA" <?= $tipo === 'ENTRADA' ? 'selected' : '' ?>>Entradas</option>
                <option value="SALIDA"  <?= $tipo === 'SALIDA' ? 'selected' : '' ?>>Salidas</option>
            </select>
        </div>
        <div class="col-md-2">
            <label for="motivo" class="form-label small mb-1">Motivo</label>
            <select id="motivo" name="motivo" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($todosMotivos as $m): ?>
                    <option value="<?= e($m) ?>" <?= $motivo === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label for="id_producto" class="form-label small mb-1">Producto</label>
            <select id="id_producto" name="id_producto" class="form-select">
                <option value="">Todos</option>
                <?php foreach ($productos as $p): ?>
                    <option value="<?= (int) $p['id_producto'] ?>" <?= $idProducto === (int) $p['id_producto'] ? 'selected' : '' ?>>
                        <?= e($p['codigo'] . ' - ' . $p['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="desde" class="form-label small mb-1">Desde</label>
            <input type="date" id="desde" name="desde" class="form-control" value="<?= e($desde) ?>">
        </div>
        <div class="col-md-2">
            <label for="hasta" class="form-label small mb-1">Hasta</label>
            <input type="date" id="hasta" name="hasta" class="form-control" value="<?= e($hasta) ?>">
        </div>
        <div class="col-md-1 d-grid gap-1">
            <button class="btn btn-dark btn-sm"><i class="bi bi-funnel"></i> Filtrar</button>
            <a href="index.php" class="btn btn-outline-secondary btn-sm">Limpiar</a>
        </div>
    </div>
</form>

<div class="row g-3 mb-3">
    <div class="col-md-4">
        <div class="card border-primary"><div class="card-body py-2">
            <div class="small text-muted">Unidades que entraron</div>
            <div class="fs-4 fw-bold text-primary">+<?= (int) $totales['unidades_entrada'] ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-danger"><div class="card-body py-2">
            <div class="small text-muted">Unidades que salieron</div>
            <div class="fs-4 fw-bold text-danger">-<?= (int) $totales['unidades_salida'] ?></div>
        </div></div>
    </div>
    <div class="col-md-4">
        <div class="card border-success"><div class="card-body py-2">
            <div class="small text-muted">Total vendido</div>
            <div class="fs-4 fw-bold text-success"><?= formato_precio($totales['total_ventas']) ?></div>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover table-sm align-middle mb-0">
            <thead>
                <tr>
                    <th>Fecha y hora</th>
                    <th>Tipo</th>
                    <th>Motivo</th>
                    <th>Producto</th>
                    <th class="num">Cantidad</th>
                    <th class="num">Precio unit.</th>
                    <th class="num">Total</th>
                    <th>Usuario</th>
                    <th>Observación</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($movimientos as $m): ?>
                <tr>
                    <td class="text-nowrap"><?= e(formato_fecha($m['fecha_hora'])) ?></td>
                    <td>
                        <?php if ($m['tipo'] === 'ENTRADA'): ?>
                            <span class="badge bg-primary"><i class="bi bi-arrow-down"></i> Entrada</span>
                        <?php else: ?>
                            <span class="badge bg-danger"><i class="bi bi-arrow-up"></i> Salida</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= e($m['motivo']) ?>
                        <?php if ($m['id_oferta']): ?>
                            <span class="badge bg-success-subtle text-success"><i class="bi bi-tag-fill"></i> Oferta</span>
                        <?php endif; ?>
                    </td>
                    <td><code><?= e($m['codigo']) ?></code> <?= e($m['producto']) ?></td>
                    <td class="num fw-bold <?= $m['tipo'] === 'ENTRADA' ? 'text-primary' : 'text-danger' ?>">
                        <?= $m['tipo'] === 'ENTRADA' ? '+' : '-' ?><?= (int) $m['cantidad'] ?>
                    </td>
                    <td class="num"><?= formato_precio($m['precio_unitario']) ?></td>
                    <td class="num"><?= formato_precio($m['cantidad'] * $m['precio_unitario']) ?></td>
                    <td><?= e($m['usuario']) ?></td>
                    <td class="small"><?= e($m['observacion']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$movimientos): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay movimientos con esos filtros.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>