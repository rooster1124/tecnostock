<?php
/**
 * Encabezado común: HTML inicial, Bootstrap y barra de navegación.
 * Antes de incluirlo, cada página define $titulo y $seccion.
 */
$titulo  = $titulo ?? 'TecnoStock';
$seccion = $seccion ?? '';

// Cantidad de productos activos con stock crítico, para el aviso del menú (RF08)
$criticos = (int) $pdo->query(
    'SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo'
)->fetchColumn();

/**
 * Devuelve la clase "active" si el enlace corresponde a la sección actual.
 */
function activo(string $nombre, string $seccion): string
{
    return $nombre === $seccion ? ' active' : '';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($titulo) ?> | TecnoStock</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="<?= BASE_URL ?>assets/css/estilos.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
       <div class="container-fluid px-lg-5">
        <a class="navbar-brand fw-bold" href="<?= BASE_URL ?>inicio.php">
            <i class="bi bi-box-seam"></i> TecnoStock
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#menu"
                aria-controls="menu" aria-expanded="false" aria-label="Abrir menú">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="menu">
            <ul class="navbar-nav me-auto">
                <li class="nav-item">
                    <a class="nav-link text-nowrap<?= activo('inicio', $seccion) ?>" href="<?= BASE_URL ?>inicio.php">
                        <i class="bi bi-house"></i> Inicio
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-nowrap<?= activo('productos', $seccion) ?>" href="<?= BASE_URL ?>productos/index.php">
                        <i class="bi bi-boxes"></i> Productos
                        <?php if ($criticos > 0): ?>
                            <span class="badge rounded-pill bg-danger" title="Productos con stock crítico"><?= $criticos ?></span>
                        <?php endif; ?>
                    </a>
                </li>
                <?php if (es_encargado()): ?>
                <li class="nav-item">
                    <a class="nav-link text-nowrap<?= activo('categorias', $seccion) ?>" href="<?= BASE_URL ?>categorias/index.php">
                        <i class="bi bi-tags"></i> Categorías
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link text-nowrap<?= activo('movimientos', $seccion) ?>" href="<?= BASE_URL ?>movimientos/index.php">
                        <i class="bi bi-arrow-left-right"></i> Movimientos
                    </a>
                </li>
            </ul>
            <div class="d-flex flex-wrap flex-lg-nowrap gap-2 align-items-center">
                <a class="btn btn-success btn-sm text-nowrap" href="<?= BASE_URL ?>movimientos/registrar.php?tipo=SALIDA&amp;motivo=Venta">
                    <i class="bi bi-cart-check"></i> Registrar venta
                </a>
                <a class="btn btn-primary btn-sm text-nowrap" href="<?= BASE_URL ?>movimientos/registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor">
                    <i class="bi bi-box-arrow-in-down"></i> Cargar stock
                </a>
                <span class="navbar-text small ms-lg-2 d-lg-none d-xxl-inline">
                    <i class="bi bi-person-circle"></i> <?= e($_SESSION['nombre'] ?? '') ?>
                </span>
                <span class="badge <?= es_encargado() ? 'bg-warning text-dark' : 'bg-info text-dark' ?>" title="Rol del usuario">
                    <?= es_encargado() ? 'Encargado' : 'Vendedor' ?>
                </span>
                <a class="btn btn-outline-light btn-sm text-nowrap" href="<?= BASE_URL ?>logout.php" title="Cerrar sesión de <?= e($_SESSION['nombre'] ?? '') ?>">
                    <i class="bi bi-box-arrow-right"></i> Salir
                </a>
            </div>
        </div>
    </div>
</nav>

   <main class="container-fluid px-lg-5">
<?php if ($criticos > 0 && $seccion !== 'inicio'): ?>
    <div class="alert alert-danger d-flex align-items-center py-2" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i>
        <div>
            Hay <strong><?= $criticos ?></strong> producto(s) con stock crítico.
            <a href="<?= BASE_URL ?>productos/index.php?critico=1" class="alert-link">Ver productos</a>
        </div>
    </div>
<?php endif; ?>
<?php mostrar_mensaje(); ?>