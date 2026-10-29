<?php
/**
 * Activa o desactiva una categoría (borrado lógico).
 * No se permite desactivar una categoría que tenga productos activos.
 */
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado(); // solo el Encargado puede usar esta página

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    set_mensaje('danger', 'Acción no permitida.');
    redirigir('categorias/index.php');
}

$id = entero($_POST['id'] ?? null, 1);

$stmt = $pdo->prepare('SELECT nombre, estado FROM categoria WHERE id_categoria = ?');
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    set_mensaje('danger', 'La categoría no existe.');
    redirigir('categorias/index.php');
}

if ($categoria['estado']) {
    // Antes de desactivar, se revisa que no tenga productos activos
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE id_categoria = ? AND estado = 1');
    $stmt->execute([$id]);
    $activos = (int) $stmt->fetchColumn();

    if ($activos > 0) {
        set_mensaje('warning', 'No se puede desactivar "' . $categoria['nombre'] . '": tiene '
            . $activos . ' producto(s) activo(s). Cámbielos de categoría o desactívelos primero.');
        redirigir('categorias/index.php');
    }

    $pdo->prepare('UPDATE categoria SET estado = 0 WHERE id_categoria = ?')->execute([$id]);
    set_mensaje('success', 'Categoría "' . $categoria['nombre'] . '" desactivada.');
} else {
    $pdo->prepare('UPDATE categoria SET estado = 1 WHERE id_categoria = ?')->execute([$id]);
    set_mensaje('success', 'Categoría "' . $categoria['nombre'] . '" activada.');
}

redirigir('categorias/index.php');