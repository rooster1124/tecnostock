<?php
require_once __DIR__ . '/arranque.php';

if (empty($_SESSION['id_usuario'])) {
    set_mensaje('warning', 'Debe iniciar sesión para continuar.');
    redirigir('index.php');
}

$menu = contadores_menu($pdo);