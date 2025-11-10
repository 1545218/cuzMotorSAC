<?php
// app/views/reportes/estado_stock.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-warehouse"></i>
        Reporte de Estado de Stock
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=reportes">Reportes</a></li>
        <li class="breadcrumb-item active">Estado de Stock</li>
    </ol>

    <!-- Filtros y Controles -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-sliders-h me-1"></i>
            Filtros y Controles
        </div>
        <div class="card-body">
            <form id="filtrosForm" class="row g-3">
                <div class="col-md-3">
                    <label for="categoria" class="form-label">Categoría</label>
                    <select class="form-select" id="categoria" name="categoria_id">
                        <option value="">Todas las categorías</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="marca" class="form-label">Marca</label>
                    <select class="form-select" id="marca" name="marca_id">
                        <option value="">Todas las marcas</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="estado_stock" class="form-label">Estado</label>
                    <select class="form-select" id="estado_stock" name="estado_stock">
                        <option value="">Todos</option>
                        <option value="critico">Crítico</option>
                        <option value="bajo">Bajo</option>
                        <option value="normal">Normal</option>
                        <option value="alto">Alto</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label for="ubicacion" class="form-label">Ubicación</label>
                    <select class="form-select" id="ubicacion" name="ubicacion_id">
                        <option value="">Todas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary me-2">
                        <i class="fas fa-filter"></i> Filtrar
                    </button>
                    <button type="button" class="btn btn-success" onclick="exportarExcel()">
                        <i class="fas fa-file-excel"></i> Excel
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Indicadores de Estado -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="stockCritico">0</h4>
                            <p class="mb-0">Stock Crítico</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#" onclick="filtrarPorEstado('critico')">Ver detalles</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="stockBajo">0</h4>
                            <p class="mb-0">Stock Bajo</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#" onclick="filtrarPorEstado('bajo')">Ver detalles</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="stockNormal">0</h4>
                            <p class="mb-0">Stock Normal</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-check-circle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#" onclick="filtrarPorEstado('normal')">Ver detalles</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="stockAlto">0</h4>
                            <p class="mb-0">Stock Alto</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-arrow-up fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#" onclick="filtrarPorEstado('alto')">Ver detalles</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos de Análisis -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Distribución de Stock por Estado
                </div>
                <div class="card-body">
                    <canvas id="estadoChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-chart-bar me-1"></i>
                    Valor de Inventario por Categoría
                </div>
                <div class="card-body">
                    <canvas id="valorChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Análisis por Ubicación -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-map-marker-alt me-1"></i>
                    Stock por Ubicación
                </div>
                <div class="card-body">
                    <canvas id="ubicacionChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock me-1"></i>
                    Rotación de Inventario
                </div>
                <div class="card-body">
                    <div id="rotacionContainer">
                        <!-- Métricas de rotación cargadas dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alertas y Recomendaciones -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-bell me-1"></i>
            Alertas y Recomendaciones
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h5><i class="fas fa-exclamation-triangle text-danger"></i> Productos Críticos</h5>
                    <div id="alertasCriticas">
                        <!-- Alertas críticas cargadas dinámicamente -->
                    </div>
                </div>
                <div class="col-md-6">
                    <h5><i class="fas fa-lightbulb text-info"></i> Recomendaciones</h5>
                    <div id="recomendaciones">
                        <!-- Recomendaciones cargadas dinámicamente -->
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada de Stock -->
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-table me-1"></i> Detalle de Stock por Producto</span>
            <div class="btn-group" role="group">
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleVista('tabla')">
                    <i class="fas fa-table"></i> Tabla
                </button>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="toggleVista('cards')">
                    <i class="fas fa-th"></i> Tarjetas
                </button>
            </div>
        </div>
        <div class="card-body">
            <!-- Vista de Tabla -->
            <div id="vistaTabla">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="stockTable">
                        <thead class="table-dark">
                            <tr>
                                <th>Código</th>
                                <th>Producto</th>
                                <th>Categoría</th>
                                <th>Ubicación</th>
                                <th>Stock Actual</th>
                                <th>Stock Mínimo</th>
                                <th>Stock Máximo</th>
                                <th>Valor Total</th>
                                <th>Última Entrada</th>
                                <th>Estado</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody id="stockBody">
                            <!-- Datos cargados dinámicamente -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Vista de Tarjetas -->
            <div id="vistaCards" style="display: none;">
                <div class="row" id="cardsContainer">
                    <!-- Tarjetas cargadas dinámicamente -->
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let charts = {};
    let vistaActual = 'tabla';

    document.addEventListener('DOMContentLoaded', function() {
        cargarSelectores();
        cargarDatos();

        document.getElementById('filtrosForm').addEventListener('submit', function(e) {
            e.preventDefault();
            cargarDatos();
        });
    });

    function cargarSelectores() {
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

        // Cargar marcas
        fetch('?page=marcas&action=api')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('marca');
                data.marcas.forEach(marca => {
                    const option = document.createElement('option');
                    option.value = marca.id;
                    option.textContent = marca.nombre;
                    select.appendChild(option);
                });
            });

        // Cargar ubicaciones
        fetch('?page=ubicaciones&action=api')
            .then(response => response.json())
            .then(data => {
                const select = document.getElementById('ubicacion');
                data.ubicaciones.forEach(ubicacion => {
                    const option = document.createElement('option');
                    option.value = ubicacion.id;
                    option.textContent = ubicacion.nombre;
                    select.appendChild(option);
                });
            });
    }

    function cargarDatos() {
        const formData = new FormData(document.getElementById('filtrosForm'));
        const filtros = Object.fromEntries(formData);

        fetch('?page=reportes&action=estado-stock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'datos',
                    filtros: filtros
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarIndicadores(data.indicadores);
                    crearGraficos(data.graficos);
                    actualizarAlertas(data.alertas);
                    actualizarTabla(data.productos);
                    actualizarRotacion(data.rotacion);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarIndicadores(indicadores) {
        document.getElementById('stockCritico').textContent = indicadores.critico;
        document.getElementById('stockBajo').textContent = indicadores.bajo;
        document.getElementById('stockNormal').textContent = indicadores.normal;
        document.getElementById('stockAlto').textContent = indicadores.alto;
    }

    function crearGraficos(graficos) {
        // Destruir gráficos existentes
        Object.values(charts).forEach(chart => {
            if (chart) chart.destroy();
        });

        // Gráfico de estado
        charts.estado = new Chart(document.getElementById('estadoChart'), {
            type: 'doughnut',
            data: {
                labels: ['Crítico', 'Bajo', 'Normal', 'Alto'],
                datasets: [{
                    data: graficos.estado.data,
                    backgroundColor: ['#dc3545', '#ffc107', '#28a745', '#17a2b8']
                }]
            },
            options: {
                responsive: true
            }
        });

        // Gráfico de valor por categoría
        charts.valor = new Chart(document.getElementById('valorChart'), {
            type: 'bar',
            data: {
                labels: graficos.valor.labels,
                datasets: [{
                    label: 'Valor (S/.)',
                    data: graficos.valor.data,
                    backgroundColor: 'rgba(54, 162, 235, 0.8)'
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return 'S/. ' + value.toLocaleString();
                            }
                        }
                    }
                }
            }
        });

        // Gráfico por ubicación
        charts.ubicacion = new Chart(document.getElementById('ubicacionChart'), {
            type: 'bar',
            data: {
                labels: graficos.ubicacion.labels,
                datasets: [{
                    label: 'Productos',
                    data: graficos.ubicacion.data,
                    backgroundColor: 'rgba(75, 192, 192, 0.8)'
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

    function actualizarAlertas(alertas) {
        // Alertas críticas
        let alertasHtml = '';
        alertas.criticas.forEach(alerta => {
            alertasHtml += `
            <div class="alert alert-danger alert-sm mb-2">
                <strong>${alerta.producto}</strong><br>
                Stock: ${alerta.stock_actual}/${alerta.stock_minimo}
            </div>
        `;
        });
        document.getElementById('alertasCriticas').innerHTML = alertasHtml;

        // Recomendaciones
        let recomendacionesHtml = '';
        alertas.recomendaciones.forEach(rec => {
            const iconClass = rec.tipo === 'compra' ? 'shopping-cart' :
                rec.tipo === 'ajuste' ? 'edit' : 'info-circle';
            recomendacionesHtml += `
            <div class="alert alert-info alert-sm mb-2">
                <i class="fas fa-${iconClass}"></i> ${rec.mensaje}
            </div>
        `;
        });
        document.getElementById('recomendaciones').innerHTML = recomendacionesHtml;
    }

    function actualizarRotacion(rotacion) {
        const rotacionHtml = `
        <div class="mb-3">
            <h6>Rotación Promedio</h6>
            <h3 class="text-primary">${rotacion.promedio.toFixed(2)}x/año</h3>
        </div>
        <div class="mb-3">
            <h6>Días Promedio en Stock</h6>
            <h4 class="text-info">${rotacion.dias_promedio} días</h4>
        </div>
        <div>
            <h6>Productos de Alta Rotación</h6>
            <small class="text-success">${rotacion.alta_rotacion} productos</small>
        </div>
    `;
        document.getElementById('rotacionContainer').innerHTML = rotacionHtml;
    }

    function actualizarTabla(productos) {
        if (vistaActual === 'tabla') {
            actualizarVistaTabla(productos);
        } else {
            actualizarVistaCards(productos);
        }
    }

    function actualizarVistaTabla(productos) {
        const tbody = document.getElementById('stockBody');
        tbody.innerHTML = '';

        productos.forEach(producto => {
            const tr = document.createElement('tr');
            const estadoBadge = getEstadoBadge(producto.estado_stock);

            tr.innerHTML = `
            <td>${producto.codigo}</td>
            <td>${producto.nombre}</td>
            <td>${producto.categoria}</td>
            <td>${producto.ubicacion}</td>
            <td class="text-end">${producto.stock_actual}</td>
            <td class="text-end">${producto.stock_minimo}</td>
            <td class="text-end">${producto.stock_maximo || '-'}</td>
            <td class="text-end">S/. ${(producto.stock_actual * producto.precio_venta).toFixed(2)}</td>
            <td>${producto.ultima_entrada || '-'}</td>
            <td>${estadoBadge}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-primary" onclick="verDetalle(${producto.id})" title="Ver detalle">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="ajustarStock(${producto.id})" title="Ajustar stock">
                        <i class="fas fa-edit"></i>
                    </button>
                </div>
            </td>
        `;
            tbody.appendChild(tr);
        });

        // Reinicializar DataTable
        if ($.fn.DataTable.isDataTable('#stockTable')) {
            $('#stockTable').DataTable().destroy();
        }

        $('#stockTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [
                [4, 'asc']
            ], // Ordenar por stock actual
            pageLength: 25,
            responsive: true
        });
    }

    function actualizarVistaCards(productos) {
        const container = document.getElementById('cardsContainer');
        container.innerHTML = '';

        productos.forEach(producto => {
            const estadoBadge = getEstadoBadge(producto.estado_stock);
            const valorTotal = (producto.stock_actual * producto.precio_venta).toFixed(2);

            const cardHtml = `
            <div class="col-md-6 col-lg-4 mb-3">
                <div class="card h-100">
                    <div class="card-body">
                        <h6 class="card-title">${producto.codigo}</h6>
                        <p class="card-text">${producto.nombre}</p>
                        <div class="row text-center mb-2">
                            <div class="col">
                                <small class="text-muted">Actual</small>
                                <h5>${producto.stock_actual}</h5>
                            </div>
                            <div class="col">
                                <small class="text-muted">Mínimo</small>
                                <h5>${producto.stock_minimo}</h5>
                            </div>
                            <div class="col">
                                <small class="text-muted">Valor</small>
                                <h5>S/. ${valorTotal}</h5>
                            </div>
                        </div>
                        <div class="text-center mb-2">
                            ${estadoBadge}
                        </div>
                        <div class="btn-group w-100">
                            <button class="btn btn-outline-primary btn-sm" onclick="verDetalle(${producto.id})">
                                <i class="fas fa-eye"></i> Ver
                            </button>
                            <button class="btn btn-outline-warning btn-sm" onclick="ajustarStock(${producto.id})">
                                <i class="fas fa-edit"></i> Ajustar
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        `;
            container.innerHTML += cardHtml;
        });
    }

    function getEstadoBadge(estado) {
        switch (estado) {
            case 'critico':
                return '<span class="badge bg-danger">Crítico</span>';
            case 'bajo':
                return '<span class="badge bg-warning">Bajo</span>';
            case 'normal':
                return '<span class="badge bg-success">Normal</span>';
            case 'alto':
                return '<span class="badge bg-info">Alto</span>';
            default:
                return '<span class="badge bg-secondary">N/A</span>';
        }
    }

    function filtrarPorEstado(estado) {
        document.getElementById('estado_stock').value = estado;
        cargarDatos();
    }

    function toggleVista(vista) {
        vistaActual = vista;

        if (vista === 'tabla') {
            document.getElementById('vistaTabla').style.display = 'block';
            document.getElementById('vistaCards').style.display = 'none';
        } else {
            document.getElementById('vistaTabla').style.display = 'none';
            document.getElementById('vistaCards').style.display = 'block';
        }

        // Recargar datos en la nueva vista
        cargarDatos();
    }

    function verDetalle(productoId) {
        window.location.href = `?page=productos&action=show&id=${productoId}`;
    }

    function ajustarStock(productoId) {
        window.location.href = `?page=ajustes&action=create&producto_id=${productoId}`;
    }

    function exportarExcel() {
        const formData = new FormData(document.getElementById('filtrosForm'));
        const params = new URLSearchParams(formData);
        params.append('exportar', 'excel');

        window.open('?page=reportes&action=estado-stock&' + params.toString(), '_blank');
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>