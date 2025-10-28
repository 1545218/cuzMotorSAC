<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-users me-2"></i>Gestión de Clientes
                </h2>
                <a href="?page=clientes&action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo Cliente
                </a>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Filtros y búsqueda -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" action="?page=clientes" class="row g-3">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text"
                                    class="form-control"
                                    name="search"
                                    placeholder="Buscar por nombre, apellido, documento o email..."
                                    value="<?= htmlspecialchars($data['search']) ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-outline-primary flex-fill">
                                    <i class="fas fa-search me-1"></i>Buscar
                                </button>
                                <a href="?page=clientes" class="btn btn-outline-secondary" title="Limpiar búsqueda">
                                    <i class="fas fa-eraser"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Tabla de clientes -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>
                        Lista de Clientes (<?= $data['total'] ?> registros)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($data['clientes'])): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Nombre Completo</th>
                                        <th>Documento</th>
                                        <th>Email</th>
                                        <th>Teléfono</th>
                                        <th>Dirección</th>
                                        <th width="150">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($data['clientes'] as $cliente): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-primary">
                                                    <?= str_pad($cliente['id_cliente'], 4, '0', STR_PAD_LEFT) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?></strong>
                                            </td>
                                            <td>
                                                <div>
                                                    <small class="text-muted"><?= htmlspecialchars($cliente['tipo_documento']) ?></small><br>
                                                    <strong><?= htmlspecialchars($cliente['numero_documento']) ?></strong>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if (!empty($cliente['email'])): ?>
                                                    <a href="mailto:<?= htmlspecialchars($cliente['email']) ?>" class="text-decoration-none">
                                                        <i class="fas fa-envelope me-1"></i>
                                                        <?= htmlspecialchars($cliente['email']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($cliente['telefono'])): ?>
                                                    <a href="tel:<?= htmlspecialchars($cliente['telefono']) ?>" class="text-decoration-none">
                                                        <i class="fas fa-phone me-1"></i>
                                                        <?= htmlspecialchars($cliente['telefono']) ?>
                                                    </a>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrado</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!empty($cliente['direccion'])): ?>
                                                    <span title="<?= htmlspecialchars($cliente['direccion']) ?>">
                                                        <?= htmlspecialchars(strlen($cliente['direccion']) > 50 ?
                                                            substr($cliente['direccion'], 0, 50) . '...' :
                                                            $cliente['direccion']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">No registrada</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm" role="group">
                                                    <a href="?page=clientes&action=edit&id=<?= $cliente['id_cliente'] ?>"
                                                        class="btn btn-outline-primary"
                                                        title="Editar cliente">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button"
                                                        class="btn btn-outline-danger"
                                                        title="Eliminar cliente"
                                                        onclick="confirmarEliminacion(<?= $cliente['id_cliente'] ?>, '<?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Paginación -->
                        <?php if (isset($data['totalPages']) && $data['totalPages'] > 1): ?>
                            <nav aria-label="Paginación de clientes" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <!-- Página anterior -->
                                    <?php if (isset($data['currentPage']) && $data['currentPage'] > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $data['currentPage'] - 1 ?><?= !empty($data['search']) ? '&search=' . urlencode($data['search']) : '' ?>">
                                                <i class="fas fa-chevron-left"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>

                                    <!-- Páginas -->
                                    <?php
                                    $currentPage = $data['currentPage'] ?? 1;
                                    $totalPages = $data['totalPages'] ?? 1;
                                    $startPage = max(1, $currentPage - 2);
                                    $endPage = min($totalPages, $currentPage + 2);
                                    ?>

                                    <?php for ($i = $startPage; $i <= $endPage; $i++): ?>
                                        <li class="page-item <?= $i == $currentPage ? 'active' : '' ?>">
                                            <a class="page-link" href="?page=<?= $i ?><?= !empty($data['search']) ? '&search=' . urlencode($data['search']) : '' ?>">
                                                <?= $i ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>

                                    <!-- Página siguiente -->
                                    <?php if ($currentPage < $totalPages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?= $currentPage + 1 ?><?= !empty($data['search']) ? '&search=' . urlencode($data['search']) : '' ?>">
                                                <i class="fas fa-chevron-right"></i>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>

                                <div class="text-center mt-2">
                                    <small class="text-muted">
                                        Página <?= $currentPage ?> de <?= $totalPages ?>
                                        (<?= $data['total'] ?? 0 ?> clientes total)
                                    </small>
                                </div>
                            </nav>
                        <?php endif; ?>

                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-users fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay clientes registrados</h5>
                            <p class="text-muted">
                                <?php if (!empty($data['search'])): ?>
                                    No se encontraron clientes que coincidan con "<?= htmlspecialchars($data['search']) ?>"
                                <?php else: ?>
                                    Comienza registrando tu primer cliente
                                <?php endif; ?>
                            </p>
                            <a href="?page=clientes&action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Registrar Cliente
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script para confirmar eliminación -->
<script>
    function confirmarEliminacion(id, nombre) {
        const safeName = String(nombre || '').replace(/\"/g, '"');
        if (!confirm(`¿Estás seguro de que deseas eliminar al cliente "${safeName}"?\n\nEsta acción no se puede deshacer.`)) return;

        // Enviar un formulario POST con CSRF y id para respetar el controlador
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '?page=clientes&action=delete';

        const inputId = document.createElement('input');
        inputId.type = 'hidden';
        inputId.name = 'id';
        inputId.value = id;
        form.appendChild(inputId);

        // Agregar token CSRF si está disponible en el servidor
        var csrfToken = '<?= isset($data["csrf_token"]) ? $data["csrf_token"] : "" ?>';
        if (csrfToken) {
            const inputCsrf = document.createElement('input');
            inputCsrf.type = 'hidden';
            inputCsrf.name = 'csrf_token';
            inputCsrf.value = csrfToken;
            form.appendChild(inputCsrf);
        }

        document.body.appendChild(form);
        form.submit();
    }

    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

</div>
</div>