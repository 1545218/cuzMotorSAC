<?php

/**
 * Vista de Reporte de Stock de Inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 */
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Reporte de Stock') ?> - Cruz Motor S.A.C.</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .stock-bajo {
            color: #dc3545;
            font-weight: bold;
        }

        .stock-normal {
            color: #198754;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        .btn-export {
            margin-left: 0.5rem;
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
                        <i class="fas fa-boxes me-2"></i>
                        <?= htmlspecialchars($title ?? 'Reporte de Stock de Inventario') ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-primary" onclick="window.print()">
                                <i class="fas fa-print"></i> Imprimir
                            </button>
                            <a href="?<?= http_build_query(array_merge($_GET, ['formato' => 'pdf'])) ?>"
                                class="btn btn-sm btn-outline-danger btn-export">
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
                        <form method="GET" action="/reportes/stock" class="row g-3">
                            <div class="col-md-3">
                                <label for="fecha" class="form-label">Fecha del Reporte</label>
                                <input type="date" class="form-control" id="fecha" name="fecha"
                                    value="<?= htmlspecialchars($_GET['fecha'] ?? date('Y-m-d')) ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Tipo de Reporte</label>
                                <div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="solo_stock_bajo"
                                            value="false" id="todos" <?= (!$soloStockBajo) ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="todos">
                                            Todos los productos
                                        </label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="solo_stock_bajo"
                                            value="true" id="stock_bajo" <?= $soloStockBajo ? 'checked' : '' ?>>
                                        <label class="form-check-label" for="stock_bajo">
                                            Solo productos con stock bajo
                                        </label>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Filtrar
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
                                <h4><?= number_format($estadisticas['total_productos'] ?? 0) ?></h4>
                                <p class="mb-0">Total Productos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['productos_stock_bajo'] ?? 0) ?></h4>
                                <p class="mb-0">Stock Bajo</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                                <h4>S/. <?= number_format($estadisticas['valor_inventario'] ?? 0, 2) ?></h4>
                                <p class="mb-0">Valor Inventario</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-calendar fa-2x mb-2"></i>
                                <h4><?= date('d/m/Y', strtotime($estadisticas['fecha_reporte'] ?? date('Y-m-d'))) ?></h4>
                                <p class="mb-0">Fecha Reporte</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tabla de Productos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            <?= $soloStockBajo ? 'Productos con Stock Bajo' : 'Inventario Completo' ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($productos)): ?>
                            <div class="alert alert-info text-center">
                                <i class="fas fa-info-circle me-2"></i>
                                No hay productos que mostrar con los filtros aplicados.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="tablaStock">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Categoría</th>
                                            <th>Marca</th>
                                            <th>Stock Actual</th>
                                            <th>Stock Mínimo</th>
                                            <th>Estado</th>
                                            <th>Precio Unit.</th>
                                            <th>Valor Total</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($productos as $producto): ?>
                                            <?php
                                            $stockActual = $producto['stock_actual'] ?? 0;
                                            $stockMinimo = $producto['stock_minimo'] ?? 0;
                                            $precioUnit = $producto['precio_unitario'] ?? 0;
                                            $valorTotal = $stockActual * $precioUnit;
                                            $esStockBajo = $stockActual <= $stockMinimo;
                                            ?>
                                            <tr>
                                                <td>
                                                    <code><?= htmlspecialchars($producto['codigo_barras'] ?? '') ?></code>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($producto['nombre'] ?? '') ?></strong>
                                                </td>
                                                <td><?= htmlspecialchars($producto['categoria'] ?? '') ?></td>
                                                <td><?= htmlspecialchars($producto['marca'] ?? '') ?></td>
                                                <td class="text-center">
                                                    <span class="<?= $esStockBajo ? 'stock-bajo' : 'stock-normal' ?>">
                                                        <?= number_format($stockActual) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center"><?= number_format($stockMinimo) ?></td>
                                                <td class="text-center">
                                                    <?php if ($esStockBajo): ?>
                                                        <span class="badge bg-danger">
                                                            <i class="fas fa-exclamation-triangle"></i> Stock Bajo
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-check"></i> Normal
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-end">S/. <?= number_format($precioUnit, 2) ?></td>
                                                <td class="text-end">S/. <?= number_format($valorTotal, 2) ?></td>
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
            $('#tablaStock').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [4, 'asc']
                ], // Ordenar por stock actual ascendente
                columnDefs: [{
                    targets: [4, 5, 7, 8],
                    className: 'text-center'
                }]
            });
        });
    </script>
</body>

</html>