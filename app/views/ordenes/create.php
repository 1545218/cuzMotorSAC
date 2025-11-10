<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus"></i> Nueva Orden de Trabajo
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Mensajes de error -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Mensajes de éxito -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <form method="POST" action="?page=ordenes&action=store" id="formOrden">
                        <div class="row">
                            <!-- Cliente -->
                            <div class="col-md-12 mb-3">
                                <label for="id_cliente" class="form-label">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php if (isset($clientes) && is_array($clientes) && count($clientes) > 0): ?>
                                        <?php foreach ($clientes as $index => $cliente): ?>
                                            <?php
                                            // Obtener datos del cliente usando los campos reales
                                            $clienteId = $cliente['id_cliente'] ?? null;

                                            // Nombre completo
                                            if (!empty($cliente['nombres']) && !empty($cliente['apellidos'])) {
                                                $clienteNombre = trim($cliente['nombres'] . ' ' . $cliente['apellidos']);
                                            } elseif (!empty($cliente['nombre']) && !empty($cliente['apellido'])) {
                                                $clienteNombre = trim($cliente['nombre'] . ' ' . $cliente['apellido']);
                                            } elseif (!empty($cliente['nombres'])) {
                                                $clienteNombre = trim($cliente['nombres']);
                                            } elseif (!empty($cliente['nombre'])) {
                                                $clienteNombre = trim($cliente['nombre']);
                                            } else {
                                                $clienteNombre = 'Sin nombre';
                                            }

                                            // Documento
                                            $clienteDoc = $cliente['dni_ruc'] ?? $cliente['numero_documento'] ?? '';

                                            if (!$clienteId) {
                                                error_log("Cliente sin ID en índice $index: " . print_r($cliente, true));
                                                continue;
                                            }
                                            ?>
                                            <option value="<?= $clienteId ?>"
                                                <?= (isset($_POST['id_cliente']) && $_POST['id_cliente'] == $clienteId) ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($clienteNombre) ?>
                                                <?php if ($clienteDoc): ?>
                                                    - <?= htmlspecialchars($clienteDoc) ?>
                                                <?php endif; ?>
                                            </option>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <option value="" disabled>No hay clientes disponibles</option>
                                    <?php endif; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione el cliente para esta orden de trabajo
                                </small>
                            </div>

                            <!-- Vehículo -->
                            <div class="col-md-12 mb-3">
                                <label for="id_vehiculo" class="form-label">
                                    Vehículo <span class="text-muted">(Opcional)</span>
                                </label>
                                <select class="form-control" id="id_vehiculo" name="id_vehiculo">
                                    <option value="">Sin vehículo específico</option>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione un vehículo del cliente (se carga automáticamente al elegir cliente)
                                </small>

                                <!-- Mostrar todos los vehículos inicialmente -->
                                <div id="vehiculosDisponibles" class="mt-2" style="display: none;">
                                    <strong>Vehículos disponibles:</strong>
                                    <div class="row">
                                        <?php foreach ($vehiculos as $vehiculo): ?>
                                            <div class="col-md-6">
                                                <div class="card border-light mb-2">
                                                    <div class="card-body p-2">
                                                        <h6 class="card-title mb-1">
                                                            <?= htmlspecialchars($vehiculo['placa']) ?>
                                                        </h6>
                                                        <p class="card-text small text-muted mb-1">
                                                            <?= htmlspecialchars($vehiculo['vehiculo_marca'] . ' ' . $vehiculo['vehiculo_modelo']) ?>
                                                        </p>
                                                        <p class="card-text small">
                                                            <strong>Cliente:</strong>
                                                            <?= htmlspecialchars($vehiculo['cliente_nombre'] ?? 'N/A') ?>
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Estado -->
                            <div class="col-md-6 mb-3">
                                <label for="estado" class="form-label">Estado Inicial</label>
                                <select class="form-control" id="estado" name="estado">
                                    <option value="abierta" selected>Abierta</option>
                                    <option value="en_proceso">En Proceso</option>
                                    <option value="cerrada">Cerrada</option>
                                </select>
                                <small class="form-text text-muted">
                                    Estado inicial de la orden de trabajo
                                </small>
                            </div>

                            <!-- Fecha -->
                            <div class="col-md-6 mb-3">
                                <label for="fecha" class="form-label">Fecha y Hora</label>
                                <input type="datetime-local"
                                    class="form-control"
                                    id="fecha"
                                    name="fecha"
                                    value="<?= date('Y-m-d\TH:i') ?>"
                                    max="<?= date('Y-m-d\TH:i', strtotime('+1 year')) ?>">
                                <small class="form-text text-muted">
                                    Fecha y hora de la orden
                                </small>
                            </div>

                            <!-- Observaciones -->
                            <div class="col-md-12 mb-3">
                                <label for="observaciones" class="form-label">
                                    Observaciones y Descripción del Trabajo
                                </label>
                                <textarea class="form-control"
                                    id="observaciones"
                                    name="observaciones"
                                    rows="4"
                                    placeholder="Describa el trabajo a realizar, problemas reportados, observaciones especiales..."><?= isset($_POST['observaciones']) ? htmlspecialchars($_POST['observaciones']) : '' ?></textarea>
                                <small class="form-text text-muted">
                                    Detalle el trabajo a realizar, problemas reportados por el cliente, etc.
                                </small>
                            </div>
                        </div>

                        <!-- Resumen de información -->
                        <div id="resumenOrden" class="card border-info mb-3" style="display: none;">
                            <div class="card-header bg-info text-white">
                                <h6 class="mb-0"><i class="fas fa-info-circle"></i> Resumen de la Orden</h6>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <strong>Cliente:</strong> <span id="resumenCliente">-</span><br>
                                        <strong>Vehículo:</strong> <span id="resumenVehiculo">Sin vehículo</span>
                                    </div>
                                    <div class="col-md-6">
                                        <strong>Estado:</strong> <span id="resumenEstado">-</span><br>
                                        <strong>Fecha:</strong> <span id="resumenFecha">-</span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-between">
                                    <a href="?page=ordenes" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <div>
                                        <button type="button" id="btnPreview" class="btn btn-info">
                                            <i class="fas fa-eye"></i> Vista Previa
                                        </button>
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save"></i> Crear Orden
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function waitForJQuery(callback) {
        if (typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }

    waitForJQuery(function() {
        $(document).ready(function() {
            // Cargar vehículos cuando se selecciona un cliente
            $('#id_cliente').change(function() {
                const clienteId = $(this).val();
                const clienteTexto = $(this).find('option:selected').text();

                console.log('Cliente seleccionado:', clienteId, '-', clienteTexto);

                // Limpiar select de vehículos
                $('#id_vehiculo').html('<option value="">-- Seleccione un vehículo --</option>');

                // Limpiar mensajes anteriores
                $('#alertNoVehiculos, #alertErrorVehiculos').remove();

                if (clienteId && clienteId != '') {
                    // Actualizar resumen
                    $('#resumenCliente').text(clienteTexto);

                    const url = '?page=ordenes&action=vehiculosPorCliente&id=' + clienteId;
                    console.log('Cargando vehículos desde:', url);

                    // Cargar vehículos del cliente
                    $.get(url)
                        .done(function(response) {
                            console.log('Respuesta:', response);

                            // Si la respuesta es string, intentar parsear
                            if (typeof response === 'string') {
                                try {
                                    response = JSON.parse(response);
                                } catch (e) {
                                    console.error('Error parseando JSON:', response);
                                    $('#id_vehiculo').html('<option value="">Error: Respuesta inválida</option>');
                                    return;
                                }
                            }

                            if (response.success && response.vehiculos && response.vehiculos.length > 0) {
                                console.log('Vehículos encontrados:', response.vehiculos.length);

                                response.vehiculos.forEach(function(vehiculo) {
                                    $('#id_vehiculo').append(
                                        '<option value="' + vehiculo.id_vehiculo + '">' +
                                        vehiculo.placa + ' - ' + vehiculo.marca + ' ' + vehiculo.modelo +
                                        '</option>'
                                    );
                                });
                            } else {
                                console.log('Sin vehículos para este cliente');
                                $('#id_vehiculo').html('<option value="">Este cliente no tiene vehículos registrados</option>');

                                // Mostrar mensaje de ayuda
                                $('#id_vehiculo').after(
                                    '<div class="alert alert-info mt-2" id="alertNoVehiculos">' +
                                    '<i class="fas fa-info-circle"></i> ' +
                                    '<strong>Sin vehículos:</strong> Este cliente no tiene vehículos registrados. ' +
                                    '</div>'
                                );
                            }
                        })
                        .fail(function(xhr, status, error) {
                            console.error('Error AJAX:', status, error, xhr.responseText);

                            $('#id_vehiculo').html('<option value="">Error al cargar vehículos</option>');

                            $('#id_vehiculo').after(
                                '<div class="alert alert-danger mt-2" id="alertErrorVehiculos">' +
                                '<i class="fas fa-exclamation-triangle"></i> ' +
                                '<strong>Error:</strong> No se pudieron cargar los vehículos.' +
                                '</div>'
                            );
                        });
                } else {
                    $('#resumenCliente').text('-');
                }

                actualizarResumen();
            });

            // Actualizar resumen cuando cambian otros campos
            $('#id_vehiculo, #estado, #fecha').change(function() {
                actualizarResumen();
            });

            // Vista previa
            $('#btnPreview').click(function() {
                actualizarResumen();
                $('#resumenOrden').show();
                $('html, body').animate({
                    scrollTop: $('#resumenOrden').offset().top - 100
                }, 500);
            });

            // Mostrar vehículos disponibles al cargar
            if ($('#vehiculosDisponibles .card').length > 0) {
                $('#vehiculosDisponibles').show();
            }

            // Validación del formulario
            $('#formOrden').submit(function(e) {
                const cliente = $('#id_cliente').val();

                if (!cliente) {
                    e.preventDefault();
                    mostrarError('Debe seleccionar un cliente');
                    $('#id_cliente').focus();
                    return false;
                }

                return true;
            });
        });

        function actualizarResumen() {
            const cliente = $('#id_cliente option:selected').text();
            const vehiculo = $('#id_vehiculo option:selected').text();
            const estado = $('#estado option:selected').text();
            const fecha = $('#fecha').val();

            $('#resumenCliente').text(cliente || '-');
            $('#resumenVehiculo').text(vehiculo === 'Sin vehículo específico' ? 'Sin vehículo' : vehiculo);
            $('#resumenEstado').text(estado || '-');
            $('#resumenFecha').text(fecha ? formatearFecha(fecha) : '-');
        }

        function formatearFecha(fechaISO) {
            const fecha = new Date(fechaISO);
            return fecha.toLocaleDateString('es-ES', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        function mostrarError(mensaje) {
            const alert = `
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="fas fa-exclamation-circle"></i> ${mensaje}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;

            $('.card-body').prepend(alert);

            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 3000);
        }

        // Auto-hide alerts
        setTimeout(function() {
            $('.alert').fadeOut('slow');
        }, 5000);
    });
</script>