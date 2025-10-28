<div class="container-fluid">
    <!-- Título y filtros -->
    <div class="row mb-4">
        <div class="col-12">
            <h2><i class="fas fa-chart-line"></i> Reportes de Ventas</h2>
            <p class="text-muted">Análisis de ventas por períodos y productos</p>
        </div>
    </div>

    <!-- Filtros de búsqueda -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0"><i class="fas fa-filter"></i> Filtros de Reporte</h5>
        </div>
        <div class="card-body">
            <form id="formFiltros" method="GET">
                <div class="row">
                    <div class="col-md-3">
                        <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                            value="<?= $_GET['fecha_inicio'] ?? date('Y-m-01') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="fecha_fin" class="form-label">Fecha Fin</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                            value="<?= $_GET['fecha_fin'] ?? date('Y-m-d') ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                        <select class="form-select" id="tipo_reporte" name="tipo_reporte">
                            <option value="ventas" <?= ($_GET['tipo_reporte'] ?? 'ventas') == 'ventas' ? 'selected' : '' ?>>
                                Ventas por Período
                            </option>
                            <option value="inventario" <?= ($_GET['tipo_reporte'] ?? '') == 'inventario' ? 'selected' : '' ?>>
                                Valorización de Inventario
                            </option>
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary me-2">
                            <i class="fas fa-search"></i> Filtrar
                        </button>
                        <div class="btn-group">
                            <button type="button" class="btn btn-success dropdown-toggle" data-bs-toggle="dropdown">
                                <i class="fas fa-download"></i> Exportar
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportar('excel')">
                                        <i class="fas fa-file-excel"></i> Excel
                                    </a></li>
                                <li><a class="dropdown-item" href="#" onclick="exportar('pdf')">
                                        <i class="fas fa-file-pdf"></i> PDF
                                    </a></li>
                            </ul>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Resultados del Reporte -->
    <div class="card">
        <div class="card-header">
            <h5 class="card-title mb-0">
                <i class="fas fa-table"></i>
                <?php if (($tipo_reporte ?? 'ventas') == 'ventas'): ?>
                    Reporte de Ventas
                <?php else: ?>
                    Valorización de Inventario
                <?php endif; ?>
            </h5>
        </div>
        <div class="card-body">
            <?php if (($tipo_reporte ?? 'ventas') == 'ventas'): ?>
                <!-- Tabla de Ventas -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Fecha</th>
                                <th>N° Venta</th>
                                <th>Cliente</th>
                                <th>Productos</th>
                                <th>Total</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($ventas) && !empty($ventas)): ?>
                                <?php foreach ($ventas as $venta): ?>
                                    <tr>
                                        <td><?= date('d/m/Y', strtotime($venta['fecha_venta'])) ?></td>
                                        <td><span class="badge bg-primary"><?= $venta['numero_venta'] ?></span></td>
                                        <td><?= htmlspecialchars($venta['cliente_nombre']) ?></td>
                                        <td>
                                            <small class="text-muted">
                                                <?= $venta['total_productos'] ?> producto(s)
                                            </small>
                                        </td>
                                        <td class="text-end">
                                            <strong>S/ <?= number_format($venta['total'], 2) ?></strong>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-info" onclick="verDetalleVenta(<?= $venta['id_venta'] ?>)">
                                                <i class="fas fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <th colspan="4" class="text-end">Total del Período:</th>
                                    <th class="text-end">S/ <?= number_format($totalVentas ?? 0, 2) ?></th>
                                    <th></th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle"></i>
                                        No se encontraron ventas en el período seleccionado
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <!-- Tabla de Valorización de Inventario -->
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Marca</th>
                                <th>Stock Actual</th>
                                <th>Precio Unitario</th>
                                <th>Valor Total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($inventario) && !empty($inventario)): ?>
                                <?php foreach ($inventario as $item): ?>
                                    <tr>
                                        <td><code><?= htmlspecialchars($item['codigo']) ?></code></td>
                                        <td><?= htmlspecialchars($item['nombre']) ?></td>
                                        <td><?= htmlspecialchars($item['marca_nombre'] ?? 'N/A') ?></td>
                                        <td class="text-center">
                                            <span class="badge <?= $item['stock_actual'] <= $item['stock_minimo'] ? 'bg-danger' : 'bg-success' ?>">
                                                <?= $item['stock_actual'] ?>
                                            </span>
                                        </td>
                                        <td class="text-end">S/ <?= number_format($item['precio_venta'], 2) ?></td>
                                        <td class="text-end">
                                            <strong>S/ <?= number_format($item['valor_total'], 2) ?></strong>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="table-info">
                                    <th colspan="5" class="text-end">Valor Total del Inventario:</th>
                                    <th class="text-end">S/ <?= number_format($valorTotalInventario ?? 0, 2) ?></th>
                                </tr>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-info-circle"></i>
                                        No hay productos en el inventario
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
</div>

<!-- Modal para detalle de venta -->
<div class="modal fade" id="modalDetalleVenta" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle de Venta</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="contenidoDetalleVenta">
                <!-- Contenido cargado dinámicamente -->
            </div>
        </div>
    </div>
</div>

<script>
    // Función para exportar reportes
    function exportar(formato) {
        const form = document.getElementById('formFiltros');
        const formData = new FormData(form);
        formData.append('exportar', formato);

        // Crear un formulario temporal para la exportación
        const tempForm = document.createElement('form');
        tempForm.method = 'POST';
        tempForm.action = '<?= APP_URL ?>/reportes/exportar';

        for (let [key, value] of formData.entries()) {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = key;
            input.value = value;
            tempForm.appendChild(input);
        }

        document.body.appendChild(tempForm);
        tempForm.submit();
        document.body.removeChild(tempForm);
    }

    // Función para ver detalle de venta
    function verDetalleVenta(idVenta) {
        fetch(`<?= APP_URL ?>/reportes/detalle-venta/${idVenta}`)
            .then(response => response.text())
            .then(html => {
                document.getElementById('contenidoDetalleVenta').innerHTML = html;
                new bootstrap.Modal(document.getElementById('modalDetalleVenta')).show();
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al cargar el detalle de la venta');
            });
    }

    // Auto-enviar formulario cuando cambian las fechas
    document.getElementById('fecha_inicio').addEventListener('change', function() {
        document.getElementById('formFiltros').submit();
    });

    document.getElementById('fecha_fin').addEventListener('change', function() {
        document.getElementById('formFiltros').submit();
    });

    document.getElementById('tipo_reporte').addEventListener('change', function() {
        document.getElementById('formFiltros').submit();
    });
</script>