<?php
// app/views/reportes/programar_reportes.php
Auth::requireAuth();
Auth::requireRole(['administrador']);
include_once '../app/views/layout/header.php';
?>

<div class="container-fluid px-4">
    <h1 class="mt-4">
        <i class="fas fa-clock"></i>
        Programación de Reportes Automáticos
    </h1>

    <ol class="breadcrumb mb-4">
        <li class="breadcrumb-item"><a href="?page=dashboard">Dashboard</a></li>
        <li class="breadcrumb-item"><a href="?page=reportes">Reportes</a></li>
        <li class="breadcrumb-item active">Programar Reportes</li>
    </ol>

    <!-- Crear Nueva Programación -->
    <div class="card mb-4">
        <div class="card-header">
            <i class="fas fa-plus me-1"></i>
            Nueva Programación de Reporte
        </div>
        <div class="card-body">
            <form id="nuevaProgramacionForm" class="row g-3">
                <div class="col-md-3">
                    <label for="tipo_reporte" class="form-label">Tipo de Reporte</label>
                    <select class="form-select" id="tipo_reporte" name="tipo_reporte" required>
                        <option value="">Seleccionar...</option>
                        <option value="stock">Estado de Stock</option>
                        <option value="ventas">Reporte de Ventas</option>
                        <option value="movimientos">Movimientos de Inventario</option>
                        <option value="consumo">Análisis de Consumo</option>
                        <option value="alertas">Alertas de Stock</option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="frecuencia" class="form-label">Frecuencia</label>
                    <select class="form-select" id="frecuencia" name="frecuencia" required>
                        <option value="">Seleccionar...</option>
                        <option value="diario">Diario</option>
                        <option value="semanal">Semanal</option>
                        <option value="mensual">Mensual</option>
                        <option value="trimestral">Trimestral</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="hora_envio" class="form-label">Hora de Envío</label>
                    <input type="time" class="form-control" id="hora_envio" name="hora_envio"
                        value="08:00" required>
                </div>

                <div class="col-md-2" id="dia_semana_container" style="display: none;">
                    <label for="dia_semana" class="form-label">Día de la Semana</label>
                    <select class="form-select" id="dia_semana" name="dia_semana">
                        <option value="1">Lunes</option>
                        <option value="2">Martes</option>
                        <option value="3">Miércoles</option>
                        <option value="4">Jueves</option>
                        <option value="5">Viernes</option>
                        <option value="6">Sábado</option>
                        <option value="7">Domingo</option>
                    </select>
                </div>

                <div class="col-md-2" id="dia_mes_container" style="display: none;">
                    <label for="dia_mes" class="form-label">Día del Mes</label>
                    <select class="form-select" id="dia_mes" name="dia_mes">
                        <?php for ($i = 1; $i <= 28; $i++): ?>
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label for="destinatarios" class="form-label">Destinatarios (Email)</label>
                    <textarea class="form-control" id="destinatarios" name="destinatarios"
                        placeholder="email1@empresa.com, email2@empresa.com" required></textarea>
                    <small class="text-muted">Separar múltiples emails con comas</small>
                </div>

                <div class="col-md-4">
                    <label for="filtros_json" class="form-label">Filtros Adicionales</label>
                    <textarea class="form-control" id="filtros_json" name="filtros_json"
                        placeholder='{"categoria_id": 1, "estado": "critico"}'></textarea>
                    <small class="text-muted">JSON con filtros específicos (opcional)</small>
                </div>

                <div class="col-md-4">
                    <label for="observaciones" class="form-label">Observaciones</label>
                    <textarea class="form-control" id="observaciones" name="observaciones"
                        placeholder="Descripción del propósito de este reporte..."></textarea>
                </div>

                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Programar Reporte
                    </button>
                    <button type="button" class="btn btn-info" onclick="previsualizarReporte()">
                        <i class="fas fa-eye"></i> Vista Previa
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Programaciones Existentes -->
    <div class="card mb-4">
        <div class="card-header d-flex justify-content-between align-items-center">
            <span><i class="fas fa-list me-1"></i> Programaciones Activas</span>
            <button class="btn btn-outline-success btn-sm" onclick="actualizarListaProgramaciones()">
                <i class="fas fa-sync"></i> Actualizar
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="programacionesTable">
                    <thead class="table-dark">
                        <tr>
                            <th>ID</th>
                            <th>Tipo de Reporte</th>
                            <th>Frecuencia</th>
                            <th>Próxima Ejecución</th>
                            <th>Última Ejecución</th>
                            <th>Destinatarios</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="programacionesBody">
                        <!-- Datos cargados dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Historial de Ejecuciones -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-history me-1"></i>
            Historial de Ejecuciones Recientes
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="historialTable">
                    <thead class="table-dark">
                        <tr>
                            <th>Fecha/Hora</th>
                            <th>Tipo de Reporte</th>
                            <th>Estado</th>
                            <th>Destinatarios</th>
                            <th>Tamaño Archivo</th>
                            <th>Error</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="historialBody">
                        <!-- Datos cargados dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Vista Previa -->
<div class="modal fade" id="previewModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Vista Previa del Reporte</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="previewContent">
                <!-- Contenido de vista previa cargado dinámicamente -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary" onclick="descargarPreview()">
                    <i class="fas fa-download"></i> Descargar
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal para Editar Programación -->
<div class="modal fade" id="editModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Editar Programación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editarProgramacionForm">
                    <input type="hidden" id="edit_programacion_id" name="programacion_id">
                    <!-- Los mismos campos que el formulario principal -->
                    <div class="mb-3">
                        <label for="edit_tipo_reporte" class="form-label">Tipo de Reporte</label>
                        <select class="form-select" id="edit_tipo_reporte" name="tipo_reporte" required>
                            <option value="stock">Estado de Stock</option>
                            <option value="ventas">Reporte de Ventas</option>
                            <option value="movimientos">Movimientos de Inventario</option>
                            <option value="consumo">Análisis de Consumo</option>
                            <option value="alertas">Alertas de Stock</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_frecuencia" class="form-label">Frecuencia</label>
                        <select class="form-select" id="edit_frecuencia" name="frecuencia" required>
                            <option value="diario">Diario</option>
                            <option value="semanal">Semanal</option>
                            <option value="mensual">Mensual</option>
                            <option value="trimestral">Trimestral</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="edit_hora_envio" class="form-label">Hora de Envío</label>
                        <input type="time" class="form-control" id="edit_hora_envio" name="hora_envio" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_destinatarios" class="form-label">Destinatarios</label>
                        <textarea class="form-control" id="edit_destinatarios" name="destinatarios" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" onclick="guardarEdicion()">
                    <i class="fas fa-save"></i> Guardar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        cargarProgramaciones();
        cargarHistorial();

        // Mostrar/ocultar campos según frecuencia
        document.getElementById('frecuencia').addEventListener('change', function() {
            const frecuencia = this.value;
            const diaSemanContainer = document.getElementById('dia_semana_container');
            const diaMesContainer = document.getElementById('dia_mes_container');

            diaSemanContainer.style.display = frecuencia === 'semanal' ? 'block' : 'none';
            diaMesContainer.style.display = (frecuencia === 'mensual' || frecuencia === 'trimestral') ? 'block' : 'none';
        });

        // Manejar envío del formulario
        document.getElementById('nuevaProgramacionForm').addEventListener('submit', function(e) {
            e.preventDefault();
            crearProgramacion();
        });
    });

    function crearProgramacion() {
        const formData = new FormData(document.getElementById('nuevaProgramacionForm'));
        const data = Object.fromEntries(formData);

        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'crear',
                    ...data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Programación creada exitosamente');
                    document.getElementById('nuevaProgramacionForm').reset();
                    cargarProgramaciones();
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear la programación');
            });
    }

    function cargarProgramaciones() {
        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'listar'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarTablaProgramaciones(data.programaciones);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarTablaProgramaciones(programaciones) {
        const tbody = document.getElementById('programacionesBody');
        tbody.innerHTML = '';

        programaciones.forEach(prog => {
            const tr = document.createElement('tr');
            const estadoBadge = prog.activo ?
                '<span class="badge bg-success">Activo</span>' :
                '<span class="badge bg-secondary">Inactivo</span>';

            tr.innerHTML = `
            <td>${prog.id}</td>
            <td>${getTipoReporteNombre(prog.tipo_reporte)}</td>
            <td>${prog.frecuencia}</td>
            <td>${new Date(prog.proxima_ejecucion).toLocaleString()}</td>
            <td>${prog.ultima_ejecucion ? new Date(prog.ultima_ejecucion).toLocaleString() : '-'}</td>
            <td>${prog.destinatarios.substring(0, 30)}...</td>
            <td>${estadoBadge}</td>
            <td>
                <div class="btn-group btn-group-sm">
                    <button class="btn btn-outline-info" onclick="editarProgramacion(${prog.id})" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button class="btn btn-outline-warning" onclick="ejecutarAhora(${prog.id})" title="Ejecutar ahora">
                        <i class="fas fa-play"></i>
                    </button>
                    <button class="btn btn-outline-success" onclick="toggleActivo(${prog.id}, ${!prog.activo})" 
                            title="${prog.activo ? 'Desactivar' : 'Activar'}">
                        <i class="fas fa-${prog.activo ? 'pause' : 'play'}"></i>
                    </button>
                    <button class="btn btn-outline-danger" onclick="eliminarProgramacion(${prog.id})" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>
            </td>
        `;
            tbody.appendChild(tr);
        });

        // Reinicializar DataTable
        if ($.fn.DataTable.isDataTable('#programacionesTable')) {
            $('#programacionesTable').DataTable().destroy();
        }

        $('#programacionesTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [
                [3, 'asc']
            ], // Ordenar por próxima ejecución
            pageLength: 10,
            responsive: true
        });
    }

    function cargarHistorial() {
        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'historial'
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    actualizarTablaHistorial(data.historial);
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function actualizarTablaHistorial(historial) {
        const tbody = document.getElementById('historialBody');
        tbody.innerHTML = '';

        historial.forEach(item => {
            const tr = document.createElement('tr');
            const estadoBadge = item.estado === 'exitoso' ?
                '<span class="badge bg-success">Exitoso</span>' :
                '<span class="badge bg-danger">Error</span>';

            tr.innerHTML = `
            <td>${new Date(item.fecha_ejecucion).toLocaleString()}</td>
            <td>${getTipoReporteNombre(item.tipo_reporte)}</td>
            <td>${estadoBadge}</td>
            <td>${item.destinatarios.substring(0, 30)}...</td>
            <td>${item.tamaño_archivo || '-'}</td>
            <td>${item.error_mensaje || '-'}</td>
            <td>
                ${item.archivo_path ? 
                    `<button class="btn btn-outline-primary btn-sm" onclick="descargarArchivo('${item.archivo_path}')">
                        <i class="fas fa-download"></i> Descargar
                    </button>` : '-'
                }
            </td>
        `;
            tbody.appendChild(tr);
        });

        // Reinicializar DataTable
        if ($.fn.DataTable.isDataTable('#historialTable')) {
            $('#historialTable').DataTable().destroy();
        }

        $('#historialTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.11.5/i18n/es-ES.json'
            },
            order: [
                [0, 'desc']
            ], // Ordenar por fecha
            pageLength: 10,
            responsive: true
        });
    }

    function getTipoReporteNombre(tipo) {
        const tipos = {
            'stock': 'Estado de Stock',
            'ventas': 'Reporte de Ventas',
            'movimientos': 'Movimientos de Inventario',
            'consumo': 'Análisis de Consumo',
            'alertas': 'Alertas de Stock'
        };
        return tipos[tipo] || tipo;
    }

    function previsualizarReporte() {
        const formData = new FormData(document.getElementById('nuevaProgramacionForm'));
        const data = Object.fromEntries(formData);

        if (!data.tipo_reporte) {
            alert('Seleccione un tipo de reporte para la vista previa');
            return;
        }

        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'preview',
                    ...data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('previewContent').innerHTML = data.preview_html;
                    new bootstrap.Modal(document.getElementById('previewModal')).show();
                } else {
                    alert('Error al generar vista previa: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al generar vista previa');
            });
    }

    function ejecutarAhora(programacionId) {
        if (confirm('¿Desea ejecutar este reporte ahora?')) {
            fetch('?page=reportes&action=programar-reportes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'ejecutar_ahora',
                        programacion_id: programacionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Reporte ejecutado exitosamente');
                        cargarHistorial();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al ejecutar el reporte');
                });
        }
    }

    function toggleActivo(programacionId, activo) {
        const accion = activo ? 'activar' : 'desactivar';
        if (confirm(`¿Desea ${accion} esta programación?`)) {
            fetch('?page=reportes&action=programar-reportes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'toggle_activo',
                        programacion_id: programacionId,
                        activo: activo
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cargarProgramaciones();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al cambiar estado');
                });
        }
    }

    function eliminarProgramacion(programacionId) {
        if (confirm('¿Está seguro de eliminar esta programación? Esta acción no se puede deshacer.')) {
            fetch('?page=reportes&action=programar-reportes', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'eliminar',
                        programacion_id: programacionId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        cargarProgramaciones();
                    } else {
                        alert('Error: ' + data.message);
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Error al eliminar');
                });
        }
    }

    function editarProgramacion(programacionId) {
        // Cargar datos de la programación y mostrar modal de edición
        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'obtener',
                    programacion_id: programacionId
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const prog = data.programacion;
                    document.getElementById('edit_programacion_id').value = prog.id;
                    document.getElementById('edit_tipo_reporte').value = prog.tipo_reporte;
                    document.getElementById('edit_frecuencia').value = prog.frecuencia;
                    document.getElementById('edit_hora_envio').value = prog.hora_envio;
                    document.getElementById('edit_destinatarios').value = prog.destinatarios;

                    new bootstrap.Modal(document.getElementById('editModal')).show();
                }
            })
            .catch(error => console.error('Error:', error));
    }

    function guardarEdicion() {
        const formData = new FormData(document.getElementById('editarProgramacionForm'));
        const data = Object.fromEntries(formData);

        fetch('?page=reportes&action=programar-reportes', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'actualizar',
                    ...data
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    bootstrap.Modal.getInstance(document.getElementById('editModal')).hide();
                    cargarProgramaciones();
                    alert('Programación actualizada exitosamente');
                } else {
                    alert('Error: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al actualizar');
            });
    }

    function actualizarListaProgramaciones() {
        cargarProgramaciones();
        cargarHistorial();
    }

    function descargarArchivo(archivePath) {
        window.open(`?page=reportes&action=descargar&file=${encodeURIComponent(archivePath)}`, '_blank');
    }

    function descargarPreview() {
        // Implementar descarga de vista previa si es necesario
        alert('Funcionalidad de descarga de vista previa en desarrollo');
    }
</script>

<?php include_once '../app/views/layout/footer.php'; ?>