<div class="container-fluid">
    <div class="row">
        <div class="col-md-10 offset-md-1">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> Orden de Trabajo #<?= $orden['id_orden'] ?>
                    </h5>
                    <div>
                        <a href="?page=ordenes&action=edit&id=<?= $orden['id_orden'] ?>" class="btn btn-primary">
                            <i class="fas fa-edit"></i> Editar
                        </a>
                        <a href="?page=ordenes" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <div class="row">
                        <!-- Información principal -->
                        <div class="col-md-8">
                            <!-- Datos de la orden -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-info-circle"></i> Información de la Orden</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Número de Orden:</strong><br>
                                            <span class="h4 text-primary">#<?= $orden['id_orden'] ?></span>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>Fecha y Hora:</strong><br>
                                            <?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?>
                                        </div>
                                    </div>
                                    <hr>
                                    <div class="row">
                                        <div class="col-md-12">
                                            <strong>Estado Actual:</strong><br>
                                            <select class="form-control form-control-lg estado-select mt-2"
                                                data-id="<?= $orden['id_orden'] ?>"
                                                style="max-width: 200px;">
                                                <option value="abierta" <?= $orden['estado'] == 'abierta' ? 'selected' : '' ?>>
                                                    🔴 Abierta
                                                </option>
                                                <option value="en_proceso" <?= $orden['estado'] == 'en_proceso' ? 'selected' : '' ?>>
                                                    🟡 En Proceso
                                                </option>
                                                <option value="cerrada" <?= $orden['estado'] == 'cerrada' ? 'selected' : '' ?>>
                                                    🟢 Cerrada
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Observaciones -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-comment-alt"></i> Observaciones y Descripción del Trabajo</h6>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($orden['observaciones'])): ?>
                                        <div class="alert alert-light">
                                            <?= nl2br(htmlspecialchars($orden['observaciones'])) ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">No se han registrado observaciones para esta orden.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Panel lateral -->
                        <div class="col-md-4">
                            <!-- Datos del cliente -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-user"></i> Cliente</h6>
                                </div>
                                <div class="card-body">
                                    <h5 class="text-primary mb-3">
                                        <?= htmlspecialchars($orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']) ?>
                                    </h5>

                                    <?php if ($orden['cliente_documento']): ?>
                                        <p class="mb-2">
                                            <strong>Documento:</strong><br>
                                            <?= htmlspecialchars($orden['cliente_documento']) ?>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($orden['cliente_telefono']): ?>
                                        <p class="mb-2">
                                            <strong>Teléfono:</strong><br>
                                            <a href="tel:<?= htmlspecialchars($orden['cliente_telefono']) ?>" class="text-decoration-none">
                                                <i class="fas fa-phone"></i> <?= htmlspecialchars($orden['cliente_telefono']) ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>

                                    <?php if ($orden['cliente_email']): ?>
                                        <p class="mb-2">
                                            <strong>Email:</strong><br>
                                            <a href="mailto:<?= htmlspecialchars($orden['cliente_email']) ?>" class="text-decoration-none">
                                                <i class="fas fa-envelope"></i> <?= htmlspecialchars($orden['cliente_email']) ?>
                                            </a>
                                        </p>
                                    <?php endif; ?>

                                    <div class="mt-3">
                                        <a href="?page=clientes&action=show&id=<?= $orden['id_cliente'] ?>"
                                            class="btn btn-outline-primary btn-sm">
                                            <i class="fas fa-eye"></i> Ver Cliente
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Datos del vehículo -->
                            <div class="card mb-4">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-car"></i> Vehículo</h6>
                                </div>
                                <div class="card-body">
                                    <?php if ($orden['vehiculo_placa']): ?>
                                        <h5 class="text-success mb-3">
                                            <?= htmlspecialchars($orden['vehiculo_placa']) ?>
                                        </h5>

                                        <p class="mb-2">
                                            <strong>Marca y Modelo:</strong><br>
                                            <?= htmlspecialchars($orden['vehiculo_marca'] . ' ' . $orden['vehiculo_modelo']) ?>
                                        </p>

                                        <?php if ($orden['vehiculo_anio']): ?>
                                            <p class="mb-2">
                                                <strong>Año:</strong><br>
                                                <?= htmlspecialchars($orden['vehiculo_anio']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <?php if ($orden['vehiculo_motor']): ?>
                                            <p class="mb-2">
                                                <strong>Motor:</strong><br>
                                                <?= htmlspecialchars($orden['vehiculo_motor']) ?>
                                            </p>
                                        <?php endif; ?>

                                        <div class="mt-3">
                                            <a href="?page=vehiculos&action=edit&id=<?= $orden['id_vehiculo'] ?>"
                                                class="btn btn-outline-success btn-sm">
                                                <i class="fas fa-eye"></i> Ver Vehículo
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="text-center text-muted">
                                            <i class="fas fa-car fa-2x mb-2"></i>
                                            <p>Sin vehículo específico asignado</p>
                                            <a href="?page=ordenes&action=edit&id=<?= $orden['id_orden'] ?>"
                                                class="btn btn-outline-secondary btn-sm">
                                                <i class="fas fa-plus"></i> Asignar Vehículo
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Acciones rápidas -->
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-tools"></i> Acciones</h6>
                                </div>
                                <div class="card-body">
                                    <div class="d-grid gap-2">
                                        <a href="?page=ordenes&action=edit&id=<?= $orden['id_orden'] ?>"
                                            class="btn btn-primary btn-sm">
                                            <i class="fas fa-edit"></i> Editar Orden
                                        </a>

                                        <button class="btn btn-info btn-sm" onclick="window.print()">
                                            <i class="fas fa-print"></i> Imprimir
                                        </button>

                                        <a href="?page=ordenes&action=create&cliente=<?= $orden['id_cliente'] ?>"
                                            class="btn btn-success btn-sm">
                                            <i class="fas fa-plus"></i> Nueva Orden
                                        </a>

                                        <hr>

                                        <button class="btn btn-danger btn-sm"
                                            onclick="confirmarEliminar(<?= $orden['id_orden'] ?>)">
                                            <i class="fas fa-trash"></i> Eliminar Orden
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación eliminar -->
<div class="modal fade" id="modalEliminar" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar esta orden de trabajo?</p>
                <p class="text-danger"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form method="POST" id="formEliminar" style="display: inline;">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Cambio de estado
        $('.estado-select').change(function() {
            const id = $(this).data('id');
            const estado = $(this).val();
            const originalValue = $(this).data('original-value') || $(this).val();

            $(this).data('original-value', originalValue);

            cambiarEstado(id, estado, $(this));
        });
    });

    function confirmarEliminar(id) {
        document.getElementById('formEliminar').action = '?page=ordenes&action=delete&id=' + id;
        $('#modalEliminar').modal('show');
    }

    function cambiarEstado(id, estado, selectElement) {
        // Deshabilitar select temporalmente
        selectElement.prop('disabled', true);

        $.post('?page=ordenes&action=cambiarEstado&id=' + id, {
                estado: estado
            })
            .done(function(response) {
                if (response.success) {
                    mostrarMensaje('Estado actualizado correctamente', 'success');

                    // Actualizar indicador visual
                    updateEstadoIndicator(estado);
                } else {
                    mostrarMensaje(response.message || 'Error al cambiar estado', 'error');
                    // Revertir select
                    selectElement.val(selectElement.data('original-value'));
                }
            })
            .fail(function() {
                mostrarMensaje('Error de conexión', 'error');
                selectElement.val(selectElement.data('original-value'));
            })
            .always(function() {
                selectElement.prop('disabled', false);
            });
    }

    function updateEstadoIndicator(estado) {
        // Aquí se puede agregar lógica para actualizar indicadores visuales
        console.log('Estado actualizado a:', estado);
    }

    function mostrarMensaje(mensaje, tipo) {
        const alertClass = tipo === 'success' ? 'alert-success' : 'alert-danger';
        const icono = tipo === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle';

        const alert = `
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                <i class="fas ${icono}"></i> ${mensaje}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

        $('.card-body').first().prepend(alert);

        // Auto hide
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 3000);
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>

<style>
    @media print {

        .btn,
        .card-header .btn-group,
        .modal {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .estado-select {
            border: none !important;
            background: transparent !important;
        }
    }
</style>