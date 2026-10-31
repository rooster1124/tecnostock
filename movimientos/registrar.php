<?php
require_once __DIR__ . '/../includes/sesion.php';

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

                // Si hay oferta, las primeras unidades van a precio de oferta y el resto a precio normal
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
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Movimientos</a></li>
        <li class="breadcrumb-item active">Registrar</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3" id="titulo-movimiento"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" id="form-movimiento" class="needs-validation" novalidate
                      data-motivos='<?= e(json_encode($motivos, JSON_UNESCAPED_UNICODE)) ?>'>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label class="form-label d-block">Tipo de movimiento <span class="text-danger">*</span></label>
                        <div class="btn-group w-100" role="group">
                            <input type="radio" class="btn-check" name="tipo" id="tipo-salida" value="SALIDA" required
                                   <?= $datos['tipo'] === 'SALIDA' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-danger" for="tipo-salida"><i class="bi bi-box-arrow-up"></i> Salida</label>

                            <input type="radio" class="btn-check" name="tipo" id="tipo-entrada" value="ENTRADA" required
                                   <?= $datos['tipo'] === 'ENTRADA' ? 'checked' : '' ?>>
                            <label class="btn btn-outline-primary" for="tipo-entrada"><i class="bi bi-box-arrow-in-down"></i> Entrada</label>
                        </div>
                        <?php if (isset($errores['tipo'])): ?>
                            <div class="text-danger small mt-1"><?= e($errores['tipo']) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="mb-3">
                        <label for="motivo" class="form-label">Motivo <span class="text-danger">*</span></label>
                        <select id="motivo" name="motivo" required
                                class="form-select<?= isset($errores['motivo']) ? ' is-invalid' : '' ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($motivos[isset($motivos[$datos['tipo']]) ? $datos['tipo'] : 'SALIDA'] as $m): ?>
                                <option value="<?= e($m) ?>" <?= $datos['motivo'] === $m ? 'selected' : '' ?>><?= e($m) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['motivo'] ?? 'Seleccione un motivo.') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select id="id_producto" name="id_producto" required
                                class="form-select<?= isset($errores['id_producto']) ? ' is-invalid' : '' ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= (int) $p['id_producto'] ?>"
                                        data-stock="<?= (int) $p['stock_actual'] ?>"
                                        data-minimo="<?= (int) $p['stock_minimo'] ?>"
                                        data-precio="<?= (int) $p['precio'] ?>"
                                        data-oferta-precio="<?= (int) $p['precio_oferta'] ?>"
                                        data-oferta-restante="<?= (int) $p['oferta_restante'] ?>"
                                        <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']) ?> (stock: <?= (int) $p['stock_actual'] ?>)<?= $p['precio_oferta'] ? ' · EN OFERTA' : '' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        <div id="info-producto" class="form-text"></div>
                    </div>

                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="100000" step="1" required
                               class="form-control<?= isset($errores['cantidad']) ? ' is-invalid' : '' ?>"
                               value="<?= e($datos['cantidad']) ?>">
                        <div class="invalid-feedback" id="error-cantidad"><?= e($errores['cantidad'] ?? 'Ingrese una cantidad mayor que 0.') ?></div>
                    </div>

                    <div id="resumen-venta" class="alert alert-success d-none"></div>

                    <div class="mb-3">
                        <label for="observacion" class="form-label">Observación</label>
                        <input type="text" id="observacion" name="observacion" maxlength="255"
                               class="form-control<?= isset($errores['observacion']) ? ' is-invalid' : '' ?>"
                               value="<?= e($datos['observacion']) ?>" placeholder="Opcional. Ej: boleta 1234, proveedor X">
                        <div class="invalid-feedback"><?= e($errores['observacion'] ?? 'Máximo 255 caracteres.') ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg"></i> Registrar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>