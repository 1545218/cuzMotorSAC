<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3>Alertas del Sistema</h3>
                    <div>
                        <button type="button" class="btn btn-info btn-sm" onclick="verificarStock()">
                            <i class="fas fa-sync"></i> Verificar Stock
                        </button>
                        <button type="button" class="btn btn-success btn-sm" onclick="marcarTodasResueltas()">
                            <i class="fas fa-check-double"></i> Marcar Todas como Resueltas
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <?php if (empty($alertas)): ?>
                        <div class="alert alert-success text-center">
                            <i class="fas fa-check-circle fa-2x mb-2"></i>
                            <h5>¡No hay alertas pendientes!</h5>
                            <p>Todo está funcionando correctamente.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Tipo</th>
                                        <th>Mensaje</th>
                                        <th>Fecha</th>
                                        <th>Estado</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alertas as $alerta): ?>
                                        <tr id="alerta-<?= $alerta['id_alerta'] ?>">
                                            <td>
                                                <?php
                                                $iconos = [
                                                    'stock_bajo' => '<span class="badge bg-warning"><i class="fas fa-exclamation-triangle"></i> Stock Bajo</span>',
                                                    'sin_stock' => '<span class="badge bg-danger"><i class="fas fa-times-circle"></i> Sin Stock</span>',
                                                    'sistema' => '<span class="badge bg-info"><i class="fas fa-cog"></i> Sistema</span>',
                                                    'otro' => '<span class="badge bg-secondary"><i class="fas fa-info-circle"></i> Otro</span>'
                                                ];
                                                echo $iconos[$alerta['tipo']] ?? $iconos['otro'];
                                                ?>
                                            </td>
                                            <td><?= htmlspecialchars($alerta['mensaje']) ?></td>
                                            <td><?= date('d/m/Y H:i', strtotime($alerta['fecha'])) ?></td>
                                            <td>
                                                <?php if ($alerta['estado'] == 'pendiente'): ?>
                                                    <span class="badge bg-warning">Pendiente</span>
                                                <?php else: ?>
                                                    <span class="badge bg-success">Resuelta</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($alerta['estado'] == 'pendiente'): ?>
                                                    <button type="button" class="btn btn-sm btn-success"
                                                        onclick="marcarResuelta(<?= $alerta['id_alerta'] ?>)">
                                                        <i class="fas fa-check"></i> Resolver
                                                    </button>
                                                <?php endif; ?>
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
</div>

<script>
    function verificarStock() {
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verificando...';
        btn.disabled = true;

        fetch('?page=alertas&action=verificarStock', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire('Éxito', data.mensaje, 'success').then(() => {
                        location.reload();
                    });
                } else {
                    Swal.fire('Error', data.mensaje, 'error');
                }
            })
            .catch(error => {
                // Error de red, ya se maneja visualmente
                Swal.fire('Error', 'Error de conexión', 'error');
            })
            .finally(() => {
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
    }

    function marcarResuelta(idAlerta) {
        Swal.fire({
            title: '¿Estás seguro?',
            text: "¿Deseas marcar esta alerta como resuelta?",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Sí, resolver',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('id_alerta', idAlerta);

                fetch('?page=alertas&action=marcarResuelta', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Animar la eliminación de la fila
                            const fila = document.getElementById('alerta-' + idAlerta);
                            fila.style.opacity = '0.5';
                            fila.style.transition = 'opacity 0.3s ease';

                            setTimeout(() => {
                                fila.remove();

                                // Verificar si quedan alertas
                                const filas = document.querySelectorAll('tbody tr');
                                if (filas.length === 0) {
                                    location.reload(); // Recargar para mostrar mensaje "sin alertas"
                                }
                            }, 300);

                            Swal.fire('Éxito', data.mensaje, 'success');
                        } else {
                            Swal.fire('Error', data.mensaje, 'error');
                        }
                    })
                    .catch(error => {
                        // Error de red, ya se maneja visualmente
                        Swal.fire('Error', 'Error de conexión', 'error');
                    });
            }
        });
    }

    function marcarTodasResueltas() {
        const alertas = document.querySelectorAll('tbody tr');
        if (alertas.length === 0) {
            Swal.fire('Info', 'No hay alertas pendientes para resolver', 'info');
            return;
        }

        Swal.fire({
            title: '¿Estás seguro?',
            text: `Se marcarán todas las ${alertas.length} alertas como resueltas`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#28a745',
            cancelButtonColor: '#d33',
            confirmButtonText: 'Sí, marcar todas',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Obtener todos los IDs de alertas
                const ids = [];
                alertas.forEach(fila => {
                    const match = fila.id.match(/alerta-(\d+)/);
                    if (match) {
                        ids.push(match[1]);
                    }
                });

                if (ids.length === 0) {
                    Swal.fire('Error', 'No se pudieron obtener los IDs de las alertas', 'error');
                    return;
                }

                // Mostrar progreso
                Swal.fire({
                    title: 'Procesando...',
                    text: 'Marcando alertas como resueltas',
                    allowOutsideClick: false,
                    didOpen: () => {
                        Swal.showLoading();
                    }
                });

                // Marcar todas como resueltas una por una
                let resueltas = 0;
                const total = ids.length;

                const procesarAlerta = (index) => {
                    if (index >= total) {
                        // Todas procesadas
                        Swal.fire('Éxito', `Se resolvieron ${resueltas} de ${total} alertas`, 'success').then(() => {
                            location.reload();
                        });
                        return;
                    }

                    const formData = new FormData();
                    formData.append('id_alerta', ids[index]);

                    fetch('?page=alertas&action=marcarResuelta', {
                            method: 'POST',
                            body: formData
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                resueltas++;
                            }
                            // Procesar siguiente alerta
                            procesarAlerta(index + 1);
                        })
                        .catch(error => {
                            // Error procesando alerta
                            // Continuar con la siguiente aunque haya error
                            procesarAlerta(index + 1);
                        });
                };

                // Iniciar procesamiento
                procesarAlerta(0);
            }
        });
    }

    // Actualizar contador de alertas en el header cada 30 segundos
    setInterval(() => {
        fetch('?page=alertas&action=contarPendientes')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contador = document.getElementById('alertas-count');
                    if (contador) {
                        if (data.total > 0) {
                            contador.textContent = data.total;
                            contador.style.display = 'inline';
                        } else {
                            contador.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                /* Error actualizando contador */ });
    }, 30000);

    // Actualizar contador al cargar la página
    document.addEventListener('DOMContentLoaded', function() {
        fetch('?page=alertas&action=contarPendientes')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const contador = document.getElementById('alertas-count');
                    if (contador) {
                        if (data.total > 0) {
                            contador.textContent = data.total;
                            contador.style.display = 'inline';
                        } else {
                            contador.style.display = 'none';
                        }
                    }
                }
            })
            .catch(error => {
                /* Error actualizando contador */ });
    });
</script>