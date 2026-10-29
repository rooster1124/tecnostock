<?php
/**
 * Inicio de sesión (RF01).
 */
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

// Si ya inició sesión, va directo al panel
if (!empty($_SESSION['id_usuario'])) {
    header('Location: ' . BASE_URL . 'inicio.php');
    exit;
}

$error  = '';
$correo = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $correo = texto($_POST['correo'] ?? '');
    $clave  = is_string($_POST['clave'] ?? null) ? $_POST['clave'] : '';

    // 1. Validación en el servidor (se repite aunque el navegador ya validó)
    if (!csrf_valido()) {
        $error = 'La sesión expiró. Recargue la página e intente nuevamente.';
    } elseif ($correo === '' || $clave === '') {
        $error = 'Debe ingresar su correo y su contraseña.';
    } elseif (!filter_var($correo, FILTER_VALIDATE_EMAIL)) {
        $error = 'El correo no tiene un formato válido.';
    } else {
        // 2. Buscar al usuario con una consulta preparada
        $sql  = 'SELECT id_usuario, nombre, clave, rol FROM usuario WHERE correo = ? AND estado = 1';
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$correo]);
        $usuario = $stmt->fetch();

        // 3. Comparar la contraseña escrita con el hash guardado
        if ($usuario && password_verify($clave, $usuario['clave'])) {
            session_regenerate_id(true); // nueva sesión al ingresar (seguridad)
            $_SESSION['id_usuario'] = $usuario['id_usuario'];
            $_SESSION['nombre']     = $usuario['nombre'];
            $_SESSION['rol']        = $usuario['rol'];   // ENCARGADO o VENDEDOR (permisos)

            header('Location: ' . BASE_URL . 'inicio.php');
            exit;
        }

        // Mensaje genérico: no revela si falló el correo o la contraseña
        $error = 'Correo o contraseña incorrectos.';
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-dark d-flex align-items-center min-vh-100">

<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-8 col-md-6 col-lg-4">
            <div class="card shadow">
                <div class="card-body p-4">
                    <h1 class="h4 text-center mb-1"><i class="bi bi-box-seam"></i> TecnoStock</h1>
                    <p class="text-center text-muted mb-4">Sistema de inventario</p>

                    <?php mostrar_mensaje(); ?>

                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= e($error) ?></div>
                    <?php endif; ?>

                    <form method="post" novalidate id="form-login" class="needs-validation">
                        <?= csrf_campo() ?>
                        <div class="mb-3">
                            <label for="correo" class="form-label">Correo</label>
                            <input type="email" class="form-control" id="correo" name="correo" maxlength="120"
                                   value="<?= e($correo) ?>" required autofocus>
                            <div class="invalid-feedback">Ingrese un correo válido (ej: nombre@dominio.cl).</div>
                        </div>
                        <div class="mb-3">
                            <label for="clave" class="form-label">Contraseña</label>
                            <input type="password" class="form-control" id="clave" name="clave" required>
                            <div class="invalid-feedback">Ingrese su contraseña.</div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="bi bi-box-arrow-in-right"></i> Ingresar
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/validaciones.js"></script>
</body>
</html>