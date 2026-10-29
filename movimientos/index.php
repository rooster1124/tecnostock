<?php
/**
 * Historial de movimientos (RF10) con filtros y total de ventas.
 */
require_once __DIR__ . '/../includes/sesion.php';

// ----- Filtros -----
$tipo       = texto($_GET['tipo'] ?? '');
$motivo     = texto($_GET['motivo'] ?? '');
$idProducto = entero($_GET['id_producto'] ?? null, 1);
$desde      = texto($_GET['desde'] ?? '');
$hasta      = texto($_GET['hasta'] ?? '');

/**
 * Devuelve la fecha si tiene formato AAAA-MM-DD válido; si no, cadena vacía.
 */
function fecha_valida(string $f): string
{
    $d = DateTime::createFromFormat('Y-m-d', $f);
    return ($d && $d->format('Y-m-d') === $f) ? $f : '';
}
$desde = fecha_valida($desde);
$hasta = fecha_valida($hasta);

$todosMotivos = array_values(array_unique(array_merge(['Stock inicial'], MOTIVOS['ENTRADA'], MOTIVOS['SALIDA'])));

$condiciones = [];
$parametros  = [];
if (in_array($tipo, ['ENTRADA', 'SALIDA'], true)) {
    $condiciones[] = 'm.tipo = ?';
    $parametros[]  = $tipo;
} else {
    $tipo = '';
}
if (in_array($motivo, $todosMotivos, true)) {
    $condiciones[] = 'm.motivo = ?';
    $parametros[]  = $motivo;
} else {
    $motivo = '';
}
if ($idProducto !== null) {
    $condiciones[] = 'm.id_producto = ?';
    $parametros[]  = $idProducto;
}
if ($desde !== '') {
    $condiciones[] = 'm.fecha_hora >= ?';
    $parametros[]  = $desde . ' 00:00:00';
}
if ($hasta !== '') {
    $condiciones[] = 'm.fecha_hora <= ?';
    $parametros[]  = $hasta . ' 23:59:59';
}
$where = $condiciones ? ' WHERE ' . implode(' AND ', $condiciones) : '';

// Movimientos (los 300 más recientes que cumplan los filtros)
$stmt = $pdo->prepare(
    'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.motivo, m.cantidad, m.precio_unitario,
            m.observacion, p.codigo, p.nombre AS producto, u.nombre AS usuario
     FROM movimiento m
     JOIN producto p ON p.id_producto = m.id_producto
     JOIN usuario  u ON u.id_usuario  = m.id_usuario' . $where . '
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
     LIMIT 300'
);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll();

// Totales de todos los movimientos filtrados
$stmt = $pdo->prepare(
    "SELECT
        COALESCE(SUM(CASE WHEN m.tipo = 'ENTRADA' THEN m.cantidad END), 0) AS unidades_entrada,
        COALESCE(SUM(CASE WHEN m.tipo = 'SALIDA'  THEN m.cantidad END), 0) AS unidades_salida,
        COALESCE(SUM(CASE WHEN m.motivo = 'Venta' THEN m.cantidad * m.precio_unitario END), 0) AS total_ventas
     FROM movimiento m" . $where
);
$stmt->execute($parametros);
$totales = $stmt->fetch();

$productos = $pdo->query('SELECT id_producto, codigo, nombre FROM producto ORDER BY nombre')->fetchAll();

$titulo  = 'Movimientos';
$seccion = 'movimientos';
require __DIR__ . '/../includes/header.php';
?>

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

<!-- Filtros -->
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

<!-- Totales -->
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
            <thead class="table-light">
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
                    <td><?= e($m['motivo']) ?></td>
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

<?php require __DIR__ . '/../includes/footer.php'; ?>