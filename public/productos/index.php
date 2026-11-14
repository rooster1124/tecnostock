<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';

$q           = texto($_GET['q'] ?? '');
$idCategoria = entero($_GET['categoria'] ?? null, 1);
$estado      = texto($_GET['estado'] ?? 'activos');
$soloCritico = !empty($_GET['critico']);

if (!in_array($estado, ['activos', 'inactivos', 'todos'], true)) {
    $estado = 'activos';
}

$condiciones = [];
$parametros  = [];

if ($q !== '') {
    $condiciones[] = '(p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)';
    $patron = '%' . $q . '%';
    array_push($parametros, $patron, $patron, $patron);
}
if ($idCategoria !== null) {
    $condiciones[] = 'p.id_categoria = ?';
    $parametros[]  = $idCategoria;
}
if ($estado === 'activos') {
    $condiciones[] = 'p.estado = 1';
} elseif ($estado === 'inactivos') {
    $condiciones[] = 'p.estado = 0';
}
if ($soloCritico) {
    $condiciones[] = 'p.stock_actual < p.stock_minimo';
}

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio,
               p.stock_actual, p.stock_minimo, p.estado, c.nombre AS categoria,
               (SELECT COUNT(*) FROM movimiento m WHERE m.id_producto = p.id_producto) AS movimientos,
               o.precio_oferta, o.cantidad_total - o.cantidad_vendida AS oferta_restante
        FROM producto p
        JOIN categoria c ON c.id_categoria = p.id_categoria
        LEFT JOIN oferta o ON o.id_producto = p.id_producto AND o.estado = \'ACTIVA\'';
if ($condiciones) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
$sql .= ' ORDER BY (p.estado = 1 AND p.stock_actual < p.stock_minimo) DESC, p.nombre';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categoria ORDER BY nombre')->fetchAll();

$titulo  = 'Productos';
$seccion = 'productos';

require VISTAS . 'productos/index.php';