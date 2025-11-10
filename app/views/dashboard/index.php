<?php
$title = 'Dashboard';
$userRole = $_SESSION['user_role'] ?? $_SESSION['rol'] ?? 'mecanico';
// Tratar vendedor como mecánico en la interfaz
if ($userRole === 'vendedor') {
    $userRole = 'mecanico';
}
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
        <?php if ($userRole === 'administrador'): ?>
            <a href="?page=reportes" class="btn btn-primary btn-sm">
                <i class="fas fa-chart-bar me-1"></i>Reportes
            </a>
            <a href="?page=backups" class="btn btn-info btn-sm">
                <i class="fas fa-database me-1"></i>Backups
            </a>
        <?php endif; ?>
    </div>
</div>

<!-- Alertas importantes ocultas del frontend -->

<!-- 🚨 ALERTAS DE STOCK BAJO - Sistema Simple -->
<?php if (!empty($alertas) && count($alertas) > 0): ?>
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-warning">
                <div class="card-header bg-warning text-dark d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        🚨 Alertas de Stock Bajo (<?= count($alertas) ?>)
                    </h5>
                    <button class="btn btn-sm btn-dark" onclick="markAllAlertsAsRead()">
                        <i class="fas fa-check me-1"></i>Marcar como vistas
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php foreach ($alertas as $alerta): ?>
                            <?php
                            $datos = $alerta['datos'] ?? [];
                            $icono = $datos['icono'] ?? '⚠️';
                            $color = $datos['color'] ?? '#ffc107';
                            $porcentaje = $datos['porcentaje'] ?? 0;
                            ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center alert-item" data-alert-id="<?= $alerta['id'] ?>">
                                <div class="d-flex align-items-center">
                                    <span style="font-size: 1.5rem; margin-right: 10px;"><?= $icono ?></span>
                                    <div>
                                        <h6 class="mb-1">
                                            <strong><?= htmlspecialchars($alerta['producto_nombre'] ?? 'Producto') ?></strong>
                                            <span class="badge badge-<?= $alerta['nivel_urgencia'] ?>" style="background-color: <?= $color ?>;">
                                                <?= strtoupper($alerta['nivel_urgencia']) ?>
                                            </span>
                                        </h6>
                                        <p class="mb-1 text-muted"><?= htmlspecialchars($alerta['mensaje']) ?></p>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('d/m/Y H:i', strtotime($alerta['fecha_creacion'])) ?>
                                            | Stock: <?= $datos['stock_actual'] ?? 0 ?>/<?= $datos['stock_minimo'] ?? 0 ?>
                                            | <?= $porcentaje ?>% del mínimo
                                        </small>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="?page=productos&action=edit&id=<?= $alerta['producto_id'] ?>"
                                        class="btn btn-sm btn-outline-primary" title="Editar producto">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-success"
                                        onclick="markAlertAsRead(<?= $alerta['id'] ?>)"
                                        title="Marcar como vista">
                                        <i class="fas fa-check"></i>
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- KPIs Principales por Rol -->
<div class="row g-3 mb-4">
    <?php if ($userRole === 'administrador'): ?>
        <!-- ADMINISTRADOR: Métricas completas del negocio -->

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
                                <i class="fas fa-boxes me-1"></i>
                                <span id="productos-variacion">En inventario</span>
                            </div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-boxes fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=productos" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-cog me-1"></i>Gestionar Productos
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
                                <?= $stats['variacion_ventas'] ?? 'Mes actual' ?>
                            </div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-chart-line fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=reportes" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-chart-bar me-1"></i>Ver Reportes
                    </a>
                </div>
            </div>
        </div>

        <!-- Stock Bajo (CRÍTICO) -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Stock Bajo</div>
                            <div class="h4 mb-0 text-warning" id="stock-bajo">
                                <?= number_format($stats['stock_bajo'] ?? 0) ?>
                            </div>
                            <div class="text-warning small">
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Requieren reposición
                            </div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-exclamation-triangle fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=alertas" class="btn btn-sm btn-outline-warning w-100">
                        <i class="fas fa-bell me-1"></i>Ver Alertas
                    </a>
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
                            <div class="text-info small">
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
                    <a href="?page=cotizaciones" class="btn btn-sm btn-outline-info w-100">
                        <i class="fas fa-list me-1"></i>Gestionar Cotizaciones
                    </a>
                </div>
            </div>
        </div>

</div>

<!-- Segunda fila de widgets para administradores -->
<div class="row g-3 mb-4">
    <?php if ($userRole === 'administrador'): ?>

        <!-- Total Vehículos -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Total Vehículos</div>
                            <div class="h4 mb-0 text-info" id="total-vehiculos">
                                <?= number_format($stats['total_vehiculos'] ?? 0) ?>
                            </div>
                            <div class="text-info small">
                                <i class="fas fa-car me-1"></i>
                                Registrados
                            </div>
                        </div>
                        <div class="text-info">
                            <i class="fas fa-car fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=vehiculos" class="btn btn-sm btn-outline-info w-100">
                        <i class="fas fa-car me-1"></i>Gestionar Vehículos
                    </a>
                </div>
            </div>
        </div>

        <!-- Órdenes Abiertas -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Órdenes Abiertas</div>
                            <div class="h4 mb-0 text-primary" id="ordenes-abiertas">
                                <?= number_format($stats['ordenes_abiertas'] ?? 0) ?>
                            </div>
                            <div class="text-primary small">
                                <i class="fas fa-clipboard-list me-1"></i>
                                Pendientes
                            </div>
                        </div>
                        <div class="text-primary">
                            <i class="fas fa-clipboard-list fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=ordenes" class="btn btn-sm btn-outline-primary w-100">
                        <i class="fas fa-tools me-1"></i>Gestionar Órdenes
                    </a>
                </div>
            </div>
        </div>

        <!-- Órdenes en Proceso -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Órdenes en Proceso</div>
                            <div class="h4 mb-0 text-warning" id="ordenes-proceso">
                                <?= number_format($stats['ordenes_proceso'] ?? 0) ?>
                            </div>
                            <div class="text-warning small">
                                <i class="fas fa-cogs me-1"></i>
                                En ejecución
                            </div>
                        </div>
                        <div class="text-warning">
                            <i class="fas fa-cogs fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=ordenes&estado=en_proceso" class="btn btn-sm btn-outline-warning w-100">
                        <i class="fas fa-tasks me-1"></i>Ver en Proceso
                    </a>
                </div>
            </div>
        </div>

        <!-- Total Clientes -->
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="flex-grow-1">
                            <div class="text-muted small mb-1">Total Clientes</div>
                            <div class="h4 mb-0 text-success" id="total-clientes">
                                <?= number_format($stats['total_clientes'] ?? 0) ?>
                            </div>
                            <div class="text-success small">
                                <i class="fas fa-users me-1"></i>
                                Activos
                            </div>
                        </div>
                        <div class="text-success">
                            <i class="fas fa-users fa-2x opacity-75"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <a href="?page=clientes" class="btn btn-sm btn-outline-success w-100">
                        <i class="fas fa-user-plus me-1"></i>Gestionar Clientes
                    </a>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- KPIs Principales por Rol continuación -->
<div class="row g-3 mb-4">
<?php else: ?>
    <!-- MECÁNICO: Métricas enfocadas en inventario y productos -->

    <!-- Productos en Inventario -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Productos Disponibles</div>
                        <div class="h4 mb-0 text-primary" id="total-productos">
                            <?= number_format($stats['total_productos'] ?? 0) ?>
                        </div>
                        <div class="text-primary small">
                            <i class="fas fa-tools me-1"></i>
                            En almacén
                        </div>
                    </div>
                    <div class="text-primary">
                        <i class="fas fa-boxes fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="?page=productos" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fas fa-search me-1"></i>Buscar Productos
                </a>
            </div>
        </div>
    </div>

    <!-- Stock Bajo (IMPORTANTE para mecánico) -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Stock Bajo</div>
                        <div class="h4 mb-0 text-warning" id="stock-bajo">
                            <?= number_format($stats['stock_bajo'] ?? 0) ?>
                        </div>
                        <div class="text-warning small">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Notificar administrador
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

    <!-- Movimientos Recientes -->
    <div class="col-xl-4 col-md-6">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex align-items-center">
                    <div class="flex-grow-1">
                        <div class="text-muted small mb-1">Movimientos Hoy</div>
                        <div class="h4 mb-0 text-success" id="movimientos-hoy">
                            <?= number_format($stats['movimientos_hoy'] ?? 0) ?>
                        </div>
                        <div class="text-success small">
                            <i class="fas fa-exchange-alt me-1"></i>
                            Entradas/Salidas
                        </div>
                    </div>
                    <div class="text-success">
                        <i class="fas fa-exchange-alt fa-2x opacity-75"></i>
                    </div>
                </div>
            </div>
            <div class="card-footer bg-transparent border-0 pt-0">
                <a href="?page=inventario" class="btn btn-sm btn-outline-success w-100">
                    <i class="fas fa-clipboard-list me-1"></i>Ver Inventario
                </a>
            </div>
        </div>
    </div>

<?php endif; ?>
</div>

<!-- Información Adicional por Rol -->
<div class="row mb-4">
    <div class="col-12">
        <?php if ($userRole === 'administrador'): ?>
            <!-- Panel de Estado del Sistema para Administrador -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-dark text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-server me-2"></i>
                        Estado del Sistema
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-lg-4 col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="fas fa-database fa-2x text-primary me-3"></i>
                                <div>
                                    <div class="fw-bold">Base de Datos</div>
                                    <small class="text-success">
                                        <i class="fas fa-check-circle me-1"></i>Operativa
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="fas fa-clock fa-2x text-info me-3"></i>
                                <div>
                                    <div class="fw-bold">Último Backup</div>
                                    <small class="text-muted">
                                        <?= date('d/m/Y H:i') ?>
                                    </small>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-4 col-md-4">
                            <div class="d-flex align-items-center p-3 bg-light rounded">
                                <i class="fas fa-users fa-2x text-secondary me-3"></i>
                                <div>
                                    <div class="fw-bold">Usuarios Activos</div>
                                    <small class="text-success">
                                        <?= $_SESSION['total_usuarios'] ?? '2' ?> usuarios
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
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

<!-- Panel de información y estadísticas -->
<div class="row mb-4">
    <!-- Estadísticas principales -->
    <div class="col-lg-8 mb-3">
        <div class="card h-100">
            <div class="card-header bg-primary text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-chart-bar me-2"></i>
                    Estadísticas del Sistema
                </h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-boxes fa-2x text-primary mb-2"></i>
                            <h4 class="mb-1"><?= number_format($stats['total_productos'] ?? 0) ?></h4>
                            <small class="text-muted">Total Productos</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-exclamation-triangle fa-2x text-warning mb-2"></i>
                            <h4 class="mb-1"><?= count($lowStockProducts) ?></h4>
                            <small class="text-muted">Stock Bajo</small>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="text-center p-3 bg-light rounded">
                            <i class="fas fa-chart-line fa-2x text-success mb-2"></i>
                            <h4 class="mb-1">S/ <?= number_format($stats['ventas_mes'] ?? 0, 2) ?></h4>
                            <small class="text-muted">Ventas del Mes</small>
                        </div>
                    </div>
                </div>

                <!-- Progreso de stock crítico -->
                <div class="mt-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Stock Crítico</span>
                        <span class="text-muted">
                            <?= count($lowStockProducts) ?> / <?= $stats['total_productos'] ?? 0 ?> productos
                        </span>
                    </div>
                    <?php
                    $totalProductos = $stats['total_productos'] ?? 1;
                    $stockCritico = count($lowStockProducts);
                    $porcentajeCritico = ($stockCritico / $totalProductos) * 100;
                    $colorProgress = $porcentajeCritico > 30 ? 'danger' : ($porcentajeCritico > 15 ? 'warning' : 'success');
                    ?>
                    <div class="progress" style="height: 10px;">
                        <div class="progress-bar bg-<?= $colorProgress ?>"
                            style="width: <?= $porcentajeCritico ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Resumen rápido -->
    <div class="col-lg-4 mb-3">
        <div class="card h-100 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    Estado Actual
                </h5>
            </div>
            <div class="card-body">
                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                    <div class="bg-primary rounded-circle p-2 me-3">
                        <i class="fas fa-calendar text-white"></i>
                    </div>
                    <div>
                        <div class="fw-bold"><?= date('d/m/Y') ?></div>
                        <small class="text-muted">Fecha actual</small>
                    </div>
                </div>

                <div class="d-flex align-items-center mb-3 p-2 bg-light rounded">
                    <div class="bg-success rounded-circle p-2 me-3">
                        <i class="fas fa-user-check text-white"></i>
                    </div>
                    <div>
                        <div class="fw-bold">Sistema Activo</div>
                        <small class="text-muted">Operando normalmente</small>
                    </div>
                </div>

                <div class="d-flex align-items-center p-2 bg-light rounded">
                    <div class="bg-warning rounded-circle p-2 me-3">
                        <i class="fas fa-bell text-white"></i>
                    </div>
                    <div>
                        <div class="fw-bold">
                            <?php if ($stockCritico > 0): ?>
                                <?= $stockCritico ?> Alertas
                            <?php else: ?>
                                Sin Alertas
                            <?php endif; ?>
                        </div>
                        <small class="text-muted">Productos críticos</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Productos con stock bajo y movimientos recientes mejorados -->
<div class="row">
    <!-- Productos con stock bajo -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100 border-0 shadow-sm">
            <div class="card-header bg-warning">
                <h5 class="card-title mb-0 text-dark">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Productos con Stock Bajo
                </h5>
            </div>
            <div class="card-body p-0">
                <?php if (!empty($lowStockProducts)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="bg-light">
                                <tr>
                                    <th class="border-0 fw-bold">Producto</th>
                                    <th class="border-0 text-center fw-bold">Stock</th>
                                    <th class="border-0 text-center fw-bold">Mínimo</th>
                                    <th class="border-0 text-center fw-bold">Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lowStockProducts as $product): ?>
                                    <tr>
                                        <td class="border-0">
                                            <div class="py-1">
                                                <div class="fw-semibold text-dark"><?= htmlspecialchars($product['nombre']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($product['codigo']) ?></small>
                                            </div>
                                        </td>
                                        <td class="border-0 text-center">
                                            <span class="badge bg-primary fs-6 px-2 py-1"><?= number_format($product['stock_actual'] ?? 0) ?></span>
                                        </td>
                                        <td class="border-0 text-center">
                                            <span class="badge bg-secondary fs-6 px-2 py-1"><?= number_format($product['stock_minimo'] ?? 0) ?></span>
                                        </td>
                                        <td class="border-0 text-center">
                                            <?php
                                            $percentage = $product['porcentaje_stock'];
                                            if ($percentage <= 25) {
                                                $badgeClass = 'danger';
                                                $icon = 'exclamation-triangle';
                                                $textColor = 'text-white';
                                            } elseif ($percentage <= 50) {
                                                $badgeClass = 'warning';
                                                $icon = 'exclamation-circle';
                                                $textColor = 'text-dark';
                                            } else {
                                                $badgeClass = 'success';
                                                $icon = 'check-circle';
                                                $textColor = 'text-white';
                                            }
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?> fs-6 px-2 py-1 <?= $textColor ?>">
                                                <i class="fas fa-<?= $icon ?> me-1"></i>
                                                <?= number_format($percentage, 1) ?>%
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-light border-0">
                        <div class="text-center">
                            <a href="?page=productos&filtro=stock_bajo" class="btn btn-warning">
                                <i class="fas fa-list me-1"></i>
                                Ver todos los productos con stock bajo
                            </a>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="text-center text-success py-5">
                        <i class="fas fa-check-circle fa-3x mb-3 text-success"></i>
                        <h6 class="text-success">¡Excelente!</h6>
                        <p class="mb-0 text-muted">No hay productos con stock bajo.</p>
                        <small class="text-muted">Todos los productos tienen stock suficiente</small>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Movimientos recientes -->
    <div class="col-lg-6 mb-3">
        <div class="card h-100 border-info">
            <div class="card-header bg-info text-white">
                <h5 class="card-title mb-0">
                    <i class="fas fa-history me-2"></i>
                    Movimientos Recientes
                </h5>
            </div>
            <div class="card-body">
                <?php if (!empty($recentMovements)): ?>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Producto</th>
                                    <th class="text-center">Tipo</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-center">Fecha</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentMovements as $movement): ?>
                                    <tr>
                                        <td>
                                            <div>
                                                <strong class="text-dark"><?= htmlspecialchars($movement['producto_nombre']) ?></strong><br>
                                                <small class="text-muted"><?= htmlspecialchars($movement['producto_codigo']) ?></small>
                                            </div>
                                        </td>
                                        <td class="text-center">
                                            <?php
                                            $badgeClass = $movement['tipo_movimiento'] === 'entrada' ? 'success' : 'danger';
                                            $icon = $movement['tipo_movimiento'] === 'entrada' ? 'arrow-up' : 'arrow-down';
                                            ?>
                                            <span class="badge bg-<?= $badgeClass ?>">
                                                <i class="fas fa-<?= $icon ?> me-1"></i>
                                                <?= ucfirst($movement['tipo_movimiento']) ?>
                                            </span>
                                        </td>
                                        <td class="text-center">
                                            <span class="badge bg-dark"><?= number_format($movement['cantidad'] ?? 0) ?></span>
                                        </td>
                                        <td class="text-center">
                                            <small class="text-muted">
                                                <?= date('d/m H:i', strtotime($movement['fecha_movimiento'])) ?>
                                            </small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="text-center mt-3">
                        <a href="?page=inventario" class="btn btn-info">
                            <i class="fas fa-list me-1"></i>
                            Ver todos los movimientos
                        </a>
                    </div>
                <?php else: ?>
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-inbox fa-3x mb-3 opacity-50"></i>
                        <h6 class="text-muted">Sin movimientos recientes</h6>
                        <p class="mb-0">No se han registrado movimientos recientemente.</p>
                        <small>Los movimientos aparecerán cuando haya entradas o salidas</small>
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