<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit"></i> Editar Orden de Trabajo #<?= $orden['id_orden'] ?>
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

                    <!-- Información actual -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información Actual</h6>
                        <strong>Cliente:</strong> <?= htmlspecialchars($orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']) ?><br>
                        <strong>Estado:</strong> <?= ucfirst($orden['estado']) ?><br>
                        <strong>Fecha:</strong> <?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?>
                    </div>

                    <form method="POST" action="?page=ordenes&action=update&id=<?= $orden['id_orden'] ?>" id="formOrden">
                        <div class="row">
                            <!-- Cliente -->
                            <div class="col-md-12 mb-3">
                                <label for="id_cliente" class="form-label">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id_cliente'] ?>"
                                            <?= $orden['id_cliente'] == $cliente['id_cliente'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>
                                            <?php if ($cliente['numero_documento']): ?>
                                                - <?= htmlspecialchars($cliente['numero_documento']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Vehículo -->
                            <div class="col-md-12 mb-3">
                                <label for="id_vehiculo" class="form-label">
                                    Vehículo <span class="text-muted">(Opcional)</span>
                                </label>
                                <select class="form-control" id="id_vehiculo" name="id_vehiculo">
                                    <option value="">Sin vehículo específico</option>
                                    <?php foreach ($vehiculos as $vehiculo): ?>
                                        <?php if ($vehiculo['id_cliente'] == $orden['id_cliente']): ?>
                                            <option value="<?= $vehiculo['id_vehiculo'] ?>"
                                                <?= $orden['id_vehiculo'] == $vehiculo['id_vehiculo'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($vehiculo['placa'] . ' - ' . $vehiculo['vehiculo_marca'] . ' ' . $vehiculo['vehiculo_modelo']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="abierta" <?= $orden['estado'] == 'abierta' ? 'selected' : '' ?>>Abierta</option>
                                    <option value="en_proceso" <?= $orden['estado'] == 'en_proceso' ? 'selected' : '' ?>>En Proceso</option>
                                    <option value="cerrada" <?= $orden['estado'] == 'cerrada' ? 'selected' : '' ?>>Cerrada</option>
                                </select>
                            </div>

                            <!-- Fecha (solo mostrar, no editable) -->
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Fecha de Creación</label>
                                <input type="text"
                                    class="form-control"
                                    value="<?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?>"
                                    readonly>
                                <small class="form-text text-muted">
                                    La fecha de creación no se puede modificar
                                </small>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-md-12 mb-3">
                                <label for="observaciones" class="form-label">
                                    Observaciones y Descripción del Trabajo
                                </label>
                                <textarea class="form-control"
                                    id="observaciones"
                                    name="observaciones"
                                    rows="5"
                                    placeholder="Describa el trabajo a realizar, problemas reportados, observaciones especiales..."><?= htmlspecialchars($orden['observaciones']) ?></textarea>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="?page=ordenes&action=show&id=<?= $orden['id_orden'] ?>" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <div>
                                        <a href="?page=ordenes" class="btn btn-outline-secondary">
                                            <i class="fas fa-times"></i> Cancelar
                                        </a>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Actualizar Orden
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

<script>
    $(document).ready(function() {
        // Cargar vehículos cuando se cambia el cliente
        $('#id_cliente').change(function() {
            const clienteId = $(this).val();
            const vehiculoActual = '<?= $orden['id_vehiculo'] ?>';

            // Limpiar select de vehículos
            $('#id_vehiculo').html('<option value="">Sin vehículo específico</option>');

            if (clienteId) {
                // Cargar vehículos del cliente
                $.get('?page=ordenes&action=vehiculosPorCliente&id=' + clienteId)
                    .done(function(response) {
                        if (response.success && response.vehiculos.length > 0) {
                            response.vehiculos.forEach(function(vehiculo) {
                                const selected = vehiculo.id_vehiculo == vehiculoActual ? 'selected' : '';
                                $('#id_vehiculo').append(
                                    '<option value="' + vehiculo.id_vehiculo + '" ' + selected + '>' +
                                    vehiculo.placa + ' - ' + vehiculo.marca + ' ' + vehiculo.modelo +
                                    '</option>'
                                );
                            });
                        }
                    })
                    .fail(function() {
                        console.log('Error al cargar vehículos');
                    });
            }
        });

        // Validación del formulario
        $('#formOrden').submit(function(e) {
            const cliente = $('#id_cliente').val();

            if (!cliente) {
                e.preventDefault();
                mostrarError('Debe seleccionar un cliente');
                $('#id_cliente').focus();
                return false;
            }

            return true;
        });

        // Advertencia de cambios no guardados
        let formularioModificado = false;

        $('#formOrden input, #formOrden select, #formOrden textarea').change(function() {
            formularioModificado = true;
        });

        window.addEventListener('beforeunload', function(e) {
            if (formularioModificado) {
                e.preventDefault();
                e.returnValue = '';
            }
        });

        $('#formOrden').submit(function() {
            formularioModificado = false;
        });
    });

    function mostrarError(mensaje) {
        const alert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> ${mensaje}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        $('.card-body').prepend(alert);

        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>