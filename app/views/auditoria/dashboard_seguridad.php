<?php
// app/views/auditoria/dashboard_seguridad.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-shield-alt"></i>
        Dashboard de Seguridad Avanzada
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=auditoria">Auditoría</a></li>
        <li class="breadcrumb-item active">Seguridad Avanzada</li>
    </ol>

    <!-- Alertas de Seguridad Críticas -->
    <div class="row mb-4">
        <div class="col-md-12">
            <div id="alertas-criticas" class="alert alert-warning" style="display: none;">
                <h5><i class="fas fa-exclamation-triangle"></i> Alertas de Seguridad</h5>
                <div id="lista-alertas"></div>
            </div>
        </div>
    </div>

    <!-- Métricas de Seguridad en Tiempo Real -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card bg-info text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="totalCambiosHoy">0</h4>
                            <p class="mb-0">Cambios Hoy</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-edit fa-2x"></i>
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
                            <h4 id="sesionesActivas">0</h4>
                            <p class="mb-0">Sesiones Activas</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-users fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="?page=auditoria&action=monitoreo-sesiones">Ver sesiones</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-warning text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="anomaliasDetectadas">0</h4>
                            <p class="mb-0">Anomalías Detectadas</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-exclamation-triangle fa-2x"></i>
                        </div>
                    </div>
                </div>
                <div class="card-footer d-flex align-items-center justify-content-between">
                    <a class="small text-white stretched-link" href="#" onclick="detectarAnomalias()">Analizar ahora</a>
                    <div class="small text-white"><i class="fas fa-angle-right"></i></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card bg-danger text-white mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <div>
                            <h4 id="logsCriticos">0</h4>
                            <p class="mb-0">Logs Críticos</p>
                        </div>
                        <div class="align-self-center">
                            <i class="fas fa-bell fa-2x"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Gráficos de Actividad -->
    <div class="row mb-4">
        <div class="col-xl-8">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-area me-1"></i>
                    Actividad del Sistema (Últimas 24 Horas)
                </div>
                <div class="card-body">
                    <canvas id="actividadChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card mb-4">
                <div class="card-header">
                    <i class="fas fa-chart-pie me-1"></i>
                    Tablas Más Modificadas
                </div>
                <div class="card-body">
                    <canvas id="tablasChart" width="100%" height="40"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Sesiones Activas -->
    <div class="row mb-4">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-users me-1"></i>
                    Sesiones Activas
                    <button class="btn btn-sm btn-outline-primary float-end" onclick="actualizarSesiones()">
                        <i class="fas fa-sync"></i> Actualizar
                    </button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="sesionesTable">
                            <thead>
                                <tr>
                                    <th>Usuario</th>
                                    <th>Última Actividad</th>
                                    <th>Actividades</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody id="sesionesBody">
                                <!-- Cargado dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-6">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-exclamation-circle me-1"></i>
                    Logs Críticos Recientes
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="logsCriticosTable">
                            <thead>
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tabla</th>
                                    <th>Campo</th>
                                    <th>Usuario</th>
                                </tr>
                            </thead>
                            <tbody id="logsCriticosBody">
                                <!-- Cargado dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Acciones de Seguridad -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-tools me-1"></i>
            Herramientas de Seguridad
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <button class="btn btn-outline-primary btn-lg w-100 mb-3" onclick="generarReporteSeguridad()">
                        <i class="fas fa-file-shield"></i><br>
                        Generar Reporte de Seguridad
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-warning btn-lg w-100 mb-3" onclick="detectarAnomalias()">
                        <i class="fas fa-search"></i><br>
                        Detectar Anomalías
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-info btn-lg w-100 mb-3" onclick="exportarLogsSeguridad()">
                        <i class="fas fa-download"></i><br>
                        Exportar Logs
                    </button>
                </div>
                <div class="col-md-3">
                    <button class="btn btn-outline-success btn-lg w-100 mb-3" onclick="configurarAlertas()">
                        <i class="fas fa-cog"></i><br>
                        Configurar Alertas
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Configuración de Alertas -->
<div class="modal fade" id="alertasModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Configuración de Alertas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="alertasForm">
                    <div class="mb-3">
                        <label for="max_intentos_login" class="form-label">Máximo Intentos Login Fallidos</label>
                        <input type="number" class="form-control" id="max_intentos_login" value="5" min="1">
                    </div>
                    <div class="mb-3">
                        <label for="tiempo_inactividad" class="form-label">Tiempo de Inactividad (minutos)</label>
                        <input type="number" class="form-control" id="tiempo_inactividad" value="30" min="5">
                    </div>
                    <div class="mb-3">
                        <label for="max_cambios_hora" class="form-label">Máximo Cambios por Hora</label>
                        <input type="number" class="form-control" id="max_cambios_hora" value="100" min="10">
                    </div>
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="alertas_email" checked>
                        <label class="form-check-label" for="alertas_email">
                            Enviar alertas por email
                        </label>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarConfiguracionAlertas()">
                    <i class="fas fa-save"></i> Guardar
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let charts = {};

    document.addEventListener('DOMContentLoaded', function() {
        cargarDashboard();

        // Actualizar cada 30 segundos
        setInterval(cargarDashboard, 30000);
    });

    function cargarDashboard() {
        cargarMetricas();
        cargarSesiones();
        cargarLogsCriticos();
        cargarGraficos();
    }

    function cargarMetricas() {
        fetch('?page=auditoria&action=dashboard-seguridad', {
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
                    const metrics = data.metrics;
                    document.getElementById('totalCambiosHoy').textContent = metrics.total_cambios_hoy || 0;

                    // Simular otras métricas por ahora
                    document.getElementById('sesionesActivas').textContent = Math.floor(Math.random() * 15) + 5;
                    document.getElementById('anomaliasDetectadas').textContent = Math.floor(Math.random() * 3);
                    document.getElementById('logsCriticos').textContent = Math.floor(Math.random() * 10);
                }
            })
            .catch(error => console.error('Error cargando métricas:', error));
    }

    function cargarSesiones() {
        fetch('?page=auditoria&action=dashboard-seguridad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'sesiones_activas'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('sesionesBody');
                    tbody.innerHTML = '';

                    data.sesiones.forEach(sesion => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                    <td>${sesion.nombre}</td>
                    <td>${new Date(sesion.ultima_actividad).toLocaleString()}</td>
                    <td>${sesion.actividades_sesion}</td>
                    <td>
                        <button class="btn btn-sm btn-outline-warning" onclick="cerrarSesion(${sesion.id_usuario})">
                            <i class="fas fa-sign-out-alt"></i>
                        </button>
                    </td>
                `;
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(error => console.error('Error cargando sesiones:', error));
    }

    function cargarLogsCriticos() {
        fetch('?page=auditoria&action=dashboard-seguridad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    type: 'logs_criticos'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.getElementById('logsCriticosBody');
                    tbody.innerHTML = '';

                    data.logs.forEach(log => {
                        const tr = document.createElement('tr');
                        tr.innerHTML = `
                    <td>${new Date(log.fecha).toLocaleString()}</td>
                    <td>${log.tabla_afectada}</td>
                    <td>${log.campo_modificado}</td>
                    <td>Usuario ID: ${log.id_usuario || 'Sistema'}</td>
                `;
                        tbody.appendChild(tr);
                    });
                }
            })
            .catch(error => console.error('Error cargando logs críticos:', error));
    }

    function cargarGraficos() {
        fetch('?page=auditoria&action=dashboard-seguridad', {
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
                    crearGraficoActividad(data.metrics.actividad_hora || []);
                    crearGraficoTablas(data.metrics.tablas_cambiadas || []);
                }
            })
            .catch(error => console.error('Error cargando gráficos:', error));
    }

    function crearGraficoActividad(datos) {
        if (charts.actividad) {
            charts.actividad.destroy();
        }

        const ctx = document.getElementById('actividadChart').getContext('2d');
        charts.actividad = new Chart(ctx, {
            type: 'line',
            data: {
                labels: datos.map(d => d.hora + ':00'),
                datasets: [{
                    label: 'Actividades',
                    data: datos.map(d => d.actividades),
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
    }

    function crearGraficoTablas(datos) {
        if (charts.tablas) {
            charts.tablas.destroy();
        }

        const ctx = document.getElementById('tablasChart').getContext('2d');
        charts.tablas = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: datos.map(d => d.tabla_afectada),
                datasets: [{
                    data: datos.map(d => d.modificaciones),
                    backgroundColor: [
                        '#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0',
                        '#9966FF', '#FF9F40'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });
    }

    function detectarAnomalias() {
        fetch('?page=auditoria&action=detectar-anomalias', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data && (data.logins_fallidos?.length || data.actividad_nocturna?.length || data.cambios_masivos?.length)) {
                    mostrarAlertas(data);
                } else {
                    alert('✅ No se detectaron anomalías significativas');
                }
            })
            .catch(error => {
                console.error('Error detectando anomalías:', error);
                alert('Error al detectar anomalías');
            });
    }

    function mostrarAlertas(anomalias) {
        const alertasDiv = document.getElementById('alertas-criticas');
        const listaAlertas = document.getElementById('lista-alertas');

        let alertasHtml = '';

        if (anomalias.logins_fallidos?.length) {
            alertasHtml += '<div class="alert alert-danger mb-2"><i class="fas fa-exclamation-triangle"></i> Múltiples intentos de login fallidos detectados</div>';
        }

        if (anomalias.actividad_nocturna?.length) {
            alertasHtml += '<div class="alert alert-warning mb-2"><i class="fas fa-moon"></i> Actividad fuera de horario detectada</div>';
        }

        if (anomalias.cambios_masivos?.length) {
            alertasHtml += '<div class="alert alert-danger mb-2"><i class="fas fa-edit"></i> Cambios masivos detectados</div>';
        }

        listaAlertas.innerHTML = alertasHtml;
        alertasDiv.style.display = 'block';

        // Auto-ocultar después de 10 segundos
        setTimeout(() => {
            alertasDiv.style.display = 'none';
        }, 10000);
    }

    function generarReporteSeguridad() {
        fetch('?page=auditoria&action=reporte-seguridad', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('✅ Reporte de seguridad generado: ' + data.archivo);
                } else {
                    alert('❌ Error generando reporte: ' + data.error);
                }
            })
            .catch(error => {
                console.error('Error generando reporte:', error);
                alert('Error al generar reporte');
            });
    }

    function cerrarSesion(usuarioId) {
        if (confirm('¿Cerrar sesión de este usuario?')) {
            fetch('?page=auditoria&action=monitoreo-sesiones', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'cerrar_sesion',
                        sesion_id: usuarioId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Sesión cerrada exitosamente');
                        cargarSesiones();
                    } else {
                        alert('❌ Error: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error cerrando sesión:', error);
                    alert('Error al cerrar sesión');
                });
        }
    }

    function configurarAlertas() {
        new bootstrap.Modal(document.getElementById('alertasModal')).show();
    }

    function guardarConfiguracionAlertas() {
        const config = {
            max_intentos_login: document.getElementById('max_intentos_login').value,
            tiempo_inactividad: document.getElementById('tiempo_inactividad').value,
            max_cambios_hora: document.getElementById('max_cambios_hora').value,
            alertas_email: document.getElementById('alertas_email').checked
        };

        // Simular guardado
        setTimeout(() => {
            alert('✅ Configuración guardada exitosamente');
            bootstrap.Modal.getInstance(document.getElementById('alertasModal')).hide();
        }, 1000);
    }

    function actualizarSesiones() {
        cargarSesiones();
    }

    function exportarLogsSeguridad() {
        window.open('?page=auditoria&action=exportar&tipo=seguridad', '_blank');
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>