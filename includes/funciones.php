<?php
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.use_strict_mode', '1');
    session_set_cookie_params([
        'httponly' => true,
        'samesite' => 'Lax',
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

const MOTIVOS = [
    'ENTRADA' => ['Compra a proveedor', 'Devolución de cliente', 'Ajuste de inventario'],
    'SALIDA'  => ['Venta', 'Producto dañado', 'Devolución a proveedor', 'Ajuste de inventario'],
];

const MOTIVOS_VENDEDOR = [
    'ENTRADA' => ['Compra a proveedor'],
    'SALIDA'  => ['Venta'],
];

function es_encargado(): bool
{
    return ($_SESSION['rol'] ?? '') === 'ENCARGADO';
}

function solo_encargado(): void
{
    if (!es_encargado()) {
        set_mensaje('danger', 'No tiene permisos para realizar esta acción.');
        redirigir('inicio.php');
    }
}

function motivos_permitidos(): array
{
    return es_encargado() ? MOTIVOS : MOTIVOS_VENDEDOR;
}

function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

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

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

function texto($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

function largo(string $texto): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($texto, 'UTF-8')
        : (int) preg_match_all('/./us', $texto);
}

function entero($valor, int $min = 0, int $max = 100000000): ?int
{
    $n = filter_var($valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);
    return $n === false ? null : $n;
}

function formato_precio($valor): string
{
    return '$' . number_format((int) $valor, 0, ',', '.');
}

function formato_fecha(string $fecha): string
{
    return date('d-m-Y H:i', strtotime($fecha));
}

function estado_stock(int $actual, int $minimo): array
{
    if ($actual === 0) {
        return ['texto' => 'Sin stock', 'clase' => 'bg-dark'];
    }
    if ($actual < $minimo) {
        return ['texto' => 'Stock crítico', 'clase' => 'bg-danger'];
    }
    return ['texto' => 'Normal', 'clase' => 'bg-success'];
}

function barra_stock(int $actual, int $minimo): array
{
    $referencia = max($minimo * 2, 1);
    $porcentaje = (int) min(100, round($actual * 100 / $referencia));
    if ($actual < $minimo) {
        $color = 'bg-danger';
    } elseif ($actual < $minimo * 1.5) {
        $color = 'bg-warning';
    } else {
        $color = 'bg-success';
    }
    return ['porcentaje' => $porcentaje, 'color' => $color];
}

function oferta_activa(PDO $pdo, int $idProducto, bool $bloquear = false): ?array
{
    $sql = "SELECT id_oferta, precio_oferta, cantidad_total, cantidad_vendida,
                   cantidad_total - cantidad_vendida AS restante
            FROM oferta WHERE id_producto = ? AND estado = 'ACTIVA' LIMIT 1";
    if ($bloquear) {
        $sql .= ' FOR UPDATE';
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$idProducto]);
    $oferta = $stmt->fetch();
    return $oferta ?: null;
}