<?php
/**
 * Registrar una entrada o salida de stock (RF07).
 *  - Venta        = SALIDA con motivo "Venta"
 *  - Cargar stock = ENTRADA con motivo "Compra a proveedor"
 *
 * Reglas que se aplican aquí:
 *  RN02 la cantidad debe ser mayor que 0
 *  RN03 se guarda fecha, tipo, cantidad, producto y usuario responsable
 *  RN04 una salida no puede dejar el stock bajo cero
 *  RN06 el stock se actualiza automáticamente, dentro de una transacción
 */
require_once __DIR__ . '/../includes/sesion.php';

// Motivos que puede usar este usuario (el Vendedor solo vende y carga stock)
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
    // ----- 1. Leer -----
    $datos['tipo']        = texto($_POST['tipo'] ?? '');
    $datos['motivo']      = texto($_POST['motivo'] ?? '');
    $datos['id_producto'] = texto($_POST['id_producto'] ?? '');
    $datos['cantidad']    = texto($_POST['cantidad'] ?? '');
    $datos['observacion'] = texto($_POST['observacion'] ?? '');

    // ----- 2. Validar en el servidor -----
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

    // ----- 3. Guardar con transacción -----
    if (!$errores) {
        try {
            $pdo->beginTransaction();

            // FOR UPDATE bloquea la fila del producto hasta terminar,
            // así dos ventas simultáneas no pueden dejar el stock negativo.
            $stmt = $pdo->prepare(
                'SELECT nombre, precio, stock_actual, stock_minimo, estado
                 FROM producto WHERE id_producto = ? FOR UPDATE'
            );
            $stmt->execute([$idProducto]);
            $producto = $stmt->fetch();

            if (!$producto || !$producto['estado']) {
                $errores['id_producto'] = 'El producto no existe o está desactivado.';
            } elseif ($datos['tipo'] === 'SALIDA' && $cantidad > $producto['stock_actual']) {
                // RN04: la salida no puede dejar el stock bajo cero
                $errores['cantidad'] = 'Stock insuficiente: solo hay ' . $producto['stock_actual']
                    . ' unidad(es) de ' . $producto['nombre'] . '.';
            }

            if ($errores) {
                $pdo->rollBack();
            } else {
                // RN06: actualizar el stock
                $signo = $datos['tipo'] === 'ENTRADA' ? '+' : '-';
                $stmt = $pdo->prepare("UPDATE producto SET stock_actual = stock_actual $signo ? WHERE id_producto = ?");
                $stmt->execute([$cantidad, $idProducto]);

                // RN03: registrar el movimiento con el usuario de la sesión
                $stmt = $pdo->prepare(
                    'INSERT INTO movimiento (tipo, motivo, cantidad, precio_unitario, observacion, id_producto, id_usuario)
                     VALUES (?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    $datos['tipo'], $datos['motivo'], $cantidad, $producto['precio'],
                    $datos['observacion'] === '' ? null : $datos['observacion'],
                    $idProducto, $_SESSION['id_usuario'],
                ]);

                $pdo->commit();

                // ----- 4. Mensaje para el usuario -----
                $nuevoStock = $datos['tipo'] === 'ENTRADA'
                    ? $producto['stock_actual'] + $cantidad
                    : $producto['stock_actual'] - $cantidad;

                if ($datos['motivo'] === 'Venta') {
                    $texto = 'Venta registrada: ' . $cantidad . ' x ' . $producto['nombre']
                           . ' = ' . formato_precio($cantidad * $producto['precio']) . '.';
                } elseif ($datos['tipo'] === 'ENTRADA') {
                    $texto = 'Entrada registrada: +' . $cantidad . ' unidad(es) de ' . $producto['nombre'] . '.';
                } else {
                    $texto = 'Salida registrada: -' . $cantidad . ' unidad(es) de ' . $producto['nombre'] . '.';
                }
                $texto .= ' Stock actual: ' . $nuevoStock . '.';

                // RF08: si el producto quedó bajo el mínimo, se avisa de inmediato
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

// Productos activos para el selector (con stock y precio para JavaScript)
$productos = $pdo->query(
    'SELECT id_producto, codigo, nombre, precio, stock_actual, stock_minimo
     FROM producto WHERE estado = 1 ORDER BY nombre'
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

                    <!-- Tipo -->
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

                    <!-- Motivo -->
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

                    <!-- Producto -->
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
                                        <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']) ?> (stock: <?= (int) $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        <div id="info-producto" class="form-text"></div>
                    </div>

                    <!-- Cantidad -->
                    <div class="mb-3">
                        <label for="cantidad" class="form-label">Cantidad <span class="text-danger">*</span></label>
                        <input type="number" id="cantidad" name="cantidad" min="1" max="100000" step="1" required
                               class="form-control<?= isset($errores['cantidad']) ? ' is-invalid' : '' ?>"
                               value="<?= e($datos['cantidad']) ?>">
                        <div class="invalid-feedback" id="error-cantidad"><?= e($errores['cantidad'] ?? 'Ingrese una cantidad mayor que 0.') ?></div>
                    </div>

                    <!-- Resumen de la venta (lo completa JavaScript) -->
                    <div id="resumen-venta" class="alert alert-success d-none"></div>

                    <!-- Observación -->
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