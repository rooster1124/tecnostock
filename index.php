<?php
require_once __DIR__ . '/config/conexion.php';
require_once __DIR__ . '/includes/funciones.php';

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
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Iniciar sesión | TecnoStock</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/estilos.css" rel="stylesheet">
</head>
<body class="app">

<div class="container-fluid login-page">
    <div class="row min-vh-100">
        <div class="col-lg-6 login-marca d-none d-lg-flex flex-column justify-content-between p-5">
            <div class="d-flex align-items-center gap-2">
                <span class="marca-icono"><i class="bi bi-box-seam"></i></span>
                <span class="fs-4 fw-bold">TecnoStock</span>
            </div>
            <div>
                <h1 class="display-6 fw-bold mb-3">Tu inventario, claro y al día.</h1>
                <p class="lead mb-4 opacity-75">Controla productos, ventas y stock de tu tienda de accesorios tecnológicos en un solo lugar.</p>
                <ul class="list-unstyled">
                    <li><i class="bi bi-check-circle-fill"></i>Alertas de stock crítico al instante</li>
                    <li><i class="bi bi-check-circle-fill"></i>Ventas con cálculo automático del total</li>
                    <li><i class="bi bi-check-circle-fill"></i>Historial de cada entrada y salida</li>
                </ul>
            </div>
            <small class="opacity-50">Sistemas de Información </small>
        </div>

        <div class="col-lg-6 d-flex align-items-center justify-content-center p-4">
            <div class="login-caja w-100">
                <div class="d-flex d-lg-none align-items-center gap-2 mb-4">
                    <span class="marca-icono text-white"><i class="bi bi-box-seam"></i></span>
                    <span class="fs-4 fw-bold">TecnoStock</span>
                </div>

                <h2 class="fw-bold mb-1">Bienvenido</h2>
                <p class="text-muted mb-4">Ingresa con tu cuenta para continuar.</p>

                <?php mostrar_mensaje(); ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><i class="bi bi-exclamation-circle"></i> <?= e($error) ?></div>
                <?php endif; ?>

                <form method="post" novalidate id="form-login" class="needs-validation">
                    <?= csrf_campo() ?>
                    <div class="mb-3">
                        <label for="correo" class="form-label fw-medium">Correo</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-white"><i class="bi bi-envelope"></i></span>
                            <input type="email" class="form-control" id="correo" name="correo" maxlength="120"
                                   value="<?= e($correo) ?>" placeholder="nombre@tecnostock.cl" required autofocus>
                            <div class="invalid-feedback">Ingrese un correo válido (ej: nombre@dominio.cl).</div>
                        </div>
                    </div>
                    <div class="mb-4">
                        <label for="clave" class="form-label fw-medium">Contraseña</label>
                        <div class="input-group has-validation">
                            <span class="input-group-text bg-white"><i class="bi bi-lock"></i></span>
                            <input type="password" class="form-control" id="clave" name="clave" placeholder="••••••••" required>
                            <button type="button" class="btn btn-outline-secondary" id="ver-clave" title="Mostrar contraseña">
                                <i class="bi bi-eye"></i>
                            </button>
                            <div class="invalid-feedback">Ingrese su contraseña.</div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary btn-lg w-100">
                        <i class="bi bi-box-arrow-in-right"></i> Ingresar
                    </button>
                </form>

                <div class="demo-cuentas border rounded-3 p-3 mt-4 bg-white">
                    <div class="small text-muted mb-2"><i class="bi bi-info-circle"></i> Cuentas de prueba</div>
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-primary flex-fill" data-correo="admin@tecnostock.cl" data-clave="admin123">
                            Encargado
                        </button>
                        <button type="button" class="btn btn-outline-primary flex-fill" data-correo="vendedor@tecnostock.cl" data-clave="vendedor123">
                            Vendedor
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>assets/js/validaciones.js"></script>
</body>
</html>