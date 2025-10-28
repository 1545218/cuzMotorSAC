<?php
$title = 'Gestión de Marcas';
$breadcrumb = [
    ['title' => 'Marcas']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-tags me-2"></i>Gestión de Marcas
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#marcaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Marca
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="marcasTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($marcas) && !empty($marcas)): ?>
                                <?php foreach ($marcas as $marca): ?>
                                    <?php
                                    $marcaId = $marca['id_marca'] ?? $marca['id'] ?? 0;
                                    $nombre = $marca['nombre'] ?? '';
                                    $descripcion = $marca['descripcion'] ?? ($marca['descripcion_corta'] ?? '');
                                    $totalProductos = $marca['total_productos'] ?? ($marca['productos'] ?? 0);
                                    $activo = isset($marca['activo']) ? (int)$marca['activo'] : (isset($marca['activo_flag']) ? (int)$marca['activo_flag'] : 0);
                                    ?>
                                    <tr data-id="<?= htmlspecialchars($marcaId) ?>" data-nombre="<?= htmlspecialchars($nombre) ?>" data-descripcion="<?= htmlspecialchars($descripcion) ?>" data-activo="<?= $activo ?>" data-total="<?= (int)$totalProductos ?>">
                                        <?php
                                        // Compatibilidad con distintos esquemas: algunas instalaciones usan 'id_marca' y no tienen 'activo' ni 'descripcion'
                                        $marcaId = $marca['id_marca'] ?? $marca['id'] ?? null;
                                        $nombre = $marca['nombre'] ?? '';
                                        $descripcion = $marca['descripcion'] ?? ($marca['descripcion_corta'] ?? '');
                                        $totalProductos = $marca['total_productos'] ?? ($marca['productos'] ?? 0);
                                        $activo = isset($marca['activo']) ? (int)$marca['activo'] : (isset($marca['activo_flag']) ? (int)$marca['activo_flag'] : 0);
                                        ?>
                                        <td><?= htmlspecialchars((string)$nombre) ?></td>
                                        <td><?= htmlspecialchars((string)$descripcion) ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= (int)$totalProductos ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $activo ? 'success' : 'secondary' ?>">
                                                <?= $activo ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary btn-editar" onclick="editarMarca(<?= htmlspecialchars($marcaId ?? 0) ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning btn-toggle" onclick="toggleMarca(<?= htmlspecialchars($marcaId ?? 0) ?>)" title="Cambiar Estado">
                                                    <i class="fas fa-toggle-<?= $activo ? 'on' : 'off' ?>"></i>
                                                </button>
                                                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger btn-eliminar" onclick="eliminarMarca(<?= htmlspecialchars($marcaId ?? 0) ?>)" title="Eliminar">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <br>No hay marcas registradas
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Nueva/Editar Marca -->
<div class="modal fade" id="marcaModal" tabindex="-1" aria-labelledby="marcaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="marcaModalLabel">Nueva Marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="marcaForm">
                <input type="hidden" id="csrf_token" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <div class="modal-body">
                    <input type="hidden" id="marca_id" name="id">
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre *</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="activo" name="activo" checked>
                        <label class="form-check-label" for="activo">
                            Activo
                        </label>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#marcasTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json'
            },
            responsive: true,
            order: [
                [0, 'asc']
            ]
        });

        // Form submit
        $('#marcaForm').on('submit', function(e) {
            e.preventDefault();
            guardarMarca();
        });
    });

    function editarMarca(id) {
        var row = $('tr[data-id="' + id + '"]');
        if (!row.length) return alert('Marca no encontrada en la tabla');

        var nombre = row.data('nombre') || '';
        var descripcion = row.data('descripcion') || '';
        var activo = row.data('activo') ? true : false;

        // Rellenar el modal
        $('#marca_id').val(id);
        $('#nombre').val(nombre);
        $('#descripcion').val(descripcion);
        $('#activo').prop('checked', activo);
        $('#marcaModalLabel').text('Editar Marca');
        $('#marcaModal').modal('show');
    }

    function toggleMarca(id) {
        if (!confirm('¿Desea cambiar el estado de esta marca?')) return;
        var token = $('#csrf_token').val() || '';
        $.post('?page=marcas&action=toggle&id=' + id, {
            csrf_token: token
        }, function(res) {
            try {
                var data = typeof res === 'string' ? JSON.parse(res) : res;
            } catch (e) {
                return alert('Respuesta inesperada del servidor');
            }
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al cambiar estado');
            }
        }).fail(function() {
            alert('Error en la petición');
        });
    }

    function eliminarMarca(id) {
        if (!confirm('¿Está seguro de eliminar esta marca?')) return;
        var token = $('#csrf_token').val() || '';
        $.post('?page=marcas&action=delete&id=' + id, {
            csrf_token: token
        }, function(res) {
            try {
                var data = typeof res === 'string' ? JSON.parse(res) : res;
            } catch (e) {
                return alert('Respuesta inesperada del servidor');
            }
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error al eliminar marca');
            }
        }).fail(function() {
            alert('Error en la petición');
        });
    }

    function guardarMarca() {
        var id = $('#marca_id').val() || '';
        var nombre = $('#nombre').val().trim();
        var descripcion = $('#descripcion').val().trim();
        var activo = $('#activo').is(':checked') ? 1 : 0;
        var token = $('#csrf_token').val() || '';

        if (!nombre) {
            alert('El nombre es obligatorio');
            return;
        }

        var url = '?page=marcas&action=store';
        if (id) url = '?page=marcas&action=update&id=' + encodeURIComponent(id);

        $.post(url, {
            nombre: nombre,
            descripcion: descripcion,
            activo: activo,
            csrf_token: token
        }, function(res) {
            try {
                var data = typeof res === 'string' ? JSON.parse(res) : res;
            } catch (e) {
                return alert('Respuesta inesperada del servidor');
            }
            if (data.success) {
                $('#marcaModal').modal('hide');
                location.reload();
            } else {
                var msg = data.errors ? Object.values(data.errors).join('\n') : (data.message || 'Error al guardar');
                alert(msg);
            }
        }).fail(function() {
            alert('Error en la petición');
        });
    }
</script>