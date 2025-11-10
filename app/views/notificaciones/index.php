<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">
                        <i class="fas fa-envelope text-primary me-2"></i>
                        Notificaciones por Correo
                    </h1>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb mb-0">
                            <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
                            <li class="breadcrumb-item active">Notificaciones</li>
                        </ol>
                    </nav>
                </div>
                <div>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                        <i class="fas fa-plus me-1"></i>Agregar Correo
                    </button>
                    <button class="btn btn-warning" onclick="testAlert()">
                        <i class="fas fa-paper-plane me-1"></i>Enviar Prueba
                    </button>
                </div>
            </div>

            <!-- Información -->
            <div class="alert alert-info" role="alert">
                <h6 class="alert-heading"><i class="fas fa-info-circle me-2"></i>¿Cómo funciona?</h6>
                <p class="mb-0">
                    Los correos registrados aquí recibirán automáticamente notificaciones cuando el sistema detecte productos con stock bajo (≤25% del stock mínimo).
                    Las alertas se envían automáticamente cada vez que se accede al dashboard y hay productos críticos.
                </p>
            </div>

            <!-- Lista de correos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-list me-2"></i>
                        Correos Registrados (<?= count($emails) ?>)
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($emails)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>ID</th>
                                        <th>Correo Electrónico</th>
                                        <th>Estado</th>
                                        <th width="100">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($emails as $email): ?>
                                        <tr>
                                            <td><?= $email['id'] ?></td>
                                            <td>
                                                <i class="fas fa-envelope text-primary me-2"></i>
                                                <?= htmlspecialchars($email['email']) ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-check me-1"></i>Activo
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-danger" onclick="deleteEmail(<?= $email['id'] ?>, '<?= htmlspecialchars($email['email']) ?>')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay correos registrados</h5>
                            <p class="text-muted mb-3">Agrega correos electrónicos para recibir notificaciones automáticas de stock bajo.</p>
                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addEmailModal">
                                <i class="fas fa-plus me-1"></i>Agregar Primer Correo
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Agregar Correo -->
<div class="modal fade" id="addEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?page=notificaciones&action=add">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Agregar Correo para Notificaciones
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="email" class="form-label">Correo Electrónico</label>
                        <input type="email" class="form-control" id="email" name="email" required>
                        <div class="form-text">
                            Este correo recibirá alertas automáticas cuando haya productos con stock bajo.
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Agregar Correo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Confirmar Eliminar -->
<div class="modal fade" id="deleteEmailModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="?page=notificaciones&action=delete" id="deleteForm">
                <div class="modal-header">
                    <h5 class="modal-title text-danger">
                        <i class="fas fa-trash me-2"></i>Confirmar Eliminación
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>¿Estás seguro de que deseas eliminar el correo <strong id="emailToDelete"></strong> de las notificaciones?</p>
                    <p class="text-muted">Este correo dejará de recibir alertas automáticas de stock bajo.</p>
                    <input type="hidden" id="deleteEmailId" name="id">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Eliminar
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    function deleteEmail(id, email) {
        document.getElementById('deleteEmailId').value = id;
        document.getElementById('emailToDelete').textContent = email;
        new bootstrap.Modal(document.getElementById('deleteEmailModal')).show();
    }

    function testAlert() {
        if (confirm('¿Enviar una alerta de prueba a todos los correos registrados?')) {
            // Mostrar loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Enviando...';
            btn.disabled = true;

            // Hacer la petición
            fetch('?page=notificaciones&action=testAlert', {
                method: 'POST'
            }).then(response => {
                // Restaurar botón
                btn.innerHTML = originalText;
                btn.disabled = false;

                // Recargar página para ver el mensaje
                window.location.reload();
            }).catch(error => {
                btn.innerHTML = originalText;
                btn.disabled = false;
                alert('Error enviando alerta de prueba');
            });
        }
    }
</script>