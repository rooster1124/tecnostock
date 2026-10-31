<?php
require_once __DIR__ . '/../includes/sesion.php';
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
require __DIR__ . '/../includes/header.php';

function invalido(array $errores, string $campo): string
{
    return isset($errores[$campo]) ? ' is-invalid' : '';
}
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Ofertas</a></li>
        <li class="breadcrumb-item active">Nueva oferta</li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card">
            <div class="card-body p-4">
                <h1 class="h4 mb-1"><i class="bi bi-tag text-primary"></i> Nueva oferta</h1>
                <p class="text-muted mb-4">
                    El precio de oferta se aplica a las próximas unidades vendidas. Al venderse todas,
                    la oferta termina sola y el producto vuelve a su precio normal.
                </p>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" id="form-oferta" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label for="id_producto" class="form-label">Producto <span class="text-danger">*</span></label>
                        <select id="id_producto" name="id_producto" required
                                class="form-select<?= invalido($errores, 'id_producto') ?>">
                            <option value="">Seleccione…</option>
                            <?php foreach ($productos as $p): ?>
                                <option value="<?= (int) $p['id_producto'] ?>"
                                        data-precio="<?= (int) $p['precio'] ?>"
                                        data-stock="<?= (int) $p['stock_actual'] ?>"
                                        <?= (string) $datos['id_producto'] === (string) $p['id_producto'] ? 'selected' : '' ?>>
                                    <?= e($p['codigo'] . ' - ' . $p['nombre']) ?> (<?= formato_precio($p['precio']) ?> · stock <?= (int) $p['stock_actual'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="invalid-feedback"><?= e($errores['id_producto'] ?? 'Seleccione un producto.') ?></div>
                        <div class="form-text">Solo aparecen productos activos, con stock y sin otra oferta activa.</div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="precio_oferta" class="form-label">Precio de oferta (CLP) <span class="text-danger">*</span></label>
                            <div class="input-group has-validation">
                                <span class="input-group-text">$</span>
                                <input type="number" id="precio_oferta" name="precio_oferta" min="1" step="1" required
                                       class="form-control<?= invalido($errores, 'precio_oferta') ?>"
                                       value="<?= e($datos['precio_oferta']) ?>">
                                <div class="invalid-feedback" id="error-precio-oferta"><?= e($errores['precio_oferta'] ?? 'Ingrese un precio mayor que 0.') ?></div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="cantidad_total" class="form-label">Unidades en oferta <span class="text-danger">*</span></label>
                            <input type="number" id="cantidad_total" name="cantidad_total" min="1" step="1" required
                                   class="form-control<?= invalido($errores, 'cantidad_total') ?>"
                                   value="<?= e($datos['cantidad_total']) ?>">
                            <div class="invalid-feedback" id="error-cantidad-oferta"><?= e($errores['cantidad_total'] ?? 'Ingrese una cantidad mayor que 0.') ?></div>
                        </div>
                    </div>

                    <div id="resumen-oferta" class="alert alert-info mt-3 d-none"></div>

                    <div class="d-flex gap-2 mt-4">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-tag"></i> Crear oferta</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>