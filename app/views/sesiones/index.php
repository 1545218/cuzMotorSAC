<?php
// Verificar autenticación
Auth::requireAuth();

$isAdmin = Auth::hasRole('administrador');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-users-cog"></i> Gestión de Sesiones</h2>

                <?php if ($isAdmin): ?>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-info" onclick="actualizarSesiones()">
                            <i class="fas fa-sync"></i> Actualizar
                        </button>
                        <a href="?page=sesiones&action=estadisticas" class="btn btn-outline-secondary">
                            <i class="fas fa-chart-bar"></i> Estadísticas
                        </a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Mostrar mensajes -->
            <?php if (isset($_SESSION['message'])): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?= htmlspecialchars($_SESSION['message']) ?>
                    <button type="button" class="close" data-dismiss="alert">
                        <span>&times;</span>
                    </button>
                </div>
                <?php unset($_SESSION['message']); ?>
            <?php endif; ?>

            <!-- Estadísticas rápidas -->
            <?php if (!empty($estadisticas)): ?>
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-users fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?= $estadisticas['sesiones_activas'] ?? 0 ?></h4>
                                        <small>Sesiones Activas</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-history fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?= $estadisticas['total_sesiones'] ?? 0 ?></h4>
                                        <small>Total Hoy</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-user-friends fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?= $estadisticas['usuarios_unicos'] ?? 0 ?></h4>
                                        <small>Usuarios Únicos</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-clock fa-2x me-3"></i>
                                    <div>
                                        <h4 class="mb-0"><?= round($estadisticas['duracion_promedio_minutos'] ?? 0) ?> min</h4>
                                        <small>Duración Promedio</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5><i class="fas fa-filter"></i> Filtros de Búsqueda</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <input type="hidden" name="page" value="sesiones">

                        <?php if ($isAdmin): ?>
                            <div class="col-md-3">
                                <label class="form-label">Usuario</label>
                                <select name="usuario_id" class="form-select">
                                    <option value="">Todos los usuarios</option>
                                    <?php foreach ($usuarios as $usuario): ?>
                                        <option value="<?= $usuario['id_usuario'] ?>"
                                            <?= ($filtros['usuario_id'] == $usuario['id_usuario']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($usuario['usuario'] . ' - ' . $usuario['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>

                        <div class="col-md-2">
                            <label class="form-label">Estado</label>
                            <select name="activo" class="form-select">
                                <option value="">Todas</option>
                                <option value="si" <?= ($filtros['activo'] == 'si') ? 'selected' : '' ?>>Activas</option>
                                <option value="no" <?= ($filtros['activo'] == 'no') ? 'selected' : '' ?>>Cerradas</option>
                            </select>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Fecha Desde</label>
                            <input type="date" name="fecha_desde" class="form-control" value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">Fecha Hasta</label>
                            <input type="date" name="fecha_hasta" class="form-control" value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                        </div>

                        <div class="col-md-2">
                            <label class="form-label">IP</label>
                            <input type="text" name="ip" class="form-control" placeholder="192.168..." value="<?= htmlspecialchars($filtros['ip']) ?>">
                        </div>

                        <div class="col-md-1">
                            <label class="form-label">&nbsp;</label>
                            <button type="submit" class="btn btn-primary d-block">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Acciones rápidas -->
            <?php if (!$isAdmin): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="fas fa-tools"></i> Gestionar Mis Sesiones</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="?page=sesiones&action=cerrarOtras" style="display: inline;">
                            <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                            <button type="submit" class="btn btn-warning"
                                onclick="return confirm('¿Cerrar todas tus otras sesiones activas?')">
                                <i class="fas fa-sign-out-alt"></i> Cerrar Otras Sesiones
                            </button>
                        </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabla de sesiones -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5><i class="fas fa-list"></i> <?= $isAdmin ? 'Todas las Sesiones' : 'Mis Sesiones' ?></h5>

                    <?php if ($isAdmin): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#limpiarModal">
                                <i class="fas fa-broom"></i> Limpiar Expiradas
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($sesiones)): ?>
                        <div class="text-center text-muted">
                            <i class="fas fa-inbox fa-3x mb-3"></i>
                            <p>No se encontraron sesiones</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <?php if ($isAdmin): ?>
                                            <th>Usuario</th>
                                        <?php endif; ?>
                                        <th>IP</th>
                                        <th>Inicio</th>
                                        <th>Fin</th>
                                        <th>Duración</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($sesiones as $sesion): ?>
                                        <tr>
                                            <?php if ($isAdmin): ?>
                                                <td>
                                                    <div>
                                                        <strong><?= htmlspecialchars($sesion['usuario']) ?></strong>
                                                        <br>
                                                        <small class="text-muted"><?= htmlspecialchars($sesion['nombre']) ?></small>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                            <td>
                                                <span class="badge bg-secondary"><?= htmlspecialchars($sesion['ip_address']) ?></span>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y H:i', strtotime($sesion['inicio_sesion'])) ?>
                                            </td>
                                            <td>
                                                <?= $sesion['fin_sesion'] ? date('d/m/Y H:i', strtotime($sesion['fin_sesion'])) : '-' ?>
                                            </td>
                                            <td>
                                                <?php
                                                $duracion = $sesion['duracion_minutos'] ?? 0;
                                                if ($duracion >= 60) {
                                                    echo floor($duracion / 60) . 'h ' . ($duracion % 60) . 'm';
                                                } else {
                                                    echo $duracion . 'm';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php if ($sesion['activo']): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-circle"></i> Activa
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">
                                                        <i class="fas fa-times-circle"></i> Cerrada
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($sesion['activo']): ?>
                                                    <form method="POST" action="?page=sesiones&action=cerrar" style="display: inline;">
                                                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">
                                                        <input type="hidden" name="session_id" value="<?= $sesion['id_sesion'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                                            onclick="return confirm('¿Cerrar esta sesión?')">
                                                            <i class="fas fa-sign-out-alt"></i>
                                                        </button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
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

<!-- Modal para limpiar sesiones expiradas (solo administradores) -->
<?php if ($isAdmin): ?>
    <div class="modal fade" id="limpiarModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Limpiar Sesiones Expiradas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="?page=sesiones&action=limpiar">
                    <div class="modal-body">
                        <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">

                        <div class="mb-3">
                            <label class="form-label">Horas de inactividad para considerar expirada</label>
                            <select name="horas_vencimiento" class="form-select">
                                <option value="1">1 hora</option>
                                <option value="6">6 horas</option>
                                <option value="12">12 horas</option>
                                <option value="24" selected>24 horas</option>
                                <option value="48">48 horas</option>
                            </select>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            Esta acción marcará como cerradas las sesiones que excedan el tiempo especificado.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-warning">Limpiar Expiradas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
<?php endif; ?>

<script>
    // Función para actualizar sesiones automáticamente
    function actualizarSesiones() {
        location.reload();
    }

    // Auto-actualizar cada 30 segundos para mostrar estado en tiempo real
    <?php if ($isAdmin): ?>
        setInterval(function() {
            // Solo auto-actualizar si no hay modales abiertos
            if (!document.querySelector('.modal.show')) {
                actualizarSesiones();
            }
        }, 30000);
    <?php endif; ?>

    // Confirmar acciones críticas
    document.querySelectorAll('form[action*="cerrar"]').forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!confirm('¿Estás seguro de que deseas cerrar esta sesión?')) {
                e.preventDefault();
            }
        });
    });
</script>