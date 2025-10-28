<?php
$title = 'Dashboard';
?>

<!-- Page Header con breadcrumb mejorado -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-1">
            <i class="fas fa-tachometer-alt text-primary me-2"></i>
            Dashboard Principal
        </h1>
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb mb-0">
                <li class="breadcrumb-item active">Dashboard</li>
            </ol>
        </nav>
    </div>
    <div class="d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" onclick="refreshDashboard()" title="Actualizar datos">
            <i class="fas fa-sync-alt me-1"></i>Actualizar
        </button>
        <?php if (isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'administrador'): ?>
            <a href="?page=reportes" class="btn btn-primary btn-sm">
                <i class="fas fa-chart-bar me-1"></i>Reportes
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Alertas importantes ocultas del frontend -->

<!-- KPIs Principales -->
<div class="row g-3 mb-4">
    <!-- Total Productos -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Total Productos</div>
                        <div class="h4 mb-0 text-dark" id="total-productos">
                            <?= number_format($stats['total_productos'] ?? 0) ?>
                        </div>
                        <div class="text-success small">
                            <i class="fas fa-arrow-up me-1"></i>
                            <span id="productos-variacion">Activos</span>
                        </div>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-boxes fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="?page=productos" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-eye me-1"></i>Ver Productos
                </a>
            </div>
        </div>
    </div>

    <!-- Stock Bajo -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Stock Bajo</div>
                        <div class="h4 mb-0 text-warning" id="stock-bajo">
                            <?= number_format($stats['stock_bajo'] ?? 0) ?>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Requieren atención
                        </div>
                    </div>
                    <div class="text-warning">
                        <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="?page=inventario&action=alertas" class="btn btn-sm btn-outline-warning w-100">
                    <i class="fas fa-bell me-1"></i>Ver Alertas
                </a>
            </div>
        </div>
    </div>

    <!-- Ventas del Mes -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Ventas del Mes</div>
                        <div class="h4 mb-0 text-success" id="ventas-mes">
                            S/ <?= number_format($stats['ventas_mes'] ?? 0, 2) ?>
                        </div>
                        <div class="text-success small">
                            <i class="fas fa-chart-line me-1"></i>
                            <?= $stats['variacion_ventas'] ?? '+0%' ?>
                        </div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-chart-line fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['administrador', 'vendedor'])): ?>
                    <a href="?page=cotizaciones" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-file-invoice-dollar me-1"></i>Ver Cotizaciones
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                        Sin acceso
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Cotizaciones Pendientes -->
    <div class="col-xl-3 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Cotizaciones Pendientes</div>
                        <div class="h4 mb-0 text-info" id="cotizaciones-pendientes">
                            <?= number_format($stats['cotizaciones_pendientes'] ?? 0) ?>
                        </div>
                        <div class="text-muted small">
                            <i class="fas fa-clock me-1"></i>
                            Esperando respuesta
                        </div>
                    </div>
                    <div class="text-info">
                        <i class="fas fa-file-invoice fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <?php if (isset($_SESSION['user_role']) && in_array($_SESSION['user_role'], ['administrador', 'vendedor'])): ?>
                    <a href="?page=cotizaciones&estado=pendiente" class="btn btn-sm btn-outline-info w-100">
                        <i class="fas fa-tasks me-1"></i>Gestionar
                    </a>
                <?php else: ?>
                    <button class="btn btn-sm btn-outline-secondary w-100" disabled>
                        Sin acceso
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>


<!-- Alertas del sistema (solo si existen) -->
<?php if (!empty($alerts)): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Alertas del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <?php foreach ($alerts as $alert): ?>
                        <div class="alert alert-<?= $alert['type'] ?> d-flex align-items-center" role="alert">
                            <i class="<?= $alert['icon'] ?> me-3"></i>
                            <div class="flex-grow-1">
                                <strong><?= htmlspecialchars($alert['title']) ?></strong><br>
                                <?= htmlspecialchars($alert['message']) ?>
                            </div>
                            <?php if (isset($alert['url'])): ?>
                                <a href="<?= $alert['url'] ?>" class="btn btn-sm btn-outline-<?= $alert['type'] ?>">
                                    Ver detalles
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- Gráficos y datos -->
<div class="row mb-4">
    <!-- Gráfico de movimientos diarios -->
    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-line text-primary me-2"></i>
                    Movimientos de las Últimas 24 Horas
                </h5>
            </div>
            <div class="card-body">
                <canvas id="movimientosChart" height="100"></canvas>
            </div>
        </div>
    </div>


</div>

<!-- Productos con stock bajo y movimientos recientes -->
<div class="row">
    <!-- Productos con stock bajo -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                    Productos con Stock Bajo
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($lowStockProducts)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Stock</th>
                                    <th>Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($product['nombre']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($product['codigo']) ?></small>
                                            </div>
                                        </td>
                                        <td><?= number_format($product['stock']) ?></td>
                                        <td><?= number_format($product['stock_minimo']) ?></td>
                                        <td>
                                            <?php
                                            $percentage = $product['porcentaje_stock'];
                                            $badgeClass = $percentage <= 25 ? 'danger' : ($percentage <= 50 ? 'warning' : 'success');
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <?= number_format($percentage, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?= BASE_PATH ?>/productos/stock-bajo" class="btn btn-sm btn-outline-warning">
                            Ver todos los productos con stock bajo
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-check-circle fa-3x mb-3"></i>
                        <p>¡Excelente! No hay productos con stock bajo.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Movimientos recientes -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history text-info me-2"></i>
                    Movimientos Recientes
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentMovements)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Tipo</th>
                                    <th>Cantidad</th>
                                    <th>Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMovements as $movement): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong><?= htmlspecialchars($movement['producto_nombre']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($movement['producto_codigo']) ?></small>
                                            </div>
                                        </td>
                                        <td>
                                            <?php
                                            $badgeClass = $movement['tipo_movimiento'] === 'entrada' ? 'success' : 'danger';
                                            $icon = $movement['tipo_movimiento'] === 'entrada' ? 'arrow-up' : 'arrow-down';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <i class="fas fa-<?= $icon ?> me-1"></i>
                                                <?= ucfirst($movement['tipo_movimiento']) ?>
                                            </span>
                                        </td>
                                        <td><?= number_format($movement['cantidad']) ?></td>
                                        <td>
                                            <small><?= formatDate($movement['fecha_movimiento'], DATETIME_FORMAT) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="<?= BASE_PATH ?>/inventario/movimientos" class="btn btn-sm btn-outline-info">
                            Ver todos los movimientos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3"></i>
                        <p>No hay movimientos recientes.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Scripts para gráficos -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Datos para gráficos
        const chartData = <?= json_encode($chartData) ?>;

        // Gráfico de movimientos diarios
        if (chartData.movimientos_diarios && chartData.movimientos_diarios.length > 0) {
            const ctx1 = document.getElementById('movimientosChart').getContext('2d');
            new Chart(ctx1, {
                type: 'line',
                data: {
                    labels: chartData.movimientos_diarios.map(item => {
                        const date = new Date(item.fecha);
                        return date.toLocaleDateString('es-PE', {
                            day: '2-digit',
                            month: '2-digit'
                        });
                    }),
                    datasets: [{
                        label: 'Entradas',
                        data: chartData.movimientos_diarios.map(item => item.entradas),
                        borderColor: 'rgb(75, 192, 192)',
                        backgroundColor: 'rgba(75, 192, 192, 0.2)',
                        tension: 0.1
                    }, {
                        label: 'Salidas',
                        data: chartData.movimientos_diarios.map(item => item.salidas),
                        borderColor: 'rgb(255, 99, 132)',
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        tension: 0.1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        }


    });

    // Función para actualizar dashboard con AJAX
    function refreshDashboard() {
        const loadingSpinner = '<i class="fas fa-spinner fa-spin"></i>';

        // Mostrar indicadores de carga
        document.getElementById('total-productos').innerHTML = loadingSpinner;
        document.getElementById('stock-bajo').innerHTML = loadingSpinner;
        document.getElementById('ventas-mes').innerHTML = loadingSpinner;
        document.getElementById('cotizaciones-pendientes').innerHTML = loadingSpinner;

        // Fetch datos actualizados
        fetch('<?= BASE_PATH ?>/public/?page=dashboard&action=refresh', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Actualizar KPIs
                    document.getElementById('total-productos').textContent = new Intl.NumberFormat('es-PE').format(data.stats.total_productos);
                    document.getElementById('stock-bajo').textContent = new Intl.NumberFormat('es-PE').format(data.stats.stock_bajo);
                    document.getElementById('ventas-mes').textContent = 'S/ ' + new Intl.NumberFormat('es-PE', {
                        minimumFractionDigits: 2
                    }).format(data.stats.ventas_mes);
                    document.getElementById('cotizaciones-pendientes').textContent = new Intl.NumberFormat('es-PE').format(data.stats.cotizaciones_pendientes);

                    showToast('Dashboard actualizado correctamente', 'success');
                } else {
                    throw new Error(data.message || 'Error al actualizar');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showToast('Error al actualizar el dashboard', 'error');

                // Restaurar valores originales en caso de error
                setTimeout(() => location.reload(), 2000);
            });
    }

    // Auto-refresh cada 5 minutos
    setInterval(refreshDashboard, 300000);
</script>