<?php
require_once __DIR__ . '/includes/sesion.php';

$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$totalCriticos  = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo')->fetchColumn();
$ventasHoy      = $pdo->query(
    "SELECT COALESCE(SUM(cantidad), 0) AS unidades, COALESCE(SUM(cantidad * precio_unitario), 0) AS total
     FROM movimiento WHERE motivo = 'Venta' AND DATE(fecha_hora) = CURDATE()"
)->fetch();
$movimientosHoy = (int) $pdo->query('SELECT COUNT(*) FROM movimiento WHERE DATE(fecha_hora) = CURDATE()')->fetchColumn();

$criticosLista = $pdo->query(
    'SELECT p.id_producto, p.codigo, p.nombre, c.nombre AS categoria, p.stock_actual, p.stock_minimo
     FROM producto p
     JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.estado = 1 AND p.stock_actual < p.stock_minimo
     ORDER BY (p.stock_minimo - p.stock_actual) DESC, p.nombre'
)->fetchAll();

$ultimos = $pdo->query(
    'SELECT m.fecha_hora, m.tipo, m.motivo, m.cantidad, p.nombre AS producto
     FROM movimiento m JOIN producto p ON p.id_producto = m.id_producto
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 5'
)->fetchAll();

$stmt = $pdo->query(
    "SELECT DATE(fecha_hora) AS dia, SUM(cantidad * precio_unitario) AS total, SUM(cantidad) AS unidades
     FROM movimiento
     WHERE motivo = 'Venta' AND fecha_hora >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(fecha_hora)"
);
$ventasPorDia = [];
foreach ($stmt as $fila) {
    $ventasPorDia[$fila['dia']] = $fila;
}

$diasSemana = ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb'];
$grafico = [];
for ($i = 6; $i >= 0; $i--) {
    $fecha = date('Y-m-d', strtotime("-$i days"));
    $grafico[] = [
        'etiqueta' => $i === 0 ? 'Hoy' : $diasSemana[(int) date('w', strtotime($fecha))] . ' ' . date('d', strtotime($fecha)),
        'total'    => (int) ($ventasPorDia[$fecha]['total'] ?? 0),
        'unidades' => (int) ($ventasPorDia[$fecha]['unidades'] ?? 0),
    ];
}
$totalSemana    = array_sum(array_column($grafico, 'total'));
$unidadesSemana = array_sum(array_column($grafico, 'unidades'));

$scriptsExtra = [
    'https://cdn.jsdelivr.net/npm/chart.js@4.4.6/dist/chart.umd.js',
    BASE_URL . 'assets/js/grafico.js',
];

$titulo  = 'Inicio';
$seccion = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-end gap-2 mb-4">
    <div>
        <h1 class="h3 mb-1">Hola, <?= e($_SESSION['nombre']) ?> 👋</h1>
        <div class="text-muted">Este es el resumen de tu inventario de hoy.</div>
    </div>
    <div class="text-muted small"><i class="bi bi-calendar3"></i> <?= date('d-m-Y') ?></div>
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <a href="productos/index.php" class="text-decoration-none">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icono bg-primary-subtle text-primary"><i class="bi bi-boxes"></i></span>
                <div>
                    <div class="etiqueta">Productos activos</div>
                    <div class="valor"><?= $totalProductos ?></div>
                </div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="productos/index.php?critico=1" class="text-decoration-none">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icono <?= $totalCriticos > 0 ? 'bg-danger-subtle text-danger' : 'bg-success-subtle text-success' ?>">
                    <i class="bi <?= $totalCriticos > 0 ? 'bi-exclamation-triangle' : 'bi-check-circle' ?>"></i>
                </span>
                <div>
                    <div class="etiqueta">Stock crítico</div>
                    <div class="valor <?= $totalCriticos > 0 ? 'text-danger' : '' ?>"><?= $totalCriticos ?></div>
                </div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="movimientos/index.php?motivo=Venta&amp;desde=<?= date('Y-m-d') ?>&amp;hasta=<?= date('Y-m-d') ?>" class="text-decoration-none">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icono bg-success-subtle text-success"><i class="bi bi-cash-coin"></i></span>
                <div>
                    <div class="etiqueta">Ventas de hoy · <?= (int) $ventasHoy['unidades'] ?> unid.</div>
                    <div class="valor"><?= formato_precio($ventasHoy['total']) ?></div>
                </div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-xl-3">
        <a href="movimientos/index.php?desde=<?= date('Y-m-d') ?>&amp;hasta=<?= date('Y-m-d') ?>" class="text-decoration-none">
            <div class="card stat-card h-100"><div class="card-body d-flex align-items-center gap-3">
                <span class="stat-icono bg-info-subtle text-info-emphasis"><i class="bi bi-arrow-left-right"></i></span>
                <div>
                    <div class="etiqueta">Movimientos de hoy</div>
                    <div class="valor"><?= $movimientosHoy ?></div>
                </div>
            </div></div>
        </a>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <span><i class="bi bi-bar-chart-line text-primary"></i> Ventas de los últimos 7 días</span>
                <span class="small text-muted">
                    Total: <strong class="text-dark"><?= formato_precio($totalSemana) ?></strong>
                    · <?= $unidadesSemana ?> unidad(es)
                </span>
            </div>
            <div class="card-body">
                <div style="height: 280px">
                    <canvas id="grafico-ventas" data-ventas='<?= e(json_encode($grafico, JSON_UNESCAPED_UNICODE)) ?>'></canvas>
                </div>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header"><i class="bi bi-clock-history text-primary"></i> Últimos movimientos</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($ultimos as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-semibold"><?= e($m['producto']) ?></div>
                            <div class="small text-muted"><?= e(formato_fecha($m['fecha_hora'])) ?> · <?= e($m['motivo']) ?></div>
                        </div>
                        <span class="badge <?= $m['tipo'] === 'ENTRADA' ? 'bg-primary-subtle text-primary' : 'bg-danger-subtle text-danger' ?>">
                            <?= $m['tipo'] === 'ENTRADA' ? '+' : '-' ?><?= (int) $m['cantidad'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$ultimos): ?>
                    <li class="list-group-item text-muted">Sin movimientos.</li>
                <?php endif; ?>
            </ul>
            <div class="card-footer bg-white text-end">
                <a href="movimientos/index.php" class="small">Ver historial completo</a>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-exclamation-triangle-fill text-danger"></i> Productos con stock crítico</span>
                <?php if ($criticosLista): ?>
                    <span class="badge bg-danger-subtle text-danger"><?= count($criticosLista) ?> por reponer</span>
                <?php endif; ?>
            </div>
            <?php if (!$criticosLista): ?>
                <div class="card-body text-success"><i class="bi bi-check-circle"></i> Todos los productos tienen stock suficiente.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th class="num">Stock</th>
                                <th class="num">Mínimo</th>
                                <th class="num d-none d-sm-table-cell">Faltan</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($criticosLista as $p): ?>
                            <tr class="fila-critica">
                                <td>
                                    <code><?= e($p['codigo']) ?></code> <?= e($p['nombre']) ?>
                                    <div class="small text-muted"><?= e($p['categoria']) ?></div>
                                </td>
                                <td class="num fw-bold"><?= (int) $p['stock_actual'] ?></td>
                                <td class="num"><?= (int) $p['stock_minimo'] ?></td>
                                <td class="num d-none d-sm-table-cell"><?= (int) $p['stock_minimo'] - (int) $p['stock_actual'] ?></td>
                                <td class="text-end">
                                    <a class="btn btn-sm btn-primary text-nowrap"
                                       href="movimientos/registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor&amp;id_producto=<?= (int) $p['id_producto'] ?>">
                                        <i class="bi bi-box-arrow-in-down"></i> Reponer
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>