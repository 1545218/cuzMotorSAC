<?php
// Vista de alertas de stock bajo
?>

<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-xl-8">
            <div class="card shadow mt-4 border-0">
                <div class="card-header bg-warning bg-gradient text-dark d-flex align-items-center">
                    <i class="fas fa-bell me-2 fa-lg"></i>
                    <h4 class="mb-0">Alertas de Stock Bajo</h4>
                </div>
                <div class="card-body">
                    <?php if (!empty($alertas)): ?>
                        <div class="table-responsive mb-4">
                            <table class="table table-hover align-middle rounded shadow-sm overflow-hidden">
                                <thead class="table-warning text-center">
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
                                        <tr class="text-center">
                                            <td><?= htmlspecialchars($producto['codigo'] ?? $producto['id_producto'] ?? $producto['codigo_barras'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($producto['producto_nombre'] ?? $producto['nombre'] ?? '-') ?></td>
                                            <td class="text-danger fw-bold fs-6 bg-light-subtle"><?= htmlspecialchars($producto['stock_actual'] ?? '-') ?></td>
                                            <td><?= htmlspecialchars($producto['stock_minimo'] ?? '-') ?></td>
                                            <td class="<?= $porcentajeClass ?>"><?= $porcentaje ?>%</td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
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
                        <!-- DEBUG: Mostrar contenido de $correos_notificacion -->
                        <div class="alert alert-secondary" style="font-size:0.9em;">
                            <strong>Debug correos_notificacion:</strong>
                            <pre><?php print_r($correos_notificacion); ?></pre>
                        </div>
                        <div class="mb-4">
                            <h5 class="mb-3"><i class="fas fa-envelope me-2"></i>Correos para notificación de stock bajo</h5>
                            <form method="post" action="?page=inventario&action=addCorreoNotificacion" class="row g-2 align-items-center mb-3">
                                <div class="col-md-6 col-lg-5">
                                    <input type="email" name="email" class="form-control form-control-sm shadow-sm" placeholder="Nuevo correo" required>
                                </div>
                                <div class="col-auto">
                                    <button type="submit" class="btn btn-sm btn-primary shadow-sm"><i class="fas fa-plus me-1"></i>Añadir correo</button>
                                </div>
                            </form>
                            <ul class="list-group list-group-flush rounded shadow-sm">
                                <?php if (!empty($correos_notificacion)): ?>
                                    <?php foreach ($correos_notificacion as $correo): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center p-2 bg-light">
                                            <span class="fw-semibold"><i class="fas fa-envelope text-primary me-2"></i><?= htmlspecialchars($correo['email']) ?></span>
                                            <form method="post" action="?page=inventario&action=deleteCorreoNotificacion" class="mb-0">
                                                <input type="hidden" name="id" value="<?= $correo['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Eliminar este correo?')">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        </li>
                                    <?php endforeach; ?>
                                <?php else: ?>
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