<?php

/**
 * Vista de Reporte de Consumo de Productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Reporte de Consumo') ?> - Cruz Motor S.A.C.</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <style>
        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .top-product {
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            color: #333;
        }

        .chart-container {
            position: relative;
            height: 400px;
            margin-bottom: 2rem;
        }

        .ranking-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 50%;
            color: white;
            font-weight: bold;
            font-size: 0.8rem;
        }

        .ranking-1 {
            background: #FFD700;
            color: #333;
        }

        .ranking-2 {
            background: #C0C0C0;
            color: #333;
        }

        .ranking-3 {
            background: #CD7F32;
        }

        .ranking-other {
            background: #6c757d;
        }
    </style>
</head>

<body class="bg-light">

    <div class="container-fluid mt-4">
        <!-- Navegación superior -->
        <nav class="navbar navbar-expand-lg navbar-dark bg-primary mb-4">
            <div class="container-fluid">
                <a class="navbar-brand" href="?page=dashboard">
                    <i class="fas fa-wrench me-2"></i>Cruz Motor S.A.C.
                </a>
                <div class="navbar-nav ms-auto">
                    <a href="?page=reportes" class="nav-link">
                        <i class="fas fa-arrow-left me-1"></i>Volver a Reportes
                    </a>
                    <a href="?page=dashboard" class="nav-link">
                        <i class="fas fa-home me-1"></i>Dashboard
                    </a>
                </div>
            </div>
        </nav>

        <div class="row">
            <!-- Contenido Principal -->
            <main class="col-12">
                <!-- Page Header -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <i class="fas fa-chart-line me-2"></i>
                        <?= htmlspecialchars($title ?? 'Reporte de Consumo de Productos') ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="?<?= http_build_query(array_merge($_GET, ['formato' => 'pdf'])) ?>"
                                class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-filter me-2"></i>Filtros de Reporte</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="/reportes/consumo" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                                    value="<?= htmlspecialchars($fechaInicio ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="fecha_fin" class="form-label">Fecha Fin</label>
                                <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                                    value="<?= htmlspecialchars($fechaFin ?? '') ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="limite" class="form-label">Productos a Mostrar</label>
                                <select class="form-select" id="limite" name="limite">
                                    <option value="10" <?= ($limite == 10) ? 'selected' : '' ?>>Top 10</option>
                                    <option value="20" <?= ($limite == 20) ? 'selected' : '' ?>>Top 20</option>
                                    <option value="50" <?= ($limite == 50) ? 'selected' : '' ?>>Top 50</option>
                                    <option value="100" <?= ($limite == 100) ? 'selected' : '' ?>>Top 100</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary me-2">
                                    <i class="fas fa-search"></i> Filtrar
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="limpiarFiltros()">
                                    <i class="fas fa-undo"></i> Limpiar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Estadísticas -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-boxes fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['total_productos_consumidos'] ?? 0) ?></h4>
                                <p class="mb-0">Productos Consumidos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-minus-circle fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['cantidad_total_salidas'] ?? 0) ?></h4>
                                <p class="mb-0">Total Unidades</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                <h4>S/. <?= number_format($estadisticas['valor_total_consumido'] ?? 0, 2) ?></h4>
                                <p class="mb-0">Valor Consumido</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar fa-2x mb-2"></i>
                                <h4>Top <?= $estadisticas['limite'] ?? 20 ?></h4>
                                <p class="mb-0">Productos Mostrados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Período -->
                <div class="alert alert-info">
                    <i class="fas fa-calendar me-2"></i>
                    <strong>Período de Análisis:</strong> <?= htmlspecialchars($estadisticas['periodo'] ?? '') ?>
                </div>

                <?php if (!empty($productos)): ?>
                    <!-- Gráfico de Consumo -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Top Productos por Consumo</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="consumoChart"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Producto Más Consumido -->
                    <?php if (!empty($productos[0])): ?>
                        <div class="card top-product mb-4">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <h3><i class="fas fa-trophy me-2"></i>Producto Más Consumido</h3>
                                        <h4><?= htmlspecialchars($productos[0]['nombre'] ?? '') ?></h4>
                                        <p class="mb-2">
                                            <strong>Código:</strong> <?= htmlspecialchars($productos[0]['codigo'] ?? '') ?>
                                        </p>
                                        <p class="mb-0">
                                            <strong>Total Consumido:</strong> <?= number_format($productos[0]['cantidad_total'] ?? 0) ?> unidades
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-center">
                                        <div style="font-size: 3rem; color: #FFD700;">
                                            <i class="fas fa-medal"></i>
                                        </div>
                                        <h5><?= number_format($productos[0]['veces_consumido'] ?? 0) ?> veces</h5>
                                        <p class="mb-0">usado en el período</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>

                <!-- Tabla de Productos Más Consumidos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Ranking de Productos por Consumo
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($productos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay datos de consumo en el período seleccionado.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="tablaConsumo">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Ranking</th>
                                            <th>Producto</th>
                                            <th>Código</th>
                                            <th>Cantidad Total</th>
                                            <th>Veces Usado</th>
                                            <th>Promedio por Uso</th>
                                            <th>Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $index => $producto): ?>
                                            <?php
                                            $ranking = $index + 1;
                                            $cantidadTotal = $producto['cantidad_total'] ?? 0;
                                            $vecesUsado = $producto['veces_consumido'] ?? 1;
                                            $promedioPorUso = $vecesUsado > 0 ? $cantidadTotal / $vecesUsado : 0;
                                            $valorTotal = $producto['valor_total'] ?? 0;

                                            // Clase para el badge del ranking
                                            $rankingClass = '';
                                            if ($ranking == 1) $rankingClass = 'ranking-1';
                                            elseif ($ranking == 2) $rankingClass = 'ranking-2';
                                            elseif ($ranking == 3) $rankingClass = 'ranking-3';
                                            else $rankingClass = 'ranking-other';
                                            ?>
                                            <tr>
                                                <td class="text-center">
                                                    <span class="ranking-badge <?= $rankingClass ?>">
                                                        <?= $ranking ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($producto['nombre'] ?? '') ?></strong>
                                                </td>
                                                <td>
                                                    <code><?= htmlspecialchars($producto['codigo'] ?? '') ?></code>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-danger fs-6">
                                                        <?= number_format($cantidadTotal) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-info fs-6">
                                                        <?= number_format($vecesUsado) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($promedioPorUso, 1) ?>
                                                </td>
                                                <td class="text-end">
                                                    <strong>S/. <?= number_format($valorTotal, 2) ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            // Inicializar DataTable
            $('#tablaConsumo').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [3, 'desc']
                ], // Ordenar por cantidad total descendente
                columnDefs: [{
                        targets: [0, 3, 4, 5],
                        className: 'text-center'
                    },
                    {
                        targets: [6],
                        className: 'text-end'
                    }
                ]
            });

            // Generar gráfico de consumo
            <?php if (!empty($productos)): ?>
                const productos = <?= json_encode(array_slice($productos, 0, 10)) ?>;
                const labels = productos.map(p => p.nombre.length > 15 ? p.nombre.substring(0, 15) + '...' : p.nombre);
                const data = productos.map(p => p.cantidad_total);

                const ctx = document.getElementById('consumoChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [{
                            label: 'Cantidad Consumida',
                            data: data,
                            backgroundColor: [
                                '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF',
                                '#FF9F40', '#FF6384', '#C9CBCF', '#4BC0C0', '#FF6384'
                            ],
                            borderWidth: 2,
                            borderRadius: 5
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: false
                            },
                            title: {
                                display: true,
                                text: 'Top 10 Productos Más Consumidos'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            },
                            x: {
                                ticks: {
                                    maxRotation: 45,
                                    minRotation: 45
                                }
                            }
                        }
                    }
                });
            <?php endif; ?>
        });

        function limpiarFiltros() {
            window.location.href = '/reportes/consumo';
        }
    </script>
</body>

</html>