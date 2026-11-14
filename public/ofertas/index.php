<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';

$ofertas = $pdo->query(
    "SELECT o.id_oferta, o.precio_oferta, o.cantidad_total, o.cantidad_vendida, o.estado,
            o.fecha_inicio, o.fecha_fin, p.codigo, p.nombre, p.precio, u.nombre AS usuario
     FROM oferta o
     JOIN producto p ON p.id_producto = o.id_producto
     JOIN usuario  u ON u.id_usuario  = o.id_usuario
     ORDER BY (o.estado = 'ACTIVA') DESC, o.fecha_inicio DESC
     LIMIT 200"
)->fetchAll();

$estados = [
    'ACTIVA'     => 'bg-success',
    'FINALIZADA' => 'bg-secondary',
    'CANCELADA'  => 'bg-danger-subtle text-danger',
];

$titulo  = 'Ofertas';
$seccion = 'ofertas';

require VISTAS . 'ofertas/index.php';