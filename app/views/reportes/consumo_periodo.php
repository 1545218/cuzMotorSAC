<?php
// app/views/reportes/consumo_periodo.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-chart-line"></i>
        Reporte de Consumo por Período
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=reportes">Reportes</a></li>
        <li class="breadcrumb-item active">Consumo por Período</li>
    </ol>

    <!-- Configuración de Período -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-calendar-alt me-1"></i>
            Configuración del Período de Análisis
        </div>
        <div class="card-body">
            <form id="periodoForm" class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                        value="<?php echo date('Y-m-d', strtotime('-90 days')); ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                        value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="col-md-2">
                    <label for="agrupacion" class="form-label">Agrupar por</label>
                    <select class="form-select" id="agrupacion" name="agrupacion">
                        <option value="dia">Día</option>
                        <option value="semana" selected>Semana</option>
                        <option value="mes">Mes</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria_id">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Analizar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarReporte()">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen Ejecutivo -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="totalConsumo">0</h4>
                            <p class="mb-0">Total Consumido</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-bar fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="promedioConsumo">0</h4>
                            <p class="mb-0">Promedio Diario</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="picoConsumo">0</h4>
                            <p class="mb-0">Pico Máximo</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-arrow-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="productosActivos">0</h4>
                            <p class="mb-0">Productos Activos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-cubes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos de Análisis -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Tendencia de Consumo en el Período
                </div>
                <div class="card-body">
                    <canvas id="tendenciaChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Distribución por Categoría
                </div>
                <div class="card-body">
                    <canvas id="categoriaChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Productos Más Consumidos -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-trophy me-1"></i>
                    Top 10 Productos Más Consumidos
                </div>
                <div class="card-body">
                    <canvas id="topProductosChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock me-1"></i>
                    Patrón de Consumo por Día de la Semana
                </div>
                <div class="card-body">
                    <canvas id="patronChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis Predictivo -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-crystal-ball me-1"></i>
            Análisis Predictivo y Recomendaciones
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <h5>Predicción Próximos 30 días</h5>
                    <div id="prediccionContainer">
                        <!-- Predicciones cargadas dinámicamente -->
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Productos en Riesgo</h5>
                    <div id="riesgoContainer">
                        <!-- Productos en riesgo cargados dinámicamente -->
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Recomendaciones</h5>
                    <div id="recomendacionesContainer">
                        <!-- Recomendaciones cargadas dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Detalle de Consumo por Producto
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="consumoTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Total Consumido</th>
                            <th>Promedio/Día</th>
                            <th>Máximo/Día</th>
                            <th>Mínimo/Día</th>
                            <th>Stock Actual</th>
                            <th>Días Restantes</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="consumoBody">
                        <!-- Datos cargados dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let charts = {};

    document.addEventListener('DOMContentLoaded', function() {
        cargarCategorias();
        cargarAnalisis();

        document.getElementById('periodoForm').addEventListener('submit', function(e) {
            e.preventDefault();
            cargarAnalisis();
        });
    });

    function cargarCategorias() {
        fetch('?page=categorias&action=api')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('categoria');
                data.categorias.forEach(categoria => {
                    const option = document.createElement('option');
                    option.value = categoria.id;
                    option.textContent = categoria.nombre;
                    select.appendChild(option);
                });
            });
    }

    function cargarAnalisis() {
        const formData = new FormData(document.getElementById('periodoForm'));
        const filtros = Object.fromEntries(formData);

        fetch('?page=reportes&action=consumo-periodo', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'analisis',
                    filtros: filtros
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarResumen(data.resumen);
                    crearGraficos(data.graficos);
                    actualizarPredicciones(data.predicciones);
                    actualizarTabla(data.detalle);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarResumen(resumen) {
        document.getElementById('totalConsumo').textContent = resumen.total_consumo.toLocaleString();
        document.getElementById('promedioConsumo').textContent = resumen.promedio_diario.toFixed(1);
        document.getElementById('picoConsumo').textContent = resumen.pico_maximo;
        document.getElementById('productosActivos').textContent = resumen.productos_activos;
    }

    function crearGraficos(graficos) {
        // Destruir gráficos existentes
        Object.values(charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Gráfico de tendencia
        charts.tendencia = new Chart(document.getElementById('tendenciaChart'), {
            type: 'line',
            data: {
                labels: graficos.tendencia.labels,
                datasets: [{
                    label: 'Consumo',
                    data: graficos.tendencia.data,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.1)',
                    tension: 0.1,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de categorías
        charts.categoria = new Chart(document.getElementById('categoriaChart'), {
            type: 'doughnut',
            data: {
                labels: graficos.categoria.labels,
                datasets: [{
                    data: graficos.categoria.data,
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40', '#FF6384', '#C9CBCF'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });

        // Gráfico de top productos
        charts.topProductos = new Chart(document.getElementById('topProductosChart'), {
            type: 'bar',
            data: {
                labels: graficos.top_productos.labels,
                datasets: [{
                    label: 'Cantidad Consumida',
                    data: graficos.top_productos.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                scales: {
                    x: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Gráfico de patrón semanal
        charts.patron = new Chart(document.getElementById('patronChart'), {
            type: 'radar',
            data: {
                labels: ['Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado', 'Domingo'],
                datasets: [{
                    label: 'Consumo Promedio',
                    data: graficos.patron.data,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    r: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function actualizarPredicciones(predicciones) {
        // Predicciones
        let prediccionHtml = '';
        predicciones.proximos_30_dias.forEach(pred => {
            prediccionHtml += `
            <div class="alert alert-info mb-2">
                <strong>${pred.producto}</strong><br>
                Consumo estimado: ${pred.estimado} unidades
            </div>
        `;
        });
        document.getElementById('prediccionContainer').innerHTML = prediccionHtml;

        // Productos en riesgo
        let riesgoHtml = '';
        predicciones.productos_riesgo.forEach(prod => {
            riesgoHtml += `
            <div class="alert alert-warning mb-2">
                <strong>${prod.producto}</strong><br>
                Stock para ${prod.dias_restantes} días
            </div>
        `;
        });
        document.getElementById('riesgoContainer').innerHTML = riesgoHtml;

        // Recomendaciones
        let recomendacionesHtml = '';
        predicciones.recomendaciones.forEach(rec => {
            const alertClass = rec.prioridad === 'alta' ? 'alert-danger' :
                rec.prioridad === 'media' ? 'alert-warning' : 'alert-info';
            recomendacionesHtml += `
            <div class="alert ${alertClass} mb-2">
                <i class="fas fa-lightbulb"></i> ${rec.mensaje}
            </div>
        `;
        });
        document.getElementById('recomendacionesContainer').innerHTML = recomendacionesHtml;
    }

    function actualizarTabla(detalle) {
        const tbody = document.getElementById('consumoBody');
        tbody.innerHTML = '';

        detalle.forEach(item => {
            const tr = document.createElement('tr');
            const estadoClass = item.dias_restantes <= 7 ? 'bg-danger' :
                item.dias_restantes <= 15 ? 'bg-warning' : 'bg-success';

            tr.innerHTML = `
            <td>${item.producto_codigo} - ${item.producto_nombre}</td>
            <td>${item.categoria}</td>
            <td class="text-end">${item.total_consumido}</td>
            <td class="text-end">${item.promedio_diario.toFixed(2)}</td>
            <td class="text-end">${item.maximo_diario}</td>
            <td class="text-end">${item.minimo_diario}</td>
            <td class="text-end">${item.stock_actual}</td>
            <td class="text-end">${item.dias_restantes}</td>
            <td>
                <span class="badge ${estadoClass}">
                    ${item.dias_restantes <= 7 ? 'Crítico' : 
                      item.dias_restantes <= 15 ? 'Bajo' : 'Normal'}
                </span>
            </td>
        `;
            tbody.appendChild(tr);
        });

        // Reinicializar DataTable
        if ($.fn.DataTable.isDataTable('#consumoTable')) {
            $('#consumoTable').DataTable().destroy();
        }

        $('#consumoTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [
                [2, 'desc']
            ], // Ordenar por total consumido
            pageLength: 25,
            responsive: true
        });
    }

    function exportarReporte() {
        const formData = new FormData(document.getElementById('periodoForm'));
        const params = new URLSearchParams(formData);
        params.append('exportar', 'excel');

        window.open('?page=reportes&action=consumo-periodo&' + params.toString(), '_blank');
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>