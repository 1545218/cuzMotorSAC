<div class="container-fluid px-4 py-3 backups-page">
    <!-- Page Header con breadcrumb mejorado -->
    <div class="d-flex justify-content-between align-items-center mb-5">
        <div>
            <h1 class="h2 mb-2 d-flex align-items-center">
                <i class="fas fa-database text-primary me-3"></i>
                Gestión de Backups del Sistema
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="?page=dashboard" class="text-decoration-none">Dashboard</a></li>
                    <li class="breadcrumb-item active">Backups</li>
                </ol>
            </nav>
        </div>
        <div class="d-flex gap-3">
            <button class="btn btn-outline-primary" onclick="actualizarDatos()" title="Actualizar lista">
                <i class="fas fa-sync-alt me-2"></i>Actualizar
            </button>
            <button class="btn btn-primary" onclick="crearBackup()">
                <i class="fas fa-plus me-2"></i>Crear Backup
            </button>
        </div>
    </div>

    <!-- Estadísticas principales -->
    <div class="row g-4 mb-4">
        <!-- Total Backups -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-total border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-database fa-2x text-white"></i>
                    </div>
                    <div class="h3 mb-1 text-white">
                        <?= number_format($estadisticas['total_backups'] ?? 0) ?>
                    </div>
                    <div class="small text-white-50 mb-0">
                        TOTAL BACKUPS
                    </div>
                    <div class="mt-2">
                        <small class="text-white-50">
                            <i class="fas fa-database me-1"></i>
                            Respaldos generados
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <button class="btn btn-light btn-sm w-100" onclick="crearBackup()">
                        <i class="fas fa-plus me-1"></i>Crear Backup
                    </button>
                </div>
            </div>
        </div>

        <!-- Backups Hoy -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-today border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-calendar-day fa-2x text-white"></i>
                    </div>
                    <div class="h3 mb-1 text-white">
                        <?= number_format($estadisticas['backups_hoy'] ?? 0) ?>
                    </div>
                    <div class="small text-white-50 mb-0">
                        BACKUPS HOY
                    </div>
                    <div class="mt-2">
                        <small class="text-white-50">
                            <i class="fas fa-calendar-day me-1"></i>
                            Realizados hoy
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <button class="btn btn-light btn-sm w-100" onclick="verificarIntegridad()">
                        <i class="fas fa-check-double me-1"></i>Verificar Integridad
                    </button>
                </div>
            </div>
        </div>

        <!-- Archivos OK -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-ok border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-check-circle fa-2x text-white"></i>
                    </div>
                    <div class="h3 mb-1 text-white">
                        <?= number_format($estadisticas['archivos_ok'] ?? 0) ?>
                    </div>
                    <div class="small text-white-50 mb-0">
                        ARCHIVOS OK
                    </div>
                    <div class="mt-2">
                        <small class="text-white-50">
                            <i class="fas fa-check-circle me-1"></i>
                            Disponibles
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <button class="btn btn-light btn-sm w-100" onclick="limpiarBackups()">
                        <i class="fas fa-broom me-1"></i>Limpiar Antiguos
                    </button>
                </div>
            </div>
        </div>

        <!-- Archivos Faltantes -->
        <div class="col-xl-3 col-md-6">
            <div class="card stats-warning border-0 shadow-sm h-100">
                <div class="card-body text-center">
                    <div class="mb-3">
                        <i class="fas fa-exclamation-triangle fa-2x text-white"></i>
                    </div>
                    <div class="h3 mb-1 text-white">
                        <?= number_format($estadisticas['archivos_faltantes'] ?? 0) ?>
                    </div>
                    <div class="small text-white-50 mb-0">
                        ARCHIVOS FALTANTES
                    </div>
                    <div class="mt-2">
                        <small class="text-white-50">
                            <i class="fas fa-exclamation-triangle me-1"></i>
                            Requieren atención
                        </small>
                    </div>
                </div>
                <div class="card-footer bg-transparent border-0 pt-0">
                    <button class="btn btn-light btn-sm w-100" onclick="verificarIntegridad()">
                        <i class="fas fa-search me-1"></i>Verificar Estado
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Lista de backups -->
    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-bottom py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="fas fa-list text-primary me-3"></i>Lista de Backups
                    </h5>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($backups)): ?>
                        <div class="text-center py-5">
                            <div class="mb-4">
                                <i class="fas fa-info-circle fa-4x text-muted opacity-50"></i>
                            </div>
                            <h5 class="text-muted">No hay backups registrados</h5>
                            <p class="text-muted">Haz clic en "Crear Backup" para generar tu primer respaldo del sistema.</p>
                            <button class="btn btn-primary mt-3" onclick="crearBackup()">
                                <i class="fas fa-plus me-2"></i>Crear mi primer backup
                            </button>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="tabla-backups">
                                <thead class="table-dark">
                                    <tr>
                                        <th><i class="fas fa-file-archive me-1"></i>Archivo de Backup</th>
                                        <th><i class="fas fa-calendar me-1"></i>Fecha/Hora</th>
                                        <th><i class="fas fa-user me-1"></i>Realizado por</th>
                                        <th><i class="fas fa-hdd me-1"></i>Tamaño</th>
                                        <th><i class="fas fa-check-circle me-1"></i>Estado</th>
                                        <th><i class="fas fa-cogs me-1"></i>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($backups as $backup): ?>
                                        <tr id="backup-<?php echo $backup['id_backup']; ?>">
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <i class="fas fa-file-archive text-warning me-2"></i>
                                                    <div>
                                                        <div class="fw-bold text-dark">
                                                            <?php echo htmlspecialchars($backup['nombre_archivo']); ?>
                                                        </div>
                                                        <div class="text-muted small">
                                                            Backup #<?php echo $backup['id_backup']; ?>
                                                        </div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php
                                                $fecha = new DateTime($backup['fecha']);
                                                $ahora = new DateTime();
                                                $diferencia = $ahora->diff($fecha);

                                                echo '<div class="text-dark fw-bold">';
                                                echo '<i class="fas fa-calendar-alt text-primary me-1"></i>';
                                                echo $fecha->format('d/m/Y');
                                                echo '</div>';
                                                echo '<div class="text-muted small">';
                                                echo '<i class="fas fa-clock me-1"></i>';
                                                echo $fecha->format('H:i:s');

                                                // Mostrar tiempo transcurrido
                                                if ($diferencia->days > 0) {
                                                    echo ' (' . $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '') . ')';
                                                } elseif ($diferencia->h > 0) {
                                                    echo ' (' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '') . ')';
                                                } else {
                                                    echo ' (hace ' . $diferencia->i . ' min)';
                                                }
                                                echo '</div>';
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                if ($backup['usuario_nombre']) {
                                                    echo '<i class="fas fa-user text-primary me-1"></i>' .
                                                        '<span class="fw-bold">' .
                                                        htmlspecialchars($backup['usuario_nombre'] . ' ' . $backup['usuario_apellido']) .
                                                        '</span>';
                                                } else {
                                                    echo '<i class="fas fa-user-slash text-muted me-1"></i>' .
                                                        '<span class="text-muted fst-italic">Usuario eliminado</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $rutaArchivo = ROOT_PATH . '/storage/backups/' . $backup['nombre_archivo'];
                                                if (file_exists($rutaArchivo)) {
                                                    $tamaño = filesize($rutaArchivo);
                                                    $tamañoFormateado = formatBytes($tamaño);

                                                    // Determinar color del badge basado en el tamaño
                                                    $claseBadge = 'bg-secondary';
                                                    if ($tamaño > 50 * 1024 * 1024) { // > 50MB
                                                        $claseBadge = 'bg-danger';
                                                    } elseif ($tamaño > 10 * 1024 * 1024) { // > 10MB
                                                        $claseBadge = 'bg-warning text-dark';
                                                    } elseif ($tamaño > 1024 * 1024) { // > 1MB
                                                        $claseBadge = 'bg-info';
                                                    } else {
                                                        $claseBadge = 'bg-success';
                                                    }

                                                    echo '<span class="badge ' . $claseBadge . '">' .
                                                        '<i class="fas fa-hdd me-1"></i>' . $tamañoFormateado .
                                                        '</span>';
                                                } else {
                                                    echo '<span class="badge bg-danger">' .
                                                        '<i class="fas fa-exclamation-triangle me-1"></i>Archivo no encontrado' .
                                                        '</span>';
                                                }
                                                ?>
                                            </td>
                                            <td>
                                                <?php
                                                $rutaArchivo = ROOT_PATH . '/storage/backups/' . $backup['nombre_archivo'];
                                                if (file_exists($rutaArchivo)):
                                                ?>
                                                    <span class="badge bg-success">
                                                        <i class="fas fa-check-circle me-1"></i> Disponible
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger">
                                                        <i class="fas fa-times-circle me-1"></i> Faltante
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <?php if (file_exists($rutaArchivo)): ?>
                                                        <button type="button"
                                                            class="btn btn-sm btn-success"
                                                            onclick="descargarBackup(<?php echo $backup['id_backup']; ?>)"
                                                            title="Descargar backup">
                                                            <i class="fas fa-download"></i>
                                                        </button>
                                                        <button type="button"
                                                            class="btn btn-sm btn-info"
                                                            onclick="restaurarBackup(<?php echo $backup['id_backup']; ?>)"
                                                            title="Restaurar backup">
                                                            <i class="fas fa-upload"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                    <button type="button"
                                                        class="btn btn-sm btn-danger"
                                                        onclick="eliminarBackup(<?php echo $backup['id_backup']; ?>)"
                                                        title="Eliminar backup">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Información adicional -->
    <?php if (!empty($estadisticas['ultimo_backup'])): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="alert border-0 shadow-sm" style="background: #ebf8ff; color: #2b6cb0; border-left: 4px solid #4299e1;">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-info-circle fa-lg"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-bold">Último backup realizado</h6>
                            <div class="mb-0">
                                <i class="fas fa-clock me-2"></i>
                                <?php
                                $ultimoBackup = new DateTime($estadisticas['ultimo_backup']);
                                echo $ultimoBackup->format('d/m/Y H:i:s');
                                $ahora = new DateTime();
                                $diferencia = $ahora->diff($ultimoBackup);
                                if ($diferencia->days > 0) {
                                    echo ' (hace ' . $diferencia->days . ' día' . ($diferencia->days > 1 ? 's' : '') . ')';
                                } elseif ($diferencia->h > 0) {
                                    echo ' (hace ' . $diferencia->h . ' hora' . ($diferencia->h > 1 ? 's' : '') . ')';
                                } else {
                                    echo ' (hace ' . $diferencia->i . ' minutos)';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <script>
        // Inicializar DataTables cuando el documento esté listo
        document.addEventListener('DOMContentLoaded', function() {
            // Verificar que jQuery y DataTables estén disponibles
            if (typeof $ !== 'undefined' && $.fn.DataTable) {
                if ($('#tabla-backups').length > 0) {
                    $('#tabla-backups').DataTable({
                        language: {
                            url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/es-ES.json'
                        },
                        responsive: true,
                        pageLength: 10,
                        order: [
                            [1, 'desc']
                        ], // Ordenar por fecha descendente
                        columnDefs: [{
                                orderable: false,
                                targets: [5]
                            }, // Columna de acciones no ordenable
                            {
                                className: "text-center",
                                targets: [3, 4, 5]
                            } // Centrar algunas columnas
                        ],
                        dom: '<"row"<"col-sm-12 col-md-6"l><"col-sm-12 col-md-6">>' +
                            '<"row"<"col-sm-12"tr>>' +
                            '<"row"<"col-sm-12 col-md-5"i><"col-sm-12 col-md-7"p>>',
                        drawCallback: function(settings) {
                            // Reinicializar tooltips después de cada redibujado
                            if (typeof bootstrap !== 'undefined') {
                                const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
                                tooltipTriggerList.map(function(tooltipTriggerEl) {
                                    return new bootstrap.Tooltip(tooltipTriggerEl);
                                });
                            }
                        }
                    });
                }
            } else {
                console.error('jQuery o DataTables no están disponibles');
            }
        });

        // Función para actualizar datos
        function actualizarDatos() {
            Swal.fire({
                title: 'Actualizando...',
                text: 'Obteniendo datos más recientes',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading();
                    // Recargar después de 1 segundo para dar tiempo a la animación
                    setTimeout(() => {
                        window.location.reload();
                    }, 1000);
                }
            });
        }

        // Función para verificar integridad
        function verificarIntegridad() {
            Swal.fire({
                title: 'Verificando integridad...',
                text: 'Comprobando estado de todos los backups',
                allowOutsideClick: false,
                didOpen: () => {
                    Swal.showLoading()
                }
            });

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        let mensaje = `Verificación completada:\n`;
                        mensaje += `✓ Archivos OK: ${data.archivos_ok}\n`;
                        mensaje += `⚠ Archivos faltantes: ${data.archivos_faltantes}\n`;
                        mensaje += `📊 Total verificados: ${data.total_verificados}`;

                        Swal.fire({
                            title: '¡Verificación completa!',
                            text: mensaje,
                            icon: data.archivos_faltantes > 0 ? 'warning' : 'success',
                            confirmButtonText: 'Aceptar'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Error', data.mensaje, 'error');
                    }
                })
                .catch(error => {
                    Swal.fire('Error', 'Error de conexión', 'error');
                });
        }

        // Función para crear un nuevo backup
        function crearBackup() {
            Swal.fire({
                title: '¿Crear nuevo backup?',
                text: 'Se generará un respaldo completo de la base de datos',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Sí, crear backup',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Creando backup...',
                        text: 'Por favor espera mientras se genera el respaldo',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });

                    fetch('?page=backups&action=crear', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('¡Éxito!', data.mensaje, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.mensaje || 'Error desconocido al crear backup', 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Error de conexión', 'error');
                        });
                }
            });
        }

        // Función para descargar backup
        function descargarBackup(id) {
            window.location.href = '?page=backups&action=descargar&id=' + id;
        }

        // Función para restaurar backup
        function restaurarBackup(id) {
            Swal.fire({
                title: '¿Restaurar este backup?',
                text: '¡Esta acción no se puede deshacer! Se reemplazará la base de datos actual.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, restaurar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Restaurando backup...',
                        text: 'Por favor espera, esto puede tomar varios minutos',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading()
                        }
                    });

                    fetch('?page=backups&action=restaurar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: 'id=' + id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('¡Éxito!', data.mensaje, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.mensaje, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Error de conexión', 'error');
                        });
                }
            });
        }

        // Función para eliminar backup
        function eliminarBackup(id) {
            Swal.fire({
                title: '¿Eliminar este backup?',
                text: 'Esta acción no se puede deshacer',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, eliminar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('?page=backups&action=eliminar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            },
                            body: 'id=' + id
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('¡Eliminado!', data.mensaje, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.mensaje, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Error de conexión', 'error');
                        });
                }
            });
        }

        // Función para limpiar backups antiguos
        function limpiarBackups() {
            Swal.fire({
                title: '¿Limpiar backups antiguos?',
                text: 'Se eliminarán backups con más de 30 días de antigüedad',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#f39c12',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Sí, limpiar',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch('?page=backups&action=limpiar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                                'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                            }
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                Swal.fire('¡Limpieza completa!', data.mensaje, 'success').then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire('Error', data.mensaje, 'error');
                            }
                        })
                        .catch(error => {
                            Swal.fire('Error', 'Error de conexión', 'error');
                        });
                }
            });
        }
    </script>

</div> <!-- /.container-fluid -->

<?php
// Función PHP para formatear bytes
function formatBytes($bytes, $precision = 2)
{
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
        $bytes /= 1024;
    }
    return round($bytes, $precision) . ' ' . $units[$i];
}
?>