<?php
require_once __DIR__ . '/../app/nucleo/sesion.php';

$totalProductos = (int) $pdo->query('SELECT COUNT(*) FROM producto WHERE estado = 1')->fetchColumn();
$totalCriticos  = $menu['criticos'];
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

require VISTAS . 'inicio.php';