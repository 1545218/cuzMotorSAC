<?php
// app/views/reportes/movimientos_inventario.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-exchange-alt"></i>
        Reporte de Movimientos de Inventario
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=reportes">Reportes</a></li>
        <li class="breadcrumb-item active">Movimientos de Inventario</li>
    </ol>

    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-filter me-1"></i>
            Filtros de Búsqueda
        </div>
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label for="fecha_inicio" class="form-label">Fecha Inicio</label>
                    <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio"
                        value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>">
                </div>
                <div class="col-md-3">
                    <label for="fecha_fin" class="form-label">Fecha Fin</label>
                    <input type="date" class="form-control" id="fecha_fin" name="fecha_fin"
                        value="<?php echo date('Y-m-d'); ?>">
                </div>
                <div class="col-md-3">
                    <label for="tipo_movimiento" class="form-label">Tipo de Movimiento</label>
                    <select class="form-select" id="tipo_movimiento" name="tipo_movimiento">
                        <option value="">Todos</option>
                        <option value="entrada">Entradas</option>
                        <option value="salida">Salidas</option>
                        <option value="ajuste">Ajustes</option>
                        <option value="venta">Ventas</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="producto" class="form-label">Producto</label>
                    <select class="form-select" id="producto" name="producto_id">
                        <option value="">Todos los productos</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria_id">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="usuario" class="form-label">Usuario</label>
                    <select class="form-select" id="usuario" name="usuario_id">
                        <option value="">Todos los usuarios</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                    <button type="button" class="btn btn-secondary me-2" onclick="limpiarFiltros()">
                        <i class="fas fa-eraser"></i> Limpiar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Resumen -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="totalMovimientos">0</h4>
                            <p class="mb-0">Total Movimientos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exchange-alt fa-2x"></i>
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
                            <h4 id="totalEntradas">0</h4>
                            <p class="mb-0">Entradas</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-arrow-up fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="totalSalidas">0</h4>
                            <p class="mb-0">Salidas</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-arrow-down fa-2x"></i>
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
                            <h4 id="totalAjustes">0</h4>
                            <p class="mb-0">Ajustes</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-tools fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráfico de Movimientos -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-chart-line me-1"></i>
            Tendencia de Movimientos por Día
        </div>
        <div class="card-body">
            <canvas id="movimientosChart" width="100%" height="40"></canvas>
        </div>
    </div>

    <!-- Tabla de Movimientos -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-table me-1"></i>
            Detalle de Movimientos
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="movimientosTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Stock Anterior</th>
                            <th>Stock Nuevo</th>
                            <th>Usuario</th>
                            <th>Observaciones</th>
                            <th>Referencia</th>
                        </tr>
                    </thead>
                    <tbody id="movimientosBody">
                        <!-- Datos cargados dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let movimientosChart;

    document.addEventListener('DOMContentLoaded', function() {
        cargarSelectores();
        cargarDatos();

        document.getElementById('filtrosForm').addEventListener('submit', function(e) {
            e.preventDefault();
            cargarDatos();
        });
    });

    function cargarSelectores() {
        // Cargar productos
        fetch('?page=productos&action=api')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('producto');
                data.productos.forEach(producto => {
                    const option = document.createElement('option');
                    option.value = producto.id;
                    option.textContent = `${producto.codigo} - ${producto.nombre}`;
                    select.appendChild(option);
                });
            });

        // Cargar categorías
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

        // Cargar usuarios
        fetch('?page=usuarios&action=api')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('usuario');
                data.usuarios.forEach(usuario => {
                    const option = document.createElement('option');
                    option.value = usuario.id;
                    option.textContent = usuario.nombre;
                    select.appendChild(option);
                });
            });
    }

    function cargarDatos() {
        const formData = new FormData(document.getElementById('filtrosForm'));
        const params = new URLSearchParams(formData);

        fetch('?page=reportes&action=movimientos-inventario', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'datos',
                    filtros: Object.fromEntries(params)
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarResumen(data.resumen);
                    actualizarGrafico(data.grafico);
                    actualizarTabla(data.movimientos);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarResumen(resumen) {
        document.getElementById('totalMovimientos').textContent = resumen.total;
        document.getElementById('totalEntradas').textContent = resumen.entradas;
        document.getElementById('totalSalidas').textContent = resumen.salidas;
        document.getElementById('totalAjustes').textContent = resumen.ajustes;
    }

    function actualizarGrafico(graficoData) {
        const ctx = document.getElementById('movimientosChart').getContext('2d');

        if (movimientosChart) {
            movimientosChart.destroy();
        }

        movimientosChart = new Chart(ctx, {
            type: 'line',
            data: {
                labels: graficoData.labels,
                datasets: [{
                    label: 'Entradas',
                    data: graficoData.entradas,
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
                    tension: 0.1
                }, {
                    label: 'Salidas',
                    data: graficoData.salidas,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                interaction: {
                    mode: 'index',
                    intersect: false,
                },
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }

    function actualizarTabla(movimientos) {
        const tbody = document.getElementById('movimientosBody');
        tbody.innerHTML = '';

        movimientos.forEach(mov => {
            const tr = document.createElement('tr');
            const tipoClass = getTipoClass(mov.tipo);

            tr.innerHTML = `
            <td>${new Date(mov.fecha_creacion).toLocaleDateString()}</td>
            <td><span class="badge ${tipoClass}">${mov.tipo.toUpperCase()}</span></td>
            <td>${mov.producto_codigo} - ${mov.producto_nombre}</td>
            <td class="text-end">${mov.cantidad}</td>
            <td class="text-end">${mov.stock_anterior}</td>
            <td class="text-end">${mov.stock_nuevo}</td>
            <td>${mov.usuario_nombre}</td>
            <td>${mov.observaciones || '-'}</td>
            <td>${mov.referencia || '-'}</td>
        `;
            tbody.appendChild(tr);
        });

        // Reinicializar DataTable si existe
        if ($.fn.DataTable.isDataTable('#movimientosTable')) {
            $('#movimientosTable').DataTable().destroy();
        }

        $('#movimientosTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [
                [0, 'desc']
            ],
            pageLength: 25,
            responsive: true
        });
    }

    function getTipoClass(tipo) {
        switch (tipo) {
            case 'entrada':
                return 'bg-success';
            case 'salida':
                return 'bg-danger';
            case 'ajuste':
                return 'bg-warning';
            case 'venta':
                return 'bg-info';
            default:
                return 'bg-secondary';
        }
    }

    function limpiarFiltros() {
        document.getElementById('filtrosForm').reset();
        document.getElementById('fecha_inicio').value = '<?php echo date('Y-m-d', strtotime('-30 days')); ?>';
        document.getElementById('fecha_fin').value = '<?php echo date('Y-m-d'); ?>';
        cargarDatos();
    }

    function exportarExcel() {
        const formData = new FormData(document.getElementById('filtrosForm'));
        const params = new URLSearchParams(formData);
        params.append('exportar', 'excel');

        window.open('?page=reportes&action=movimientos-inventario&' + params.toString(), '_blank');
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>