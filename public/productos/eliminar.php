<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';
solo_encargado();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    set_mensaje('danger', 'Acción no permitida.');
    redirigir('productos/index.php');
}

$id = entero($_POST['id'] ?? null, 1);

$stmt = $pdo->prepare('SELECT nombre FROM producto WHERE id_producto = ?');
$stmt->execute([$id]);
$nombre = $stmt->fetchColumn();

if ($nombre === false) {
    set_mensaje('danger', 'El producto no existe.');
    redirigir('productos/index.php');
}

// RN05: con movimientos no se elimina, se desactiva
$stmt = $pdo->prepare('SELECT COUNT(*) FROM movimiento WHERE id_producto = ?');
$stmt->execute([$id]);
if ($stmt->fetchColumn() > 0) {
    set_mensaje('warning', 'No se puede eliminar "' . $nombre . '" porque tiene movimientos registrados. Puede desactivarlo.');
    redirigir('productos/index.php');
}

try {
    $pdo->prepare('DELETE FROM producto WHERE id_producto = ?')->execute([$id]);
    set_mensaje('success', 'Producto "' . $nombre . '" eliminado.');
} catch (PDOException $ex) {
    // Respaldo: la clave foránea (ON DELETE RESTRICT) también lo impide
    set_mensaje('warning', 'No se puede eliminar "' . $nombre . '" porque tiene movimientos registrados. Puede desactivarlo.');
}

redirigir('productos/index.php');