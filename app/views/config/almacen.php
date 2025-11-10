<?php include_once '../app/views/layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (isset($item['url'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['url'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($item['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <!-- Título -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($title) ?></h1>
                <a href="?page=config&action=createAlmacen" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Nueva Configuración
                </a>
            </div>

            <!-- Configuraciones de Almacén -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-warehouse"></i> Configuraciones de Almacén
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($configuraciones)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-warehouse fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No hay configuraciones de almacén registradas</p>
                            <a href="?page=config&action=createAlmacen" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Crear Primera Configuración
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead>
                                    <tr>
                                        <th>Nombre del Almacén</th>
                                        <th>Capacidad Máxima</th>
                                        <th>Horario</th>
                                        <th>Responsable</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($configuraciones as $config): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($config['nombre_almacen']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($config['capacidad_maxima']): ?>
                                                    <span class="badge badge-info"><?= number_format($config['capacidad_maxima']) ?> unidades</span>
                                                <?php else: ?>
                                                    <span class="text-muted">No definida</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($config['horario_apertura'] && $config['horario_cierre']): ?>
                                                    <small>
                                                        <i class="fas fa-clock"></i>
                                                        <?= $config['horario_apertura'] ?> - <?= $config['horario_cierre'] ?>
                                                    </small>
                                                <?php else: ?>
                                                    <span class="text-muted">No definido</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($config['responsable_nombre']): ?>
                                                    <span class="badge badge-secondary">
                                                        <?= htmlspecialchars($config['responsable_nombre'] . ' ' . $config['responsable_apellido']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sin asignar</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?page=config&action=editAlmacen&id=<?= $config['id_config'] ?>"
                                                        class="btn btn-outline-primary" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger"
                                                        onclick="confirmarEliminar(<?= $config['id_config'] ?>, '<?= htmlspecialchars($config['nombre_almacen']) ?>')"
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

            <!-- Botones de navegación -->
            <div class="mt-4">
                <a href="?page=config" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Configuración
                </a>
                <a href="?page=config&action=sistema" class="btn btn-info">
                    <i class="fas fa-cogs"></i> Parámetros del Sistema
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Modal de confirmación para eliminar -->
<div class="modal fade" id="confirmarEliminarModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Confirmar eliminación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea eliminar la configuración del almacén <strong id="nombreAlmacen"></strong>?</p>
                <p class="text-danger">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <a href="#" id="btnConfirmarEliminar" class="btn btn-danger">Eliminar</a>
            </div>
        </div>
    </div>
</div>

<script>
    function confirmarEliminar(id, nombre) {
        document.getElementById('nombreAlmacen').textContent = nombre;
        document.getElementById('btnConfirmarEliminar').href = '?page=config&action=deleteAlmacen&id=' + id;

        const modal = new bootstrap.Modal(document.getElementById('confirmarEliminarModal'));
        modal.show();
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>