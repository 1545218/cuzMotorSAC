<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <!-- Page Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-plus me-2"></i>Nuevo Ajuste de Inventario
                </h2>
                <a href="?page=ajustes" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>

            <!-- Mensajes de error -->
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>

            <!-- Formulario -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Información del Ajuste
                    </h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="?page=ajustes&action=store" id="formAjuste">
                        <div class="row">
                            <!-- Producto -->
                            <div class="col-md-12 mb-3">
                                <label for="id_producto" class="form-label">
                                    Producto <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="id_producto" name="id_producto" required>
                                    <option value="">Seleccionar producto...</option>
                                    <?php foreach ($productos as $producto): ?>
                                        <option value="<?= $producto['id_producto'] ?>"
                                            data-stock="<?= $producto['stock_actual'] ?>"
                                            data-minimo="<?= $producto['stock_minimo'] ?>">
                                            <?= htmlspecialchars($producto['nombre']) ?>
                                            <?php if ($producto['codigo_barras']): ?>
                                                - <?= htmlspecialchars($producto['codigo_barras']) ?>
                                            <?php endif; ?>
                                            (Stock: <?= $producto['stock_actual'] ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">
                                    Seleccione el producto a ajustar
                                </small>
                            </div>
                        </div>

                        <!-- Información del producto seleccionado -->
                        <div id="infoProducto" class="alert alert-info d-none mb-3">
                            <h6><i class="fas fa-info-circle"></i> Información del Producto</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Stock Actual:</strong> <span id="stockActual">-</span>
                                </div>
                                <div class="col-md-6">
                                    <strong>Stock Mínimo:</strong> <span id="stockMinimo">-</span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Tipo de ajuste -->
                            <div class="col-md-6 mb-3">
                                <label for="tipo" class="form-label">
                                    Tipo de Ajuste <span class="text-danger">*</span>
                                </label>
                                <select class="form-control" id="tipo" name="tipo" required>
                                    <option value="">Seleccionar tipo...</option>
                                    <option value="aumento">
                                        <i class="fas fa-plus"></i> Aumento de Stock
                                    </option>
                                    <option value="disminucion">
                                        <i class="fas fa-minus"></i> Disminución de Stock
                                    </option>
                                </select>
                                <small class="form-text text-muted">
                                    Especifique si es aumento o reducción de inventario
                                </small>
                            </div>

                            <!-- Cantidad -->
                            <div class="col-md-6 mb-3">
                                <label for="cantidad" class="form-label">
                                    Cantidad <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control" id="cantidad" name="cantidad"
                                    min="1" step="1" required>
                                <small class="form-text text-muted">
                                    Cantidad a ajustar (número positivo)
                                </small>
                            </div>
                        </div>

                        <!-- Motivo -->
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="motivo" class="form-label">
                                    Motivo del Ajuste <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="motivo" name="motivo" rows="3"
                                    placeholder="Describa el motivo del ajuste..." required></textarea>
                                <small class="form-text text-muted">
                                    Explique la razón por la cual se realiza este ajuste
                                </small>
                            </div>
                        </div>

                        <!-- Preview del ajuste -->
                        <div id="previewAjuste" class="alert alert-warning d-none mb-3">
                            <h6><i class="fas fa-exclamation-triangle"></i> Vista Previa del Ajuste</h6>
                            <div id="previewContent"></div>
                        </div>

                        <!-- Botones -->
                        <div class="d-flex justify-content-between">
                            <button type="button" class="btn btn-secondary" onclick="history.back()">
                                <i class="fas fa-times me-2"></i>Cancelar
                            </button>
                            <div>
                                <button type="button" class="btn btn-info me-2" onclick="mostrarPreview()">
                                    <i class="fas fa-eye me-2"></i>Vista Previa
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Registrar Ajuste
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const formAjuste = document.getElementById('formAjuste');
        const selectProducto = document.getElementById('id_producto');
        const selectTipo = document.getElementById('tipo');
        const inputCantidad = document.getElementById('cantidad');

        // Mostrar información del producto al seleccionar
        selectProducto.addEventListener('change', function() {
            const option = this.selectedOptions[0];
            const infoDiv = document.getElementById('infoProducto');

            if (this.value && option) {
                const stockActual = option.getAttribute('data-stock');
                const stockMinimo = option.getAttribute('data-minimo');

                document.getElementById('stockActual').textContent = stockActual;
                document.getElementById('stockMinimo').textContent = stockMinimo;

                infoDiv.classList.remove('d-none');

                // Actualizar preview automáticamente
                setTimeout(mostrarPreview, 100);
            } else {
                infoDiv.classList.add('d-none');
                document.getElementById('previewAjuste').classList.add('d-none');
            }
        });

        // Actualizar preview cuando cambien los valores
        [selectTipo, inputCantidad].forEach(element => {
            element.addEventListener('change', mostrarPreview);
            element.addEventListener('input', mostrarPreview);
        });

        // Validar formulario
        formAjuste.addEventListener('submit', function(e) {
            if (!validarFormulario()) {
                e.preventDefault();
            }
        });
    });

    function mostrarPreview() {
        const producto = document.getElementById('id_producto');
        const tipo = document.getElementById('tipo').value;
        const cantidad = parseInt(document.getElementById('cantidad').value) || 0;
        const motivo = document.getElementById('motivo').value;

        const previewDiv = document.getElementById('previewAjuste');
        const contentDiv = document.getElementById('previewContent');

        if (!producto.value || !tipo || cantidad <= 0) {
            previewDiv.classList.add('d-none');
            return;
        }

        const option = producto.selectedOptions[0];
        const stockActual = parseInt(option.getAttribute('data-stock'));
        const nombreProducto = option.textContent;

        let nuevoStock;
        if (tipo === 'aumento') {
            nuevoStock = stockActual + cantidad;
        } else {
            nuevoStock = stockActual - cantidad;
        }

        let alertClass = 'alert-info';
        let mensaje = '';

        if (tipo === 'disminucion' && nuevoStock < 0) {
            alertClass = 'alert-danger';
            mensaje = '<strong>¡ADVERTENCIA!</strong> El ajuste resultará en stock negativo.<br>';
        } else if (nuevoStock <= parseInt(option.getAttribute('data-minimo'))) {
            alertClass = 'alert-warning';
            mensaje = '<strong>¡ATENCIÓN!</strong> El stock resultante estará por debajo del mínimo.<br>';
        }

        contentDiv.innerHTML = `
        ${mensaje}
        <strong>Producto:</strong> ${nombreProducto}<br>
        <strong>Tipo:</strong> ${tipo === 'aumento' ? 'Aumento' : 'Disminución'}<br>
        <strong>Cantidad:</strong> ${cantidad.toLocaleString()}<br>
        <strong>Stock Actual:</strong> ${stockActual.toLocaleString()}<br>
        <strong>Stock Final:</strong> ${nuevoStock.toLocaleString()}
    `;

        previewDiv.className = `alert ${alertClass} mb-3`;
        previewDiv.classList.remove('d-none');
    }

    function validarFormulario() {
        const producto = document.getElementById('id_producto').value;
        const tipo = document.getElementById('tipo').value;
        const cantidad = document.getElementById('cantidad').value;
        const motivo = document.getElementById('motivo').value.trim();

        if (!producto) {
            alert('Debe seleccionar un producto');
            return false;
        }

        if (!tipo) {
            alert('Debe seleccionar el tipo de ajuste');
            return false;
        }

        if (!cantidad || cantidad <= 0) {
            alert('Debe especificar una cantidad válida');
            return false;
        }

        if (!motivo) {
            alert('Debe especificar el motivo del ajuste');
            return false;
        }

        // Confirmar si el stock resultante será negativo
        if (tipo === 'disminucion') {
            const option = document.getElementById('id_producto').selectedOptions[0];
            const stockActual = parseInt(option.getAttribute('data-stock'));
            const nuevoStock = stockActual - parseInt(cantidad);

            if (nuevoStock < 0) {
                return confirm('El ajuste resultará en stock negativo. ¿Está seguro de continuar?');
            }
        }

        return true;
    }
</script>