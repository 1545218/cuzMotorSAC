<?php
$usuario = $usuario ?? null;
$isEdit = $usuario !== null;
$roles = ['admin' => 'Administrador', 'vendedor' => 'Vendedor', 'mecanico' => 'Mecánico'];
?>

<form id="formUsuario" method="POST">
    <?php if ($isEdit): ?>
        <input type="hidden" name="id" value="<?= $usuario['id_usuario'] ?>">
    <?php endif; ?>

    <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="username">Usuario <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="username" name="username"
                    value="<?= $usuario['username'] ?? '' ?>" required>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="email">Email <span class="text-danger">*</span></label>
                <input type="email" class="form-control" id="email" name="email"
                    value="<?= $usuario['email'] ?? '' ?>" required>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="nombre">Nombre <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="nombre" name="nombre"
                    value="<?= $usuario['nombre'] ?? '' ?>" required>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="apellido">Apellido <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="apellido" name="apellido"
                    value="<?= $usuario['apellido'] ?? '' ?>" required>
                <div class="invalid-feedback"></div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label for="rol">Rol <span class="text-danger">*</span></label>
                <select class="form-control" id="rol" name="rol" required>
                    <option value="">Seleccionar rol...</option>
                    <?php foreach ($roles as $key => $value): ?>
                        <option value="<?= $key ?>" <?= ($usuario['rol'] ?? '') === $key ? 'selected' : '' ?>>
                            <?= $value ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <div class="invalid-feedback"></div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="form-group">
                <label for="estado">Estado</label>
                <select class="form-control" id="estado" name="estado">
                    <option value="1" <?= ($usuario['estado'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= ($usuario['estado'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>
    </div>

    <?php if (!$isEdit): ?>
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password">Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password" name="password" required>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label for="password_confirm">Confirmar Contraseña <span class="text-danger">*</span></label>
                    <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                    <div class="invalid-feedback"></div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="form-group">
        <label for="telefono">Teléfono</label>
        <input type="text" class="form-control" id="telefono" name="telefono"
            value="<?= $usuario['telefono'] ?? '' ?>">
    </div>

    <div class="form-group">
        <label for="direccion">Dirección</label>
        <textarea class="form-control" id="direccion" name="direccion" rows="2"><?= $usuario['direccion'] ?? '' ?></textarea>
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