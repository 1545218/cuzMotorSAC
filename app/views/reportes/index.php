<?php
$title = 'Reportes del Sistema';
$breadcrumb = [
    ['title' => 'Reportes']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-chart-bar me-2"></i>Centro de Reportes y Análisis
                </h3>
            </div>
            <div class="card-body">
                <!-- Reportes Disponibles -->
                <div class="row">
                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-primary">
                            <div class="card-body text-center">
                                <i class="fas fa-cash-register fa-3x text-primary mb-3"></i>
                                <h5 class="card-title">Reporte de Ventas</h5>
                                <p class="card-text">Consulta las ventas realizadas por período</p>
                                <button type="button" class="btn btn-primary" onclick="generarReporteVentas()">
                                    <i class="fas fa-file-alt me-1"></i>Generar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-success">
                            <div class="card-body text-center">
                                <i class="fas fa-boxes fa-3x text-success mb-3"></i>
                                <h5 class="card-title">Reporte de Inventario</h5>
                                <p class="card-text">Estado actual del inventario y stock</p>
                                <button type="button" class="btn btn-success" onclick="generarReporteInventario()">
                                    <i class="fas fa-file-alt me-1"></i>Generar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-info">
                            <div class="card-body text-center">
                                <i class="fas fa-users fa-3x text-info mb-3"></i>
                                <h5 class="card-title">Reporte de Clientes</h5>
                                <p class="card-text">Listado y estadísticas de clientes</p>
                                <button type="button" class="btn btn-info" onclick="generarReporteClientes()">
                                    <i class="fas fa-file-alt me-1"></i>Generar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-warning">
                            <div class="card-body text-center">
                                <i class="fas fa-file-invoice-dollar fa-3x text-warning mb-3"></i>
                                <h5 class="card-title">Reporte de Cotizaciones</h5>
                                <p class="card-text">Estado de cotizaciones por período</p>
                                <button type="button" class="btn btn-warning" onclick="generarReporteCotizaciones()">
                                    <i class="fas fa-file-alt me-1"></i>Generar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-danger">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-3x text-danger mb-3"></i>
                                <h5 class="card-title">Productos Bajo Stock</h5>
                                <p class="card-text">Productos que requieren reposición</p>
                                <button type="button" class="btn btn-danger" onclick="generarReporteBajoStock()">
                                    <i class="fas fa-file-alt me-1"></i>Generar
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 col-lg-4 mb-4">
                        <div class="card h-100 border-secondary">
                            <div class="card-body text-center">
                                <i class="fas fa-chart-line fa-3x text-secondary mb-3"></i>
                                <h5 class="card-title">Resumen General</h5>
                                <p class="card-text">Dashboard con métricas principales</p>
                                <button type="button" class="btn btn-secondary" onclick="window.location.href='<?= BASE_PATH ?>/public/?page=dashboard'">
                                    <i class="fas fa-tachometer-alt me-1"></i>Ver Dashboard
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filtros de Fecha (para los reportes) -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-filter me-2"></i>Filtros para Reportes
                                </h5>
                            </div>
                            <div class="card-body">
                                <form id="filtrosReporte">
                                    <div class="row">
                                        <div class="col-md-4">
                                            <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                            <input type="date" class="form-control" id="fecha_inicio" 
                                                   value="<?= date('Y-m-01') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                            <input type="date" class="form-control" id="fecha_fin" 
                                                   value="<?= date('Y-m-d') ?>">
                                        </div>
                                        <div class="col-md-4">
                                            <label for="tipo_reporte" class="form-label">Formato</label>
                                            <select class="form-control" id="tipo_reporte">
                                                <option value="pdf">PDF</option>
                                                <option value="excel">Excel</option>
                                                <option value="html">Ver en pantalla</option>
                                            </select>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function generarReporteVentas() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const formato = document.getElementById('tipo_reporte').value;
        
        if (!fechaInicio || !fechaFin) {
            alert('Por favor selecciona las fechas para el reporte');
            return;
        }
        
        const url = `<?= BASE_PATH ?>/public/?page=reportes&action=ventas&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&formato=${formato}`;
        
        if (formato === 'html') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }

    function generarReporteInventario() {
        const formato = document.getElementById('tipo_reporte').value;
        const url = `<?= BASE_PATH ?>/public/?page=reportes&action=inventario&formato=${formato}`;
        
        if (formato === 'html') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }

    function generarReporteClientes() {
        const formato = document.getElementById('tipo_reporte').value;
        const url = `<?= BASE_PATH ?>/public/?page=reportes&action=clientes&formato=${formato}`;
        
        if (formato === 'html') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }

    function generarReporteCotizaciones() {
        const fechaInicio = document.getElementById('fecha_inicio').value;
        const fechaFin = document.getElementById('fecha_fin').value;
        const formato = document.getElementById('tipo_reporte').value;
        
        if (!fechaInicio || !fechaFin) {
            alert('Por favor selecciona las fechas para el reporte');
            return;
        }
        
        const url = `<?= BASE_PATH ?>/public/?page=reportes&action=cotizaciones&fecha_inicio=${fechaInicio}&fecha_fin=${fechaFin}&formato=${formato}`;
        
        if (formato === 'html') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }

    function generarReporteBajoStock() {
        const formato = document.getElementById('tipo_reporte').value;
        const url = `<?= BASE_PATH ?>/public/?page=reportes&action=bajo_stock&formato=${formato}`;
        
        if (formato === 'html') {
            window.location.href = url;
        } else {
            window.open(url, '_blank');
        }
    }
</script>