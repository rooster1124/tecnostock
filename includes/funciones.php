<?php
/**
 * Funciones de apoyo usadas en todo el sistema.
 */

if (session_status() === PHP_SESSION_NONE) {
    // Configuración segura de la cookie de sesión
    ini_set('session.use_strict_mode', '1'); // rechaza ids de sesión inventados
    session_set_cookie_params([
        'httponly' => true,   // JavaScript no puede leer la cookie (protege ante XSS)
        'samesite' => 'Lax',  // el navegador no la envía desde otros sitios (protege ante CSRF)
        'secure'   => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off', // solo por HTTPS si está disponible
    ]);
    session_start();
}

/**
 * Motivos permitidos para cada tipo de movimiento.
 * Deben coincidir con la restricción chk_movimiento_motivo de la base de datos.
 * 'Stock inicial' no aparece aquí porque solo se registra al crear un producto.
 */
const MOTIVOS = [
    'ENTRADA' => ['Compra a proveedor', 'Devolución de cliente', 'Ajuste de inventario'],
    'SALIDA'  => ['Venta', 'Producto dañado', 'Devolución a proveedor', 'Ajuste de inventario'],
];

/**
 * Motivos que puede usar el VENDEDOR: solo vender y recibir mercadería.
 * Los ajustes, mermas y devoluciones quedan para el ENCARGADO.
 */
const MOTIVOS_VENDEDOR = [
    'ENTRADA' => ['Compra a proveedor'],
    'SALIDA'  => ['Venta'],
];

// ---------------------------------------------------------------------
// Roles y permisos (autorización)
// ---------------------------------------------------------------------

/**
 * ¿El usuario de la sesión es Encargado de inventario?
 */
function es_encargado(): bool
{
    return ($_SESSION['rol'] ?? '') === 'ENCARGADO';
}

/**
 * Corta la ejecución si el usuario NO es Encargado.
 * Se pone al inicio de las páginas que solo puede usar el Encargado
 * (crear/editar productos y categorías, desactivar, eliminar).
 * Aunque el botón no se muestre, alguien podría escribir la URL a mano:
 * por eso el permiso se revisa también en el servidor.
 */
function solo_encargado(): void
{
    if (!es_encargado()) {
        set_mensaje('danger', 'No tiene permisos para realizar esta acción.');
        redirigir('inicio.php');
    }
}

/**
 * Motivos de movimiento que puede usar el usuario actual, según su rol.
 */
function motivos_permitidos(): array
{
    return es_encargado() ? MOTIVOS : MOTIVOS_VENDEDOR;
}

/**
 * Escapa un texto antes de mostrarlo en HTML (evita ataques XSS).
 */
function e($texto): string
{
    return htmlspecialchars((string) $texto, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirige a una página del sistema y termina la ejecución.
 * Ejemplo: redirigir('productos/index.php');
 */
function redirigir(string $ruta): void
{
    header('Location: ' . BASE_URL . $ruta);
    exit;
}

/**
 * Guarda un mensaje para mostrarlo en la siguiente página.
 * $tipo: success, danger, warning o info (colores de Bootstrap).
 */
function set_mensaje(string $tipo, string $texto): void
{
    $_SESSION['mensaje'] = ['tipo' => $tipo, 'texto' => $texto];
}

/**
 * Muestra el mensaje guardado (si existe) y lo borra.
 */
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

/**
 * Token anti-CSRF: un valor secreto por sesión que todo formulario debe enviar.
 * Así se comprueba que el formulario salió de este sistema y no de otro sitio.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf'];
}

/**
 * Campo oculto con el token, para poner dentro de cada <form method="post">.
 */
function csrf_campo(): string
{
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}

/**
 * Verifica que el token enviado coincida con el de la sesión.
 */
function csrf_valido(): bool
{
    return isset($_POST['csrf'], $_SESSION['csrf'])
        && is_string($_POST['csrf'])
        && hash_equals($_SESSION['csrf'], $_POST['csrf']);
}

/**
 * Lee un texto recibido del formulario sin espacios al inicio ni al final.
 * Si alguien manipula el formulario y envía otra cosa (por ejemplo un arreglo),
 * devuelve una cadena vacía en vez de provocar un error.
 */
function texto($valor): string
{
    return is_string($valor) ? trim($valor) : '';
}

/**
 * Cantidad de caracteres de un texto, contando bien tildes y la ñ
 * ("Micrófono" = 9 caracteres, aunque en UTF-8 ocupe más bytes).
 */
function largo(string $texto): int
{
    return function_exists('mb_strlen')
        ? mb_strlen($texto, 'UTF-8')
        : (int) preg_match_all('/./us', $texto);
}

/**
 * Convierte un valor recibido del formulario a número entero.
 * Devuelve null si no es un entero válido dentro del rango.
 * Ejemplo: entero('15') -> 15 | entero('-3') -> null | entero('abc') -> null
 */
function entero($valor, int $min = 0, int $max = 100000000): ?int
{
    $n = filter_var($valor, FILTER_VALIDATE_INT, [
        'options' => ['min_range' => $min, 'max_range' => $max],
    ]);
    return $n === false ? null : $n;
}

/**
 * Formatea un número como precio en pesos chilenos: 24990 -> $24.990
 */
function formato_precio($valor): string
{
    return '$' . number_format((int) $valor, 0, ',', '.');
}

/**
 * Formatea una fecha de la base de datos: 2026-10-03 11:00:00 -> 03-10-2026 11:00
 */
function formato_fecha(string $fecha): string
{
    return date('d-m-Y H:i', strtotime($fecha));
}

/**
 * Indica el estado del stock de un producto, para mostrarlo con colores.
 * Regla RF08: es crítico cuando el stock actual es menor que el mínimo.
 */
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