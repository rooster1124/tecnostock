<?php
require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';

if (empty($_SESSION['id_usuario'])) {
    set_mensaje('warning', 'Debe iniciar sesión para continuar.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}