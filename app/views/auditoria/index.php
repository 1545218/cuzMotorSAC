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
                <h1 class="h3">
                    <i class="fas fa-shield-alt"></i> <?= htmlspecialchars($title) ?>
                </h1>
                <div class="btn-group">
                    <a href="?page=auditoria&action=estadisticas" class="btn btn-info">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </a>
                    <a href="?page=auditoria&action=buscar" class="btn btn-secondary">
                        <i class="fas fa-search"></i> Buscar
                    </a>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                            <i class="fas fa-download"></i> Exportar
                        </button>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="?page=auditoria&action=exportar&formato=csv">CSV</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Estadísticas rápidas -->
            <?php if (!empty($estadisticas)): ?>
                <div class="row mb-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-chart-pie"></i> Actividad por Tabla (Últimos 30 días)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach (array_slice($estadisticas, 0, 4) as $stat): ?>
                                        <div class="col-md-3 mb-2">
                                            <div class="d-flex align-items-center">
                                                <div class="flex-grow-1">
                                                    <strong><?= htmlspecialchars($stat['tabla_afectada']) ?></strong><br>
                                                    <small class="text-muted"><?= number_format($stat['total_cambios']) ?> cambios</small>
                                                </div>
                                                <span class="badge badge-primary"><?= $stat['usuarios_activos'] ?> usuarios</span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-filter"></i> Filtros de Búsqueda
                    </h5>
                </div>
                <div class="card-body">
                    <form method="GET" action="?page=auditoria">
                        <input type="hidden" name="page" value="auditoria">

                        <div class="row">
                            <div class="col-md-3">
                                <label for="tabla" class="form-label">Tabla:</label>
                                <select class="form-select" name="tabla" id="tabla">
                                    <option value="">Todas las tablas</option>
                                    <?php if (!empty($estadisticas)): ?>
                                        <?php foreach ($estadisticas as $stat): ?>
                                            <option value="<?= htmlspecialchars($stat['tabla_afectada']) ?>"
                                                <?= $filtros['tabla'] === $stat['tabla_afectada'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($stat['tabla_afectada']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </select>
                            </div>

                            <div class="col-md-3">
                                <label for="usuario" class="form-label">Usuario:</label>
                                <select class="form-select" name="usuario" id="usuario">
                                    <option value="">Todos los usuarios</option>
                                    <?php foreach ($usuarios as $user): ?>
                                        <option value="<?= $user['id_usuario'] ?>"
                                            <?= $filtros['usuario'] == $user['id_usuario'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['nombre'] . ' ' . $user['apellido']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="col-md-2">
                                <label for="fecha_desde" class="form-label">Desde:</label>
                                <input type="date" class="form-control" name="fecha_desde" id="fecha_desde"
                                    value="<?= htmlspecialchars($filtros['fecha_desde']) ?>">
                            </div>

                            <div class="col-md-2">
                                <label for="fecha_hasta" class="form-label">Hasta:</label>
                                <input type="date" class="form-control" name="fecha_hasta" id="fecha_hasta"
                                    value="<?= htmlspecialchars($filtros['fecha_hasta']) ?>">
                            </div>

                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <a href="?page=auditoria" class="btn btn-outline-secondary">
                                    <i class="fas fa-times"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Historial de cambios -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-history"></i> Historial de Cambios
                        <?php if (!empty($filtros['tabla']) || !empty($filtros['usuario']) || !empty($filtros['fecha_desde'])): ?>
                            <span class="badge badge-info ms-2">Filtrado</span>
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($historial)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-history fa-3x text-muted mb-3"></i>
                            <p class="text-muted">No se encontraron cambios registrados</p>
                            <?php if (!empty($filtros['tabla']) || !empty($filtros['usuario']) || !empty($filtros['fecha_desde'])): ?>
                                <a href="?page=auditoria" class="btn btn-outline-primary">
                                    <i class="fas fa-times"></i> Limpiar filtros
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-hover table-sm">
                                <thead>
                                    <tr>
                                        <th width="140">Fecha</th>
                                        <th width="100">Usuario</th>
                                        <th width="100">Tabla</th>
                                        <th width="80">ID</th>
                                        <th width="120">Campo</th>
                                        <th>Valor Anterior</th>
                                        <th>Valor Nuevo</th>
                                        <th width="80">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($historial as $cambio): ?>
                                        <tr>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($cambio['fecha'])) ?></small>
                                            </td>
                                            <td>
                                                <?php if ($cambio['nombre']): ?>
                                                    <span class="badge badge-secondary">
                                                        <?= htmlspecialchars($cambio['nombre']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Sistema</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= htmlspecialchars($cambio['tabla_afectada']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?page=auditoria&action=registro&tabla=<?= urlencode($cambio['tabla_afectada']) ?>&id=<?= $cambio['registro_id'] ?>"
                                                    class="text-decoration-none">
                                                    #<?= $cambio['registro_id'] ?>
                                                </a>
                                            </td>
                                            <td>
                                                <code><?= htmlspecialchars($cambio['campo_modificado']) ?></code>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;"
                                                    title="<?= htmlspecialchars($cambio['valor_anterior'] ?? 'NULL') ?>">
                                                    <?php if (is_null($cambio['valor_anterior'])): ?>
                                                        <em class="text-muted">NULL</em>
                                                    <?php else: ?>
                                                        <span class="text-danger"><?= htmlspecialchars($cambio['valor_anterior']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-truncate" style="max-width: 200px;"
                                                    title="<?= htmlspecialchars($cambio['valor_nuevo'] ?? 'NULL') ?>">
                                                    <?php if (is_null($cambio['valor_nuevo'])): ?>
                                                        <em class="text-muted">NULL</em>
                                                    <?php else: ?>
                                                        <span class="text-success"><?= htmlspecialchars($cambio['valor_nuevo']) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <a href="?page=auditoria&action=registro&tabla=<?= urlencode($cambio['tabla_afectada']) ?>&id=<?= $cambio['registro_id'] ?>"
                                                    class="btn btn-outline-primary btn-sm" title="Ver historial completo">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Información adicional -->
                        <div class="mt-3">
                            <small class="text-muted">
                                <i class="fas fa-info-circle"></i>
                                Mostrando <?= count($historial) ?> cambios recientes.
                                Para ver más resultados, use los filtros de búsqueda.
                            </small>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel de administración (solo administradores) -->
            <div class="card mt-4">
                <div class="card-header bg-warning">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-cogs"></i> Administración de Auditoría
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>Limpieza de Registros Antiguos</h6>
                            <p class="text-muted">Elimina registros de auditoría antiguos para optimizar el rendimiento.</p>
                            <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#limpiezaModal">
                                <i class="fas fa-broom"></i> Limpiar Registros Antiguos
                            </button>
                        </div>
                        <div class="col-md-6">
                            <h6>Estado del Sistema</h6>
                            <p class="text-muted">La auditoría automática está <strong class="text-success">ACTIVA</strong> para todos los modelos.</p>
                            <div class="small text-muted">
                                Total de tablas auditadas: <strong><?= count($estadisticas) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de limpieza -->
<div class="modal fade" id="limpiezaModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title">Limpiar Registros de Auditoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="?page=auditoria&action=limpiar">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                        <strong>Atención:</strong> Esta acción eliminará permanentemente los registros antiguos.
                    </div>

                    <div class="mb-3">
                        <label for="dias_mantenimiento" class="form-label">Mantener registros de los últimos:</label>
                        <select class="form-select" name="dias_mantenimiento" id="dias_mantenimiento">
                            <option value="30">30 días</option>
                            <option value="90">90 días (3 meses)</option>
                            <option value="180">180 días (6 meses)</option>
                            <option value="365" selected>365 días (1 año)</option>
                            <option value="730">730 días (2 años)</option>
                        </select>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-broom"></i> Ejecutar Limpieza
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Auto-refresh cada 30 segundos para cambios recientes (opcional)
    // setTimeout(function() { location.reload(); }, 30000);
</script>

<?php include_once '../app/views/layout/footer.php'; ?>