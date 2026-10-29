<?php
/**
 * Listado y búsqueda de productos (RF03, RF04) con indicador de stock crítico (RF08).
 */
require_once __DIR__ . '/../includes/sesion.php';

// ----- Filtros recibidos por GET -----
$q           = texto($_GET['q'] ?? '');
$idCategoria = entero($_GET['categoria'] ?? null, 1);
$estado      = texto($_GET['estado'] ?? 'activos');   // activos | inactivos | todos
$soloCritico = !empty($_GET['critico']);

if (!in_array($estado, ['activos', 'inactivos', 'todos'], true)) {
    $estado = 'activos';
}

// ----- Se arma la consulta solo con los filtros usados -----
// Los valores nunca se pegan en el SQL: van en $parametros (consulta preparada).
$condiciones = [];
$parametros  = [];

if ($q !== '') {
    // RF04: busca por nombre, código o nombre de la categoría
    $condiciones[] = '(p.nombre LIKE ? OR p.codigo LIKE ? OR c.nombre LIKE ?)';
    $patron = '%' . $q . '%';
    array_push($parametros, $patron, $patron, $patron);
}
if ($idCategoria !== null) {
    $condiciones[] = 'p.id_categoria = ?';
    $parametros[]  = $idCategoria;
}
if ($estado === 'activos') {
    $condiciones[] = 'p.estado = 1';
} elseif ($estado === 'inactivos') {
    $condiciones[] = 'p.estado = 0';
}
if ($soloCritico) {
    $condiciones[] = 'p.stock_actual < p.stock_minimo';
}

$sql = 'SELECT p.id_producto, p.codigo, p.nombre, p.descripcion, p.precio,
               p.stock_actual, p.stock_minimo, p.estado, c.nombre AS categoria,
               (SELECT COUNT(*) FROM movimiento m WHERE m.id_producto = p.id_producto) AS movimientos
        FROM producto p
        JOIN categoria c ON c.id_categoria = p.id_categoria';
if ($condiciones) {
    $sql .= ' WHERE ' . implode(' AND ', $condiciones);
}
// Primero los críticos, después por nombre
$sql .= ' ORDER BY (p.estado = 1 AND p.stock_actual < p.stock_minimo) DESC, p.nombre';

$stmt = $pdo->prepare($sql);
$stmt->execute($parametros);
$productos = $stmt->fetchAll();

$categorias = $pdo->query('SELECT id_categoria, nombre FROM categoria ORDER BY nombre')->fetchAll();

$titulo  = 'Productos';
$seccion = 'productos';
require __DIR__ . '/../includes/header.php';
?>

<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <h1 class="h3 mb-0">Productos</h1>
    <?php if (es_encargado()): ?>
        <a href="form.php" class="btn btn-primary">
            <i class="bi bi-plus-lg"></i> Nuevo producto
        </a>
    <?php endif; ?>
</div>

<!-- Buscador -->
<form method="get" class="card card-body mb-3">
    <div class="row g-2 align-items-end">
        <div class="col-md-4">
            <label for="q" class="form-label small mb-1">Buscar</label>
            <input type="search" id="q" name="q" class="form-control" value="<?= e($q) ?>"
                   placeholder="Nombre, código o categoría">
        </div>
        <div class="col-md-3">
            <label for="categoria" class="form-label small mb-1">Categoría</label>
            <select id="categoria" name="categoria" class="form-select">
                <option value="">Todas</option>
                <?php foreach ($categorias as $c): ?>
                    <option value="<?= (int) $c['id_categoria'] ?>" <?= $idCategoria === (int) $c['id_categoria'] ? 'selected' : '' ?>>
                        <?= e($c['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label for="estado" class="form-label small mb-1">Estado</label>
            <select id="estado" name="estado" class="form-select">
                <option value="activos"   <?= $estado === 'activos' ? 'selected' : '' ?>>Activos</option>
                <option value="inactivos" <?= $estado === 'inactivos' ? 'selected' : '' ?>>Inactivos</option>
                <option value="todos"     <?= $estado === 'todos' ? 'selected' : '' ?>>Todos</option>
            </select>
        </div>
        <div class="col-md-3 d-flex align-items-center gap-2 flex-wrap">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" id="critico" name="critico" value="1" <?= $soloCritico ? 'checked' : '' ?>>
                <label class="form-check-label small" for="critico">Solo stock crítico</label>
            </div>
            <button class="btn btn-dark"><i class="bi bi-search"></i> Buscar</button>
            <a href="index.php" class="btn btn-outline-secondary">Limpiar</a>
        </div>
    </div>
</form>

<p class="text-muted small mb-2"><?= count($productos) ?> producto(s) encontrado(s)</p>

<div class="card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Categoría</th>
                    <th class="num">Precio</th>
                    <th class="num">Stock</th>
                    <th class="num">Mínimo</th>
                    <th>Estado de stock</th>
                    <th class="text-end">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($productos as $p):
                $stock   = estado_stock((int) $p['stock_actual'], (int) $p['stock_minimo']);
                $critico = $p['estado'] && $p['stock_actual'] < $p['stock_minimo'];
                $clase   = !$p['estado'] ? 'fila-inactiva' : ($critico ? 'fila-critica' : '');
            ?>
                <tr class="<?= $clase ?>">
                    <td><code><?= e($p['codigo']) ?></code></td>
                    <td>
                        <div class="fw-semibold"><?= e($p['nombre']) ?></div>
                        <div class="small text-muted"><?= e($p['descripcion']) ?></div>
                    </td>
                    <td><?= e($p['categoria']) ?></td>
                    <td class="num"><?= formato_precio($p['precio']) ?></td>
                    <td class="num fw-bold"><?= (int) $p['stock_actual'] ?></td>
                    <td class="num"><?= (int) $p['stock_minimo'] ?></td>
                    <td>
                        <?php if (!$p['estado']): ?>
                            <span class="badge bg-secondary">Desactivado</span>
                        <?php else: ?>
                            <span class="badge <?= $stock['clase'] ?>">
                                <?php if ($critico): ?><i class="bi bi-exclamation-triangle-fill"></i><?php endif; ?>
                                <?= e($stock['texto']) ?>
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="text-end text-nowrap">
                        <?php if ($p['estado']): ?>
                            <a class="btn btn-sm btn-success" title="Registrar venta"
                               href="../movimientos/registrar.php?tipo=SALIDA&amp;motivo=Venta&amp;id_producto=<?= (int) $p['id_producto'] ?>">
                                <i class="bi bi-cart-check"></i>
                            </a>
                            <a class="btn btn-sm btn-primary" title="Cargar stock"
                               href="../movimientos/registrar.php?tipo=ENTRADA&amp;motivo=Compra%20a%20proveedor&amp;id_producto=<?= (int) $p['id_producto'] ?>">
                                <i class="bi bi-box-arrow-in-down"></i>
                            </a>
                        <?php endif; ?>
                        <?php if (es_encargado()): ?>
                        <a class="btn btn-sm btn-outline-primary" title="Editar" href="form.php?id=<?= (int) $p['id_producto'] ?>">
                            <i class="bi bi-pencil"></i>
                        </a>
                        <?php endif; ?>
                        <a class="btn btn-sm btn-outline-secondary" title="Ver movimientos"
                           href="../movimientos/index.php?id_producto=<?= (int) $p['id_producto'] ?>">
                            <i class="bi bi-clock-history"></i>
                        </a>

                        <?php if (es_encargado()): ?>
                        <!-- Desactivar / activar (borrado lógico) -->
                        <form method="post" action="estado.php" class="d-inline"
                              data-confirm="<?= $p['estado']
                                  ? '¿Desactivar ' . e($p['nombre']) . '? Ya no aparecerá para registrar movimientos.'
                                  : '¿Activar ' . e($p['nombre']) . '?' ?>">
                            <?= csrf_campo() ?>
                            <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                            <?php if ($p['estado']): ?>
                                <button class="btn btn-sm btn-outline-danger" title="Desactivar"><i class="bi bi-slash-circle"></i></button>
                            <?php else: ?>
                                <button class="btn btn-sm btn-outline-success" title="Activar"><i class="bi bi-check-circle"></i></button>
                            <?php endif; ?>
                        </form>

                        <!-- RN05: solo se puede eliminar si nunca tuvo movimientos -->
                        <?php if ((int) $p['movimientos'] === 0): ?>
                            <form method="post" action="eliminar.php" class="d-inline"
                                  data-confirm="<?= '¿Eliminar definitivamente ' . e($p['nombre']) . '? Esta acción no se puede deshacer.' ?>">
                                <?= csrf_campo() ?>
                                <input type="hidden" name="id" value="<?= (int) $p['id_producto'] ?>">
                                <button class="btn btn-sm btn-danger" title="Eliminar"><i class="bi bi-trash"></i></button>
                            </form>
                        <?php endif; ?>
                        <?php endif; /* fin acciones del Encargado */ ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$productos): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No se encontraron productos con esos filtros.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php if (es_encargado()): ?>
<p class="small text-muted mt-2">
    <i class="bi bi-info-circle"></i>
    El botón <i class="bi bi-trash"></i> solo aparece en productos sin movimientos.
    Un producto con movimientos se desactiva para conservar su historial.
</p>
<?php endif; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>