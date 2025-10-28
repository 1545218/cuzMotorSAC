<?php
// Vista de alertas de stock bajo
?>
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card mt-4">
                <div class="card-header bg-warning text-dark">
                    <h4 class="mb-0"><i class="fas fa-bell me-2"></i>Alertas de Stock Bajo</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($alertas)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Código</th>
                                        <th>Nombre</th>
                                        <th>Stock Actual</th>
                                        <th>Stock Mínimo</th>
                                        <th>% Actual</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($alertas as $producto): ?>
                                        <?php
                                        $stockActual = isset($producto['stock_actual']) ? (float)$producto['stock_actual'] : 0;
                                        $stockMinimo = isset($producto['stock_minimo']) ? (float)$producto['stock_minimo'] : 1;
                                        $porcentaje = $stockMinimo > 0 ? round(($stockActual / $stockMinimo) * 100, 1) : 0;
                                        $porcentajeClass = $porcentaje <= 50 ? 'text-danger fw-bold' : ($porcentaje <= 100 ? 'text-warning fw-bold' : 'text-success');
                                        ?>
                                        <tr>
                                            <td><?= htmlspecialchars($producto['codigo'] ?? $producto['id_producto'] ?? $producto['codigo_barras'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($producto['producto_nombre'] ?? $producto['nombre'] ?? '-') ?></td>
                                            <td class="text-danger fw-bold"><?= htmlspecialchars($producto['stock_actual'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($producto['stock_minimo'] ?? '-') ?></td>
                                            <td class="<?= $porcentajeClass ?>"><?= $porcentaje ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-success mb-0" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            No hay productos con stock bajo en este momento.
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($_SESSION['alerta_correo'])): ?>
                        <div class="alert alert-info alert-dismissible fade show" role="alert">
                            <?= htmlspecialchars($_SESSION['alerta_correo']) ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Cerrar"></button>
                        </div>
                        <?php unset($_SESSION['alerta_correo']); ?>
                    <?php endif; ?>
                    <?php if (isset($correos_notificacion) && is_array($correos_notificacion)): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-envelope me-2"></i>Correos para notificación de stock bajo</h5>
                            <form method="post" action="?page=inventario&action=addCorreoNotificacion" class="row g-2 align-items-center mb-2">
                                <div class="col-auto">
                                    <input type="email" name="email" class="form-control form-control-sm" placeholder="Nuevo correo" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary">Añadir correo</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($correos_notificacion as $correo): ?>
                                    <li class="list-group-item d-flex justify-content-between align-items-center p-2">
                                        <span><?= htmlspecialchars($correo['email']) ?></span>
                                        <form method="post" action="?page=inventario&action=deleteCorreoNotificacion" class="mb-0">
                                            <input type="hidden" name="id" value="<?= $correo['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este correo?')">
                                                <i class="fas fa-trash-alt"></i>
                                            </button>
                                        </form>
                                    </li>
                                <?php endforeach; ?>
                                <?php if (empty($correos_notificacion)): ?>
                                    <li class="list-group-item text-muted">No hay correos registrados.</li>
                                <?php endif; ?>
                            </ul>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>