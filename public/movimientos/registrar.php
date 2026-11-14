<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';

$motivos = motivos_permitidos();

$errores = [];
$datos = [
    'tipo'        => texto($_GET['tipo'] ?? 'SALIDA'),
    'motivo'      => texto($_GET['motivo'] ?? ''),
    'id_producto' => texto($_GET['id_producto'] ?? ''),
    'cantidad'    => '',
    'observacion' => '',
];
if (!isset($motivos[$datos['tipo']])) {
    $datos['tipo'] = 'SALIDA';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['tipo']        = texto($_POST['tipo'] ?? '');
    $datos['motivo']      = texto($_POST['motivo'] ?? '');
    $datos['id_producto'] = texto($_POST['id_producto'] ?? '');
    $datos['cantidad']    = texto($_POST['cantidad'] ?? '');
    $datos['observacion'] = texto($_POST['observacion'] ?? '');

    if (!csrf_valido()) {
        $errores['general'] = 'La sesión expiró. Recargue la página e intente nuevamente.';
    }
    if (!isset($motivos[$datos['tipo']])) {
        $errores['tipo'] = 'Seleccione si es entrada o salida.';
    } elseif (!in_array($datos['motivo'], $motivos[$datos['tipo']], true)) {
        $errores['motivo'] = 'Seleccione un motivo válido para su rol.';
    }
    $idProducto = entero($datos['id_producto'], 1);
    if ($idProducto === null) {
        $errores['id_producto'] = 'Seleccione un producto.';
    }
    $cantidad = entero($datos['cantidad'], 1, 100000);
    if ($cantidad === null) {
        $errores['cantidad'] = 'La cantidad debe ser un número entero mayor que 0.';
    }
    if (largo($datos['observacion']) > 255) {
        $errores['observacion'] = 'La observación no puede superar los 255 caracteres.';
    }

    if (!$errores) {
        try {
            // RN06: stock y movimiento se guardan juntos (transacción)
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                'SELECT nombre, precio, stock_actual, stock_minimo, estado
                 FROM producto WHERE id_producto = ? FOR UPDATE'
            );
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();

            if (!$producto || !$producto['estado']) {
                $errores['id_producto'] = 'El producto no existe o está desactivado.';
            } elseif ($datos['tipo'] === 'SALIDA' && $cantidad > $producto['stock_actual']) {
                // RN04: una salida no puede dejar el stock bajo cero
                $errores['cantidad'] = 'Stock insuficiente: solo hay ' . $producto['stock_actual']
                    . ' unidad(es) de ' . $producto['nombre'] . '.';
            }

            if ($errores) {
                $pdo->rollBack();
            } else {
                $signo = $datos['tipo'] === 'ENTRADA' ? '+' : '-';
                $stmt = $pdo->prepare("UPDATE producto SET stock_actual = stock_actual $signo ? WHERE id_producto = ?");
                $stmt->execute([$cantidad, $idProducto]);

                $esVenta = $datos['motivo'] === 'Venta';
                $oferta  = $esVenta ? oferta_activa($pdo, $idProducto, true) : null;

                // RN11: las primeras unidades van con precio de oferta y el resto con precio normal
                $enOferta = $oferta ? min($cantidad, $oferta['restante']) : 0;
                $lineas = [];
                if ($enOferta > 0) {
                    $lineas[] = [$enOferta, $oferta['precio_oferta'], $oferta['id_oferta']];
                }
                if ($cantidad - $enOferta > 0) {
                    $lineas[] = [$cantidad - $enOferta, $producto['precio'], null];
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO movimiento (tipo, motivo, cantidad, precio_unitario, observacion, id_producto, id_usuario, id_oferta)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $total = 0;
                foreach ($lineas as [$cant, $precio, $idOferta]) {
                    $stmt->execute([
                        $datos['tipo'], $datos['motivo'], $cant, $precio,
                        $datos['observacion'] === '' ? null : $datos['observacion'],
                        $idProducto, $_SESSION['id_usuario'], $idOferta,
                    ]);
                    $total += $cant * $precio;
                }

                $ofertaTerminada = false;
                if ($enOferta > 0) {
                    $vendidas = $oferta['cantidad_vendida'] + $enOferta;
                    $ofertaTerminada = $vendidas >= $oferta['cantidad_total'];
                    $stmt = $pdo->prepare(
                        'UPDATE oferta SET cantidad_vendida = ?, estado = ?, fecha_fin = ? WHERE id_oferta = ?'
                    );
                    $stmt->execute([
                        $vendidas,
                        $ofertaTerminada ? 'FINALIZADA' : 'ACTIVA',
                        $ofertaTerminada ? date('Y-m-d H:i:s') : null,
                        $oferta['id_oferta'],
                    ]);
                }

                $pdo->commit();

                $nuevoStock = $datos['tipo'] === 'ENTRADA'
                    ? $producto['stock_actual'] + $cantidad
                    : $producto['stock_actual'] - $cantidad;

                if ($esVenta) {
                    $texto = 'Venta registrada: ' . $cantidad . ' x ' . $producto['nombre']
                           . ' = ' . formato_precio($total) . '.';
                    if ($enOferta > 0) {
                        $texto .= ' ' . $enOferta . ' unidad(es) con oferta a ' . formato_precio($oferta['precio_oferta']) . '.';
                    }
                    if ($ofertaTerminada) {
                        $texto .= ' La oferta terminó: el producto vuelve a su precio normal ('
                                . formato_precio($producto['precio']) . ').';
                    }
                } elseif ($datos['tipo'] === 'ENTRADA') {
                    $texto = 'Entrada registrada: +' . $cantidad . ' unidad(es) de ' . $producto['nombre'] . '.';
                } else {
                    $texto = 'Salida registrada: -' . $cantidad . ' unidad(es) de ' . $producto['nombre'] . '.';
                }
                $texto .= ' Stock actual: ' . $nuevoStock . '.';

                // RF08: aviso inmediato de stock crítico
                if ($nuevoStock < $producto['stock_minimo']) {
                    set_mensaje('warning', $texto . ' ATENCIÓN: quedó bajo su stock mínimo ('
                        . $producto['stock_minimo'] . '). Es necesario reponer.');
                } else {
                    set_mensaje('success', $texto);
                }
                redirigir('movimientos/index.php');
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores['general'] = 'No se pudo registrar el movimiento. Intente nuevamente.';
        }
    }
}

$productos = $pdo->query(
    "SELECT p.id_producto, p.codigo, p.nombre, p.precio, p.stock_actual, p.stock_minimo,
            o.precio_oferta, o.cantidad_total - o.cantidad_vendida AS oferta_restante
     FROM producto p
     LEFT JOIN oferta o ON o.id_producto = p.id_producto AND o.estado = 'ACTIVA'
     WHERE p.estado = 1
     ORDER BY p.nombre"
)->fetchAll();

$esVenta = $datos['tipo'] === 'SALIDA' && $datos['motivo'] === 'Venta';
$titulo  = $esVenta ? 'Registrar venta' : 'Registrar movimiento';
$seccion = 'movimientos';

require VISTAS . 'movimientos/registrar.php';