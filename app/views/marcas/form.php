<?php
$marca = $marca ?? null;
$isEdit = $marca !== null;
?>

<form id="formMarcaModal" method="POST">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $marca['id'] ?>">
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">

    <div class="form-group">
        <label for="nombre_modal">Nombre <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="nombre_modal" name="nombre"
            value="<?= $marca['nombre'] ?? '' ?>" required>
        <div class="invalid-feedback"></div>
    </div>

    <div class="form-group">
        <label for="descripcion_modal">Descripción</label>
        <textarea class="form-control" id="descripcion_modal" name="descripcion"
            rows="3"><?= $marca['descripcion'] ?? '' ?></textarea>
        <div class="invalid-feedback"></div>
    </div>

    <div class="form-group">
        <label for="pais_modal">País de Origen</label>
        <select class="form-control" id="pais_modal" name="pais">
            <option value="">Seleccionar país...</option>
            <option value="Alemania" <?= ($marca['pais'] ?? '') === 'Alemania' ? 'selected' : '' ?>>Alemania</option>
            <option value="Brasil" <?= ($marca['pais'] ?? '') === 'Brasil' ? 'selected' : '' ?>>Brasil</option>
            <option value="China" <?= ($marca['pais'] ?? '') === 'China' ? 'selected' : '' ?>>China</option>
            <option value="Estados Unidos" <?= ($marca['pais'] ?? '') === 'Estados Unidos' ? 'selected' : '' ?>>Estados Unidos</option>
            <option value="Francia" <?= ($marca['pais'] ?? '') === 'Francia' ? 'selected' : '' ?>>Francia</option>
            <option value="Italia" <?= ($marca['pais'] ?? '') === 'Italia' ? 'selected' : '' ?>>Italia</option>
            <option value="Japón" <?= ($marca['pais'] ?? '') === 'Japón' ? 'selected' : '' ?>>Japón</option>
            <option value="México" <?= ($marca['pais'] ?? '') === 'México' ? 'selected' : '' ?>>México</option>
            <option value="Perú" <?= ($marca['pais'] ?? '') === 'Perú' ? 'selected' : '' ?>>Perú</option>
        </select>
    </div>

    <div class="form-group">
        <label for="sitio_web_modal">Sitio Web</label>
        <input type="url" class="form-control" id="sitio_web_modal" name="sitio_web"
            value="<?= $marca['sitio_web'] ?? '' ?>" placeholder="https://www.ejemplo.com">
        <div class="invalid-feedback"></div>
    </div>

    <div class="form-group">
        <label for="estado_modal">Estado</label>
        <select class="form-control" id="estado_modal" name="estado">
            <option value="1" <?= ($marca['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
            <option value="0" <?= ($marca['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
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