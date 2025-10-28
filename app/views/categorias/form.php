<?php
$categoria = $categoria ?? null;
$isEdit = $categoria !== null;
?>

<form id="formCategoriaModal" method="POST">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $categoria['id'] ?>">
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">

    <div class="form-group">
        <label for="nombre_modal">Nombre <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="nombre_modal" name="nombre"
            value="<?= $categoria['nombre'] ?? '' ?>" required>
        <div class="invalid-feedback"></div>
    </div>

    <div class="form-group">
        <label for="descripcion_modal">Descripción</label>
        <textarea class="form-control" id="descripcion_modal" name="descripcion"
            rows="3"><?= $categoria['descripcion'] ?? '' ?></textarea>
        <div class="invalid-feedback"></div>
    </div>

    <div class="form-group">
        <label for="estado_modal">Estado</label>
        <select class="form-control" id="estado_modal" name="estado">
            <option value="1" <?= ($categoria['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= ($categoria['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
        </select>
    </div>

    <div class="form-group">
        <button type="submit" class="btn btn-primary">
            <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Guardar' ?>
        </button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">
            <i class="fas fa-times"></i> Cancelar
        </button>
    </div>
</form>