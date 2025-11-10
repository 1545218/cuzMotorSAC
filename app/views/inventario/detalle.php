<?php
$inventarios = $data['inventarios'] ?? [];
$detalles = $data['detalles'] ?? [];
$inventarioSeleccionado = $data['inventarioSeleccionado'] ?? null;
$idInventario = $data['idInventario'] ?? null;
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-clipboard-list me-2"></i>
                    Detalle de Inventario
                </h2>
                <div>
                    <a href="?page=inventario" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver al Inventario
                    </a>
                </div>
            </div>

            <!-- Selector de Inventario -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-calendar-alt me-2"></i>
                        Seleccionar Inventario
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($inventarios)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            No hay inventarios registrados en el sistema.
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="selectInventario" class="form-label">Inventario:</label>
                                <select class="form-select" id="selectInventario" onchange="cambiarInventario()">
                                    <option value="">Seleccionar inventario...</option>
                                    <?php foreach ($inventarios as $inventario): ?>
                                        <option value="<?= $inventario['id_inventario'] ?>"
                                            <?= $idInventario == $inventario['id_inventario'] ? 'selected' : '' ?>>
                                            #<?= $inventario['id_inventario'] ?> -
                                            <?= date('d/m/Y H:i', strtotime($inventario['fecha'])) ?>
                                            <?php if (!empty($inventario['observaciones'])): ?>
                                                (<?= htmlspecialchars($inventario['observaciones']) ?>)
                                            <?php endif; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Información del Inventario Seleccionado -->
            <?php if ($inventarioSeleccionado): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Inventario #<?= $inventarioSeleccionado['id_inventario'] ?>
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <strong>Fecha:</strong><br>
                                <?= date('d/m/Y H:i', strtotime($inventarioSeleccionado['fecha'])) ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Usuario:</strong><br>
                                ID <?= $inventarioSeleccionado['id_usuario'] ?>
                            </div>
                            <div class="col-md-4">
                                <strong>Productos:</strong><br>
                                <?= count($detalles) ?> productos registrados
                            </div>
                        </div>
                        <?php if (!empty($inventarioSeleccionado['observaciones'])): ?>
                            <div class="row mt-3">
                                <div class="col-12">
                                    <strong>Observaciones:</strong><br>
                                    <?= nl2br(htmlspecialchars($inventarioSeleccionado['observaciones'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Tabla de Detalles -->
            <?php if (!empty($detalles)): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list-alt me-2"></i>
                            Detalle de Productos
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th width="8%">#</th>
                                        <th width="30%">Producto</th>
                                        <th width="12%">Stock Sistema</th>
                                        <th width="12%">Stock Físico</th>
                                        <th width="12%">Diferencia</th>
                                        <th width="12%">Estado</th>
                                        <th width="14%">Ubicación</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $totalDiferencias = 0;
                                    $productosConDiferencia = 0;

                                    foreach ($detalles as $index => $detalle):
                                        $diferencia = $detalle['diferencia'] ?? 0;
                                        $stockSistema = $detalle['stock_actual'] ?? 0;
                                        $stockFisico = $detalle['stock_fisico'] ?? 0;

                                        if ($diferencia != 0) {
                                            $totalDiferencias += abs($diferencia);
                                            $productosConDiferencia++;
                                        }

                                        // Determinar clase para la diferencia
                                        $claseDiferencia = '';
                                        $estadoTexto = 'Correcto';
                                        $estadoClass = 'success';

                                        if ($diferencia > 0) {
                                            $claseDiferencia = 'text-success fw-bold';
                                            $estadoTexto = 'Sobrante';
                                            $estadoClass = 'info';
                                        } elseif ($diferencia < 0) {
                                            $claseDiferencia = 'text-danger fw-bold';
                                            $estadoTexto = 'Faltante';
                                            $estadoClass = 'warning';
                                        }
                                    ?>
                                        <tr>
                                            <td><?= $index + 1 ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($detalle['producto_nombre'] ?? $detalle['nombre'] ?? 'Sin nombre') ?></strong>
                                                <?php if (!empty($detalle['codigo_barras'])): ?>
                                                    <br><small class="text-muted">Código: <?= htmlspecialchars($detalle['codigo_barras']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-secondary"><?= number_format($stockSistema) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-primary"><?= number_format($stockFisico) ?></span>
                                            </td>
                                            <td class="text-center">
                                                <span class="<?= $claseDiferencia ?>">
                                                    <?= $diferencia > 0 ? '+' : '' ?><?= number_format($diferencia) ?>
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <span class="badge bg-<?= $estadoClass ?>"><?= $estadoTexto ?></span>
                                            </td>
                                            <td>
                                                <?php
                                                // Obtener ubicación del producto
                                                try {
                                                    $db = Database::getInstance();
                                                    $ubicacion = $db->selectOne(
                                                        "SELECT u.nombre FROM ubicaciones u 
                                                         JOIN productos p ON u.id_ubicacion = p.id_ubicacion 
                                                         WHERE p.id_producto = ?",
                                                        [$detalle['id_producto']]
                                                    );
                                                    echo htmlspecialchars($ubicacion['nombre'] ?? 'Sin ubicación');
                                                } catch (Exception $e) {
                                                    echo 'Sin ubicación';
                                                }
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Resumen de Diferencias -->
                <div class="card mt-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-chart-pie me-2"></i>
                            Resumen de Conteo
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-3">
                                <div class="h4 text-primary"><?= count($detalles) ?></div>
                                <div class="text-muted">Total Productos</div>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-success"><?= count($detalles) - $productosConDiferencia ?></div>
                                <div class="text-muted">Sin Diferencias</div>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-warning"><?= $productosConDiferencia ?></div>
                                <div class="text-muted">Con Diferencias</div>
                            </div>
                            <div class="col-md-3">
                                <div class="h4 text-danger"><?= number_format($totalDiferencias) ?></div>
                                <div class="text-muted">Total Diferencias</div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php elseif ($idInventario): ?>
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                        <h4 class="text-muted">No hay productos en este inventario</h4>
                        <p class="text-muted">El inventario seleccionado no tiene productos registrados.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    function cambiarInventario() {
        const select = document.getElementById('selectInventario');
        const idInventario = select.value;

        if (idInventario) {
            window.location.href = `?page=inventario&action=detalle&id=${idInventario}`;
        } else {
            window.location.href = `?page=inventario&action=detalle`;
        }
    }
</script>