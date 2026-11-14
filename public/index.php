<?php
require_once __DIR__ . '/../app/nucleo/arranque.php';

if (!empty($_SESSION['id_usuario'])) {
    header('Location: ' . BASE_URL . 'inicio.php');
    exit;
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = texto($_POST['correo'] ?? '');
    $clave  = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    if (!csrf_valido()) {
        $error = 'La sesión expiró. Recargue la página e intente nuevamente.';
    } elseif ($correo === '' || $clave === '') {
        $error = 'Debe ingresar su correo y su contraseña.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } else {
        $sql  = 'SELECT id_usuario, nombre, clave, rol FROM usuario WHERE correo = ? AND estado = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        if ($usuario && password_verify($clave, $usuario['clave'])) {
            session_regenerate_id(true);
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['rol']        = $usuario['rol'];

            header('Location: ' . BASE_URL . 'inicio.php');
            exit;
        }

        $error = 'Correo o contraseña incorrectos.';
    }
}

$titulo = 'Iniciar sesión';
require VISTAS . 'login.php';