<div class="container-fluid">
    <!-- Header -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags"></i> Gestión de Subcategorías
        </h1>
        <div>
            <a href="?page=subcategorias&action=create" class="btn btn-primary btn-sm">
                <i class="fas fa-plus"></i> Nueva Subcategoría
            </a>
        </div>
    </div>

    <!-- Filtros -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filtros de Búsqueda</h6>
        </div>
        <div class="card-body">
            <form method="GET" action="" class="row g-3">
                <input type="hidden" name="page" value="subcategorias">

                <div class="col-md-4">
                    <label for="search" class="form-label">Buscar subcategoría</label>
                    <input type="text" class="form-control" id="search" name="search"
                        value="<?= htmlspecialchars($search) ?>"
                        placeholder="Nombre o descripción...">
                </div>

                <div class="col-md-4">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-control" id="categoria" name="categoria">
                        <option value="">Todas las categorías</option>
                        <?php foreach ($categorias as $cat): ?>
                            <option value="<?= $cat['id_categoria'] ?>"
                                <?= $categoria_selected == $cat['id_categoria'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cat['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4 d-flex align-items-end">
                    <button type="submit" class="btn btn-outline-primary me-2">
                        <i class="fas fa-search"></i> Buscar
                    </button>
                    <a href="?page=subcategorias" class="btn btn-outline-secondary">
                        <i class="fas fa-times"></i> Limpiar
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Subcategorías -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Listado de Subcategorías
                <span class="badge badge-primary"><?= count($subcategorias) ?></span>
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($subcategorias)): ?>
                <div class="text-center py-4">
                    <i class="fas fa-tags fa-3x text-gray-300 mb-3"></i>
                    <p class="text-gray-500">No se encontraron subcategorías</p>
                    <a href="?page=subcategorias&action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Crear Primera Subcategoría
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Nombre</th>
                                <th>Categoría</th>
                                <th>Descripción</th>
                                <th>Productos</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($subcategorias as $subcategoria): ?>
                                <tr>
                                    <td><?= $subcategoria['id_subcategoria'] ?></td>
                                    <td>
                                        <strong><?= htmlspecialchars($subcategoria['nombre']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge badge-info">
                                            <?= htmlspecialchars($subcategoria['categoria_nombre']) ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($subcategoria['descripcion'] ?? '-') ?></td>
                                    <td>
                                        <span class="badge badge-secondary">
                                            <?= $subcategoria['total_productos'] ?? 0 ?> productos
                                        </span>
                                    </td>
                                    <td>
                                        <div class="btn-group" role="group">
                                            <a href="?page=subcategorias&action=edit&id=<?= $subcategoria['id_subcategoria'] ?>"
                                                class="btn btn-sm btn-outline-primary" title="Editar">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <button onclick="eliminarSubcategoria(<?= $subcategoria['id_subcategoria'] ?>)"
                                                class="btn btn-sm btn-outline-danger"
                                                title="Eliminar"
                                                <?= ($subcategoria['total_productos'] ?? 0) > 0 ? 'disabled' : '' ?>>
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

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmDeleteModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar Eliminación</h5>
                <button type="button" class="close" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro que desea eliminar esta subcategoría?</p>
                <p class="text-warning"><strong>Esta acción no se puede deshacer.</strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
            </div>
        </div>
    </div>
</div>

<script>
    let subcategoriaIdToDelete = null;

    function eliminarSubcategoria(id) {
        subcategoriaIdToDelete = id;
        $('#confirmDeleteModal').modal('show');
    }

    document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
        if (subcategoriaIdToDelete) {
            const formData = new FormData();
            formData.append('id', subcategoriaIdToDelete);
            formData.append('csrf_token', '<?= $csrf_token ?>');

            fetch('?page=subcategorias&action=delete', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    $('#confirmDeleteModal').modal('hide');

                    if (data.success) {
                        CruzMotor.showAlert('success', 'Éxito', data.message);
                        setTimeout(() => location.reload(), 1000);
                    } else {
                        CruzMotor.showAlert('error', 'Error', data.message);
                    }
                })
                .catch(error => {
                    $('#confirmDeleteModal').modal('hide');
                    CruzMotor.showAlert('error', 'Error', 'Error al eliminar la subcategoría');
                });
        }
    });
</script>

</div>
</div>