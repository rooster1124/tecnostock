<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';
solo_encargado();

$id        = entero($_GET['id'] ?? null, 1);
$esEdicion = $id !== null;
$errores   = [];
$datos     = [
    'codigo' => '', 'nombre' => '', 'id_categoria' => '', 'descripcion' => '',
    'precio' => '', 'stock_inicial' => '0', 'stock_minimo' => '',
];
$producto = null;

if ($esEdicion) {
    $stmt = $pdo->prepare('SELECT * FROM producto WHERE id_producto = ?');
    $stmt->execute([$id]);
    $producto = $stmt->fetch();
    if (!$producto) {
        set_mensaje('danger', 'El producto no existe.');
        redirigir('productos/index.php');
    }
    $datos = array_merge($datos, [
        'codigo'       => $producto['codigo'],
        'nombre'       => $producto['nombre'],
        'id_categoria' => $producto['id_categoria'],
        'descripcion'  => (string) $producto['descripcion'],
        'precio'       => $producto['precio'],
        'stock_minimo' => $producto['stock_minimo'],
    ]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$esEdicion) {
        $datos['codigo']        = strtoupper(texto($_POST['codigo'] ?? ''));
        $datos['stock_inicial'] = texto($_POST['stock_inicial'] ?? '');
    }
    $datos['nombre']       = texto($_POST['nombre'] ?? '');
    $datos['id_categoria'] = texto($_POST['id_categoria'] ?? '');
    $datos['descripcion']  = texto($_POST['descripcion'] ?? '');
    $datos['precio']       = texto($_POST['precio'] ?? '');
    $datos['stock_minimo'] = texto($_POST['stock_minimo'] ?? '');

    if (!csrf_valido()) {
        $errores['general'] = 'La sesión expiró. Recargue la página e intente nuevamente.';
    }

    if (!$esEdicion) {
        if ($datos['codigo'] === '') {
            $errores['codigo'] = 'El código es obligatorio.';
        } elseif (!preg_match('/^[A-Z0-9-]{3,20}$/', $datos['codigo'])) {
            $errores['codigo'] = 'Use entre 3 y 20 letras, números o guiones (ej: AUD-003).';
        } else {
            // RN01: el código no puede repetirse
            $stmt = $pdo->prepare('SELECT COUNT(*) FROM producto WHERE codigo = ?');
            $stmt->execute([$datos['codigo']]);
            if ($stmt->fetchColumn() > 0) {
                $errores['codigo'] = 'Ya existe un producto con el código ' . $datos['codigo'] . '.';
            }
        }
    }

    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (largo($datos['nombre']) > 100) {
        $errores['nombre'] = 'El nombre no puede superar los 100 caracteres.';
    }

    $idCategoria = entero($datos['id_categoria'], 1);
    if ($idCategoria === null) {
        $errores['id_categoria'] = 'Seleccione una categoría.';
    } else {
        $stmt = $pdo->prepare('SELECT estado FROM categoria WHERE id_categoria = ?');
        $stmt->execute([$idCategoria]);
        $estadoCategoria = $stmt->fetchColumn();
        $mismaCategoria  = $esEdicion && (int) $producto['id_categoria'] === $idCategoria;
        if ($estadoCategoria === false) {
            $errores['id_categoria'] = 'La categoría no existe.';
        } elseif (!$estadoCategoria && !$mismaCategoria) {
            $errores['id_categoria'] = 'La categoría está desactivada.';
        }
    }

    if (largo($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    // RN02: precio, stock y stock mínimo no pueden ser negativos
    $precio = entero($datos['precio'], 0);
    if ($precio === null) {
        $errores['precio'] = 'Ingrese un precio válido: número entero mayor o igual a 0.';
    }
    $stockMinimo = entero($datos['stock_minimo'], 0);
    if ($stockMinimo === null) {
        $errores['stock_minimo'] = 'Ingrese un stock mínimo válido: número entero mayor o igual a 0.';
    }
    $stockInicial = 0;
    if (!$esEdicion) {
        $stockInicial = entero($datos['stock_inicial'], 0);
        if ($stockInicial === null) {
            $errores['stock_inicial'] = 'Ingrese un stock inicial válido: número entero mayor o igual a 0.';
        }
    }

    if (!$errores) {
        $descripcion = $datos['descripcion'] === '' ? null : $datos['descripcion'];

        if ($esEdicion) {
            $stmt = $pdo->prepare(
                'UPDATE producto
                 SET nombre = ?, id_categoria = ?, descripcion = ?, precio = ?, stock_minimo = ?
                 WHERE id_producto = ?'
            );
            $stmt->execute([$datos['nombre'], $idCategoria, $descripcion, $precio, $stockMinimo, $id]);
            set_mensaje('success', 'Producto "' . $datos['nombre'] . '" actualizado correctamente.');
            redirigir('productos/index.php');
        }

        // RN06: el producto y su stock inicial se guardan juntos (transacción)
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'INSERT INTO producto (codigo, nombre, descripcion, precio, stock_actual, stock_minimo, id_categoria)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([$datos['codigo'], $datos['nombre'], $descripcion, $precio,
                            $stockInicial, $stockMinimo, $idCategoria]);
            $idNuevo = (int) $pdo->lastInsertId();

            if ($stockInicial > 0) {
                $stmt = $pdo->prepare(
                    'INSERT INTO movimiento (tipo, motivo, cantidad, precio_unitario, id_producto, id_usuario)
                     VALUES (\'ENTRADA\', \'Stock inicial\', ?, ?, ?, ?)'
                );
                $stmt->execute([$stockInicial, $precio, $idNuevo, $_SESSION['id_usuario']]);
            }

            $pdo->commit();
            set_mensaje('success', 'Producto "' . $datos['nombre'] . '" creado con stock inicial de ' . $stockInicial . ' unidad(es).');
            redirigir('productos/index.php');
        } catch (PDOException $ex) {
            $pdo->rollBack();
            // 1062 = código duplicado creado al mismo tiempo por otro usuario
            if (($ex->errorInfo[1] ?? null) === 1062) {
                $errores['codigo'] = 'Ya existe un producto con el código ' . $datos['codigo'] . '.';
            } else {
                $errores['general'] = 'No se pudo guardar el producto. Intente nuevamente.';
            }
        }
    }
}

$stmt = $pdo->prepare(
    'SELECT id_categoria, nombre, estado FROM categoria
     WHERE estado = 1 OR id_categoria = ? ORDER BY nombre'
);
$stmt->execute([$producto['id_categoria'] ?? 0]);
$categorias = $stmt->fetchAll();

$titulo  = $esEdicion ? 'Editar producto' : 'Nuevo producto';
$seccion = 'productos';

require VISTAS . 'productos/form.php';