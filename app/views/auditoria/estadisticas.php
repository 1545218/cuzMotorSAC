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
                    <i class="fas fa-chart-bar"></i> <?= htmlspecialchars($title) ?>
                </h1>
                <a href="?page=auditoria" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver
                </a>
            </div>

            <!-- Resumen de actividad -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-primary"><?= count($estadisticasTablas) ?></h4>
                            <p class="text-muted mb-0">Tablas con Actividad</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-success"><?= array_sum(array_column($estadisticasTablas, 'total_cambios')) ?></h4>
                            <p class="text-muted mb-0">Total de Cambios</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-info"><?= count($cambiosRecientes) ?></h4>
                            <p class="text-muted mb-0">Cambios Recientes</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h4 class="text-warning">
                                <?= count(array_filter($estadisticasTablas, function ($t) {
                                    return strtotime($t['ultimo_cambio']) > strtotime('-1 day');
                                })) ?>
                            </h4>
                            <p class="text-muted mb-0">Activas Hoy</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Estadísticas por tabla -->
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-table"></i> Actividad por Tabla
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Tabla</th>
                                    <th>Total de Cambios</th>
                                    <th>Días Activos</th>
                                    <th>Primer Cambio</th>
                                    <th>Último Cambio</th>
                                    <th>Actividad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($estadisticasTablas as $tabla): ?>
                                    <tr>
                                        <td>
                                            <span class="badge badge-primary"><?= htmlspecialchars($tabla['tabla_afectada']) ?></span>
                                        </td>
                                        <td>
                                            <strong><?= number_format($tabla['total_cambios']) ?></strong>
                                        </td>
                                        <td><?= $tabla['dias_activos'] ?> días</td>
                                        <td>
                                            <small><?= date('d/m/Y', strtotime($tabla['primer_cambio'])) ?></small>
                                        </td>
                                        <td>
                                            <small><?= date('d/m/Y H:i', strtotime($tabla['ultimo_cambio'])) ?></small>
                                        </td>
                                        <td>
                                            <div class="progress" style="height: 20px;">
                                                <?php
                                                $maxCambios = max(array_column($estadisticasTablas, 'total_cambios'));
                                                $porcentaje = $maxCambios > 0 ? ($tabla['total_cambios'] / $maxCambios) * 100 : 0;
                                                ?>
                                                <div class="progress-bar" style="width: <?= $porcentaje ?>%">
                                                    <?= number_format($porcentaje, 1) ?>%
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Cambios recientes -->
            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-clock"></i> Cambios Más Recientes
                    </h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th width="120">Fecha</th>
                                    <th width="100">Usuario</th>
                                    <th width="100">Tabla</th>
                                    <th width="80">ID</th>
                                    <th>Campo Modificado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cambiosRecientes as $cambio): ?>
                                    <tr>
                                        <td>
                                            <small><?= date('d/m H:i', strtotime($cambio['fecha'])) ?></small>
                                        </td>
                                        <td>
                                            <?php if ($cambio['nombre']): ?>
                                                <small class="badge badge-secondary">
                                                    <?= htmlspecialchars($cambio['nombre']) ?>
                                                </small>
                                            <?php else: ?>
                                                <small class="text-muted">Sistema</small>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <small class="badge badge-info">
                                                <?= htmlspecialchars($cambio['tabla_afectada']) ?>
                                            </small>
                                        </td>
                                        <td>#<?= $cambio['registro_id'] ?></td>
                                        <td>
                                            <code style="font-size: 0.8rem;"><?= htmlspecialchars($cambio['campo_modificado']) ?></code>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include_once '../app/views/layout/footer.php'; ?>