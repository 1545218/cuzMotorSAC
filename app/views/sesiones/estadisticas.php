<?php
// Solo administradores pueden ver estadísticas
Auth::requireRole('administrador');
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-chart-bar"></i> Estadísticas de Sesiones</h2>
                <div class="btn-group">
                    <a href="?page=sesiones" class="btn btn-outline-primary">
                        <i class="fas fa-arrow-left"></i> Volver a Sesiones
                    </a>
                    <button type="button" class="btn btn-outline-info" onclick="location.reload()">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
            </div>

            <!-- Estadísticas por período -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-header bg-primary text-white">
                            <h5><i class="fas fa-calendar-day"></i> Hoy</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $estadisticas['today']['total_sesiones'] ?? 0 ?></h4>
                                    <small class="text-muted">Total Sesiones</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= $estadisticas['today']['sesiones_activas'] ?? 0 ?></h4>
                                    <small class="text-muted">Activas</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="text-info"><?= $estadisticas['today']['usuarios_unicos'] ?? 0 ?></h5>
                                    <small class="text-muted">Usuarios Únicos</small>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-warning"><?= round($estadisticas['today']['duracion_promedio_minutos'] ?? 0) ?>m</h5>
                                    <small class="text-muted">Duración Prom.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h5><i class="fas fa-calendar-week"></i> Esta Semana</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $estadisticas['week']['total_sesiones'] ?? 0 ?></h4>
                                    <small class="text-muted">Total Sesiones</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= $estadisticas['week']['sesiones_activas'] ?? 0 ?></h4>
                                    <small class="text-muted">Activas</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="text-info"><?= $estadisticas['week']['usuarios_unicos'] ?? 0 ?></h5>
                                    <small class="text-muted">Usuarios Únicos</small>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-warning"><?= round($estadisticas['week']['duracion_promedio_minutos'] ?? 0) ?>m</h5>
                                    <small class="text-muted">Duración Prom.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-header bg-success text-white">
                            <h5><i class="fas fa-calendar-alt"></i> Este Mes</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6">
                                    <h4 class="text-primary"><?= $estadisticas['month']['total_sesiones'] ?? 0 ?></h4>
                                    <small class="text-muted">Total Sesiones</small>
                                </div>
                                <div class="col-6">
                                    <h4 class="text-success"><?= $estadisticas['month']['sesiones_activas'] ?? 0 ?></h4>
                                    <small class="text-muted">Activas</small>
                                </div>
                            </div>
                            <hr>
                            <div class="row">
                                <div class="col-6">
                                    <h5 class="text-info"><?= $estadisticas['month']['usuarios_unicos'] ?? 0 ?></h5>
                                    <small class="text-muted">Usuarios Únicos</small>
                                </div>
                                <div class="col-6">
                                    <h5 class="text-warning"><?= round($estadisticas['month']['duracion_promedio_minutos'] ?? 0) ?>m</h5>
                                    <small class="text-muted">Duración Prom.</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top usuarios -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-trophy"></i> Top Usuarios (Últimos 30 días)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($topUsuarios)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <p>No hay datos de usuarios</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Usuario</th>
                                                <th>Total Sesiones</th>
                                                <th>Activas</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topUsuarios as $index => $usuario): ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <span class="badge bg-primary me-2"><?= $index + 1 ?></span>
                                                            <div>
                                                                <strong><?= htmlspecialchars($usuario['usuario']) ?></strong>
                                                                <br>
                                                                <small class="text-muted"><?= htmlspecialchars($usuario['nombre']) ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-info"><?= $usuario['total_sesiones'] ?></span>
                                                    </td>
                                                    <td>
                                                        <?php if ($usuario['sesiones_activas'] > 0): ?>
                                                            <span class="badge bg-success"><?= $usuario['sesiones_activas'] ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">0</span>
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

                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-clock"></i> Sesiones por Hora (Últimos 7 días)</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($sesionesPorHora)): ?>
                                <div class="text-center text-muted">
                                    <i class="fas fa-chart-line fa-2x mb-2"></i>
                                    <p>No hay datos horarios</p>
                                </div>
                            <?php else: ?>
                                <div class="chart-container">
                                    <?php
                                    // Crear array de 24 horas con datos
                                    $horasData = array_fill(0, 24, 0);
                                    foreach ($sesionesPorHora as $hora) {
                                        $horasData[(int)$hora['hora']] = (int)$hora['total'];
                                    }
                                    $maxValue = max($horasData) ?: 1;
                                    ?>

                                    <div class="row">
                                        <?php for ($h = 0; $h < 24; $h++): ?>
                                            <?php
                                            $valor = $horasData[$h];
                                            $porcentaje = ($valor / $maxValue) * 100;
                                            $altura = max($porcentaje, 5); // Mínimo 5% para visibilidad
                                            ?>
                                            <div class="col" style="padding: 2px;">
                                                <div class="text-center">
                                                    <div class="bg-primary d-flex align-items-end justify-content-center"
                                                        style="height: 60px; border-radius: 3px;">
                                                        <div class="bg-info text-white small"
                                                            style="height: <?= $altura ?>%; width: 100%; display: flex; align-items: end; justify-content: center; border-radius: 2px;">
                                                            <?= $valor > 0 ? $valor : '' ?>
                                                        </div>
                                                    </div>
                                                    <small class="text-muted"><?= sprintf('%02d', $h) ?></small>
                                                </div>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información del sistema -->
            <div class="card">
                <div class="card-header">
                    <h5><i class="fas fa-info-circle"></i> Información del Sistema</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <strong>Configuración de Sesiones:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="fas fa-check text-success"></i> Sesiones en BD habilitadas</li>
                                <li><i class="fas fa-clock"></i> Timeout: <?= defined('SESSION_LIFETIME') ? (SESSION_LIFETIME / 3600) . 'h' : '1h' ?></li>
                                <li><i class="fas fa-users"></i> Máx. concurrentes: <?= defined('MAX_SESIONES_CONCURRENTES') ? MAX_SESIONES_CONCURRENTES : '3' ?></li>
                            </ul>
                        </div>

                        <div class="col-md-3">
                            <strong>Políticas de Seguridad:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="fas fa-shield-alt text-success"></i> CSRF Protection</li>
                                <li><i class="fas fa-lock text-success"></i> Cookies Seguras</li>
                                <li><i class="fas fa-sync text-success"></i> Regeneración de ID</li>
                            </ul>
                        </div>

                        <div class="col-md-3">
                            <strong>Funcionalidades:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="fas fa-history text-info"></i> Auditoría automática</li>
                                <li><i class="fas fa-broom text-warning"></i> Limpieza automática</li>
                                <li><i class="fas fa-ban text-danger"></i> Control concurrente</li>
                            </ul>
                        </div>

                        <div class="col-md-3">
                            <strong>Estado Actual:</strong>
                            <ul class="list-unstyled mt-2">
                                <li><i class="fas fa-server text-success"></i> Sistema operativo</li>
                                <li><i class="fas fa-database text-success"></i> BD conectada</li>
                                <li><i class="fas fa-user-check text-success"></i> Tu sesión: Activa</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<style>
    .chart-container {
        height: 80px;
    }

    .table td {
        vertical-align: middle;
    }

    .badge {
        font-size: 0.8em;
    }

    .card-header {
        background-color: rgba(0, 123, 255, .1);
        border-bottom: 1px solid rgba(0, 123, 255, .2);
    }

    .border-primary {
        border-color: #007bff !important;
    }

    .border-info {
        border-color: #17a2b8 !important;
    }

    .border-success {
        border-color: #28a745 !important;
    }
</style>

<script>
    // Auto-actualizar estadísticas cada 60 segundos
    setInterval(function() {
        location.reload();
    }, 60000);

    // Mostrar tooltip en el gráfico de barras
    document.querySelectorAll('.chart-container .col').forEach((bar, index) => {
        bar.setAttribute('title', `Hora ${index.toString().padStart(2, '0')}:00`);
        bar.style.cursor = 'pointer';
    });
</script>