<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-map-marker-alt"></i> Gestión de Ubicaciones
        </h1>
        <a href="?page=ubicaciones&action=create" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nueva Ubicación
        </a>
    </div>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" id="filtrosForm">
                <input type="hidden" name="page" value="ubicaciones">
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="search">Buscar:</label>
                            <input type="text" class="form-control" id="search" name="search"
                                value="<?= htmlspecialchars($_GET['search'] ?? '') ?>"
                                placeholder="Nombre o descripción...">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="tipo">Tipo de Ubicación:</label>
                            <select class="form-control" id="tipo" name="tipo">
                                <option value="">Todos los tipos</option>
                                <option value="deposito" <?= ($_GET['tipo'] ?? '') === 'deposito' ? 'selected' : '' ?>>Depósito</option>
                                <option value="estante" <?= ($_GET['tipo'] ?? '') === 'estante' ? 'selected' : '' ?>>Estante</option>
                                <option value="seccion" <?= ($_GET['tipo'] ?? '') === 'seccion' ? 'selected' : '' ?>>Sección</option>
                                <option value="almacen" <?= ($_GET['tipo'] ?? '') === 'almacen' ? 'selected' : '' ?>>Almacén</option>
                                <option value="otros" <?= ($_GET['tipo'] ?? '') === 'otros' ? 'selected' : '' ?>>Otros</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <div class="d-flex">
                                <button type="submit" class="btn btn-info btn-sm mr-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="?page=ubicaciones" class="btn btn-secondary btn-sm">
                                    <i class="fas fa-eraser"></i> Limpiar
                                </a>
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
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Ubicaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= count($ubicaciones) ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-map-marker-alt fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Con Productos</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="ubicacionesConProductos">
                                <?= array_sum(array_map(function ($u) {
                                    return $u['total_productos'] > 0 ? 1 : 0;
                                }, $ubicaciones)) ?>
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
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Tipo Más Común</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?php
                                $tipos = array_count_values(array_column($ubicaciones, 'tipo'));
                                arsort($tipos);
                                echo ucfirst(array_key_first($tipos) ?? 'N/A');
                                ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-pie fa-2x text-gray-300"></i>
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
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Stock Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= array_sum(array_column($ubicaciones, 'stock_total')) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-cubes fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de ubicaciones -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Listado de Ubicaciones
                <?php if (!empty($_GET['search']) || !empty($_GET['tipo'])): ?>
                    <small class="text-muted">(Filtrado)</small>
                <?php endif; ?>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($ubicaciones)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-map-marker-alt fa-3x text-gray-300 mb-3"></i>
                    <h5 class="text-gray-600">No hay ubicaciones registradas</h5>
                    <p class="text-gray-500">
                        <?php if (!empty($_GET['search']) || !empty($_GET['tipo'])): ?>
                            No se encontraron ubicaciones con los filtros aplicados.
                        <?php else: ?>
                            Comienza agregando la primera ubicación del inventario.
                        <?php endif; ?>
                    </p>
                    <a href="?page=ubicaciones&action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nueva Ubicación
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="ubicacionesTable">
                        <thead class="thead-dark">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Stock Total</th>
                                <th>Fecha Creación</th>
                                <th width="120">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ubicaciones as $ubicacion): ?>
                                <tr data-id="<?= $ubicacion['id_ubicacion'] ?>">
                                    <td><?= $ubicacion['id_ubicacion'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($ubicacion['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-<?=
                                                                    $ubicacion['tipo'] === 'deposito' ? 'primary' : ($ubicacion['tipo'] === 'estante' ? 'info' : ($ubicacion['tipo'] === 'seccion' ? 'success' : ($ubicacion['tipo'] === 'almacen' ? 'warning' : 'secondary'))) ?>">
                                            <?= ucfirst($ubicacion['tipo']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($ubicacion['descripcion']): ?>
                                            <span class="text-truncate d-inline-block" style="max-width: 200px;"
                                                title="<?= htmlspecialchars($ubicacion['descripcion']) ?>">
                                                <?= htmlspecialchars($ubicacion['descripcion']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">Sin descripción</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-light">
                                            <?= $ubicacion['total_productos'] ?>
                                        </span>
                                    </td>
                                    <td class="text-right">
                                        <strong><?= number_format($ubicacion['stock_total']) ?></strong>
                                    </td>
                                    <td>
                                        <?= date('d/m/Y', strtotime($ubicacion['fecha_creacion'])) ?>
                                        <small class="text-muted d-block">
                                            <?= date('H:i', strtotime($ubicacion['fecha_creacion'])) ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?page=ubicaciones&action=edit&id=<?= $ubicacion['id_ubicacion'] ?>"
                                                class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                onclick="eliminarUbicacion(<?= $ubicacion['id_ubicacion'] ?>, '<?= htmlspecialchars($ubicacion['nombre'], ENT_QUOTES) ?>')"
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
                <p>¿Está seguro que desea eliminar la ubicación <strong id="ubicacionNombre"></strong>?</p>
                <div class="alert alert-warning" id="alertProductos" style="display: none;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <strong>Atención:</strong> Esta ubicación tiene productos asociados.
                    La eliminación también removerá estas asociaciones.
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

<script>
    // Función para eliminar ubicación
    function eliminarUbicacion(id, nombre) {
        document.getElementById('ubicacionNombre').textContent = nombre;

        // Verificar si tiene productos
        const fila = document.querySelector(`tr[data-id="${id}"]`);
        const totalProductos = parseInt(fila.querySelector('.badge-light').textContent);

        const alertProductos = document.getElementById('alertProductos');
        if (totalProductos > 0) {
            alertProductos.style.display = 'block';
        } else {
            alertProductos.style.display = 'none';
        }

        document.getElementById('confirmDelete').onclick = function() {
            ejecutarEliminacion(id);
        };

        $('#deleteModal').modal('show');
    }

    function ejecutarEliminacion(id) {
        const btn = document.getElementById('confirmDelete');
        const originalText = btn.innerHTML;

        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Eliminando...';

        fetch('?page=ubicaciones&action=delete', {
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
                    CruzMotor.showAlert('success', 'Éxito', 'Ubicación eliminada correctamente');
                    setTimeout(() => {
                        location.reload();
                    }, 1000);
                } else {
                    CruzMotor.showAlert('error', 'Error', data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                CruzMotor.showAlert('error', 'Error', 'Error al eliminar la ubicación');
            })
            .finally(() => {
                btn.disabled = false;
                btn.innerHTML = originalText;
            });
    }

    // Auto-envío del formulario de filtros
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('search');
        const tipoSelect = document.getElementById('tipo');

        let timeout;

        searchInput.addEventListener('input', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                document.getElementById('filtrosForm').submit();
            }, 500);
        });

        tipoSelect.addEventListener('change', function() {
            document.getElementById('filtrosForm').submit();
        });
    });
</script>

</div>
</div>