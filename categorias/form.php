<?php
/**
 * Crear o editar una categoría (RF09).
 * Sin ?id= crea una nueva; con ?id=N edita la existente.
 */
require_once __DIR__ . '/../includes/sesion.php';
solo_encargado(); // solo el Encargado puede usar esta página

$id       = entero($_GET['id'] ?? null, 1);
$esEdicion = $id !== null;
$errores  = [];
$datos    = ['nombre' => '', 'descripcion' => ''];

// Si es edición, se cargan los datos actuales
if ($esEdicion) {
    $stmt = $pdo->prepare('SELECT nombre, descripcion FROM categoria WHERE id_categoria = ?');
    $stmt->execute([$id]);
    $actual = $stmt->fetch();
    if (!$actual) {
        set_mensaje('danger', 'La categoría no existe.');
        redirigir('categorias/index.php');
    }
    $datos = ['nombre' => $actual['nombre'], 'descripcion' => (string) $actual['descripcion']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $datos['nombre']      = texto($_POST['nombre'] ?? '');
    $datos['descripcion'] = texto($_POST['descripcion'] ?? '');

    // Validaciones en el servidor
    if (!csrf_valido()) {
        $errores['general'] = 'La sesión expiró. Recargue la página e intente nuevamente.';
    }
    if ($datos['nombre'] === '') {
        $errores['nombre'] = 'El nombre es obligatorio.';
    } elseif (largo($datos['nombre']) > 60) {
        $errores['nombre'] = 'El nombre no puede superar los 60 caracteres.';
    } else {
        // El nombre no puede repetirse (sin contar la misma categoría al editar)
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM categoria WHERE nombre = ? AND id_categoria <> ?');
        $stmt->execute([$datos['nombre'], $id ?? 0]);
        if ($stmt->fetchColumn() > 0) {
            $errores['nombre'] = 'Ya existe una categoría con ese nombre.';
        }
    }
    if (largo($datos['descripcion']) > 255) {
        $errores['descripcion'] = 'La descripción no puede superar los 255 caracteres.';
    }

    if (!$errores) {
        $descripcion = $datos['descripcion'] === '' ? null : $datos['descripcion'];

        if ($esEdicion) {
            $stmt = $pdo->prepare('UPDATE categoria SET nombre = ?, descripcion = ? WHERE id_categoria = ?');
            $stmt->execute([$datos['nombre'], $descripcion, $id]);
            set_mensaje('success', 'Categoría actualizada correctamente.');
        } else {
            $stmt = $pdo->prepare('INSERT INTO categoria (nombre, descripcion) VALUES (?, ?)');
            $stmt->execute([$datos['nombre'], $descripcion]);
            set_mensaje('success', 'Categoría "' . $datos['nombre'] . '" creada correctamente.');
        }
        redirigir('categorias/index.php');
    }
}

$titulo  = $esEdicion ? 'Editar categoría' : 'Nueva categoría';
$seccion = 'categorias';
require __DIR__ . '/../includes/header.php';
?>

<nav aria-label="breadcrumb">
    <ol class="breadcrumb">
        <li class="breadcrumb-item"><a href="index.php">Categorías</a></li>
        <li class="breadcrumb-item active"><?= e($titulo) ?></li>
    </ol>
</nav>

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card">
            <div class="card-body">
                <h1 class="h4 mb-3"><?= e($titulo) ?></h1>

                <?php if (isset($errores['general'])): ?>
                    <div class="alert alert-danger"><?= e($errores['general']) ?></div>
                <?php endif; ?>

                <form method="post" class="needs-validation" novalidate>
                    <?= csrf_campo() ?>

                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre <span class="text-danger">*</span></label>
                        <input type="text" id="nombre" name="nombre" maxlength="60" required
                               class="form-control<?= isset($errores['nombre']) ? ' is-invalid' : '' ?>"
                               value="<?= e($datos['nombre']) ?>">
                        <div class="invalid-feedback"><?= e($errores['nombre'] ?? 'Ingrese el nombre de la categoría.') ?></div>
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea id="descripcion" name="descripcion" maxlength="255" rows="3"
                                  class="form-control<?= isset($errores['descripcion']) ? ' is-invalid' : '' ?>"><?= e($datos['descripcion']) ?></textarea>
                        <div class="invalid-feedback"><?= e($errores['descripcion'] ?? 'Máximo 255 caracteres.') ?></div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary"><i class="bi bi-save"></i> Guardar</button>
                        <a href="index.php" class="btn btn-outline-secondary">Cancelar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>