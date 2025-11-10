<!-- Toast de notificación Bootstrap -->
<div class="position-fixed top-0 end-0 p-3" style="z-index: 9999">
    <div id="toastNotificacion" class="toast align-items-center text-bg-primary border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMensaje">
                <!-- Mensaje dinámico -->
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Cerrar"></button>
        </div>
    </div>
</div>
<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header del conteo -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-flag-checkered me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h4>
                    <div>
                        <a href="?page=inventario&action=realizarConteo&id=<?= $conteo['id_inventario'] ?>" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver al Conteo
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Usuario:</strong> <?= htmlspecialchars(($conteo['usuario_nombre'] ?? '') . ' ' . ($conteo['usuario_apellido'] ?? '')) ?></p>
                            <p class="mb-1"><strong>Fecha Inicio:</strong> <?= isset($conteo['fecha']) ? date('d/m/Y H:i', strtotime($conteo['fecha'])) : 'No disponible' ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Estado:</strong>
                                <span class="badge bg-warning">En Proceso de Finalización</span>
                            </p>
                            <p class="mb-1"><strong>Observaciones:</strong> <?= htmlspecialchars($conteo['observaciones'] ?? 'Sin observaciones') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Resumen de diferencias -->
            <?php
            $diferenciasEncontradas = [];
            $totalDiferencias = 0;
            foreach ($detalles as $detalle) {
                if ($detalle['diferencia'] != 0) {
                    $diferenciasEncontradas[] = $detalle;
                    $totalDiferencias++;
                }
            }
            ?>

            <?php if (empty($diferenciasEncontradas)): ?>
                <!-- Sin diferencias -->
                <div class="card border-success">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-circle me-2"></i>
                            Conteo Completado Sin Diferencias
                        </h5>
                    </div>
                    <div class="card-body text-center py-5">
                        <i class="fas fa-thumbs-up fa-3x text-success mb-3"></i>
                        <h4>¡Excelente trabajo!</h4>
                        <p class="lead">El inventario físico coincide perfectamente con el sistema.</p>
                        <p class="text-muted">No se requieren ajustes de stock.</p>

                        <div class="mt-4">
                            <button type="button" class="btn btn-success btn-lg" id="finalizarSinAjustesBtn">
                                <i class="fas fa-check me-2"></i> Completar Conteo
                            </button>
                        </div>
                    </div>
                </div>

            <?php else: ?>
                <!-- Con diferencias -->
                <div class="card border-warning mb-4">
                    <div class="card-header bg-warning">
                        <h5 class="mb-0">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Diferencias Encontradas (<?= $totalDiferencias ?> productos)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Se han encontrado diferencias entre el stock del sistema y el conteo físico.
                            Revise cuidadosamente antes de aplicar los ajustes.
                        </div>

                        <!-- Tabla de diferencias -->
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-light">
                                    <tr>
                                        <th>Producto</th>
                                        <th width="12%">Stock Sistema</th>
                                        <th width="12%">Conteo Físico</th>
                                        <th width="12%">Diferencia</th>
                                        <th width="15%">Valor Diferencia</th>
                                        <th width="15%">Aplicar Ajuste</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($diferenciasEncontradas as $diferencia): ?>
                                        <tr class="diferencia-row" data-detalle-id="<?= $diferencia['id'] ?>">
                                            <td>
                                                <div class="d-flex">
                                                    <div>
                                                        <div class="fw-semibold"><?= htmlspecialchars($diferencia['producto_nombre']) ?></div>
                                                        <small class="text-muted">
                                                            Código: <?= htmlspecialchars($diferencia['codigo']) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info fs-6">
                                                    <?= number_format($diferencia['stock_sistema'] ?? 0, 0) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary fs-6">
                                                    <?= number_format($diferencia['stock_fisico'] ?? 0, 0) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php if ($diferencia['diferencia'] > 0): ?>
                                                    <span class="badge bg-success fs-6">
                                                        +<?= number_format($diferencia['diferencia'], 0) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-danger fs-6">
                                                        <?= number_format($diferencia['diferencia'], 0) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php
                                                $valorUnitario = $diferencia['precio_compra'] ?? 0;
                                                $valorDiferencia = $diferencia['diferencia'] * $valorUnitario;
                                                ?>
                                                <?php if ($valorDiferencia > 0): ?>
                                                    <span class="text-success">
                                                        +S/ <?= number_format($valorDiferencia, 2) ?>
                                                    </span>
                                                <?php elseif ($valorDiferencia < 0): ?>
                                                    <span class="text-danger">
                                                        -S/ <?= number_format(abs($valorDiferencia), 2) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">S/ 0.00</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="form-check">
                                                    <input class="form-check-input aplicar-ajuste-check"
                                                        type="checkbox"
                                                        id="ajuste_<?= $diferencia['id'] ?>"
                                                        data-detalle-id="<?= $diferencia['id'] ?>"
                                                        checked>
                                                    <label class="form-check-label" for="ajuste_<?= $diferencia['id'] ?>">
                                                        <small>Aplicar</small>
                                                    </label>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Resumen de ajustes -->
                        <div class="row mt-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Resumen de Ajustes</h6>
                                        <div class="row text-center">
                                            <div class="col-md-3">
                                                <div class="fw-bold text-success fs-4" id="incrementos">0</div>
                                                <small class="text-muted">Incrementos</small>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="fw-bold text-danger fs-4" id="decrementos">0</div>
                                                <small class="text-muted">Decrementos</small>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="fw-bold text-info fs-4" id="totalSeleccionados"><?= count($diferenciasEncontradas) ?></div>
                                                <small class="text-muted">Seleccionados</small>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="fw-bold text-primary fs-4" id="valorTotal">S/ 0.00</div>
                                                <small class="text-muted">Impacto Total</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Observaciones finales -->
                        <div class="mt-4">
                            <label for="observacionesFinales" class="form-label">
                                <strong>Observaciones del Conteo (Opcional)</strong>
                            </label>
                            <textarea class="form-control" id="observacionesFinales" rows="3"
                                placeholder="Agregue cualquier observación sobre las diferencias encontradas..."></textarea>
                        </div>

                        <!-- Acciones -->
                        <div class="d-flex justify-content-end gap-2 mt-4">
                            <button type="button" class="btn btn-outline-secondary" id="seleccionarTodosBtn">
                                <i class="fas fa-check-square me-1"></i> Seleccionar Todos
                            </button>
                            <button type="button" class="btn btn-outline-secondary" id="deseleccionarTodosBtn">
                                <i class="fas fa-square me-1"></i> Deseleccionar Todos
                            </button>
                            <button type="button" class="btn btn-success btn-lg" id="aplicarAjustesBtn">
                                <i class="fas fa-save me-2"></i> Aplicar Ajustes Seleccionados
                            </button>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Historial de acciones -->
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Historial del Conteo
                    </h6>
                </div>
                <div class="card-body">
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">Conteo Iniciado</div>
                                <small class="text-muted">
                                    <?= isset($conteo['fecha']) ? date('d/m/Y H:i', strtotime($conteo['fecha'])) : 'No disponible' ?>
                                    por <?= htmlspecialchars(($conteo['usuario_nombre'] ?? '') . ' ' . ($conteo['usuario_apellido'] ?? '')) ?>
                                </small>
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="timeline-marker bg-warning"></div>
                            <div class="timeline-content">
                                <div class="fw-semibold">En Proceso de Finalización</div>
                                <small class="text-muted">
                                    <?= date('d/m/Y H:i') ?> - Revisando diferencias encontradas
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .timeline {
        position: relative;
        padding-left: 30px;
    }

    .timeline-item {
        position: relative;
        padding-bottom: 20px;
    }

    .timeline-item:not(:last-child):before {
        content: '';
        position: absolute;
        left: -21px;
        top: 25px;
        width: 2px;
        height: calc(100% - 10px);
        background: #dee2e6;
    }

    .timeline-marker {
        position: absolute;
        left: -25px;
        top: 5px;
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid white;
    }

    .timeline-content {
        margin-left: 10px;
    }
</style>

<script>
    // Usar funciones del common.js centralizado
    function mostrarToast(mensaje, tipo = 'primary') {
        if (typeof CruzMotor !== 'undefined') {
            CruzMotor.mostrarToast(mensaje, tipo);
        } else {
            alert(mensaje); // Fallback
        }
    }

    function waitForJQuery(callback) {
        if (typeof CruzMotor !== 'undefined') {
            CruzMotor.waitForJQuery(callback);
        } else if (typeof jQuery !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForJQuery(callback), 100);
        }
    }

    waitForJQuery(function() {
        const $ = jQuery;

        // Actualizar resumen cuando cambie la selección
        function actualizarResumen() {
            let incrementos = 0;
            let decrementos = 0;
            let valorTotal = 0;
            let seleccionados = 0;

            $('.aplicar-ajuste-check:checked').each(function() {
                const $row = $(this).closest('.diferencia-row');
                const diferencia = parseInt($row.find('.badge:contains("+")')
                        .text().replace('+', '')) ||
                    parseInt($row.find('.badge:not(:contains("+"))')
                        .text()) || 0;

                seleccionados++;

                if (diferencia > 0) {
                    incrementos++;
                } else if (diferencia < 0) {
                    decrementos++;
                }

                // Calcular valor (esto es una aproximación)
                const valorTexto = $row.find('.text-success, .text-danger').text();
                const valor = parseFloat(valorTexto.replace(/[^0-9.-]/g, '')) || 0;
                valorTotal += valor;
            });

            $('#incrementos').text(incrementos);
            $('#decrementos').text(decrementos);
            $('#totalSeleccionados').text(seleccionados);
            $('#valorTotal').text('S/ ' + Math.abs(valorTotal).toFixed(2));
        }

        // Eventos de checkboxes
        $('.aplicar-ajuste-check').on('change', actualizarResumen);

        // Seleccionar/Deseleccionar todos
        $('#seleccionarTodosBtn').on('click', function() {
            $('.aplicar-ajuste-check').prop('checked', true);
            actualizarResumen();
        });

        $('#deseleccionarTodosBtn').on('click', function() {
            $('.aplicar-ajuste-check').prop('checked', false);
            actualizarResumen();
        });

        // Aplicar ajustes
        $('#aplicarAjustesBtn').on('click', function() {
            const ajustesSeleccionados = $('.aplicar-ajuste-check:checked').length;

            if (ajustesSeleccionados === 0) {
                mostrarToast('Debe seleccionar al menos un ajuste para aplicar', 'warning');
                return;
            }

            if (!confirm('¿Está seguro de que desea aplicar los ajustes seleccionados? Esta acción no se puede deshacer.')) {
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Aplicando Ajustes...');

            // Recopilar datos de ajustes
            const ajustes = [];
            $('.aplicar-ajuste-check:checked').each(function() {
                ajustes.push($(this).data('detalle-id'));
            });

            $.ajax({
                url: '?page=inventario&action=aplicarAjustes',
                method: 'POST',
                data: {
                    id_inventario: <?= $conteo['id_inventario'] ?>,
                    ajustes: ajustes,
                    observaciones: $('#observacionesFinales').val(),
                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        window.location.href = '?page=inventario&action=conteo';
                    } else {
                        mostrarToast('Error: ' + response.message, 'danger');
                        $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Aplicar Ajustes Seleccionados');
                    }
                },
                error: function() {
                    mostrarToast('Error al aplicar los ajustes', 'danger');
                    $btn.prop('disabled', false).html('<i class="fas fa-save me-2"></i>Aplicar Ajustes Seleccionados');
                }
            });
        });

        // Finalizar sin ajustes
        $('#finalizarSinAjustesBtn').on('click', function() {
            // Mostrar modal de confirmación en lugar de confirm()
            const confirmModal = `
                <div class="modal fade" id="modalConfirmacion" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">Confirmar Finalización</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                ¿Confirma que desea completar el conteo sin aplicar ajustes?
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <button type="button" class="btn btn-success" id="confirmarFinalizacion">Completar Conteo</button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Eliminar modal anterior si existe
            $('#modalConfirmacion').remove();

            // Agregar modal al DOM
            $('body').append(confirmModal);

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalConfirmacion'));
            modal.show();

            // Manejar confirmación
            $('#confirmarFinalizacion').on('click', function() {
                modal.hide();

                const $btn = $('#finalizarSinAjustesBtn');
                $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Finalizando...');

                $.ajax({
                    url: '?page=inventario&action=aplicarAjustes',
                    method: 'POST',
                    data: {
                        id_inventario: <?= $conteo['id_inventario'] ?>,
                        ajustes: [],
                        observaciones: 'Conteo completado sin diferencias',
                        csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                    },
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            window.location.href = '?page=inventario&action=conteo';
                        } else {
                            mostrarToast('Error: ' + response.message, 'danger');
                            $btn.prop('disabled', false).html('<i class="fas fa-check me-2"></i>Completar Conteo');
                        }
                    },
                    error: function() {
                        mostrarToast('Error al finalizar el conteo', 'danger');
                        $btn.prop('disabled', false).html('<i class="fas fa-check me-2"></i>Completar Conteo');
                    }
                });
            });
        });

        // Inicializar resumen
        actualizarResumen();
    });
</script>