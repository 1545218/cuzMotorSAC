<?php
$title = 'Gestión de Categorías';
$breadcrumb = [
    ['title' => 'Categorías']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-list me-2"></i>Gestión de Categorías
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#categoriaModal">
                        <i class="fas fa-plus me-1"></i>Nueva Categoría
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="categoriasTable" class="table table-striped table-hover">
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
                            <?php if (isset($categorias) && !empty($categorias)): ?>
                                <?php foreach ($categorias as $categoria): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($categoria['nombre']) ?></td>
                                        <td><?= htmlspecialchars($categoria['descripcion'] ?? '') ?></td>
                                        <td>
                                            <span class="badge bg-info"><?= $categoria['total_productos'] ?? 0 ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= $categoria['activo'] ? 'success' : 'secondary' ?>">
                                                <?= $categoria['activo'] ? 'Activo' : 'Inactivo' ?>
                                            </span>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCategoria(<?= $categoria['id'] ?>)" title="Editar">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleCategoria(<?= $categoria['id'] ?>)" title="Cambiar Estado">
                                                    <i class="fas fa-toggle-<?= $categoria['activo'] ? 'on' : 'off' ?>"></i>
                                                </button>
                                                <?php if ($_SESSION['user_role'] === 'administrador'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCategoria(<?= $categoria['id'] ?>)" title="Eliminar">
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
                                        <br>No hay categorías registradas
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

<!-- Modal para Nueva/Editar Categoría -->
<div class="modal fade" id="categoriaModal" tabindex="-1" aria-labelledby="categoriaModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="categoriaModalLabel">Nueva Categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="categoriaForm">
                <div class="modal-body">
                    <input type="hidden" id="categoria_id" name="id">
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
        $('#categoriasTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json'
            },
            responsive: true,
            order: [
                [0, 'asc']
            ]
        });

        // Form submit
        $('#categoriaForm').on('submit', function(e) {
            e.preventDefault();
            guardarCategoria();
        });
    });

    function editarCategoria(id) {
        // Implementar edición
    }

    function toggleCategoria(id) {
        // Implementar toggle estado
    }

    function eliminarCategoria(id) {
        if (confirm('¿Está seguro de eliminar esta categoría?')) {
            // Implementar eliminación
        }
    }

    function guardarCategoria() {
        // Implementar guardado
    }
</script>