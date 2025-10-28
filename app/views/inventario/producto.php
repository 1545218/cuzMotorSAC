<div class="container-fluid">
    <div class="row">
        <div class="col-8">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title">Inventario - <?= htmlspecialchars($producto['nombre'] ?? 'Producto') ?></h5>
                    <div>
                        <a href="?page=inventario&action=movimiento&id=<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>" class="btn btn-success btn-sm">Registrar Movimiento</a>
                        <a href="?page=productos&action=view&id=<?= htmlspecialchars($producto['id_producto'] ?? $producto['id'] ?? 0) ?>" class="btn btn-secondary btn-sm">Ver Producto</a>
                    </div>
                </div>
                <div class="card-body">
                    <h6>Resumen</h6>
                    <p><strong>Stock actual:</strong> <?= htmlspecialchars($producto['stock_actual'] ?? 0) ?></p>
                    <p><strong>Stock mínimo:</strong> <?= htmlspecialchars($producto['stock_minimo'] ?? 0) ?></p>

                    <h6 class="mt-4">Últimos movimientos</h6>
                    <?php if (!empty($movimientos)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Tipo</th>
                                        <th>Cantidad</th>
                                        <th>Motivo</th>
                                        <th>Usuario</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($movimientos as $mov): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($mov['fecha'] ?? $mov['fecha_movimiento'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($mov['tipo'] ?? $mov['tipo_movimiento'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($mov['cantidad'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($mov['motivo'] ?? $mov['observaciones'] ?? '') ?></td>
                                            <td><?= htmlspecialchars($mov['usuario_nombre'] ?? $mov['usuario'] ?? '') ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">No hay movimientos registrados para este producto.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>