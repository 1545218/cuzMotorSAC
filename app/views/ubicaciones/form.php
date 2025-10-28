<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-map-marker-alt"></i> <?= $title ?>
        </h1>
        <a href="?page=ubicaciones" class="btn btn-secondary btn-sm">
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
                    <form id="ubicacionForm" action="<?= $action ?>" method="<?= $method ?>">
                        <input type="hidden" name="csrf_token" value="<?= $csrf_token ?>">
                        <?php if ($ubicacion): ?>
                            <input type="hidden" name="id" value="<?= $ubicacion['id_ubicacion'] ?>">
                        <?php endif; ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="nombre">Nombre de Ubicación <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nombre" name="nombre"
                                        value="<?= htmlspecialchars($ubicacion['nombre'] ?? '') ?>"
                                        required maxlength="100" placeholder="Ej: Depósito Principal A">
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="tipo">Tipo de Ubicación <span class="text-danger">*</span></label>
                                    <select class="form-control" id="tipo" name="tipo" required>
                                        <option value="">Seleccionar tipo...</option>
                                        <option value="deposito" <?= ($ubicacion['tipo'] ?? '') === 'deposito' ? 'selected' : '' ?>>Depósito</option>
                                        <option value="estante" <?= ($ubicacion['tipo'] ?? '') === 'estante' ? 'selected' : '' ?>>Estante</option>
                                        <option value="seccion" <?= ($ubicacion['tipo'] ?? '') === 'seccion' ? 'selected' : '' ?>>Sección</option>
                                        <option value="almacen" <?= ($ubicacion['tipo'] ?? '') === 'almacen' ? 'selected' : '' ?>>Almacén</option>
                                        <option value="otros" <?= ($ubicacion['tipo'] ?? '') === 'otros' ? 'selected' : '' ?>>Otros</option>
                                    </select>
                                    <div class="invalid-feedback"></div>
                                </div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="descripcion">Descripción</label>
                            <textarea class="form-control" id="descripcion" name="descripcion"
                                rows="3" maxlength="255"
                                placeholder="Descripción detallada de la ubicación..."><?= htmlspecialchars($ubicacion['descripcion'] ?? '') ?></textarea>
                            <small class="form-text text-muted">Máximo 255 caracteres</small>
                            <div class="invalid-feedback"></div>
                        </div>

                        <?php if ($ubicacion): ?>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>ID de Ubicación</label>
                                        <input type="text" class="form-control" value="<?= $ubicacion['id_ubicacion'] ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Productos Asociados</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($ubicacion['total_productos']) ? $ubicacion['total_productos'] . ' productos' : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Stock Total</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($ubicacion['stock_total']) ? number_format($ubicacion['stock_total']) . ' unidades' : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Fecha de Creación</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($ubicacion['fecha_creacion']) ? date('d/m/Y H:i', strtotime($ubicacion['fecha_creacion'])) : 'No disponible' ?>"
                                            readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Última Actualización</label>
                                        <input type="text" class="form-control"
                                            value="<?= isset($ubicacion['fecha_actualizacion']) && $ubicacion['fecha_actualizacion'] ? date('d/m/Y H:i', strtotime($ubicacion['fecha_actualizacion'])) : 'Sin actualizaciones' ?>"
                                            readonly>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Tips de ayuda -->
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle"></i> Tipos de Ubicación:</h6>
                            <ul class="mb-0">
                                <li><strong>Depósito:</strong> Espacios grandes de almacenamiento principal</li>
                                <li><strong>Estante:</strong> Mobiliario específico para organización vertical</li>
                                <li><strong>Sección:</strong> Áreas temáticas dentro de un depósito</li>
                                <li><strong>Almacén:</strong> Espacios de almacenamiento secundario</li>
                                <li><strong>Otros:</strong> Ubicaciones especiales o temporales</li>
                            </ul>
                        </div>

                        <hr>

                        <div class="form-group text-right">
                            <a href="?page=ubicaciones" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancelar
                            </a>
                            <button type="submit" class="btn btn-primary ml-2">
                                <i class="fas fa-save"></i>
                                <?= $ubicacion ? 'Actualizar' : 'Guardar' ?> Ubicación
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.getElementById('ubicacionForm').addEventListener('submit', function(e) {
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
                        '<?= $ubicacion ? "Ubicación actualizada" : "Ubicación creada" ?> correctamente');
                    setTimeout(() => {
                        window.location.href = '?page=ubicaciones';
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

    // Sugerencias dinámicas basadas en el tipo
    document.getElementById('tipo').addEventListener('change', function() {
        const nombreInput = document.getElementById('nombre');
        const descripcionInput = document.getElementById('descripcion');

        if (!nombreInput.value) {
            const sugerencias = {
                'deposito': {
                    nombre: 'Depósito Principal A',
                    descripcion: 'Depósito principal para almacenamiento de productos de alta rotación'
                },
                'estante': {
                    nombre: 'Estante E-001',
                    descripcion: 'Estante metálico con 5 niveles para productos pequeños'
                },
                'seccion': {
                    nombre: 'Sección Aceites',
                    descripcion: 'Área especializada para almacenamiento de aceites y lubricantes'
                },
                'almacen': {
                    nombre: 'Almacén Secundario',
                    descripcion: 'Almacén para productos de baja rotación y reserva'
                },
                'otros': {
                    nombre: 'Ubicación Temporal',
                    descripcion: 'Espacio temporal para productos en tránsito'
                }
            };

            if (sugerencias[this.value]) {
                nombreInput.placeholder = sugerencias[this.value].nombre;
                descripcionInput.placeholder = sugerencias[this.value].descripcion;
            }
        }
    });
</script>

</div>
</div>