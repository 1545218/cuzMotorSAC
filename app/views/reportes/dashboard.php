<?php
// app/views/reportes/dashboard.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-chart-line"></i>
        Dashboard de Reportes Avanzados
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=reportes">Reportes</a></li>
        <li class="breadcrumb-item active">Dashboard Avanzado</li>
    </ol>

    <!-- Métricas Principales -->
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-primary text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="totalProductos">0</h4>
                            <p class="mb-0">Total Productos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-cubes fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="stockBajo">0</h4>
                            <p class="mb-0">Stock Bajo</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="ventasHoy">S/. 0.00</h4>
                            <p class="mb-0">Ventas Hoy</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-chart-line fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="movimientosHoy">0</h4>
                            <p class="mb-0">Movimientos Hoy</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exchange-alt fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos -->
    <div class="row">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Ventas de los Últimos 30 Días
                </div>
                <div class="card-body">
                    <canvas id="ventasChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
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

    <!-- Gráfico de Movimientos -->
    <div class="row">
        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-exchange-alt me-1"></i>
                    Movimientos de Inventario (7 días)
                </div>
                <div class="card-body">
                    <canvas id="movimientosChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Stock por Ubicación
                </div>
                <div class="card-body">
                    <canvas id="stockChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos Críticos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-exclamation-triangle me-1"></i>
            Productos con Stock Crítico
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="productosTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="productosBody">
                        <!-- Datos cargados dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Accesos Rápidos -->
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt me-1"></i>
                    Accesos Rápidos a Reportes
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3">
                            <a href="?page=reportes&action=movimientos-inventario" class="btn btn-outline-primary btn-lg w-100 mb-3">
                                <i class="fas fa-exchange-alt"></i><br>
                                Movimientos de Inventario
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=reportes&action=consumo-periodo" class="btn btn-outline-success btn-lg w-100 mb-3">
                                <i class="fas fa-chart-line"></i><br>
                                Consumo por Período
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=reportes&action=estado-stock" class="btn btn-outline-warning btn-lg w-100 mb-3">
                                <i class="fas fa-warehouse"></i><br>
                                Estado de Stock
                            </a>
                        </div>
                        <div class="col-md-3">
                            <a href="?page=reportes&action=programar-reportes" class="btn btn-outline-info btn-lg w-100 mb-3">
                                <i class="fas fa-clock"></i><br>
                                Programar Reportes
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Cargar métricas principales
        cargarMetricas();

        // Cargar gráficos
        cargarGraficos();

        // Cargar productos críticos
        cargarProductosCriticos();

        // Actualizar cada 30 segundos
        setInterval(function() {
            cargarMetricas();
            cargarProductosCriticos();
        }, 30000);
    });

    function cargarMetricas() {
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'metricas'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalProductos').textContent = data.metrics.total_productos;
                    document.getElementById('stockBajo').textContent = data.metrics.productos_stock_bajo;
                    document.getElementById('ventasHoy').textContent = 'S/. ' + parseFloat(data.metrics.ventas_hoy).toFixed(2);
                    document.getElementById('movimientosHoy').textContent = data.metrics.movimientos_hoy;
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function cargarGraficos() {
        // Gráfico de ventas
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'ventas_chart'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ctx = document.getElementById('ventasChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: data.chart_data.labels,
                            datasets: [{
                                label: 'Ventas',
                                data: data.chart_data.data,
                                borderColor: 'rgb(75, 192, 192)',
                                tension: 0.1
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
                }
            });

        // Gráfico de categorías
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'categoria_chart'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ctx = document.getElementById('categoriaChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'pie',
                        data: {
                            labels: data.chart_data.labels,
                            datasets: [{
                                data: data.chart_data.data,
                                backgroundColor: [
                                    '#FF6384',
                                    '#36A2EB',
                                    '#FFCE56',
                                    '#4BC0C0',
                                    '#9966FF',
                                    '#FF9F40'
                                ]
                            }]
                        },
                        options: {
                            responsive: true
                        }
                    });
                }
            });

        // Gráfico de movimientos
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'movimientos_chart'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ctx = document.getElementById('movimientosChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.chart_data.labels,
                            datasets: [{
                                label: 'Entradas',
                                data: data.chart_data.entradas,
                                backgroundColor: 'rgba(75, 192, 192, 0.8)'
                            }, {
                                label: 'Salidas',
                                data: data.chart_data.salidas,
                                backgroundColor: 'rgba(255, 99, 132, 0.8)'
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
                }
            });

        // Gráfico de stock por ubicación
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'stock_chart'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const ctx = document.getElementById('stockChart').getContext('2d');
                    new Chart(ctx, {
                        type: 'bar',
                        data: {
                            labels: data.chart_data.labels,
                            datasets: [{
                                label: 'Stock',
                                data: data.chart_data.data,
                                backgroundColor: 'rgba(54, 162, 235, 0.8)'
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
                }
            });
    }

    function cargarProductosCriticos() {
        fetch('?page=reportes&action=dashboard', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'productos_criticos'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('productosBody');
                    tbody.innerHTML = '';

                    data.productos.forEach(producto => {
                        const tr = document.createElement('tr');
                        const estado = producto.stock_actual <= producto.stock_minimo ?
                            '<span class="badge bg-danger">Crítico</span>' :
                            '<span class="badge bg-warning">Bajo</span>';

                        tr.innerHTML = `
                    <td>${producto.codigo}</td>
                    <td>${producto.nombre}</td>
                    <td>${producto.categoria}</td>
                    <td>${producto.stock_actual}</td>
                    <td>${producto.stock_minimo}</td>
                    <td>${estado}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="ajustarStock(${producto.id})">
                            <i class="fas fa-edit"></i> Ajustar
                        </button>
                    </td>
                `;
                        tbody.appendChild(tr);
                    });
                }
            });
    }

    function ajustarStock(productoId) {
        window.location.href = `?page=ajustes&action=create&producto_id=${productoId}`;
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>