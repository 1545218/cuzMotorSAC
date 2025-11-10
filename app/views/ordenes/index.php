<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list"></i> Órdenes de Trabajo
                    </h5>
                    <div>
                        <a href="?page=ordenes&action=create" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nueva Orden
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Estadísticas rápidas -->
                    <?php if (!empty($estadisticas)): ?>
                        <div class="row mb-4">
                            <?php foreach ($estadisticas as $stat): ?>
                                <div class="col-md-4">
                                    <div class="card border-0 shadow-sm">
                                        <div class="card-body text-center">
                                            <h3 class="text-primary"><?= $stat['cantidad'] ?></h3>
                                            <p class="mb-0 text-muted">
                                                <?php
                                                $estados = [
                                                    'abierta' => 'Abiertas',
                                                    'en_proceso' => 'En Proceso',
                                                    'cerrada' => 'Cerradas'
                                                ];
                                                echo $estados[$stat['estado']] ?? ucfirst($stat['estado']);
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

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

                    <!-- Buscador -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" class="form-control" id="buscador"
                                    placeholder="Buscar por cliente o placa...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" id="btnBuscar">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <div class="btn-group" role="group">
                                <button type="button" class="btn btn-outline-primary active" data-estado="todos">
                                    Todas
                                </button>
                                <button type="button" class="btn btn-outline-warning" data-estado="abierta">
                                    Abiertas
                                </button>
                                <button type="button" class="btn btn-outline-info" data-estado="en_proceso">
                                    En Proceso
                                </button>
                                <button type="button" class="btn btn-outline-success" data-estado="cerrada">
                                    Cerradas
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de órdenes -->
                    <?php if (empty($ordenes)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-list fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay órdenes de trabajo registradas</h5>
                            <p class="text-muted">Comienza creando tu primera orden de trabajo.</p>
                            <a href="?page=ordenes&action=create" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Orden
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover" id="tablaOrdenes">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="8%">#</th>
                                        <th width="12%">Fecha</th>
                                        <th width="20%">Cliente</th>
                                        <th width="15%">Vehículo</th>
                                        <th width="12%">Estado</th>
                                        <th width="20%">Observaciones</th>
                                        <th width="13%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ordenes as $orden): ?>
                                        <tr data-estado="<?= $orden['estado'] ?>">
                                            <td>
                                                <strong>#<?= $orden['id_orden'] ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($orden['fecha'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($orden['cliente_nombre'] . ' ' . $orden['cliente_apellido']) ?></strong>
                                                    <?php if ($orden['cliente_telefono']): ?>
                                                        <br><small class="text-muted">
                                                            <i class="fas fa-phone"></i> <?= htmlspecialchars($orden['cliente_telefono']) ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($orden['vehiculo_placa']): ?>
                                                    <div>
                                                        <strong><?= htmlspecialchars($orden['vehiculo_placa']) ?></strong>
                                                        <br><small class="text-muted">
                                                            <?= htmlspecialchars($orden['vehiculo_marca'] . ' ' . $orden['vehiculo_modelo']) ?>
                                                        </small>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin vehículo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <select class="form-control form-control-sm estado-select"
                                                    data-id="<?= $orden['id_orden'] ?>">
                                                    <option value="abierta" <?= $orden['estado'] == 'abierta' ? 'selected' : '' ?>>
                                                        Abierta
                                                    </option>
                                                    <option value="en_proceso" <?= $orden['estado'] == 'en_proceso' ? 'selected' : '' ?>>
                                                        En Proceso
                                                    </option>
                                                    <option value="cerrada" <?= $orden['estado'] == 'cerrada' ? 'selected' : '' ?>>
                                                        Cerrada
                                                    </option>
                                                </select>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars(substr($orden['observaciones'], 0, 50)) ?><?= strlen($orden['observaciones']) > 50 ? '...' : '' ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="?page=ordenes&action=show&id=<?= $orden['id_orden'] ?>"
                                                        class="btn btn-outline-info"
                                                        title="Ver detalles">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?page=ordenes&action=edit&id=<?= $orden['id_orden'] ?>"
                                                        class="btn btn-outline-primary"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button class="btn btn-outline-danger"
                                                        onclick="confirmarEliminar(<?= $orden['id_orden'] ?>)"
                                                        title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
                    <input type="hidden" name="id_orden" id="inputIdEliminar" value="">
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    function waitForJQuery(callback) {
        if (typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }

    waitForJQuery(function() {
        $(document).ready(function() {
            // Filtros por estado
            $('[data-estado]').click(function() {
                const estado = $(this).data('estado');
                $('[data-estado]').removeClass('active');
                $(this).addClass('active');

                if (estado === 'todos') {
                    $('#tablaOrdenes tbody tr').show();
                } else {
                    $('#tablaOrdenes tbody tr').hide();
                    $('#tablaOrdenes tbody tr[data-estado="' + estado + '"]').show();
                }
            });

            // Buscador
            $('#buscador, #btnBuscar').on('keyup click', function(e) {
                if (e.type === 'click' || e.which === 13) {
                    const termino = $('#buscador').val();
                    buscarOrdenes(termino);
                }
            });

            // Cambio de estado
            $('.estado-select').change(function() {
                const id = $(this).data('id');
                const estado = $(this).val();
                cambiarEstado(id, estado, $(this));
            });
        });

        function confirmarEliminar(id) {
            document.getElementById('formEliminar').action = '?page=ordenes&action=delete&id=' + id;
            document.getElementById('inputIdEliminar').value = id;
            $('#modalEliminar').modal('show');
        }

        function cambiarEstado(id, estado, selectElement) {
            $.post('?page=ordenes&action=cambiarEstado&id=' + id, {
                    estado: estado
                })
                .done(function(response) {
                    if (response.success) {
                        // Actualizar la fila
                        const fila = selectElement.closest('tr');
                        fila.attr('data-estado', estado);

                        // Mostrar mensaje
                        mostrarMensaje('Estado actualizado correctamente', 'success');
                    } else {
                        mostrarMensaje(response.message || 'Error al cambiar estado', 'error');
                        // Revertir select
                        selectElement.val(selectElement.data('original-value'));
                    }
                })
                .fail(function() {
                    mostrarMensaje('Error de conexión', 'error');
                    selectElement.val(selectElement.data('original-value'));
                });
        }

        function buscarOrdenes(termino) {
            if (termino.length < 3 && termino.length > 0) return;

            $.get('?page=ordenes&action=buscar', {
                    termino: termino
                })
                .done(function(response) {
                    if (response.success) {
                        // Actualizar tabla
                        actualizarTabla(response.ordenes);
                    }
                })
                .fail(function() {
                    mostrarMensaje('Error en la búsqueda', 'error');
                });
        }

        function actualizarTabla(ordenes) {
            // Implementar actualización de tabla via JavaScript
            // Por ahora recargamos la página
            if ($('#buscador').val() === '') {
                location.reload();
            }
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

            $('.card-body').prepend(alert);

            // Auto hide
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 3000);
        }

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>