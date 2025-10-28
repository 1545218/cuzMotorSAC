<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ruler"></i> <?= $title ?>
        </h1>
        <a href="?page=unidades" class="btn btn-secondary btn-sm">
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
                    <form id="unidadForm" action="<?= $action ?>" method="<?= $method ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php if ($unidad): ?>
                            <input type="hidden" name="id" value="<?= $unidad['id_unidad'] ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre de la Unidad <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        value="<?= htmlspecialchars($unidad['nombre'] ?? '') ?>"
                                        required maxlength="50" placeholder="Ej: Kilogramo">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="simbolo">Símbolo <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="simbolo" name="simbolo"
                                        value="<?= htmlspecialchars($unidad['simbolo'] ?? '') ?>"
                                        required maxlength="10" placeholder="Ej: KG" style="text-transform: uppercase;">
                                    <small class="form-text text-muted">Abreviatura de la unidad (máximo 10 caracteres)</small>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion"
                                rows="3" maxlength="255"
                                placeholder="Descripción detallada de la unidad de medida..."><?= htmlspecialchars($unidad['descripcion'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Máximo 255 caracteres</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <?php if ($unidad): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ID de Unidad</label>
                                        <input type="text" class="form-control" value="<?= $unidad['id_unidad'] ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Productos Usando</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($unidad['productos_usando']) ? $unidad['productos_usando'] . ' productos' : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Estado</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($unidad['productos_usando']) && $unidad['productos_usando'] > 0 ? 'En uso' : 'Sin usar' ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha de Creación</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($unidad['fecha_creacion']) ? date('d/m/Y H:i', strtotime($unidad['fecha_creacion'])) : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Última Actualización</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($unidad['fecha_actualizacion']) && $unidad['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($unidad['fecha_actualizacion'])) : 'Sin actualizaciones' ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <?php if (isset($unidad['productos_usando']) && $unidad['productos_usando'] > 0): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <strong>Atención:</strong> Esta unidad está siendo utilizada por <?= $unidad['productos_usando'] ?> producto(s).
                                    Cambiar el nombre o símbolo afectará la visualización en esos productos.
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <!-- Tips de ayuda -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Ejemplos de Unidades Comunes:</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li><strong>Peso:</strong> Kilogramo (KG), Gramo (G), Libra (LB)</li>
                                        <li><strong>Volumen:</strong> Litro (L), Mililitro (ML), Galón (GAL)</li>
                                        <li><strong>Longitud:</strong> Metro (M), Centímetro (CM), Pulgada (IN)</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="mb-0">
                                        <li><strong>Cantidad:</strong> Unidad (UN), Pieza (PZ), Docena (DOC)</li>
                                        <li><strong>Empaque:</strong> Caja (CJ), Paquete (PQ), Bolsa (BL)</li>
                                        <li><strong>Área:</strong> Metro² (M²), Centímetro² (CM²)</li>
                                    </ul>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group text-right">
                            <a href="?page=unidades" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary ml-2">
                                <i class="fas fa-save"></i>
                                <?= $unidad ? 'Actualizar' : 'Guardar' ?> Unidad
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('unidadForm').addEventListener('submit', function(e) {
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
                        '<?= $unidad ? "Unidad actualizada" : "Unidad creada" ?> correctamente');
                    setTimeout(() => {
                        window.location.href = '?page=unidades';
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

    // Convertir símbolo a mayúsculas automáticamente
    document.getElementById('simbolo').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Sugerencias dinámicas basadas en el nombre
    document.getElementById('nombre').addEventListener('input', function() {
        const simboloInput = document.getElementById('simbolo');

        if (!simboloInput.value && this.value) {
            const sugerencias = {
                'kilogramo': 'KG',
                'gramo': 'G',
                'libra': 'LB',
                'litro': 'L',
                'mililitro': 'ML',
                'galón': 'GAL',
                'galon': 'GAL',
                'metro': 'M',
                'centímetro': 'CM',
                'centimetro': 'CM',
                'pulgada': 'IN',
                'unidad': 'UN',
                'pieza': 'PZ',
                'docena': 'DOC',
                'caja': 'CJ',
                'paquete': 'PQ',
                'bolsa': 'BL',
                'metro cuadrado': 'M²',
                'metro²': 'M²'
            };

            const nombreLower = this.value.toLowerCase();
            for (const [nombre, simbolo] of Object.entries(sugerencias)) {
                if (nombreLower.includes(nombre)) {
                    simboloInput.value = simbolo;
                    break;
                }
            }
        }
    });

    // Validación en tiempo real
    document.getElementById('simbolo').addEventListener('blur', function() {
        if (this.value.length > 10) {
            this.classList.add('is-invalid');
            this.parentNode.querySelector('.invalid-feedback').textContent = 'El símbolo no puede exceder 10 caracteres';
        } else {
            this.classList.remove('is-invalid');
            this.parentNode.querySelector('.invalid-feedback').textContent = '';
        }
    });

    document.getElementById('nombre').addEventListener('blur', function() {
        if (this.value.length > 50) {
            this.classList.add('is-invalid');
            this.parentNode.querySelector('.invalid-feedback').textContent = 'El nombre no puede exceder 50 caracteres';
        } else {
            this.classList.remove('is-invalid');
            this.parentNode.querySelector('.invalid-feedback').textContent = '';
        }
    });
</script>

</div>
</div>