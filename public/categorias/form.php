<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';
solo_encargado();

$id       = entero($_GET['id'] ?? null, 1);
$esEdicion = $id !== null;
$errores  = [];
$datos    = ['nombre' => '', 'descripcion' => ''];

if ($esEdicion) {
    $stmt = $pdo->prepare('SELECT nombre, descripcion FROM categoria WHERE id_categoria = ?');
    $stmt->execute([$id]);
    $actual = $stmt->fetch();
    if (!$actual) {
        set_mensaje('danger', 'La categoría no existe.');
        redirigir('categorias/index.php');
    }
    $datos = ['nombre' => $actual['nombre'], 'descripcion' => (string) $actual['descripcion']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']      = texto($_POST['nombre'] ?? '');
    $datos['descripcion'] = texto($_POST['descripcion'] ?? '');

    if (!csrf_valido()) {
        $errores['general'] = 'La sesión expiró. Recargue la página e intente nuevamente.';
    }
    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (largo($datos['nombre']) > 60) {
        $errores['nombre'] = 'El nombre no puede superar los 60 caracteres.';
    } else {
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categoria WHERE nombre = ? AND id_categoria <> ?');
        $stmt->execute([$datos['nombre'], $id ?? 0]);
        if ($stmt->fetchColumn() > 0) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }
    }
    if (largo($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    if (!$errores) {
        $descripcion = $datos['descripcion'] === '' ? null : $datos['descripcion'];

        if ($esEdicion) {
            $stmt = $pdo->prepare('UPDATE categoria SET nombre = ?, descripcion = ? WHERE id_categoria = ?');
            $stmt->execute([$datos['nombre'], $descripcion, $id]);
            set_mensaje('success', 'Categoría actualizada correctamente.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO categoria (nombre, descripcion) VALUES (?, ?)');
            $stmt->execute([$datos['nombre'], $descripcion]);
            set_mensaje('success', 'Categoría "' . $datos['nombre'] . '" creada correctamente.');
        }
        redirigir('categorias/index.php');
    }
}

$titulo  = $esEdicion ? 'Editar categoría' : 'Nueva categoría';
$seccion = 'categorias';

require VISTAS . 'categorias/form.php';