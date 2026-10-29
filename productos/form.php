<?php
/**
 * Crear (RF02) o editar (RF05) un producto.
 * Sin ?id= crea uno nuevo; con ?id=N edita el existente.
 *
 * Decisión de diseño: el stock actual NO se edita aquí.
 * - Al crear, el stock inicial queda registrado como un movimiento de ENTRADA.
 * - Después, el stock solo cambia registrando entradas o salidas (RN06).
 */
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado(); // solo el Encargado puede usar esta página

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
    // ----- 1. Leer los datos del formulario -----
    if (!$esEdicion) {
        $datos['codigo']        = strtoupper(texto($_POST['codigo'] ?? ''));
        $datos['stock_inicial'] = texto($_POST['stock_inicial'] ?? '');
    }
    $datos['nombre']       = texto($_POST['nombre'] ?? '');
    $datos['id_categoria'] = texto($_POST['id_categoria'] ?? '');
    $datos['descripcion']  = texto($_POST['descripcion'] ?? '');
    $datos['precio']       = texto($_POST['precio'] ?? '');
    $datos['stock_minimo'] = texto($_POST['stock_minimo'] ?? '');

    // ----- 2. Validar en el servidor (aunque JavaScript ya lo haya hecho) -----
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

    // ----- 3. Guardar -----
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

        // Al crear: el producto y su movimiento de stock inicial se guardan juntos (transacción)
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
            // 1062 = valor duplicado (por si otro usuario creó el mismo código al mismo tiempo)
            if (($ex->errorInfo[1] ?? null) === 1062) {
                $errores['codigo'] = 'Ya existe un producto con el código ' . $datos['codigo'] . '.';
            } else {
                $errores['general'] = 'No se pudo guardar el producto. Intente nuevamente.';
            }
        }
    }
}

// Categorías para el selector: las activas (y la actual, si está desactivada)
$stmt = $pdo->prepare(
    'SELECT id_categoria, nombre, estado FROM categoria
     WHERE estado = 1 OR id_categoria = ? ORDER BY nombre'
);
$stmt->execute([$producto['id_categoria'] ?? 0]);
$categorias = $stmt->fetchAll();

$titulo  = $esEdicion ? 'Editar producto' : 'Nuevo producto';
$seccion = 'productos';
require __DIR__ . '/../includes/header.php';

/**
 * Clase de Bootstrap para marcar un campo con error.
 */
function invalido(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' is-invalid' : '';
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Productos</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="codigo" class="form-label">Código <?= $esEdicion ? '' : '<span class="text-danger">*</span>' ?></label>
                            <?php if ($esEdicion): ?>
                                <input type="text" id="codigo" class="form-control" value="<?= e($datos['codigo']) ?>" readonly disabled>
                                <div class="form-text">El código no se modifica.</div>
                            <?php else: ?>
                                <input type="text" id="codigo" name="codigo" maxlength="20" required
                                       pattern="[A-Za-z0-9\-]{3,20}" data-mayusculas
                                       class="form-control<?= invalido($errores, 'codigo') ?>"
                                       value="<?= e($datos['codigo']) ?>" placeholder="Ej: AUD-003">
                                <div class="invalid-feedback"><?= e($errores['codigo'] ?? 'Entre 3 y 20 letras, números o guiones.') ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-8">
                            <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                            <input type="text" id="nombre" name="nombre" maxlength="100" required
                                   class="form-control<?= invalido($errores, 'nombre') ?>"
                                   value="<?= e($datos['nombre']) ?>">
                            <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese el nombre del producto.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                            <select id="id_categoria" name="id_categoria" required
                                    class="form-select<?= invalido($errores, 'id_categoria') ?>">
                                <option value="">Seleccione…</option>
                                <?php foreach ($categorias as $c): ?>
                                    <option value="<?= (int) $c['id_categoria'] ?>"
                                        <?= (string) $datos['id_categoria'] === (string) $c['id_categoria'] ? 'selected' : '' ?>>
                                        <?= e($c['nombre']) ?><?= $c['estado'] ? '' : ' (inactiva)' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="invalid-feedback"><?= e($errores['id_categoria'] ?? 'Seleccione una categoría.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <label for="precio" class="form-label">Precio (CLP) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input type="number" id="precio" name="precio" min="0" step="1" required
                                       class="form-control<?= invalido($errores, 'precio') ?>"
                                       value="<?= e($datos['precio']) ?>">
                                <div class="invalid-feedback"><?= e($errores['precio'] ?? 'El precio debe ser un número entero mayor o igual a 0.') ?></div>
                            </div>
                        </div>

                        <div class="col-12">
                            <label for="descripcion" class="form-label">Descripción</label>
                            <textarea id="descripcion" name="descripcion" maxlength="255" rows="2"
                                      class="form-control<?= invalido($errores, 'descripcion') ?>"><?= e($datos['descripcion']) ?></textarea>
                            <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'Máximo 255 caracteres.') ?></div>
                        </div>

                        <div class="col-md-6">
                            <?php if ($esEdicion): ?>
                                <label class="form-label">Stock actual</label>
                                <input type="text" class="form-control" value="<?= (int) $producto['stock_actual'] ?> unidad(es)" readonly disabled>
                                <div class="form-text">
                                    El stock cambia registrando
                                    <a href="../movimientos/registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor&amp;id_producto=<?= (int) $id ?>">entradas</a>
                                    o
                                    <a href="../movimientos/registrar.php?tipo=SALIDA&amp;motivo=Venta&amp;id_producto=<?= (int) $id ?>">salidas</a>.
                                </div>
                            <?php else: ?>
                                <label for="stock_inicial" class="form-label">Stock inicial <span class="text-danger">*</span></label>
                                <input type="number" id="stock_inicial" name="stock_inicial" min="0" step="1" required
                                       class="form-control<?= invalido($errores, 'stock_inicial') ?>"
                                       value="<?= e($datos['stock_inicial']) ?>">
                                <div class="invalid-feedback"><?= e($errores['stock_inicial'] ?? 'Número entero mayor o igual a 0.') ?></div>
                                <div class="form-text">Se registra como una entrada con motivo "Stock inicial".</div>
                            <?php endif; ?>
                        </div>

                        <div class="col-md-6">
                            <label for="stock_minimo" class="form-label">Stock mínimo <span class="text-danger">*</span></label>
                            <input type="number" id="stock_minimo" name="stock_minimo" min="0" step="1" required
                                   class="form-control<?= invalido($errores, 'stock_minimo') ?>"
                                   value="<?= e($datos['stock_minimo']) ?>">
                            <div class="invalid-feedback"><?= e($errores['stock_minimo'] ?? 'Número entero mayor o igual a 0.') ?></div>
                            <div class="form-text">Si el stock baja de este número, el sistema muestra una alerta.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>