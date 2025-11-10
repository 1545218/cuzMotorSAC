<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <!-- Header del conteo -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-clipboard-check me-2"></i>
                        <?= htmlspecialchars($title) ?>
                    </h4>
                    <div>
                        <a href="?page=inventario&action=conteo" class="btn btn-secondary btn-sm">
                            <i class="fas fa-arrow-left me-1"></i> Volver
                        </a>
                        <button type="button" class="btn btn-success btn-sm" id="finalizarConteoBtn">
                            <i class="fas fa-check me-1"></i> Finalizar Conteo
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Estado:</strong>
                                <span class="badge bg-warning">En Progreso</span>
                            </p>
                            <p class="mb-1"><strong>Usuario:</strong> <?= htmlspecialchars(($conteo['usuario_nombre'] ?? '') . ' ' . ($conteo['usuario_apellido'] ?? '')) ?></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Fecha Inicio:</strong> <?= isset($conteo['fecha']) ? date('d/m/Y H:i', strtotime($conteo['fecha'])) : 'No disponible' ?></p>
                            <p class="mb-1"><strong>Observaciones:</strong> <?= htmlspecialchars($conteo['observaciones'] ?? 'Sin observaciones') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Formulario de conteo de productos -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-boxes me-2"></i>
                        Productos para Contar
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Filtro de productos -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text">
                                    <i class="fas fa-search"></i>
                                </span>
                                <input type="text" class="form-control" id="filtroProducto"
                                    placeholder="Buscar producto por código o nombre...">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <select class="form-select" id="filtroEstado">
                                <option value="">Todos los productos</option>
                                <option value="pendiente">Sin contar</option>
                                <option value="contado">Ya contados</option>
                                <option value="diferencia">Con diferencias</option>
                            </select>
                        </div>
                    </div>

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-hover" id="tablaProductos">
                            <thead class="table-light">
                                <tr>
                                    <th width="15%">Código</th>
                                    <th width="35%">Producto</th>
                                    <th width="10%">Stock Sistema</th>
                                    <th width="15%">Conteo Físico</th>
                                    <th width="10%">Diferencia</th>
                                    <th width="15%">Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <i class="fas fa-inbox fa-2x text-muted mb-2"></i>
                                            <br>No hay productos para mostrar
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php
                                    // Crear array asociativo con los detalles del conteo
                                    $detallesMap = [];
                                    foreach ($detalles as $detalle) {
                                        $detallesMap[$detalle['id_producto']] = $detalle;
                                    }
                                    ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <?php
                                        $productoId = $producto['id_producto'] ?? $producto['id'] ?? 0;
                                        $productoCodigo = $producto['codigo_barras'] ?? $producto['codigo'] ?? 'Sin código';
                                        $detalle = $detallesMap[$productoId] ?? null;
                                        $stockSistema = $producto['stock_actual'] ?? 0;
                                        $conteoFisico = $detalle ? ($detalle['stock_fisico'] ?? '') : '';
                                        $diferencia = $detalle ? (($detalle['stock_fisico'] ?? 0) - $stockSistema) : '';
                                        $estado = $detalle ? 'contado' : 'pendiente';

                                        if ($detalle && ($detalle['stock_fisico'] ?? 0) != $stockSistema) {
                                            $estado = 'diferencia';
                                        }
                                        ?>
                                        <tr class="producto-row" data-producto-id="<?= $productoId ?>"
                                            data-estado="<?= $estado ?>"
                                            data-codigo="<?= strtolower($productoCodigo ?? '') ?>"
                                            data-nombre="<?= strtolower($producto['nombre'] ?? '') ?>">
                                            <td>
                                                <code><?= htmlspecialchars($productoCodigo) ?></code>
                                            </td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($producto['nombre'] ?? 'Sin nombre') ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($producto['marca_nombre'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= number_format($stockSistema, 0) ?></span>
                                            </td>
                                            <td>
                                                <input type="number"
                                                    class="form-control form-control-sm conteo-input"
                                                    id="conteo_<?= $productoId ?>"
                                                    min="0"
                                                    step="1"
                                                    value="<?= $conteoFisico ?>"
                                                    data-producto-id="<?= $productoId ?>"
                                                    data-stock-sistema="<?= $stockSistema ?>">
                                            </td>
                                            <td>
                                                <span class="diferencia-badge" id="diferencia_<?= $productoId ?>">
                                                    <?php if ($diferencia !== ''): ?>
                                                        <?php if ($diferencia > 0): ?>
                                                            <span class="badge bg-success">+<?= $diferencia ?></span>
                                                        <?php elseif ($diferencia < 0): ?>
                                                            <span class="badge bg-danger"><?= $diferencia ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">0</span>
                                                        <?php endif; ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">-</span>
                                                    <?php endif; ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button type="button"
                                                    class="btn btn-primary btn-sm guardar-conteo-btn"
                                                    data-producto-id="<?= $productoId ?>">
                                                    <i class="fas fa-save"></i> Guardar
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Resumen de conteo -->
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Resumen del Conteo</h6>
                                    <div class="row text-center">
                                        <div class="col-md-3">
                                            <div class="fw-bold text-primary fs-4" id="totalProductos"><?= count($productos) ?></div>
                                            <small class="text-muted">Total Productos</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fw-bold text-success fs-4" id="productosContados"><?= count($detalles) ?></div>
                                            <small class="text-muted">Contados</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fw-bold text-warning fs-4" id="productosPendientes"><?= count($productos) - count($detalles) ?></div>
                                            <small class="text-muted">Pendientes</small>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="fw-bold text-danger fs-4" id="productosConDiferencia">
                                                <?php
                                                $diferencias = 0;
                                                foreach ($detalles as $det) {
                                                    $prod = array_filter($productos, function ($p) use ($det) {
                                                        $pId = $p['id_producto'] ?? $p['id'] ?? 0;
                                                        return $pId == $det['id_producto'];
                                                    });
                                                    $prod = reset($prod);
                                                    if ($prod && ($det['stock_fisico'] ?? 0) != ($prod['stock_actual'] ?? 0)) {
                                                        $diferencias++;
                                                    }
                                                }
                                                echo $diferencias;
                                                ?>
                                            </div>
                                            <small class="text-muted">Con Diferencias</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Esperar a que jQuery esté disponible
    function waitForJQuery(callback) {
        if (typeof jQuery !== 'undefined') {
            callback();
        } else {
            setTimeout(() => waitForJQuery(callback), 100);
        }
    }

    waitForJQuery(function() {
        const $ = jQuery;
        console.log('jQuery cargado, inicializando eventos');

        // Filtro de productos
        $('#filtroProducto').on('input', function() {
            const filtro = $(this).val().toLowerCase();
            filtrarProductos();
        });

        $('#filtroEstado').on('change', function() {
            filtrarProductos();
        });

        function filtrarProductos() {
            const filtroTexto = $('#filtroProducto').val().toLowerCase();
            const filtroEstado = $('#filtroEstado').val();

            $('.producto-row').each(function() {
                const $row = $(this);
                const codigo = $row.data('codigo');
                const nombre = $row.data('nombre');
                const estado = $row.data('estado');

                const coincideTexto = filtroTexto === '' ||
                    codigo.includes(filtroTexto) ||
                    nombre.includes(filtroTexto);

                const coincideEstado = filtroEstado === '' || estado === filtroEstado;

                if (coincideTexto && coincideEstado) {
                    $row.show();
                } else {
                    $row.hide();
                }
            });
        }

        // Calcular diferencia en tiempo real
        $('.conteo-input').on('input', function() {
            const $input = $(this);
            const productoId = $input.data('producto-id');
            const stockSistema = parseInt($input.data('stock-sistema'));
            const conteoFisico = parseInt($input.val()) || 0;
            const diferencia = conteoFisico - stockSistema;

            const $diferenciaBadge = $('#diferencia_' + productoId);

            if ($input.val() === '') {
                $diferenciaBadge.html('<span class="text-muted">-</span>');
            } else if (diferencia > 0) {
                $diferenciaBadge.html('<span class="badge bg-success">+' + diferencia + '</span>');
            } else if (diferencia < 0) {
                $diferenciaBadge.html('<span class="badge bg-danger">' + diferencia + '</span>');
            } else {
                $diferenciaBadge.html('<span class="badge bg-secondary">0</span>');
            }
        });

        // Guardar conteo individual
        $('.guardar-conteo-btn').on('click', function() {
            const $btn = $(this);
            const productoId = $btn.data('producto-id');
            const $input = $('#conteo_' + productoId);
            const cantidad = $input.val();

            if (cantidad === '') {
                alert('Por favor, ingrese una cantidad');
                $input.focus();
                return;
            }

            $btn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

            $.ajax({
                url: '?page=inventario&action=guardarConteoProducto',
                method: 'POST',
                data: {
                    id_inventario: <?= $conteo['id_inventario'] ?>,
                    id_producto: productoId,
                    stock_fisico: cantidad,
                    csrf_token: '<?= $_SESSION['csrf_token'] ?? '' ?>'
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $btn.removeClass('btn-primary').addClass('btn-success')
                            .html('<i class="fas fa-check"></i> Guardado');

                        // Actualizar estado de la fila
                        const $row = $btn.closest('.producto-row');
                        const stockSistema = parseInt($input.data('stock-sistema'));
                        const conteoFisico = parseInt(cantidad);

                        if (conteoFisico !== stockSistema) {
                            $row.attr('data-estado', 'diferencia');
                        } else {
                            $row.attr('data-estado', 'contado');
                        }

                        // Actualizar resumen
                        actualizarResumen();

                        setTimeout(() => {
                            $btn.removeClass('btn-success').addClass('btn-primary')
                                .html('<i class="fas fa-save"></i> Guardar');
                        }, 2000);
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function() {
                    alert('Error al guardar el conteo');
                },
                complete: function() {
                    $btn.prop('disabled', false);
                }
            });
        });

        // Actualizar resumen
        function actualizarResumen() {
            let contados = 0;
            let conDiferencia = 0;

            $('.producto-row').each(function() {
                const estado = $(this).attr('data-estado');
                if (estado === 'contado' || estado === 'diferencia') {
                    contados++;
                    if (estado === 'diferencia') {
                        conDiferencia++;
                    }
                }
            });

            const total = $('.producto-row').length;
            const pendientes = total - contados;

            $('#productosContados').text(contados);
            $('#productosPendientes').text(pendientes);
            $('#productosConDiferencia').text(conDiferencia);
        }

        // Finalizar conteo - Versión con modal Bootstrap
        $('#finalizarConteoBtn').on('click', function() {
            // Crear modal de confirmación elegante
            const confirmModal = `
                <div class="modal fade" id="modalFinalizarConteo" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title">
                                    <i class="fas fa-flag-checkered me-2"></i>
                                    Finalizar Conteo
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                            </div>
                            <div class="modal-body">
                                <p>¿Está seguro de que desea finalizar el conteo físico?</p>
                                <small class="text-muted">
                                    Esta acción revisará las diferencias y le permitirá aplicar ajustes.
                                </small>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                    <i class="fas fa-times me-1"></i> Cancelar
                                </button>
                                <button type="button" class="btn btn-success" id="confirmarFinalizar">
                                    <i class="fas fa-check me-1"></i> Finalizar Conteo
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Eliminar modal anterior si existe
            $('#modalFinalizarConteo').remove();

            // Agregar modal al DOM
            $('body').append(confirmModal);

            // Mostrar modal
            const modal = new bootstrap.Modal(document.getElementById('modalFinalizarConteo'));
            modal.show();

            // Manejar confirmación
            $('#confirmarFinalizar').on('click', function() {
                const url = '?page=inventario&action=finalizarConteo&id=<?= $conteo['id_inventario'] ?>';
                window.location.href = url;
            });
        });

        // Auto-guardar al presionar Enter
        $('.conteo-input').on('keypress', function(e) {
            if (e.which === 13) { // Enter
                const productoId = $(this).data('producto-id');
                $('.guardar-conteo-btn[data-producto-id="' + productoId + '"]').click();
            }
        });
    });
</script>