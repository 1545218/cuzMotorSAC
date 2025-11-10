<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-car"></i> Gestión de Vehículos
                    </h5>
                    <a href="?page=vehiculos&action=create" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus"></i> Registrar Vehículo
                    </a>
                </div>
                <div class="card-body">
                    <!-- Mensajes de éxito/error -->
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

                    <!-- Filtros -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="text" id="filtroVehiculo" class="form-control" placeholder="Buscar por placa, marca o modelo...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button">
                                        <i class="fas fa-search"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <small class="text-muted">
                                Total de vehículos: <strong><?= count($vehiculos) ?></strong>
                            </small>
                        </div>
                    </div>

                    <!-- Tabla de vehículos -->
                    <?php if (empty($vehiculos)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-car fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay vehículos registrados</h5>
                            <p class="text-muted">Comienza registrando el primer vehículo de tus clientes</p>
                            <a href="?page=vehiculos&action=create" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Registrar Primer Vehículo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover" id="tablaVehiculos">
                                <thead class="thead-dark">
                                    <tr>
                                        <th>Placa</th>
                                        <th>Cliente</th>
                                        <th>Marca</th>
                                        <th>Modelo</th>
                                        <th>Documento</th>
                                        <th>Teléfono</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($vehiculos as $vehiculo): ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold text-primary">
                                                    <?= htmlspecialchars($vehiculo['placa']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($vehiculo['cliente_nombre'] . ' ' . $vehiculo['cliente_apellido']) ?>
                                            </td>
                                            <td><?= htmlspecialchars($vehiculo['marca'] ?: 'N/A') ?></td>
                                            <td><?= htmlspecialchars($vehiculo['modelo'] ?: 'N/A') ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($vehiculo['cliente_documento'] ?? 'N/A') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= htmlspecialchars($vehiculo['telefono'] ?: 'N/A') ?>
                                                </small>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?page=vehiculos&action=edit&id=<?= $vehiculo['id_vehiculo'] ?>"
                                                        class="btn btn-outline-primary"
                                                        title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-outline-danger"
                                                        title="Eliminar"
                                                        onclick="confirmarEliminacion(<?= $vehiculo['id_vehiculo'] ?>, '<?= htmlspecialchars($vehiculo['placa']) ?>')">
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
                <p>¿Está seguro de que desea eliminar el vehículo <strong id="placaEliminar"></strong>?</p>
                <p class="text-warning">
                    <i class="fas fa-exclamation-triangle"></i>
                    Esta acción no se puede deshacer.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <form id="formEliminar" method="POST" style="display: inline;">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Filtro de búsqueda
    document.getElementById('filtroVehiculo').addEventListener('keyup', function() {
        const filtro = this.value.toLowerCase();
        const tabla = document.getElementById('tablaVehiculos');
        if (!tabla) return;

        const filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');

        for (let i = 0; i < filas.length; i++) {
            const fila = filas[i];
            const texto = fila.textContent || fila.innerText;

            if (texto.toLowerCase().indexOf(filtro) > -1) {
                fila.style.display = '';
            } else {
                fila.style.display = 'none';
            }
        }
    });

    // Confirmar eliminación
    function confirmarEliminacion(id, placa) {
        document.getElementById('placaEliminar').textContent = placa;
        document.getElementById('formEliminar').action = '?page=vehiculos&action=delete&id=' + id;
        $('#modalEliminar').modal('show');
    }

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>