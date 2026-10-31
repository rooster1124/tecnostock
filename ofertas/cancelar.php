<?php
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_valido()) {
    set_mensaje('danger', 'Acción no permitida.');
    redirigir('ofertas/index.php');
}

$id = entero($_POST['id'] ?? null, 1);

$stmt = $pdo->prepare(
    "UPDATE oferta SET estado = 'CANCELADA', fecha_fin = NOW() WHERE id_oferta = ? AND estado = 'ACTIVA'"
);
$stmt->execute([$id]);

if ($stmt->rowCount() === 1) {
    set_mensaje('success', 'Oferta cancelada. El producto vuelve a su precio normal.');
} else {
    set_mensaje('warning', 'La oferta no existe o ya no está activa.');
}

redirigir('ofertas/index.php');