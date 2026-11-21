<?php
const MOTIVOS = [
    'ENTRADA' => ['Compra a proveedor', 'Devolución de cliente', 'Ajuste de inventario'],
    'SALIDA'  => ['Venta', 'Producto dañado', 'Devolución a proveedor', 'Ajuste de inventario'],
];

const MOTIVOS_VENDEDOR = [
    'ENTRADA' => ['Compra a proveedor'],
    'SALIDA'  => ['Venta'],
];

function motivos_permitidos(): array
{
    return es_encargado() ? MOTIVOS : MOTIVOS_VENDEDOR;
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

function contadores_menu(PDO $pdo): array
{
    return [
        'criticos' => (int) $pdo->query(
            'SELECT COUNT(*) FROM producto WHERE estado = 1 AND stock_actual < stock_minimo'
        )->fetchColumn(),
        'ofertas'  => (int) $pdo->query("SELECT COUNT(*) FROM oferta WHERE estado = 'ACTIVA'")->fetchColumn(),
    ];
}