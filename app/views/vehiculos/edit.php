<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Vehículo:
                        <span class="text-primary"><?= htmlspecialchars($vehiculo['placa']) ?></span>
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Mensajes de error -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <form method="POST" action="?page=vehiculos&action=update&id=<?= $vehiculo['id_vehiculo'] ?>" id="formVehiculo">
                        <div class="row">
                            <!-- Información actual -->
                            <div class="col-md-12 mb-3">
                                <div class="alert alert-info">
                                    <h6><i class="fas fa-info-circle"></i> Cliente Actual</h6>
                                    <strong><?= htmlspecialchars($vehiculo['cliente_nombre'] . ' ' . $vehiculo['cliente_apellido']) ?></strong>
                                </div>
                            </div>

                            <!-- Cliente -->
                            <div class="col-md-12 mb-3">
                                <label for="id_cliente" class="form-label">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id_cliente'] ?>"
                                            <?= ($vehiculo['id_cliente'] == $cliente['id_cliente']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>
                                            <?php if ($cliente['numero_documento']): ?>
                                                - <?= htmlspecialchars($cliente['numero_documento']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione el cliente propietario del vehículo
                                </small>
                            </div>

                            <!-- Placa -->
                            <div class="col-md-6 mb-3">
                                <label for="placa" class="form-label">
                                    Placa <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="placa"
                                    name="placa"
                                    value="<?= htmlspecialchars($vehiculo['placa']) ?>"
                                    placeholder="Ej: ABC-123"
                                    style="text-transform: uppercase;"
                                    maxlength="20"
                                    required>
                                <small class="form-text text-muted">
                                    Placa del vehículo
                                </small>
                            </div>

                            <!-- Marca -->
                            <div class="col-md-6 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text"
                                    class="form-control"
                                    id="marca"
                                    name="marca"
                                    value="<?= htmlspecialchars($vehiculo['marca'] ?? '') ?>"
                                    placeholder="Ej: Toyota, Honda, Ford"
                                    maxlength="100">
                                <small class="form-text text-muted">
                                    Marca del vehículo
                                </small>
                            </div>

                            <!-- Modelo -->
                            <div class="col-md-12 mb-3">
                                <label for="modelo" class="form-label">Modelo</label>
                                <input type="text"
                                    class="form-control"
                                    id="modelo"
                                    name="modelo"
                                    value="<?= htmlspecialchars($vehiculo['modelo'] ?? '') ?>"
                                    placeholder="Ej: Corolla 2020, Civic EX, F-150"
                                    maxlength="100">
                                <small class="form-text text-muted">
                                    Modelo y año del vehículo
                                </small>
                            </div>
                        </div>

                        <!-- Información adicional -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-history"></i> Información del Registro
                                        </h6>
                                        <small class="text-muted">
                                            ID del vehículo: <strong><?= $vehiculo['id_vehiculo'] ?></strong>
                                        </small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row mt-3">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="?page=vehiculos" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <div>
                                        <button type="button" class="btn btn-outline-danger me-2" onclick="confirmarEliminacion()">
                                            <i class="fas fa-trash"></i> Eliminar
                                        </button>
                                        <button type="submit" class="btn btn-primary" id="btnGuardar">
                                            <i class="fas fa-save"></i> Actualizar Vehículo
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar el vehículo <strong><?= htmlspecialchars($vehiculo['placa']) ?></strong>?</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form method="POST" action="?page=vehiculos&action=delete&id=<?= $vehiculo['id_vehiculo'] ?>" style="display: inline;">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Convertir placa a mayúsculas automáticamente
    document.getElementById('placa').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Validación del formulario
    document.getElementById('formVehiculo').addEventListener('submit', function(e) {
        const cliente = document.getElementById('id_cliente').value;
        const placa = document.getElementById('placa').value.trim();

        if (!cliente) {
            e.preventDefault();
            alert('Debe seleccionar un cliente');
            document.getElementById('id_cliente').focus();
            return;
        }

        if (!placa) {
            e.preventDefault();
            alert('Debe ingresar la placa del vehículo');
            document.getElementById('placa').focus();
            return;
        }

        // Deshabilitar botón para evitar doble envío
        document.getElementById('btnGuardar').disabled = true;
    });

    // Confirmar eliminación
    function confirmarEliminacion() {
        $('#modalEliminar').modal('show');
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);

    // Detectar cambios en el formulario
    let datosOriginales = {
        cliente: document.getElementById('id_cliente').value,
        placa: document.getElementById('placa').value,
        marca: document.getElementById('marca').value,
        modelo: document.getElementById('modelo').value
    };

    function hayacambios() {
        return (
            datosOriginales.cliente !== document.getElementById('id_cliente').value ||
            datosOriginales.placa !== document.getElementById('placa').value ||
            datosOriginales.marca !== document.getElementById('marca').value ||
            datosOriginales.modelo !== document.getElementById('modelo').value
        );
    }

    // Advertir antes de salir si hay cambios sin guardar
    window.addEventListener('beforeunload', function(e) {
        if (hayacambios()) {
            e.preventDefault();
            e.returnValue = '';
        }
    });
</script>