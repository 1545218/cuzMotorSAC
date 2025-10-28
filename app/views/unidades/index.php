<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-ruler"></i> Gestión de Unidades de Medida
        </h1>
        <a href="?page=unidades&action=create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nueva Unidad
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="filtrosForm">
                <input type="hidden" name="page" value="unidades">
                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="search">Buscar:</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                placeholder="Nombre, símbolo o descripción...">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-info btn-sm mr-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="?page=unidades" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </a>
                                <button type="button" class="btn btn-success btn-sm ml-2" onclick="inicializarUnidades()">
                                    <i class="fas fa-magic"></i> Inicializar Estándar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Estadísticas -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Unidades</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($unidades) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-ruler fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">En Uso</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function ($u) {
                                    return $u['productos_usando'] > 0 ? 1 : 0;
                                }, $unidades)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Productos Totales</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_column($unidades, 'productos_usando')) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-boxes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Sin Usar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_map(function ($u) {
                                    return $u['productos_usando'] == 0 ? 1 : 0;
                                }, $unidades)) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exclamation-triangle fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de unidades -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Listado de Unidades de Medida
                <?php if (!empty($_GET['search'])): ?>
                    <small class="text-muted">(Filtrado)</small>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($unidades)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-ruler fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No hay unidades de medida registradas</h5>
                    <p class="text-gray-500">
                        <?php if (!empty($_GET['search'])): ?>
                            No se encontraron unidades con los filtros aplicados.
                        <?php else: ?>
                            Comienza agregando las unidades de medida estándar del sistema.
                        <?php endif; ?>
                    </p>
                    <div>
                        <a href="?page=unidades&action=create" class="btn btn-primary mr-2">
                            <i class="fas fa-plus"></i> Nueva Unidad
                        </a>
                        <button type="button" class="btn btn-success" onclick="inicializarUnidades()">
                            <i class="fas fa-magic"></i> Inicializar Estándar
                        </button>
                    </div>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="unidadesTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Símbolo</th>
                                <th>Descripción</th>
                                <th>Productos Usando</th>
                                <th>Fecha Creación</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unidades as $unidad): ?>
                                <tr data-id="<?= $unidad['id_unidad'] ?>">
                                    <td><?= $unidad['id_unidad'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($unidad['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info font-weight-bold">
                                            <?= htmlspecialchars($unidad['simbolo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($unidad['descripcion']): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 250px;"
                                                title="<?= htmlspecialchars($unidad['descripcion']) ?>">
                                                <?= htmlspecialchars($unidad['descripcion']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin descripción</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($unidad['productos_usando'] > 0): ?>
                                            <span class="badge badge-success">
                                                <?= $unidad['productos_usando'] ?> productos
                                            </span>
                                        <?php else: ?>
                                            <span class="badge badge-secondary">Sin uso</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($unidad['fecha_creacion'])) ?>
                                        <small class="text-muted d-block">
                                            <?= date('H:i', strtotime($unidad['fecha_creacion'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?page=unidades&action=edit&id=<?= $unidad['id_unidad'] ?>"
                                                class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="eliminarUnidad(<?= $unidad['id_unidad'] ?>, '<?= htmlspecialchars($unidad['nombre'], ENT_QUOTES) ?>', <?= $unidad['productos_usando'] ?>)"
                                                title="Eliminar"
                                                <?= $unidad['productos_usando'] > 0 ? 'disabled' : '' ?>>
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

<!-- Modal de Confirmación de Eliminación -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar la unidad <strong id="unidadNombre"></strong>?</p>
                <div class="alert alert-danger" id="alertProductos" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Error:</strong> No se puede eliminar esta unidad porque tiene productos asociados.
                    Debe cambiar la unidad de esos productos primero.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Inicialización -->
<div class="modal fade" id="initModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Inicializar Unidades Estándar</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>Esta acción creará las siguientes unidades de medida estándar:</p>
                <ul>
                    <li><strong>Unidad (UN)</strong> - Para productos individuales</li>
                    <li><strong>Kilogramo (KG)</strong> - Para peso</li>
                    <li><strong>Litro (L)</strong> - Para volumen</li>
                    <li><strong>Metro (M)</strong> - Para longitud</li>
                    <li><strong>Caja (CJ)</strong> - Para empaque</li>
                    <li><strong>Paquete (PQ)</strong> - Para conjunto de productos</li>
                    <li><strong>Galón (GAL)</strong> - Para líquidos</li>
                    <li><strong>Pieza (PZ)</strong> - Para partes</li>
                </ul>
                <p class="text-muted">Las unidades que ya existan no se duplicarán.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success" id="confirmInit">
                    <i class="fas fa-magic"></i> Inicializar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // Función para eliminar unidad
    function eliminarUnidad(id, nombre, productosUsando) {
        document.getElementById('unidadNombre').textContent = nombre;

        const alertProductos = document.getElementById('alertProductos');
        const confirmBtn = document.getElementById('confirmDelete');

        if (productosUsando > 0) {
            alertProductos.style.display = 'block';
            confirmBtn.style.display = 'none';
        } else {
            alertProductos.style.display = 'none';
            confirmBtn.style.display = 'inline-block';
            confirmBtn.onclick = function() {
                ejecutarEliminacion(id);
            };
        }

        $('#deleteModal').modal('show');
    }

    function ejecutarEliminacion(id) {
        const btn = document.getElementById('confirmDelete');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

        fetch('?page=unidades&action=delete', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    id: id,
                    csrf_token: '<?= $csrf_token ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#deleteModal').modal('hide');
                    CruzMotor.showAlert('success', 'Éxito', 'Unidad eliminada correctamente');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    CruzMotor.showAlert('error', 'Error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                CruzMotor.showAlert('error', 'Error', 'Error al eliminar la unidad');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    }

    // Función para inicializar unidades estándar
    function inicializarUnidades() {
        $('#initModal').modal('show');

        document.getElementById('confirmInit').onclick = function() {
            ejecutarInicializacion();
        };
    }

    function ejecutarInicializacion() {
        const btn = document.getElementById('confirmInit');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Inicializando...';

        fetch('?page=unidades&action=init', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    csrf_token: '<?= $csrf_token ?>'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    $('#initModal').modal('hide');
                    CruzMotor.showAlert('success', 'Éxito',
                        `Inicialización completada. ${data.created || 0} unidades creadas, ${data.skipped || 0} ya existían.`);
                    setTimeout(() => {
                        location.reload();
                    }, 1500);
                } else {
                    CruzMotor.showAlert('error', 'Error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                CruzMotor.showAlert('error', 'Error', 'Error al inicializar las unidades');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    }

    // Auto-envío del formulario de filtros
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');

        let timeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('filtrosForm').submit();
            }, 500);
        });
    });
</script>

</div>
</div>