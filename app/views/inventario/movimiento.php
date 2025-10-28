<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title">Registrar Movimiento - <?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?></h5>
                </div>
                <div class="card-body">
                    <form id="formMovimiento" method="POST" action="?page=inventario&action=registrar-entrada">
                        <?php $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                        $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                        <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">
                        <input type="hidden" name="producto_id" value="<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>">

                        <div class="mb-3">
                            <label for="tipo">Tipo</label>
                            <select name="tipo" id="tipo" class="form-control">
                                <option value="entrada">Entrada</option>
                                <option value="salida">Salida</option>
                                <option value="ajuste">Ajuste</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="cantidad">Cantidad</label>
                            <input type="number" name="cantidad" id="cantidad" class="form-control" min="0" required>
                        </div>

                        <div class="mb-3">
                            <label for="motivo">Motivo</label>
                            <input type="text" name="motivo" id="motivo" class="form-control" required>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">Registrar</button>
                            <a href="?page=productos&action=view&id=<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>" class="btn btn-secondary">Volver</a>
                        </div>
                    </form>

                    <script>
                        document.getElementById('formMovimiento').addEventListener('submit', function(e) {
                            e.preventDefault();
                            const form = this;
                            const tipo = form.tipo.value;
                            let url = '?page=inventario&action=registrar-entrada';
                            if (tipo === 'salida') url = '?page=inventario&action=registrar-salida';
                            if (tipo === 'ajuste') url = '?page=inventario&action=registrar-ajuste';

                            fetch(url, {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/x-www-form-urlencoded',
                                    'X-CSRF-Token': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                },
                                body: new URLSearchParams(new FormData(form))
                            }).then(r => r.json()).then(resp => {
                                if (resp.success) {
                                    alert(resp.message || 'Registrado');
                                    window.location.href = '?page=inventario&action=producto&id=' + encodeURIComponent(form.producto_id.value);
                                } else {
                                    alert('Error: ' + (resp.error || JSON.stringify(resp.errors || resp)));
                                }
                            }).catch(err => {
                                alert('Error en la petición: ' + err.message);
                            });
                        });
                    </script>
                </div>
            </div>
        </div>

        <div class="col-4">
            <div class="card">
                <div class="card-header">
                    <h6>Información</h6>
                </div>
                <div class="card-body">
                    <p><strong>Producto:</strong> <?= htmlspecialchars($producto['nombre'] ?? '') ?></p>
                    <p><strong>Stock actual:</strong> <?= htmlspecialchars($producto['stock_actual'] ?? $producto['stock'] ?? 0) ?></p>
                    <p><strong>Código:</strong> <?= htmlspecialchars($producto['codigo_barras'] ?? $producto['codigo'] ?? '') ?></p>
                </div>
            </div>
        </div>
    </div>
</div>