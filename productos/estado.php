<?php
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    set_mensaje('danger', 'Acción no permitida.');
    redirigir('productos/index.php');
}

$id = entero($_POST['id'] ?? null, 1);

$stmt = $pdo->prepare(
    'SELECT p.nombre, p.estado, c.estado AS categoria_activa
     FROM producto p JOIN categoria c ON c.id_categoria = p.id_categoria
     WHERE p.id_producto = ?'
);
$stmt->execute([$id]);
$producto = $stmt->fetch();

if (!$producto) {
    set_mensaje('danger', 'El producto no existe.');
    redirigir('productos/index.php');
}

if ($producto['estado']) {
    $pdo->prepare('UPDATE producto SET estado = 0 WHERE id_producto = ?')->execute([$id]);
    $pdo->prepare("UPDATE oferta SET estado = 'CANCELADA', fecha_fin = NOW() WHERE id_producto = ? AND estado = 'ACTIVA'")
        ->execute([$id]);
    set_mensaje('success', 'Producto "' . $producto['nombre'] . '" desactivado. Su historial de movimientos se conserva.');
    redirigir('productos/index.php');
}

if (!$producto['categoria_activa']) {
    set_mensaje('warning', 'No se puede activar "' . $producto['nombre'] . '": su categoría está desactivada. Edítelo y asígnele otra categoría.');
    redirigir('productos/index.php?estado=inactivos');
}

$pdo->prepare('UPDATE producto SET estado = 1 WHERE id_producto = ?')->execute([$id]);
set_mensaje('success', 'Producto "' . $producto['nombre'] . '" activado.');
redirigir('productos/index.php');