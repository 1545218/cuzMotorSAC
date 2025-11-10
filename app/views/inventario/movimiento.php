<div class="container-fluid inventario-movimiento-page">
    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card movement-form-card">
                <div class="card-header bg-gradient-primary">
                    <div class="d-flex align-items-center">
                        <div class="header-icon">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div>
                            <h5 class="card-title text-white mb-0">
                                Registrar Movimiento de Inventario
                            </h5>
                            <small class="text-white-50">
                                <?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?>
                            </small>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <form id="formMovimiento" method="POST" action="?page=inventario&action=registrar-entrada" class="needs-validation" novalidate>
                        <?php $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                        $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                        <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">
                        <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>">

                        <!-- SECCIÓN: TIPO DE MOVIMIENTO -->
                        <div class="movement-section">
                            <div class="section-header">
                                <h6 class="section-title">
                                    <i class="fas fa-arrows-alt text-primary me-2"></i>
                                    Tipo de Movimiento
                                </h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-12">
                                    <div class="movement-type-selector">
                                        <input type="radio" class="btn-check" name="tipo" id="tipo_entrada" value="entrada" checked>
                                        <label class="btn btn-outline-success movement-type-btn" for="tipo_entrada">
                                            <i class="fas fa-plus-circle fa-2x mb-2"></i>
                                            <span class="d-block fw-bold">ENTRADA</span>
                                            <small class="text-muted">Agregar stock</small>
                                        </label>

                                        <input type="radio" class="btn-check" name="tipo" id="tipo_salida" value="salida">
                                        <label class="btn btn-outline-danger movement-type-btn" for="tipo_salida">
                                            <i class="fas fa-minus-circle fa-2x mb-2"></i>
                                            <span class="d-block fw-bold">SALIDA</span>
                                            <small class="text-muted">Reducir stock</small>
                                        </label>

                                        <input type="radio" class="btn-check" name="tipo" id="tipo_ajuste" value="ajuste">
                                        <label class="btn btn-outline-warning movement-type-btn" for="tipo_ajuste">
                                            <i class="fas fa-edit fa-2x mb-2"></i>
                                            <span class="d-block fw-bold">AJUSTE</span>
                                            <small class="text-muted">Corregir inventario</small>
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN: DETALLES DEL MOVIMIENTO -->
                        <div class="movement-section">
                            <div class="section-header">
                                <h6 class="section-title">
                                    <i class="fas fa-clipboard-list text-info me-2"></i>
                                    Detalles del Movimiento
                                </h6>
                            </div>

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="cantidad" class="form-label fw-bold">
                                        <i class="fas fa-hashtag text-info me-1"></i>
                                        Cantidad <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-cubes"></i>
                                        </span>
                                        <input type="number"
                                            name="cantidad"
                                            id="cantidad"
                                            class="form-control"
                                            min="1"
                                            placeholder="Ingrese cantidad"
                                            required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, ingrese una cantidad válida.
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle"></i>
                                        <span id="stock-info">Stock actual: <?= htmlspecialchars($producto['stock_actual'] ?? $producto['stock'] ?? 0) ?> unidades</span>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <label for="motivo" class="form-label fw-bold">
                                        <i class="fas fa-comment-dots text-info me-1"></i>
                                        Motivo <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <span class="input-group-text">
                                            <i class="fas fa-pen"></i>
                                        </span>
                                        <input type="text"
                                            name="motivo"
                                            id="motivo"
                                            class="form-control"
                                            placeholder="Ej: Compra, Venta, Merma, etc."
                                            required>
                                    </div>
                                    <div class="invalid-feedback">
                                        Por favor, indique el motivo del movimiento.
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTONES DE ACCIÓN -->
                        <div class="movement-actions">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="?page=productos&action=view&id=<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>"
                                    class="btn btn-light btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Volver al Producto
                                </a>

                                <button type="submit" class="btn btn-primary btn-lg" id="btnRegistrar">
                                    <i class="fas fa-save me-2"></i>
                                    Registrar Movimiento
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card info-card">
                <div class="card-header bg-gradient-info">
                    <h6 class="text-white mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Información del Producto
                    </h6>
                </div>
                <div class="card-body">
                    <div class="product-info-item">
                        <div class="info-label">
                            <i class="fas fa-tag text-primary"></i>
                            Producto:
                        </div>
                        <div class="info-value">
                            <?= htmlspecialchars($producto['nombre'] ?? '') ?>
                        </div>
                    </div>

                    <div class="product-info-item">
                        <div class="info-label">
                            <i class="fas fa-cubes text-success"></i>
                            Stock Actual:
                        </div>
                        <div class="info-value">
                            <span class="badge bg-success fs-6">
                                <?= htmlspecialchars($producto['stock_actual'] ?? $producto['stock'] ?? 0) ?> unidades
                            </span>
                        </div>
                    </div>

                    <div class="product-info-item">
                        <div class="info-label">
                            <i class="fas fa-barcode text-info"></i>
                            Código:
                        </div>
                        <div class="info-value">
                            <code><?= htmlspecialchars($producto['codigo_barras'] ?? $producto['codigo'] ?? 'Sin código') ?></code>
                        </div>
                    </div>

                    <?php if (isset($producto['precio_unitario'])): ?>
                        <div class="product-info-item">
                            <div class="info-label">
                                <i class="fas fa-dollar-sign text-warning"></i>
                                Precio Unitario:
                            </div>
                            <div class="info-value">
                                <span class="badge bg-warning text-dark fs-6">
                                    S/ <?= number_format($producto['precio_unitario'], 2) ?>
                                </span>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($producto['stock_minimo'])): ?>
                        <div class="product-info-item">
                            <div class="info-label">
                                <i class="fas fa-exclamation-triangle text-danger"></i>
                                Stock Mínimo:
                            </div>
                            <div class="info-value">
                                <?= htmlspecialchars($producto['stock_minimo']) ?> unidades
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- CARD DE AYUDA -->
            <div class="card help-card mt-3">
                <div class="card-header bg-gradient-secondary">
                    <h6 class="text-white mb-0">
                        <i class="fas fa-question-circle me-2"></i>
                        Ayuda
                    </h6>
                </div>
                <div class="card-body">
                    <div class="help-item">
                        <strong>🟢 Entrada:</strong> Para registrar llegada de mercancía
                    </div>
                    <div class="help-item">
                        <strong>🔴 Salida:</strong> Para registrar ventas o mermas
                    </div>
                    <div class="help-item">
                        <strong>🟡 Ajuste:</strong> Para corregir discrepancias en inventario
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Script mejorado para movimientos de inventario -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('formMovimiento');
        const stockActual = <?= $producto['stock_actual'] ?? $producto['stock'] ?? 0 ?>;
        const cantidadInput = document.getElementById('cantidad');
        const motivoInput = document.getElementById('motivo');
        const btnRegistrar = document.getElementById('btnRegistrar');

        // Sugerencias de motivos por tipo
        const motivosSugeridos = {
            entrada: ['Compra', 'Devolución', 'Producción', 'Transferencia entrada'],
            salida: ['Venta', 'Merma', 'Daño', 'Transferencia salida'],
            ajuste: ['Inventario físico', 'Corrección', 'Auditoría', 'Error sistema']
        };

        // Actualizar interfaz según tipo seleccionado
        function updateTipoMovimiento() {
            const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked').value;
            const stockInfo = document.getElementById('stock-info');

            // Actualizar información de stock
            if (tipoSeleccionado === 'salida') {
                stockInfo.innerHTML = `<i class="fas fa-exclamation-triangle text-warning"></i> Stock actual: ${stockActual} unidades (no exceder este límite)`;
                cantidadInput.max = stockActual;
            } else {
                stockInfo.innerHTML = `<i class="fas fa-info-circle"></i> Stock actual: ${stockActual} unidades`;
                cantidadInput.removeAttribute('max');
            }

            // Actualizar placeholder de motivo
            const sugerencias = motivosSugeridos[tipoSeleccionado];
            motivoInput.placeholder = `Ej: ${sugerencias.join(', ')}`;

            // Actualizar color del botón
            btnRegistrar.className = btnRegistrar.className.replace(/btn-\w+/, getButtonClass(tipoSeleccionado));

            // Limpiar motivo para que usuario escriba uno apropiado
            motivoInput.value = '';
        }

        function getButtonClass(tipo) {
            switch (tipo) {
                case 'entrada':
                    return 'btn-success';
                case 'salida':
                    return 'btn-danger';
                case 'ajuste':
                    return 'btn-warning';
                default:
                    return 'btn-primary';
            }
        }

        // Event listeners para radio buttons
        document.querySelectorAll('input[name="tipo"]').forEach(radio => {
            radio.addEventListener('change', updateTipoMovimiento);
        });

        // Validación en tiempo real
        cantidadInput.addEventListener('input', function() {
            const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked').value;
            const cantidad = parseInt(this.value);

            this.classList.remove('is-valid', 'is-invalid');

            if (cantidad > 0) {
                if (tipoSeleccionado === 'salida' && cantidad > stockActual) {
                    this.classList.add('is-invalid');
                } else {
                    this.classList.add('is-valid');
                }
            }
        });

        // Autocompletado de motivos
        motivoInput.addEventListener('focus', function() {
            const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked').value;
            const sugerencias = motivosSugeridos[tipoSeleccionado];

            // Crear datalist si no existe
            let datalist = document.getElementById('motivos-list');
            if (!datalist) {
                datalist = document.createElement('datalist');
                datalist.id = 'motivos-list';
                this.setAttribute('list', 'motivos-list');
                this.parentNode.appendChild(datalist);
            }

            // Llenar opciones
            datalist.innerHTML = '';
            sugerencias.forEach(motivo => {
                const option = document.createElement('option');
                option.value = motivo;
                datalist.appendChild(option);
            });
        });

        // Envío del formulario
        form.addEventListener('submit', function(e) {
            e.preventDefault();

            if (!form.checkValidity()) {
                e.stopPropagation();
                form.classList.add('was-validated');
                return;
            }

            const tipoSeleccionado = document.querySelector('input[name="tipo"]:checked').value;
            const cantidad = parseInt(cantidadInput.value);

            // Validación específica para salidas
            if (tipoSeleccionado === 'salida' && cantidad > stockActual) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cantidad no válida',
                    text: `No puede sacar ${cantidad} unidades. Stock actual: ${stockActual}`,
                    confirmButtonText: 'Entendido'
                });
                return;
            }

            // Confirmación
            const tipoTexto = tipoSeleccionado.charAt(0).toUpperCase() + tipoSeleccionado.slice(1);
            Swal.fire({
                title: `¿Confirmar ${tipoTexto}?`,
                html: `
                <strong>Producto:</strong> <?= htmlspecialchars($producto['nombre'] ?? '') ?><br>
                <strong>Tipo:</strong> ${tipoTexto}<br>
                <strong>Cantidad:</strong> ${cantidad} unidades<br>
                <strong>Motivo:</strong> ${motivoInput.value}
            `,
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Sí, registrar',
                cancelButtonText: 'Cancelar',
                confirmButtonColor: getConfirmButtonColor(tipoSeleccionado)
            }).then((result) => {
                if (result.isConfirmed) {
                    enviarFormulario(tipoSeleccionado);
                }
            });
        });

        function getConfirmButtonColor(tipo) {
            switch (tipo) {
                case 'entrada':
                    return '#28a745';
                case 'salida':
                    return '#dc3545';
                case 'ajuste':
                    return '#ffc107';
                default:
                    return '#007bff';
            }
        }

        function enviarFormulario(tipo) {
            let url = '?page=inventario&action=registrar-entrada';
            if (tipo === 'salida') url = '?page=inventario&action=registrar-salida';
            if (tipo === 'ajuste') url = '?page=inventario&action=registrar-ajuste';

            // Mostrar loading
            Swal.fire({
                title: 'Procesando...',
                text: 'Registrando movimiento de inventario',
                allowOutsideClick: false,
                showConfirmButton: false,
                willOpen: () => {
                    Swal.showLoading();
                }
            });

            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: new URLSearchParams(new FormData(form))
                })
                .then(response => {
                    // Verificar si la respuesta es exitosa
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }

                    // Intentar obtener el texto primero para debugging
                    return response.text().then(text => {
                        console.log('Respuesta del servidor:', text);
                        try {
                            return JSON.parse(text);
                        } catch (e) {
                            console.error('Error parsing JSON:', e);
                            console.error('Respuesta recibida:', text);
                            throw new Error(`Respuesta no válida del servidor: ${text.substring(0, 100)}...`);
                        }
                    });
                })
                .then(data => {
                    Swal.close();

                    if (data.success) {
                        let mensaje = data.message || 'El movimiento se ha registrado correctamente';
                        if (data.alerta) {
                            mensaje += '\n\n' + data.alerta;
                        }

                        Swal.fire({
                            icon: 'success',
                            title: '¡Registrado exitosamente!',
                            text: mensaje,
                            confirmButtonText: 'Continuar'
                        }).then(() => {
                            window.location.href = '?page=inventario&action=producto&id=' + encodeURIComponent(form.producto_id.value);
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error al registrar',
                            text: data.error || 'Ha ocurrido un error inesperado',
                            confirmButtonText: 'Intentar de nuevo'
                        });
                    }
                })
                .catch(error => {
                    Swal.close();
                    Swal.fire({
                        icon: 'error',
                        title: 'Error de conexión',
                        text: 'No se pudo conectar con el servidor: ' + error.message,
                        confirmButtonText: 'Reintentar'
                    });
                });
        }

        // Inicializar
        updateTipoMovimiento();
    });
</script>