<?php

/**
 * Vista de Reporte de Movimientos de Inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 */
?>

<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title ?? 'Reporte de Movimientos') ?> - Cruz Motor S.A.C.</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css" rel="stylesheet">

    <style>
        .entrada {
            color: #28a745;
            font-weight: bold;
        }

        .salida {
            color: #dc3545;
            font-weight: bold;
        }

        .ajuste {
            color: #ffc107;
            font-weight: bold;
        }

        .stat-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .stat-card .card-body {
            padding: 1.5rem;
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
                        <i class="fas fa-exchange-alt me-2"></i>
                        <?= htmlspecialchars($title ?? 'Reporte de Movimientos de Inventario') ?>
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
                        <form method="GET" action="/reportes/movimientos" class="row g-3">
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
                                <label for="tipo" class="form-label">Tipo de Movimiento</label>
                                <select class="form-select" id="tipo" name="tipo">
                                    <option value="todos" <?= ($tipoMovimiento === 'todos') ? 'selected' : '' ?>>Todos</option>
                                    <option value="entrada" <?= ($tipoMovimiento === 'entrada') ? 'selected' : '' ?>>Entradas</option>
                                    <option value="salida" <?= ($tipoMovimiento === 'salida') ? 'selected' : '' ?>>Salidas</option>
                                    <option value="ajuste" <?= ($tipoMovimiento === 'ajuste') ? 'selected' : '' ?>>Ajustes</option>
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
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-list fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['total_movimientos'] ?? 0) ?></h4>
                                <p class="mb-0">Total Movimientos</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-up fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['total_entradas'] ?? 0) ?></h4>
                                <p class="mb-0">Entradas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-arrow-down fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['total_salidas'] ?? 0) ?></h4>
                                <p class="mb-0">Salidas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-edit fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['total_ajustes'] ?? 0) ?></h4>
                                <p class="mb-0">Ajustes</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-plus fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['cantidad_entradas'] ?? 0) ?></h4>
                                <p class="mb-0">Cant. Entradas</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="card stat-card">
                            <div class="card-body text-center">
                                <i class="fas fa-minus fa-2x mb-2"></i>
                                <h4><?= number_format($estadisticas['cantidad_salidas'] ?? 0) ?></h4>
                                <p class="mb-0">Cant. Salidas</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del Período -->
                <div class="alert alert-info">
                    <i class="fas fa-calendar me-2"></i>
                    <strong>Período del Reporte:</strong> <?= htmlspecialchars($estadisticas['periodo'] ?? '') ?>
                </div>

                <!-- Tabla de Movimientos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Detalle de Movimientos
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($movimientos)): ?>
                            <div class="alert alert-warning text-center">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No hay movimientos registrados en el período seleccionado.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover" id="tablaMovimientos">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Fecha</th>
                                            <th>Producto</th>
                                            <th>Tipo</th>
                                            <th>Cantidad</th>
                                            <th>Stock Anterior</th>
                                            <th>Stock Nuevo</th>
                                            <th>Motivo</th>
                                            <th>Usuario</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($movimientos as $movimiento): ?>
                                            <?php
                                            $tipo = $movimiento['tipo'] ?? '';
                                            $cantidad = $movimiento['cantidad'] ?? 0;
                                            $tipoClass = '';
                                            $tipoIcon = '';

                                            switch ($tipo) {
                                                case 'entrada':
                                                    $tipoClass = 'entrada';
                                                    $tipoIcon = 'fas fa-arrow-up';
                                                    break;
                                                case 'salida':
                                                    $tipoClass = 'salida';
                                                    $tipoIcon = 'fas fa-arrow-down';
                                                    break;
                                                case 'ajuste':
                                                    $tipoClass = 'ajuste';
                                                    $tipoIcon = 'fas fa-edit';
                                                    break;
                                                default:
                                                    $tipoClass = '';
                                                    $tipoIcon = 'fas fa-question';
                                            }
                                            ?>
                                            <tr>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'] ?? '')) ?>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($movimiento['producto_nombre'] ?? '') ?></strong>
                                                    <br>
                                                    <small class="text-muted"><?= htmlspecialchars($movimiento['codigo_barras'] ?? '') ?></small>
                                                </td>
                                                <td class="text-center">
                                                    <span class="<?= $tipoClass ?>">
                                                        <i class="<?= $tipoIcon ?>"></i>
                                                        <?= ucfirst($tipo) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="<?= $tipoClass ?>">
                                                        <?= $tipo === 'entrada' ? '+' : ($tipo === 'salida' ? '-' : '±') ?>
                                                        <?= number_format(abs($cantidad)) ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($movimiento['stock_anterior'] ?? 0) ?>
                                                </td>
                                                <td class="text-center">
                                                    <?= number_format($movimiento['stock_nuevo'] ?? 0) ?>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($movimiento['motivo'] ?? '') ?></small>
                                                </td>
                                                <td>
                                                    <small><?= htmlspecialchars($movimiento['usuario_nombre'] ?? '') ?></small>
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
            $('#tablaMovimientos').DataTable({
                language: {
                    url: '//cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
                },
                pageLength: 25,
                order: [
                    [0, 'desc']
                ], // Ordenar por fecha descendente
                columnDefs: [{
                    targets: [2, 3, 4, 5],
                    className: 'text-center'
                }]
            });
        });

        function limpiarFiltros() {
            window.location.href = '/reportes/movimientos';
        }
    </script>
</body>

</html>