<?php
require_once __DIR__ . '/../includes/sesion.php';

$ofertas = $pdo->query(
    "SELECT o.id_oferta, o.precio_oferta, o.cantidad_total, o.cantidad_vendida, o.estado,
            o.fecha_inicio, o.fecha_fin, p.codigo, p.nombre, p.precio, u.nombre AS usuario
     FROM oferta o
     JOIN producto p ON p.id_producto = o.id_producto
     JOIN usuario  u ON u.id_usuario  = o.id_usuario
     ORDER BY (o.estado = 'ACTIVA') DESC, o.fecha_inicio DESC
     LIMIT 200"
)->fetchAll();

$estados = [
    'ACTIVA'     => 'bg-success',
    'FINALIZADA' => 'bg-secondary',
    'CANCELADA'  => 'bg-danger-subtle text-danger',
];

$titulo  = 'Ofertas';
$seccion = 'ofertas';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h1 class="h3 mb-1">Ofertas</h1>
        <div class="text-muted small">Precio especial para las próximas unidades vendidas. Al venderse todas, vuelve el precio normal.</div>
    </div>
    <?php if (es_encargado()): ?>
        <a href="form.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Nueva oferta</a>
    <?php endif; ?>
</div>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Producto</th>
                    <th class="num">Precio normal</th>
                    <th class="num">Precio oferta</th>
                    <th>Unidades vendidas</th>
                    <th>Estado</th>
                    <th>Inicio</th>
                    <th>Fin</th>
                    <th>Creada por</th>
                    <?php if (es_encargado()): ?><th class="text-end">Acciones</th><?php endif; ?>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($ofertas as $o):
                $porcentaje = (int) round($o['cantidad_vendida'] * 100 / $o['cantidad_total']);
                $descuento  = (int) round(100 - $o['precio_oferta'] * 100 / max($o['precio'], 1));
            ?>
                <tr class="<?= $o['estado'] === 'ACTIVA' ? '' : 'fila-inactiva' ?>">
                    <td><code><?= e($o['codigo']) ?></code> <?= e($o['nombre']) ?></td>
                    <td class="num"><del class="text-muted"><?= formato_precio($o['precio']) ?></del></td>
                    <td class="num">
                        <span class="fw-bold text-success"><?= formato_precio($o['precio_oferta']) ?></span>
                        <span class="badge bg-success-subtle text-success">-<?= $descuento ?>%</span>
                    </td>
                    <td style="min-width: 160px">
                        <div class="small mb-1"><?= (int) $o['cantidad_vendida'] ?> de <?= (int) $o['cantidad_total'] ?></div>
                        <div class="progress barra-stock" role="progressbar" aria-label="Avance de la oferta"
                             aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                            <div class="progress-bar bg-success" style="width: <?= $porcentaje ?>%"></div>
                        </div>
                    </td>
                    <td><span class="badge <?= $estados[$o['estado']] ?>"><?= ucfirst(strtolower($o['estado'])) ?></span></td>
                    <td class="small text-nowrap"><?= e(formato_fecha($o['fecha_inicio'])) ?></td>
                    <td class="small text-nowrap"><?= $o['fecha_fin'] ? e(formato_fecha($o['fecha_fin'])) : '—' ?></td>
                    <td class="small"><?= e($o['usuario']) ?></td>
                    <?php if (es_encargado()): ?>
                        <td class="text-end">
                            <?php if ($o['estado'] === 'ACTIVA'): ?>
                                <form method="post" action="cancelar.php" class="d-inline"
                                      data-confirm="<?= '¿Cancelar la oferta de ' . e($o['nombre']) . '? Volverá a su precio normal.' ?>">
                                    <?= csrf_campo() ?>
                                    <input type="hidden" name="id" value="<?= (int) $o['id_oferta'] ?>">
                                    <button class="btn btn-sm btn-outline-danger text-nowrap"><i class="bi bi-x-circle"></i> Cancelar</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    <?php endif; ?>
                </tr>
            <?php endforeach; ?>
            <?php if (!$ofertas): ?>
                <tr><td colspan="9" class="text-center text-muted py-4">No hay ofertas registradas.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>