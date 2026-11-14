<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';

$tipo       = texto($_GET['tipo'] ?? '');
$motivo     = texto($_GET['motivo'] ?? '');
$idProducto = entero($_GET['id_producto'] ?? null, 1);
$desde      = texto($_GET['desde'] ?? '');
$hasta      = texto($_GET['hasta'] ?? '');

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

$stmt = $pdo->prepare(
    'SELECT m.id_movimiento, m.fecha_hora, m.tipo, m.motivo, m.cantidad, m.precio_unitario, m.id_oferta,
            m.observacion, p.codigo, p.nombre AS producto, u.nombre AS usuario
     FROM movimiento m
     JOIN producto p ON p.id_producto = m.id_producto
     JOIN usuario  u ON u.id_usuario  = m.id_usuario' . $where . '
     ORDER BY m.fecha_hora DESC, m.id_movimiento DESC
     LIMIT 300'
);
$stmt->execute($parametros);
$movimientos = $stmt->fetchAll();

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

require VISTAS . 'movimientos/index.php';