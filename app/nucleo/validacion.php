<?php
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

function fecha_valida(string $fecha): string
{
    $d = DateTime::createFromFormat('Y-m-d', $fecha);
    return ($d && $d->format('Y-m-d') === $fecha) ? $fecha : '';
}

function invalido(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' is-invalid' : '';
}