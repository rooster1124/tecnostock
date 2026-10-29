<?php
/**
 * Panel de inicio: resumen del inventario, ventas del día y alerta de stock crítico (RF08).
 */
require_once __DIR__ . '/includes/sesion.php';

// Totales para las tarjetas
$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$totalCriticos  = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo')->fetchColumn();
$ventasHoy      = $pdo->query(
    "SELECT COUNT(*) AS cantidad, COALESCE(SUM(cantidad * precio_unitario), 0) AS total
     FROM movimiento WHERE motivo = 'Venta' AND DATE(fecha_hora) = CURDATE()"
)->fetch();
$movimientosHoy = (int) $pdo->query('SELECT COUNT(*) FROM movimiento WHERE DATE(fecha_hora) = CURDATE()')->fetchColumn();

// Productos activos bajo su stock mínimo, del más urgente al menos urgente
$criticosLista = $pdo->query(
    'SELECT p.id_producto, p.codigo, p.nombre, c.nombre AS categoria, p.stock_actual, p.stock_minimo
     FROM producto p
     JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.estado = 1 AND p.stock_actual < p.stock_minimo
     ORDER BY (p.stock_minimo - p.stock_actual) DESC, p.nombre'
)->fetchAll();

// Últimos 5 movimientos
$ultimos = $pdo->query(
    'SELECT m.fecha_hora, m.tipo, m.motivo, m.cantidad, p.nombre AS producto
     FROM movimiento m JOIN producto p ON p.id_producto = m.id_producto
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC LIMIT 5'
)->fetchAll();

$titulo  = 'Inicio';
$seccion = 'inicio';
require __DIR__ . '/includes/header.php';
?>

<h1 class="h3 mb-4">Bienvenido, <?= e($_SESSION['nombre']) ?></h1>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-3">
        <a href="productos/index.php" class="text-decoration-none">
            <div class="card border-primary h-100 tarjeta-resumen"><div class="card-body">
                <div class="text-muted small">Productos activos</div>
                <div class="valor text-primary"><?= $totalProductos ?></div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="productos/index.php?critico=1" class="text-decoration-none">
            <div class="card h-100 tarjeta-resumen <?= $totalCriticos > 0 ? 'border-danger' : 'border-success' ?>"><div class="card-body">
                <div class="text-muted small">Stock crítico</div>
                <div class="valor <?= $totalCriticos > 0 ? 'text-danger' : 'text-success' ?>">
                    <?php if ($totalCriticos > 0): ?><i class="bi bi-exclamation-triangle-fill"></i><?php endif; ?>
                    <?= $totalCriticos ?>
                </div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="movimientos/index.php?motivo=Venta&amp;desde=<?= date('Y-m-d') ?>&amp;hasta=<?= date('Y-m-d') ?>" class="text-decoration-none">
            <div class="card border-success h-100 tarjeta-resumen"><div class="card-body">
                <div class="text-muted small">Ventas de hoy (<?= (int) $ventasHoy['cantidad'] ?>)</div>
                <div class="valor text-success"><?= formato_precio($ventasHoy['total']) ?></div>
            </div></div>
        </a>
    </div>
    <div class="col-sm-6 col-lg-3">
        <a href="movimientos/index.php?desde=<?= date('Y-m-d') ?>&amp;hasta=<?= date('Y-m-d') ?>" class="text-decoration-none">
            <div class="card border-secondary h-100 tarjeta-resumen"><div class="card-body">
                <div class="text-muted small">Movimientos de hoy</div>
                <div class="valor text-dark"><?= $movimientosHoy ?></div>
            </div></div>
        </a>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-7">
        <div class="card <?= $criticosLista ? 'border-danger' : '' ?>">
            <div class="card-header fw-semibold <?= $criticosLista ? 'bg-danger text-white' : '' ?>">
                <i class="bi bi-exclamation-triangle-fill"></i> Productos con stock crítico
            </div>
            <?php if (!$criticosLista): ?>
                <div class="card-body text-success"><i class="bi bi-check-circle"></i> Todos los productos tienen stock suficiente.</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover mb-0 align-middle">
                        <thead class="table-light">
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

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header fw-semibold"><i class="bi bi-clock-history"></i> Últimos movimientos</div>
            <ul class="list-group list-group-flush">
                <?php foreach ($ultimos as $m): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <div>
                            <div class="small fw-semibold"><?= e($m['producto']) ?></div>
                            <div class="small text-muted"><?= e(formato_fecha($m['fecha_hora'])) ?> · <?= e($m['motivo']) ?></div>
                        </div>
                        <span class="badge <?= $m['tipo'] === 'ENTRADA' ? 'bg-primary' : 'bg-danger' ?>">
                            <?= $m['tipo'] === 'ENTRADA' ? '+' : '-' ?><?= (int) $m['cantidad'] ?>
                        </span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$ultimos): ?>
                    <li class="list-group-item text-muted">Sin movimientos.</li>
                <?php endif; ?>
            </ul>
            <div class="card-footer text-end">
                <a href="movimientos/index.php" class="small">Ver historial completo</a>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>