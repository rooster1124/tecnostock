<?php require VISTAS . 'layout/header.php'; ?>
<div class="d-flex justify-content-between align-items-center mb-3">
    <h1 class="h3 mb-0">Categorías</h1>
    <a href="form.php" class="btn btn-primary">
        <i class="bi bi-plus-lg"></i> Nueva categoría
    </a>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Descripción</th>
                    <th class="num">Productos activos</th>
                    <th>Estado</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($categorias as $c): ?>
                <tr class="<?= $c['estado'] ? '' : 'fila-inactiva' ?>">
                    <td class="fw-semibold"><?= e($c['nombre']) ?></td>
                    <td><?= e($c['descripcion']) ?></td>
                    <td class="num"><?= (int) $c['productos_activos'] ?></td>
                    <td>
                        <?php if ($c['estado']): ?>
                            <span class="badge bg-success">Activa</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Inactiva</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <a href="form.php?id=<?= (int) $c['id_categoria'] ?>" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-pencil"></i> Editar
                        </a>
                        <form method="post" action="estado.php" class="d-inline"
                              data-confirm="<?= $c['estado']
                                  ? '¿Desactivar la categoría ' . e($c['nombre']) . '?'
                                  : '¿Activar la categoría ' . e($c['nombre']) . '?' ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $c['id_categoria'] ?>">
                            <?php if ($c['estado']): ?>
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-slash-circle"></i> Desactivar</button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-success"><i class="bi bi-check-circle"></i> Activar</button>
                            <?php endif; ?>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$categorias): ?>
                <tr><td colspan="5" class="text-center text-muted py-4">No hay categorías registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require VISTAS . 'layout/footer.php'; ?>