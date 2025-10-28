<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags"></i> <?= $title ?>
        </h1>
        <a href="?page=subcategorias" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver al Listado
        </a>
    </div>

    <!-- Formulario -->
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><?= $title ?></h6>
                </div>
                <div class="card-body">
                    <form id="subcategoriaForm" action="<?= $action ?>" method="<?= $method ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php if ($subcategoria): ?>
                            <input type="hidden" name="id" value="<?= $subcategoria['id_subcategoria'] ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_categoria">Categoría <span class="text-danger">*</span></label>
                                    <select class="form-control" id="id_categoria" name="id_categoria" required>
                                        <option value="">Seleccionar categoría...</option>
                                        <?php foreach ($categorias as $categoria): ?>
                                            <option value="<?= $categoria['id_categoria'] ?>"
                                                <?= ($subcategoria['id_categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($categoria['nombre']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        value="<?= htmlspecialchars($subcategoria['nombre'] ?? '') ?>"
                                        required maxlength="100" placeholder="Ej: Aceites para Motor">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion"
                                rows="3" maxlength="255"
                                placeholder="Descripción opcional de la subcategoría..."><?= htmlspecialchars($subcategoria['descripcion'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Máximo 255 caracteres</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <?php if ($subcategoria): ?>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>ID de Subcategoría</label>
                                        <input type="text" class="form-control" value="<?= $subcategoria['id_subcategoria'] ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Productos Asociados</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($subcategoria['total_productos']) ? $subcategoria['total_productos'] . ' productos' : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <hr>

                        <div class="form-group text-right">
                            <a href="?page=subcategorias" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary ml-2">
                                <i class="fas fa-save"></i>
                                <?= $subcategoria ? 'Actualizar' : 'Guardar' ?> Subcategoría
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('subcategoriaForm').addEventListener('submit', function(e) {
        e.preventDefault();

        // Limpiar errores anteriores
        document.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        document.querySelectorAll('.invalid-feedback').forEach(el => el.textContent = '');

        const formData = new FormData(this);
        const submitBtn = this.querySelector('button[type="submit"]');
        const originalText = submitBtn.innerHTML;

        // Deshabilitar botón
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';

        fetch(this.action, {
                method: this.method,
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    CruzMotor.showAlert('success', 'Éxito',
                        '<?= $subcategoria ? "Subcategoría actualizada" : "Subcategoría creada" ?> correctamente');
                    setTimeout(() => {
                        window.location.href = '?page=subcategorias';
                    }, 1000);
                } else {
                    if (data.errors) {
                        // Mostrar errores de validación
                        CruzMotor.mostrarErrores(data.errors);
                    } else {
                        CruzMotor.showAlert('error', 'Error', data.message);
                    }
                }
            })
            .catch(error => {
                console.error('Error:', error);
                CruzMotor.showAlert('error', 'Error', 'Error al procesar la solicitud');
            })
            .finally(() => {
                // Rehabilitar botón
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
    });

    // Contador de caracteres para descripción
    document.getElementById('descripcion').addEventListener('input', function() {
        const maxLength = 255;
        const currentLength = this.value.length;
        const remaining = maxLength - currentLength;

        let small = this.parentNode.querySelector('.form-text');
        if (remaining < 50) {
            small.innerHTML = `Máximo 255 caracteres. <span class="text-warning">Quedan ${remaining} caracteres</span>`;
        } else {
            small.innerHTML = 'Máximo 255 caracteres';
        }
    });
</script>

</div>
</div>