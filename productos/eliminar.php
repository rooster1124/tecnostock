<?php
/**
 * Elimina físicamente un producto SOLO si nunca tuvo movimientos (RN05).
 * Si tiene movimientos, debe desactivarse para conservar el historial.
 * La base de datos también lo impide con ON DELETE RESTRICT.
 */
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado(); // solo el Encargado puede usar esta página

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
    // Respaldo: si la clave foránea lo impide, se informa sin mostrar el error técnico
    set_mensaje('warning', 'No se puede eliminar "' . $nombre . '" porque tiene movimientos registrados. Puede desactivarlo.');
}

redirigir('productos/index.php');