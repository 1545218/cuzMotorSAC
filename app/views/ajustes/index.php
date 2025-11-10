<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-balance-scale me-2"></i>Ajustes de Inventario
                </h2>
                <a href="?page=ajustes&action=create" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Nuevo Ajuste
                </a>
            </div>

            <!-- Mensajes -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>

            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Filtros rápidos -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <select class="form-control" id="filtroTipo">
                                <option value="">Todos los tipos</option>
                                <option value="aumento">Aumentos</option>
                                <option value="disminucion">Disminuciones</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="fechaInicio" placeholder="Fecha inicio">
                        </div>
                        <div class="col-md-3">
                            <input type="date" class="form-control" id="fechaFin" placeholder="Fecha fin">
                        </div>
                        <div class="col-md-3">
                            <button class="btn btn-outline-secondary" onclick="aplicarFiltros()">
                                <i class="fas fa-search"></i> Filtrar
                            </button>
                            <button class="btn btn-outline-info" onclick="limpiarFiltros()">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de ajustes -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-list me-2"></i>Historial de Ajustes
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($ajustes)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped" id="tablaAjustes">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Producto</th>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Fecha</th>
                                        <th>Usuario</th>
                                        <th>Motivo</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($ajustes as $ajuste): ?>
                                        <tr>
                                            <td>#<?= $ajuste['id_ajuste'] ?></td>
                                            <td>
                                                <div>
                                                    <strong><?= htmlspecialchars($ajuste['producto_nombre']) ?></strong>
                                                    <?php if ($ajuste['codigo_barras']): ?>
                                                        <br><small class="text-muted"><?= htmlspecialchars($ajuste['codigo_barras']) ?></small>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($ajuste['tipo'] === 'aumento'): ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-plus"></i> Aumento
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-minus"></i> Disminución
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong><?= number_format($ajuste['cantidad']) ?></strong>
                                            </td>
                                            <td>
                                                <small><?= date('d/m/Y H:i', strtotime($ajuste['fecha'])) ?></small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($ajuste['usuario_nombre'] ?? 'Sistema') ?>
                                            </td>
                                            <td>
                                                <span title="<?= htmlspecialchars($ajuste['motivo']) ?>">
                                                    <?= strlen($ajuste['motivo']) > 30
                                                        ? substr(htmlspecialchars($ajuste['motivo']), 0, 30) . '...'
                                                        : htmlspecialchars($ajuste['motivo']) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="?page=ajustes&action=show&id=<?= $ajuste['id_ajuste'] ?>"
                                                    class="btn btn-sm btn-outline-info" title="Ver detalles">
                                                    <i class="fas fa-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-balance-scale fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay ajustes registrados</h5>
                            <p class="text-muted">Los ajustes de inventario se mostrarán aquí una vez que sean creados.</p>
                            <a href="?page=ajustes&action=create" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Crear Primer Ajuste
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Enlaces rápidos -->
            <div class="row mt-4">
                <div class="col-md-4">
                    <div class="card border-primary">
                        <div class="card-body text-center">
                            <i class="fas fa-chart-bar fa-2x text-primary mb-2"></i>
                            <h6>Estadísticas</h6>
                            <a href="?page=ajustes&action=estadisticas" class="btn btn-outline-primary btn-sm">
                                Ver Estadísticas
                            </a>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-success">
                        <div class="card-body text-center">
                            <i class="fas fa-file-export fa-2x text-success mb-2"></i>
                            <h6>Exportar</h6>
                            <button class="btn btn-outline-success btn-sm" onclick="exportarAjustes()">
                                Exportar Excel
                            </button>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-info">
                        <div class="card-body text-center">
                            <i class="fas fa-boxes fa-2x text-info mb-2"></i>
                            <h6>Inventario</h6>
                            <a href="?page=inventario" class="btn btn-outline-info btn-sm">
                                Ver Inventario
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Inicializar DataTable si hay datos
        <?php if (!empty($ajustes)): ?>
            $('#tablaAjustes').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                },
                order: [
                    [4, 'desc']
                ], // Ordenar por fecha descendente
                pageLength: 25,
                responsive: true
            });
        <?php endif; ?>
    });

    function aplicarFiltros() {
        // Implementar filtros si es necesario
        const tipo = document.getElementById('filtroTipo').value;
        const fechaInicio = document.getElementById('fechaInicio').value;
        const fechaFin = document.getElementById('fechaFin').value;

        let url = '?page=ajustes';
        const params = [];

        if (tipo) params.push('tipo=' + tipo);
        if (fechaInicio) params.push('fecha_inicio=' + fechaInicio);
        if (fechaFin) params.push('fecha_fin=' + fechaFin);

        if (params.length > 0) {
            url += '&' + params.join('&');
        }

        window.location.href = url;
    }

    function limpiarFiltros() {
        document.getElementById('filtroTipo').value = '';
        document.getElementById('fechaInicio').value = '';
        document.getElementById('fechaFin').value = '';
        window.location.href = '?page=ajustes';
    }

    function exportarAjustes() {
        // Implementar exportación si es necesario
        alert('Función de exportación en desarrollo');
    }
</script>