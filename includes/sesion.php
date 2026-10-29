<?php
/**
 * Protege las páginas internas.
 * Se incluye al inicio de cada página que requiere haber iniciado sesión.
 * Si no hay sesión, redirige al login.
 */

require_once __DIR__ . '/../config/conexion.php';
require_once __DIR__ . '/funciones.php';

if (empty($_SESSION['id_usuario'])) {
    set_mensaje('warning', 'Debe iniciar sesión para continuar.');
    header('Location: ' . BASE_URL . 'index.php');
    exit;
}