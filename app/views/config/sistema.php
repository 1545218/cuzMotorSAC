<?php include_once '../app/views/layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (isset($item['url'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['url'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($item['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <!-- Título -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($title) ?></h1>
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoParametroModal">
                    <i class="fas fa-plus"></i> Nuevo Parámetro
                </button>
            </div>

            <!-- Formulario de parámetros -->
            <form method="POST" action="?page=config&action=updateSistema" id="formParametros">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-cogs"></i> Parámetros de Configuración
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($parametros)): ?>
                            <div class="text-center py-4">
                                <i class="fas fa-cogs fa-3x text-muted mb-3"></i>
                                <p class="text-muted">No hay parámetros del sistema configurados</p>
                                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#nuevoParametroModal">
                                    <i class="fas fa-plus"></i> Crear Primer Parámetro
                                </button>
                            </div>
                        <?php else: ?>
                            <div class="row">
                                <?php foreach ($parametros as $parametro): ?>
                                    <div class="col-md-6 mb-4">
                                        <div class="card h-100">
                                            <div class="card-body">
                                                <h6 class="card-title text-primary">
                                                    <i class="fas fa-sliders-h"></i> <?= htmlspecialchars($parametro['clave']) ?>
                                                </h6>

                                                <?php if ($parametro['descripcion']): ?>
                                                    <p class="card-text small text-muted mb-3">
                                                        <?= htmlspecialchars($parametro['descripcion']) ?>
                                                    </p>
                                                <?php endif; ?>

                                                <div class="form-group">
                                                    <label for="param_<?= $parametro['id_parametro'] ?>" class="form-label fw-bold">
                                                        Valor:
                                                    </label>

                                                    <?php if (in_array($parametro['clave'], ['NOTIFICAR_STOCK_BAJO', 'PRECIO_IVA_INCLUIDO'])): ?>
                                                        <!-- Switch para parámetros booleanos -->
                                                        <div class="form-check form-switch">
                                                            <input type="hidden" name="param_<?= $parametro['id_parametro'] ?>" value="0">
                                                            <input class="form-check-input"
                                                                type="checkbox"
                                                                id="param_<?= $parametro['id_parametro'] ?>"
                                                                name="param_<?= $parametro['id_parametro'] ?>"
                                                                value="1"
                                                                <?= $parametro['valor'] == '1' ? 'checked' : '' ?>>
                                                            <label class="form-check-label" for="param_<?= $parametro['id_parametro'] ?>">
                                                                <?= $parametro['valor'] == '1' ? 'Activado' : 'Desactivado' ?>
                                                            </label>
                                                        </div>
                                                    <?php elseif (in_array($parametro['clave'], ['STOCK_MINIMO_GLOBAL', 'DIAS_BACKUP_AUTOMATICO', 'HORAS_SESION_MAXIMA', 'PORCENTAJE_IVA'])): ?>
                                                        <!-- Input numérico -->
                                                        <input type="number"
                                                            class="form-control"
                                                            id="param_<?= $parametro['id_parametro'] ?>"
                                                            name="param_<?= $parametro['id_parametro'] ?>"
                                                            value="<?= htmlspecialchars($parametro['valor']) ?>"
                                                            min="0"
                                                            step="<?= $parametro['clave'] === 'PORCENTAJE_IVA' ? '0.01' : '1' ?>">
                                                    <?php else: ?>
                                                        <!-- Input de texto para otros parámetros -->
                                                        <input type="text"
                                                            class="form-control"
                                                            id="param_<?= $parametro['id_parametro'] ?>"
                                                            name="param_<?= $parametro['id_parametro'] ?>"
                                                            value="<?= htmlspecialchars($parametro['valor']) ?>">
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>

                            <hr>

                            <div class="d-flex justify-content-between">
                                <a href="?page=config" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Volver
                                </a>
                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-save"></i> Guardar Cambios
                                </button>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </form>

            <!-- Enlaces adicionales -->
            <div class="mt-4">
                <a href="?page=config&action=almacen" class="btn btn-info">
                    <i class="fas fa-warehouse"></i> Configuración de Almacén
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal para nuevo parámetro -->
<div class="modal fade" id="nuevoParametroModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Crear Nuevo Parámetro</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=config&action=createParametro">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="modal-body">
                    <div class="mb-3">
                        <label for="clave" class="form-label">
                            <i class="fas fa-key"></i> Clave del Parámetro *
                        </label>
                        <input type="text"
                            class="form-control"
                            id="clave"
                            name="clave"
                            required
                            maxlength="100"
                            placeholder="Ej: NUEVO_PARAMETRO"
                            style="text-transform: uppercase;">
                        <small class="form-text text-muted">
                            Use MAYÚSCULAS y guiones bajos. Ej: MI_PARAMETRO
                        </small>
                    </div>

                    <div class="mb-3">
                        <label for="valor" class="form-label">
                            <i class="fas fa-edit"></i> Valor *
                        </label>
                        <input type="text"
                            class="form-control"
                            id="valor"
                            name="valor"
                            required
                            maxlength="255"
                            placeholder="Valor del parámetro">
                    </div>

                    <div class="mb-3">
                        <label for="descripcion" class="form-label">
                            <i class="fas fa-info-circle"></i> Descripción
                        </label>
                        <textarea class="form-control"
                            id="descripcion"
                            name="descripcion"
                            rows="3"
                            placeholder="Descripción opcional del parámetro..."></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Crear Parámetro
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Convertir clave a mayúsculas automáticamente
    document.getElementById('clave').addEventListener('input', function(e) {
        e.target.value = e.target.value.toUpperCase().replace(/[^A-Z0-9_]/g, '');
    });

    // Actualizar etiquetas de switches
    document.querySelectorAll('.form-check-input').forEach(function(input) {
        if (input.type === 'checkbox') {
            input.addEventListener('change', function() {
                const label = this.nextElementSibling;
                label.textContent = this.checked ? 'Activado' : 'Desactivado';
            });
        }
    });

    // Confirmación antes de guardar cambios
    document.getElementById('formParametros').addEventListener('submit', function(e) {
        if (!confirm('¿Está seguro de que desea guardar los cambios en los parámetros del sistema?')) {
            e.preventDefault();
        }
    });
</script>

<?php include_once '../app/views/layout/footer.php'; ?>