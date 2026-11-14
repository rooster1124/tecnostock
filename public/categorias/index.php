<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';
solo_encargado();

$categorias = $pdo->query(
    'SELECT c.id_categoria, c.nombre, c.descripcion, c.estado,
            COUNT(p.id_producto) AS productos_activos
     FROM categoria c
     LEFT JOIN producto p ON p.id_categoria = c.id_categoria AND p.estado = 1
     GROUP BY c.id_categoria, c.nombre, c.descripcion, c.estado
     ORDER BY c.estado DESC, c.nombre'
)->fetchAll();

$titulo  = 'Categorías';
$seccion = 'categorias';

require VISTAS . 'categorias/index.php';