<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

$_SESSION = [];
session_destroy();

session_start();
set_mensaje('success', 'Sesión cerrada correctamente.');

header('Location: ' . BASE_URL . 'index.php');
exit;