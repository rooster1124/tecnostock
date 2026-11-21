<?php
function formato_precio($valor): string
{
    return '$' . number_format((int) $valor, 0, ',', '.');
}

function formato_fecha(string $fecha): string
{
    return date('d-m-Y H:i', strtotime($fecha));
}