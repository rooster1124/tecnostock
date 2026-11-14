<?php
require_once __DIR__ . '/../../app/nucleo/sesion.php';
solo_encargado();

$errores = [];
$datos   = [
    'id_producto'    => texto($_GET['id_producto'] ?? ''),
    'precio_oferta'  => '',
    'cantidad_total' => '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['id_producto']    = texto($_POST['id_producto'] ?? '');
    $datos['precio_oferta']  = texto($_POST['precio_oferta'] ?? '');
    $datos['cantidad_total'] = texto($_POST['cantidad_total'] ?? '');

    if (!csrf_valido()) {
        $errores['general'] = 'La sesión expiró. Recargue la página e intente nuevamente.';
    }
    $idProducto = entero($datos['id_producto'], 1);
    if ($idProducto === null) {
        $errores['id_producto'] = 'Seleccione un producto.';
    }
    $precioOferta = entero($datos['precio_oferta'], 1);
    if ($precioOferta === null) {
        $errores['precio_oferta'] = 'Ingrese un precio de oferta válido (número entero mayor que 0).';
    }
    $cantidad = entero($datos['cantidad_total'], 1, 100000);
    if ($cantidad === null) {
        $errores['cantidad_total'] = 'Ingrese una cantidad válida (número entero mayor que 0).';
    }

    if (!$errores) {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare('SELECT nombre, precio, stock_actual, estado FROM producto WHERE id_producto = ? FOR UPDATE');
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();

            // RN10: una oferta activa por producto, precio menor al normal y cantidad no mayor al stock
            if (!$producto || !$producto['estado']) {
                $errores['id_producto'] = 'El producto no existe o está desactivado.';
            } elseif (oferta_activa($pdo, $idProducto)) {
                $errores['id_producto'] = 'Este producto ya tiene una oferta activa. Cancélela antes de crear otra.';
            } else {
                if ($precioOferta >= $producto['precio']) {
                    $errores['precio_oferta'] = 'El precio de oferta debe ser menor al precio normal ('
                        . formato_precio($producto['precio']) . ').';
                }
                if ($cantidad > $producto['stock_actual']) {
                    $errores['cantidad_total'] = 'La cantidad no puede superar el stock actual ('
                        . $producto['stock_actual'] . ' unidades).';
                }
            }

            if ($errores) {
                $pdo->rollBack();
            } else {
                $stmt = $pdo->prepare(
                    'INSERT INTO oferta (id_producto, precio_oferta, cantidad_total, id_usuario) VALUES (?, ?, ?, ?)'
                );
                $stmt->execute([$idProducto, $precioOferta, $cantidad, $_SESSION['id_usuario']]);
                $pdo->commit();

                $descuento = round(100 - $precioOferta * 100 / $producto['precio']);
                set_mensaje('success', 'Oferta creada: ' . $producto['nombre'] . ' a ' . formato_precio($precioOferta)
                    . ' por las próximas ' . $cantidad . ' unidad(es) (' . $descuento . '% de descuento).');
                redirigir('ofertas/index.php');
            }
        } catch (PDOException $ex) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $errores['general'] = 'No se pudo crear la oferta. Intente nuevamente.';
        }
    }
}

$productos = $pdo->query(
    "SELECT p.id_producto, p.codigo, p.nombre, p.precio, p.stock_actual
     FROM producto p
     WHERE p.estado = 1 AND p.stock_actual > 0
       AND NOT EXISTS (SELECT 1 FROM oferta o WHERE o.id_producto = p.id_producto AND o.estado = 'ACTIVA')
     ORDER BY p.nombre"
)->fetchAll();

$titulo  = 'Nueva oferta';
$seccion = 'ofertas';

require VISTAS . 'ofertas/form.php';