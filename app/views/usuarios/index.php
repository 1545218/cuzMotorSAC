<?php
$title = 'Gestión de Usuarios';
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title"><i class="fas fa-users me-2"></i>Gestión de Usuarios</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-primary btn-sm" onclick="window.location.href='?page=usuarios&action=create'">
                            <i class="fas fa-plus me-1"></i>Nuevo Usuario
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="table-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Nombre</th>
                                    <th>Apellido</th>
                                    <th>Teléfono</th>
                                    <th>Rol</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (isset($usuarios) && !empty($usuarios)): ?>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <tr>
                                            <td><?= $usuario['id_usuario'] ?></td>
                                            <td><?= htmlspecialchars($usuario['usuario']) ?></td>
                                            <td><?= htmlspecialchars($usuario['nombre']) ?></td>
                                            <td><?= htmlspecialchars($usuario['apellido']) ?></td>
                                            <td><?= htmlspecialchars($usuario['telefono'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($usuario['id_rol']) ?></td>
                                            <td>
                                                <span class="badge bg-<?= $usuario['estado'] === 'activo' ? 'success' : 'secondary' ?>">
                                                    <?= ucfirst($usuario['estado']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <a href="?page=usuarios&action=edit&id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-outline-info" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if (isset($_SESSION['rol']) && $_SESSION['rol'] === 'administrador'): ?>
                                                        <a href="?page=usuarios&action=edit&id=<?= $usuario['id_usuario'] ?>#email" class="btn btn-sm btn-outline-warning" title="Editar correo electrónico">
                                                            <i class="fas fa-envelope"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="?page=usuarios&action=delete&id=<?= $usuario['id_usuario'] ?>" class="btn btn-sm btn-outline-danger" title="Eliminar" onclick="return confirm('¿Eliminar este usuario?');">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3"></i>
                                            <br>No hay usuarios registrados
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
</div>