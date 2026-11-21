<?php
function redirigir(string $ruta): void
{
    header('Location: ' . BASE_URL . $ruta);
    exit;
}

function set_mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

function mostrar_mensaje(): void
{
    if (!empty($_SESSION['mensaje'])) {
        $m = $_SESSION['mensaje'];
        echo '<div class="alert alert-' . e($m['tipo']) . ' alert-dismissible fade show" role="alert">'
           . e($m['texto'])
           . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>'
           . '</div>';
        unset($_SESSION['mensaje']);
    }
}

function activo(string $nombre, string $seccion): string
{
    return $nombre === $seccion ? ' active' : '';
}