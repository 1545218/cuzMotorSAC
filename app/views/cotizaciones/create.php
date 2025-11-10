<?php /* header ya se incluye desde Controller->view */ ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>Crear Nueva Cotización
                    </h3>
                    <div>
                        <a href="?page=cotizaciones" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <?php
                    $formAction = $form_action ?? '?page=cotizaciones&action=store';
                    $csrfToken = $_SESSION['csrf_token'] ?? '';
                    $isEdit = !empty($cotizacion);
                    ?>
                    <form method="POST" action="<?= $formAction ?>" id="formCotizacion">
                        <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>">
                        <?php if ($isEdit): ?>
                            <input type="hidden" name="id_cotizacion" value="<?= htmlspecialchars($cotizacion['id_cotizacion']) ?>">
                        <?php endif; ?>

                        <!-- Información del Cliente -->
                        <div class="row">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2 mb-3">
                                    <i class="fas fa-user mr-2"></i>Información del Cliente
                                </h5>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group">
                                    <label for="id_cliente">Cliente <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <select class="form-control" id="id_cliente" name="id_cliente" required>
                                            <option value="">Seleccionar cliente...</option>
                                            <?php foreach ($clientes as $cliente): ?>
                                                <?php
                                                $selectedClient = '';
                                                if ($isEdit && isset($cotizacion['id_cliente']) && $cotizacion['id_cliente'] == $cliente['id_cliente']) {
                                                    $selectedClient = 'selected';
                                                } elseif (isset($_POST['id_cliente']) && $_POST['id_cliente'] == $cliente['id_cliente']) {
                                                    $selectedClient = 'selected';
                                                }
                                                ?>
                                                <option value="<?= $cliente['id_cliente'] ?>"
                                                    data-email="<?= htmlspecialchars($cliente['email']) ?>"
                                                    data-telefono="<?= htmlspecialchars($cliente['telefono']) ?>"
                                                    data-direccion="<?= htmlspecialchars($cliente['direccion']) ?>"
                                                    <?= $selectedClient ?>>
                                                    <?= htmlspecialchars($cliente['nombre']) ?> - <?= htmlspecialchars($cliente['numero_documento']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <div class="input-group-append">
                                            <button type="button" class="btn btn-outline-primary" data-toggle="modal" data-target="#modalNuevoCliente">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="fecha_vencimiento">Válida hasta <span class="text-danger">*</span></label>
                                    <input type="date" class="form-control" id="fecha_vencimiento" name="fecha_vencimiento"
                                        value="<?= htmlspecialchars($_POST['fecha_vencimiento'] ?? ($cotizacion['fecha_vencimiento'] ?? date('Y-m-d', strtotime('+30 days')))) ?>" required>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="tipo_cliente">Tipo de Cliente</label>
                                    <?php $tipoClienteVal = $_POST['tipo_cliente'] ?? ($cotizacion['tipo_cliente'] ?? 'minorista'); ?>
                                    <select class="form-control" id="tipo_cliente" name="tipo_cliente">
                                        <option value="minorista" <?= $tipoClienteVal == 'minorista' ? 'selected' : '' ?>>Minorista</option>
                                        <option value="mayorista" <?= $tipoClienteVal == 'mayorista' ? 'selected' : '' ?>>Mayorista</option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label for="id_vehiculo">Vehículo (Opcional)</label>
                                    <select class="form-control" id="id_vehiculo" name="id_vehiculo">
                                        <option value="">Sin vehículo asociado</option>
                                    </select>
                                    <small class="text-muted">Selecciona primero un cliente para ver sus vehículos</small>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6" id="cliente_info" style="display: none;">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">Información de Contacto</h6>
                                        <p class="mb-1"><strong>Email:</strong> <span id="cliente_email"></span></p>
                                        <p class="mb-1"><strong>Teléfono:</strong> <span id="cliente_telefono"></span></p>
                                        <p class="mb-0"><strong>Dirección:</strong> <span id="cliente_direccion"></span></p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6" id="vehiculo_info" style="display: none;">
                                <div class="card bg-info text-white">
                                    <div class="card-body">
                                        <h6 class="card-title">Información del Vehículo</h6>
                                        <p class="mb-1"><strong>Placa:</strong> <span id="vehiculo_placa"></span></p>
                                        <p class="mb-1"><strong>Marca:</strong> <span id="vehiculo_marca"></span></p>
                                        <p class="mb-0"><strong>Modelo:</strong> <span id="vehiculo_modelo"></span></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="observaciones">Observaciones</label>
                                <textarea class="form-control" id="observaciones" name="observaciones" rows="3"
                                    placeholder="Observaciones generales de la cotización..."><?= htmlspecialchars($_POST['observaciones'] ?? ($cotizacion['observaciones'] ?? '')) ?></textarea>
                            </div>
                        </div>
                </div>

                <!-- Productos -->
                <div class="row mt-4">
                    <div class="col-12">
                        <h5 class="text-primary border-bottom pb-2 mb-3">
                            <i class="fas fa-box mr-2"></i>Productos a Cotizar
                        </h5>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header">
                                <div class="row">
                                    <div class="col-md-8">
                                        <div class="form-group mb-0">
                                            <label for="producto_search">Buscar producto:</label>
                                            <input type="text" class="form-control" id="producto_search"
                                                placeholder="Buscar por nombre o código...">
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <div class="form-group mb-0">
                                            <label>&nbsp;</label>
                                            <button type="button" class="btn btn-success btn-block" id="btn_agregar_producto">
                                                <i class="fas fa-plus"></i> Agregar Producto
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead class="thead-light">
                                            <tr>
                                                <th width="40%">Producto</th>
                                                <th width="15%">Cantidad</th>
                                                <th width="15%">Precio Unit.</th>
                                                <th width="15%">Descuento</th>
                                                <th width="15%">Subtotal</th>
                                                <th width="5%"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="productos_cotizacion">
                                            <?php
                                            // Preferir productos enviados por POST (cuando se reenvía el formulario)
                                            if (!empty($_POST['productos'])):
                                                $rows = $_POST['productos'];
                                            // Si estamos en modo edición, usar $detalles pasados por el controlador
                                            elseif (!empty($detalles)):
                                                // Mapear detalles del modelo a la misma estructura de inputs que espera el formulario
                                                $rows = [];
                                                foreach ($detalles as $i => $d) {
                                                    $rows[$i] = [
                                                        'id_producto' => $d['id_producto'] ?? $d['id_producto'] ?? '',
                                                        'cantidad' => $d['cantidad'] ?? $d['cantidad'] ?? 1,
                                                        'precio_unitario' => $d['precio_unitario'] ?? $d['precio_unitario'] ?? $d['precio'] ?? 0,
                                                        'descuento' => $d['descuento'] ?? 0,
                                                        'descripcion_servicio' => $d['descripcion_servicio'] ?? ''
                                                    ];
                                                }
                                            else:
                                                $rows = [];
                                            endif;

                                            if (!empty($rows)):
                                                foreach ($rows as $index => $producto): ?>
                                                    <tr data-index="<?= $index ?>">
                                                        <td>
                                                            <select class="form-control form-control-sm producto-select" name="productos[<?= $index ?>][id_producto]" required>
                                                                <option value="">Seleccionar...</option>
                                                                <?php foreach ($productos as $prod): ?>
                                                                    <option value="<?= $prod['id_producto'] ?>"
                                                                        data-precio="<?= $prod['precio_unitario'] ?>"
                                                                        data-stock="<?= $prod['stock_actual'] ?>"
                                                                        <?= (string)($producto['id_producto'] ?? '') === (string)$prod['id_producto'] ? 'selected' : '' ?>>
                                                                        <?= htmlspecialchars($prod['nombre']) ?> - <?= formatCurrency($prod['precio_unitario']) ?>
                                                                    </option>
                                                                <?php endforeach; ?>
                                                            </select>
                                                            <small class="stock-info text-muted"></small>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm cantidad-input"
                                                                name="productos[<?= $index ?>][cantidad]" min="1" step="1"
                                                                value="<?= htmlspecialchars($producto['cantidad'] ?? 1) ?>" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm precio-input"
                                                                name="productos[<?= $index ?>][precio_unitario]" min="0" step="0.01"
                                                                value="<?= htmlspecialchars($producto['precio_unitario'] ?? 0) ?>" required>
                                                        </td>
                                                        <td>
                                                            <input type="number" class="form-control form-control-sm descuento-input"
                                                                name="productos[<?= $index ?>][descuento]" min="0" max="100" step="0.01"
                                                                value="<?= htmlspecialchars($producto['descuento'] ?? 0) ?>" placeholder="0">
                                                        </td>
                                                        <td>
                                                            <span class="subtotal-display">S/ 0.00</span>
                                                        </td>
                                                        <td>
                                                            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-producto">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach;
                                            else: ?>
                                                <tr id="no_productos">
                                                    <td colspan="6" class="text-center text-muted py-3">
                                                        <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                                                        Agregue productos a la cotización
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Totales -->
                <div class="row mt-4">
                    <div class="col-md-8"></div>
                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Subtotal:</span>
                                    <span id="subtotal_display">S/ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>Descuento Total:</span>
                                    <span id="descuento_total_display" class="text-success">S/ 0.00</span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span>IGV (<?= DEFAULT_IVA ?>%):</span>
                                    <span id="igv_display"><?= formatCurrency(0) ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong id="total_display">S/ 0.00</strong>
                                </div>
                                <input type="hidden" name="subtotal" id="subtotal_hidden">
                                <input type="hidden" name="descuento_total" id="descuento_total_hidden">
                                <input type="hidden" name="igv" id="igv_hidden">
                                <input type="hidden" name="total" id="total_hidden">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary btn-lg">
                        <i class="fas fa-save"></i> Guardar Cotización
                    </button>
                    <a href="?page=cotizaciones" class="btn btn-secondary btn-lg ml-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
                </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>

<!-- Modal Nuevo Cliente -->
<div class="modal fade" id="modalNuevoCliente" tabindex="-1" aria-labelledby="modalNuevoClienteLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="formNuevoCliente">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalNuevoClienteLabel">
                        <i class="fas fa-user-plus mr-2"></i>Registrar Nuevo Cliente
                    </h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cliente_nombre">Nombre/Razón Social <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cliente_nombre" name="nombre" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="cliente_tipo_documento">Tipo Doc.</label>
                                <select class="form-control" id="cliente_tipo_documento" name="tipo_documento">
                                    <option value="DNI">DNI</option>
                                    <option value="RUC">RUC</option>
                                    <option value="PASAPORTE">Pasaporte</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="cliente_numero_documento">Número <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="cliente_numero_documento" name="numero_documento" required>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cliente_email">Email</label>
                                <input type="email" class="form-control" id="cliente_email" name="email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="cliente_telefono">Teléfono</label>
                                <input type="text" class="form-control" id="cliente_telefono" name="telefono">
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="cliente_direccion">Dirección</label>
                        <textarea class="form-control" id="cliente_direccion" name="direccion" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    let productoIndex = <?= !empty($_POST['productos']) ? count($_POST['productos']) : 0 ?>;
    const productosDisponibles = <?= json_encode($productos) ?>;

    // Funciones de cálculo
    function calcularSubtotales() {
        let subtotal = 0;
        let descuentoTotal = 0;

        document.querySelectorAll('#productos_cotizacion tr[data-index]').forEach(function(row) {
            const cantidad = parseFloat(row.querySelector('.cantidad-input').value) || 0;
            const precio = parseFloat(row.querySelector('.precio-input').value) || 0;
            const descuentoPorcentaje = parseFloat(row.querySelector('.descuento-input').value) || 0;

            const subtotalProducto = cantidad * precio;
            const descuentoProducto = subtotalProducto * (descuentoPorcentaje / 100);
            const totalProducto = subtotalProducto - descuentoProducto;

            row.querySelector('.subtotal-display').textContent = 'S/ ' + totalProducto.toFixed(2);

            subtotal += subtotalProducto;
            descuentoTotal += descuentoProducto;
        });

        const igv = (subtotal - descuentoTotal) * (<?= DEFAULT_IVA ?> / 100);
        const total = subtotal - descuentoTotal + igv;

        // Actualizar displays
        document.getElementById('subtotal_display').textContent = 'S/ ' + subtotal.toFixed(2);
        document.getElementById('descuento_total_display').textContent = 'S/ ' + descuentoTotal.toFixed(2);
        document.getElementById('igv_display').textContent = 'S/ ' + igv.toFixed(2);
        document.getElementById('total_display').textContent = 'S/ ' + total.toFixed(2);

        // Campos ocultos
        document.getElementById('subtotal_hidden').value = subtotal.toFixed(2);
        document.getElementById('descuento_total_hidden').value = descuentoTotal.toFixed(2);
        document.getElementById('igv_hidden').value = igv.toFixed(2);
        document.getElementById('total_hidden').value = total.toFixed(2);
    }

    // Agregar producto
    document.getElementById('btn_agregar_producto').addEventListener('click', function() {
        const tbody = document.getElementById('productos_cotizacion');
        const noProductos = document.getElementById('no_productos');

        if (noProductos) {
            noProductos.remove();
        }

        const row = document.createElement('tr');
        row.setAttribute('data-index', productoIndex);
        row.innerHTML = `
        <td>
            <select class="form-control form-control-sm producto-select" name="productos[${productoIndex}][id_producto]" required>
                <option value="">Seleccionar...</option>
                ${productosDisponibles.map(p => `
                    <option value="${p.id_producto}" data-precio="${p.precio_unitario}" data-stock="${p.stock_actual}">
                        ${p.nombre} - S/ ${parseFloat(p.precio_unitario).toFixed(2)}
                    </option>
                `).join('')}
            </select>
            <small class="stock-info text-muted"></small>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm cantidad-input" 
                   name="productos[${productoIndex}][cantidad]" min="1" step="1" value="1" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm precio-input" 
                   name="productos[${productoIndex}][precio_unitario]" min="0" step="0.01" required>
        </td>
        <td>
            <input type="number" class="form-control form-control-sm descuento-input" 
                   name="productos[${productoIndex}][descuento]" min="0" max="100" step="0.01" value="0" placeholder="0">
        </td>
        <td>
            <span class="subtotal-display">S/ 0.00</span>
        </td>
        <td>
            <button type="button" class="btn btn-sm btn-outline-danger btn-remove-producto">
                <i class="fas fa-times"></i>
            </button>
        </td>
    `;

        tbody.appendChild(row);

        // Event listeners para la nueva fila
        setupRowEventListeners(row);

        productoIndex++;
        calcularSubtotales();
    });

    // Configurar event listeners para una fila
    function setupRowEventListeners(row) {
        const productoSelect = row.querySelector('.producto-select');
        const cantidadInput = row.querySelector('.cantidad-input');
        const precioInput = row.querySelector('.precio-input');
        const descuentoInput = row.querySelector('.descuento-input');
        const removeBtn = row.querySelector('.btn-remove-producto');
        const stockInfo = row.querySelector('.stock-info');

        // Cuando se selecciona un producto
        productoSelect.addEventListener('change', function() {
            const option = this.options[this.selectedIndex];
            if (option.value) {
                const precio = option.getAttribute('data-precio');
                const stock = option.getAttribute('data-stock');

                precioInput.value = precio;
                stockInfo.textContent = `Stock disponible: ${stock}`;
                cantidadInput.setAttribute('max', stock);
            } else {
                precioInput.value = '';
                stockInfo.textContent = '';
                cantidadInput.removeAttribute('max');
            }
            calcularSubtotales();
        });

        // Calcular cuando cambian valores
        [cantidadInput, precioInput, descuentoInput].forEach(input => {
            input.addEventListener('input', calcularSubtotales);
        });

        // Remover producto
        removeBtn.addEventListener('click', function() {
            row.remove();

            // Si no quedan productos, mostrar mensaje
            if (document.querySelectorAll('#productos_cotizacion tr[data-index]').length === 0) {
                const tbody = document.getElementById('productos_cotizacion');
                tbody.innerHTML = `
                <tr id="no_productos">
                    <td colspan="6" class="text-center text-muted py-3">
                        <i class="fas fa-plus-circle fa-2x mb-2"></i><br>
                        Agregue productos a la cotización
                    </td>
                </tr>
            `;
            }

            calcularSubtotales();
        });
    }

    // Configurar event listeners para productos existentes
    document.querySelectorAll('#productos_cotizacion tr[data-index]').forEach(setupRowEventListeners);

    // Información del cliente
    document.getElementById('id_cliente').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const clienteInfo = document.getElementById('cliente_info');
        const vehiculoInfo = document.getElementById('vehiculo_info');
        const vehiculoSelect = document.getElementById('id_vehiculo');

        if (option.value) {
            // Mostrar información del cliente
            document.getElementById('cliente_email').textContent = option.getAttribute('data-email') || 'No especificado';
            document.getElementById('cliente_telefono').textContent = option.getAttribute('data-telefono') || 'No especificado';
            document.getElementById('cliente_direccion').textContent = option.getAttribute('data-direccion') || 'No especificada';
            clienteInfo.style.display = 'block';

            // Cargar vehículos del cliente
            cargarVehiculosCliente(option.value);
        } else {
            clienteInfo.style.display = 'none';
            vehiculoInfo.style.display = 'none';

            // Limpiar vehículos
            vehiculoSelect.innerHTML = '<option value="">Sin vehículo asociado</option>';
        }
    });

    // Nuevo cliente
    document.getElementById('formNuevoCliente').addEventListener('submit', function(e) {
        e.preventDefault();

        const formData = new FormData(this);

        fetch('?page=clientes&action=store', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Agregar cliente al select
                    const clienteSelect = document.getElementById('id_cliente');
                    const option = document.createElement('option');
                    option.value = data.cliente.id_cliente;
                    option.textContent = `${data.cliente.nombre} - ${data.cliente.numero_documento}`;
                    option.setAttribute('data-email', data.cliente.email || '');
                    option.setAttribute('data-telefono', data.cliente.telefono || '');
                    option.setAttribute('data-direccion', data.cliente.direccion || '');
                    option.selected = true;

                    clienteSelect.appendChild(option);

                    // Disparar evento change
                    clienteSelect.dispatchEvent(new Event('change'));

                    // Cerrar modal
                    $('#modalNuevoCliente').modal('hide');

                    // Limpiar formulario
                    this.reset();
                } else {
                    alert('Error al crear cliente: ' + (data.message || 'Error desconocido'));
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error al crear cliente');
            });
    });

    // Buscar productos
    document.getElementById('producto_search').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const selects = document.querySelectorAll('.producto-select');

        selects.forEach(select => {
            const options = select.querySelectorAll('option');
            options.forEach(option => {
                if (option.value === '') return;

                const text = option.textContent.toLowerCase();
                option.style.display = text.includes(searchTerm) ? 'block' : 'none';
            });
        });
    });

    // Calcular totales al cargar
    document.addEventListener('DOMContentLoaded', function() {
        // Disparar evento change para cliente si ya está seleccionado
        const clienteSelect = document.getElementById('id_cliente');
        if (clienteSelect.value) {
            clienteSelect.dispatchEvent(new Event('change'));
        }

        calcularSubtotales();
    });

    // Validación antes de enviar
    document.getElementById('formCotizacion').addEventListener('submit', function(e) {
        const productos = document.querySelectorAll('#productos_cotizacion tr[data-index]');

        if (productos.length === 0) {
            e.preventDefault();
            alert('Debe agregar al menos un producto a la cotización');
            return;
        }

        // Validar que todos los productos tengan cantidad y precio
        let isValid = true;
        productos.forEach(row => {
            const cantidad = row.querySelector('.cantidad-input').value;
            const precio = row.querySelector('.precio-input').value;
            const producto = row.querySelector('.producto-select').value;

            if (!producto || !cantidad || !precio) {
                isValid = false;
            }
        });

        if (!isValid) {
            e.preventDefault();
            alert('Complete todos los campos de los productos agregados');
        }
    });

    // Función para cargar vehículos del cliente
    function cargarVehiculosCliente(clienteId) {
        const vehiculoSelect = document.getElementById('id_vehiculo');

        // Limpiar opciones
        vehiculoSelect.innerHTML = '<option value="">Cargando vehículos...</option>';

        fetch(`?page=cotizaciones&action=vehiculosPorCliente&cliente_id=${clienteId}`)
            .then(response => response.json())
            .then(data => {
                vehiculoSelect.innerHTML = '<option value="">Sin vehículo asociado</option>';

                if (data.success && data.vehiculos.length > 0) {
                    data.vehiculos.forEach(vehiculo => {
                        const option = document.createElement('option');
                        option.value = vehiculo.id_vehiculo;
                        option.textContent = `${vehiculo.placa} - ${vehiculo.marca || ''} ${vehiculo.modelo || ''}`.trim();
                        option.setAttribute('data-placa', vehiculo.placa);
                        option.setAttribute('data-marca', vehiculo.marca || '');
                        option.setAttribute('data-modelo', vehiculo.modelo || '');
                        vehiculoSelect.appendChild(option);
                    });
                } else {
                    const option = document.createElement('option');
                    option.value = '';
                    option.textContent = 'No hay vehículos registrados';
                    option.disabled = true;
                    vehiculoSelect.appendChild(option);
                }
            })
            .catch(error => {
                console.error('Error al cargar vehículos:', error);
                vehiculoSelect.innerHTML = '<option value="">Error al cargar vehículos</option>';
            });
    }

    // Información del vehículo
    document.getElementById('id_vehiculo').addEventListener('change', function() {
        const option = this.options[this.selectedIndex];
        const vehiculoInfo = document.getElementById('vehiculo_info');

        if (option.value) {
            document.getElementById('vehiculo_placa').textContent = option.getAttribute('data-placa') || 'N/A';
            document.getElementById('vehiculo_marca').textContent = option.getAttribute('data-marca') || 'N/A';
            document.getElementById('vehiculo_modelo').textContent = option.getAttribute('data-modelo') || 'N/A';
            vehiculoInfo.style.display = 'block';
        } else {
            vehiculoInfo.style.display = 'none';
        }
    });
</script>

<?php /* footer ya se incluye desde Controller->view */ ?>