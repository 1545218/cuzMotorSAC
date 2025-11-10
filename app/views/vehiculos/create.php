<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus"></i> Registrar Nuevo Vehículo
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

                    <form method="POST" action="?page=vehiculos&action=store" id="formVehiculo">
                        <div class="row">
                            <!-- Cliente -->
                            <div class="col-md-12 mb-3">
                                <label for="id_cliente" class="form-label">
                                    Cliente <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_cliente" name="id_cliente" required>
                                    <option value="">Seleccionar cliente...</option>
                                    <?php foreach ($clientes as $cliente): ?>
                                        <option value="<?= $cliente['id_cliente'] ?>"
                                            <?= (isset($_POST['id_cliente']) && $_POST['id_cliente'] == $cliente['id_cliente']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($cliente['nombre'] . ' ' . $cliente['apellido']) ?>
                                            <?php if ($cliente['numero_documento']): ?>
                                                - <?= htmlspecialchars($cliente['numero_documento']) ?>
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione el cliente propietario del vehículo
                                </small>
                            </div>

                            <!-- Placa -->
                            <div class="col-md-6 mb-3">
                                <label for="placa" class="form-label">
                                    Placa <span class="text-danger">*</span>
                                </label>
                                <input type="text"
                                    class="form-control"
                                    id="placa"
                                    name="placa"
                                    value="<?= htmlspecialchars($_POST['placa'] ?? '') ?>"
                                    placeholder="Ej: ABC-123"
                                    style="text-transform: uppercase;"
                                    maxlength="20"
                                    required>
                                <small class="form-text text-muted">
                                    Ingrese la placa del vehículo
                                </small>
                            </div>

                            <!-- Marca -->
                            <div class="col-md-6 mb-3">
                                <label for="marca" class="form-label">Marca</label>
                                <input type="text"
                                    class="form-control"
                                    id="marca"
                                    name="marca"
                                    value="<?= htmlspecialchars($_POST['marca'] ?? '') ?>"
                                    placeholder="Ej: Toyota, Honda, Ford"
                                    maxlength="100">
                                <small class="form-text text-muted">
                                    Marca del vehículo (opcional)
                                </small>
                            </div>

                            <!-- Modelo -->
                            <div class="col-md-12 mb-3">
                                <label for="modelo" class="form-label">Modelo</label>
                                <input type="text"
                                    class="form-control"
                                    id="modelo"
                                    name="modelo"
                                    value="<?= htmlspecialchars($_POST['modelo'] ?? '') ?>"
                                    placeholder="Ej: Corolla 2020, Civic EX, F-150"
                                    maxlength="100">
                                <small class="form-text text-muted">
                                    Modelo y año del vehículo (opcional)
                                </small>
                            </div>
                        </div>

                        <!-- Información del cliente seleccionado -->
                        <div id="infoCliente" class="alert alert-info" style="display: none;">
                            <h6><i class="fas fa-info-circle"></i> Información del Cliente</h6>
                            <div id="datosCliente"></div>
                        </div>

                        <!-- Botones -->
                        <div class="row">
                            <div class="col-md-12">
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <a href="?page=vehiculos" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                    <button type="submit" class="btn btn-primary" id="btnGuardar">
                                        <i class="fas fa-save"></i> Registrar Vehículo
                                    </button>
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
    // Convertir placa a mayúsculas automáticamente
    document.getElementById('placa').addEventListener('input', function() {
        this.value = this.value.toUpperCase();
    });

    // Mostrar información del cliente seleccionado
    document.getElementById('id_cliente').addEventListener('change', function() {
        const clienteId = this.value;
        const infoDiv = document.getElementById('infoCliente');
        const datosDiv = document.getElementById('datosCliente');

        if (clienteId) {
            // Obtener datos del cliente seleccionado
            const option = this.options[this.selectedIndex];
            const clienteNombre = option.textContent;

            datosDiv.innerHTML = `
            <div class="row">
                <div class="col-md-6">
                    <strong>Cliente:</strong> ${clienteNombre}
                </div>
                <div class="col-md-6">
                    <small class="text-muted">Verifique que sea el cliente correcto</small>
                </div>
            </div>
        `;
            infoDiv.style.display = 'block';

            // Opcional: Cargar vehículos existentes del cliente
            cargarVehiculosCliente(clienteId);
        } else {
            infoDiv.style.display = 'none';
        }
    });

    // Cargar vehículos existentes del cliente (para mostrar advertencia si ya tiene vehículos)
    function cargarVehiculosCliente(clienteId) {
        fetch(`?page=vehiculos&action=porCliente&id=${clienteId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.vehiculos.length > 0) {
                    const vehiculosHtml = data.vehiculos.map(v =>
                        `<span class="badge badge-secondary">${v.placa}</span>`
                    ).join(' ');

                    document.getElementById('datosCliente').innerHTML += `
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <small><strong>Vehículos existentes:</strong> ${vehiculosHtml}</small>
                        </div>
                    </div>
                `;
                }
            })
            .catch(error => {
                // Error cargando vehículos
            });
    }

    // Validación del formulario
    document.getElementById('formVehiculo').addEventListener('submit', function(e) {
        const cliente = document.getElementById('id_cliente').value;
        const placa = document.getElementById('placa').value.trim();

        if (!cliente) {
            e.preventDefault();
            alert('Debe seleccionar un cliente');
            document.getElementById('id_cliente').focus();
            return;
        }

        if (!placa) {
            e.preventDefault();
            alert('Debe ingresar la placa del vehículo');
            document.getElementById('placa').focus();
            return;
        }

        // Deshabilitar botón para evitar doble envío
        document.getElementById('btnGuardar').disabled = true;
    });

    // Auto-hide alerts
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
</script>