<?php /* header ya se incluye desde Controller->view */ ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-eye mr-2"></i>Detalle del Producto
                    </h3>
                    <div>
                        <?php if ($this->auth->checkPermission('productos.update')): ?>
                            <a href="?page=productos&action=edit&id=<?= $producto['id_producto'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>
                        <a href="?page=productos" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Información Principal -->
                        <div class="col-md-8">
                            <h4 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-box mr-2"></i>Información General
                            </h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Nombre del Producto:</label>
                                        <p class="form-control-static"><?= htmlspecialchars($producto['nombre']) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Código de Barras:</label>
                                        <p class="form-control-static"><?= $producto['codigo_barras'] ? htmlspecialchars($producto['codigo_barras']) : '<span class="text-muted">No asignado</span>' ?></p>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="font-weight-bold">Descripción:</label>
                                <p class="form-control-static"><?= $producto['descripcion'] ? htmlspecialchars($producto['descripcion']) : '<span class="text-muted">Sin descripción</span>' ?></p>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Categoría:</label>
                                        <p class="form-control-static">
                                            <span class="badge badge-primary"><?= htmlspecialchars($producto['categoria_nombre']) ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Subcategoría:</label>
                                        <p class="form-control-static">
                                            <span class="badge badge-info"><?= htmlspecialchars($producto['subcategoria_nombre']) ?></span>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Marca:</label>
                                        <p class="form-control-static">
                                            <?= $producto['marca_nombre'] ? '<span class="badge bg-secondary">' . htmlspecialchars($producto['marca_nombre']) . '</span>' : '<span class="text-muted">Sin marca</span>' ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Unidad:</label>
                                        <p class="form-control-static"><?= htmlspecialchars($producto['unidad_nombre']) ?></p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Ubicación:</label>
                                        <p class="form-control-static">
                                            <?= $producto['ubicacion_nombre'] ? htmlspecialchars($producto['ubicacion_nombre']) : '<span class="text-muted">Sin ubicación</span>' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Estado:</label>
                                        <p class="form-control-static">
                                            <?php if ($producto['estado'] === 'activo'): ?>
                                                <span class="badge bg-success">Activo</span>
                                            <?php else: ?>
                                                <span class="badge bg-danger">Inactivo</span>
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </div>
                            </div>

                            <h4 class="text-success border-bottom pb-2 mb-3 mt-4">
                                <i class="fas fa-dollar-sign mr-2"></i>Información de Precios
                            </h4>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Precio Unitario:</label>
                                        <p class="form-control-static">
                                            <span class="h5 text-success"><?= formatCurrency($producto['precio_unitario']) ?></span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Panel de Stock -->
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h5 class="card-title mb-0">
                                        <i class="fas fa-warehouse mr-2"></i>Control de Stock
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <div class="text-center mb-3">
                                        <?php
                                        $stock_actual = isset($producto['stock_actual']) ? (int)$producto['stock_actual'] : 0;
                                        $stock_minimo = isset($producto['stock_minimo']) ? (int)$producto['stock_minimo'] : 0;
                                        $stock_class = ($stock_minimo > 0 && $stock_actual <= $stock_minimo) ? 'text-danger' : 'text-primary';
                                        ?>
                                        <h2 class="display-4 <?= $stock_class ?>" style="user-select:none;">
                                            <?= number_format($stock_actual) ?>
                                        </h2>
                                        <p class="text-muted mb-1">Stock Actual (solo lectura)</p>
                                        <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="window.location='?page=inventario&action=movimiento&id=<?= $producto['id_producto'] ?>'">
                                            <i class="fas fa-boxes"></i> Ir al módulo de Inventario
                                        </button>
                                        <small class="form-text text-muted d-block mt-2">
                                            <i class="fas fa-info-circle"></i> Los cambios de stock deben realizarse desde el módulo de Inventario para registrar correctamente los movimientos.
                                        </small>
                                    </div>

                                    <div class="mb-3">
                                        <label class="font-weight-bold">Stock Mínimo:</label>
                                        <p class="mb-1"><?= number_format($stock_minimo) ?></p>
                                        <?php if ($stock_minimo > 0 && $stock_actual <= $stock_minimo): ?>
                                            <div class="alert alert-warning alert-sm">
                                                <i class="fas fa-exclamation-triangle"></i> Stock bajo
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <?php if ($this->auth->checkPermission('inventario.create')): ?>
                                        <div class="d-grid gap-2">
                                            <a href="?page=inventario&action=movimiento&id=<?= $producto['id_producto'] ?>" class="btn btn-success btn-sm">
                                                <i class="fas fa-plus"></i> Registrar Entrada
                                            </a>
                                            <a href="?page=inventario&action=salida&id=<?= $producto['id_producto'] ?>" class="btn btn-warning btn-sm">
                                                <i class="fas fa-minus"></i> Registrar Salida
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Historial de Movimientos -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4 class="text-info border-bottom pb-2 mb-3">
                                <i class="fas fa-history mr-2"></i>Últimos Movimientos de Inventario
                            </h4>

                            <?php if (!empty($movimientos) && is_array($movimientos)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="thead-light">
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Tipo</th>
                                                <th>Cantidad</th>
                                                <th>Motivo</th>
                                                <th>Usuario</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($movimientos as $movimiento): ?>
                                                <tr>
                                                    <td><?= isset($movimiento['fecha']) ? formatDate($movimiento['fecha'], DATETIME_FORMAT) : '-' ?></td>
                                                    <td>
                                                        <?php if (($movimiento['tipo'] ?? '') === 'entrada'): ?>
                                                            <span class="badge bg-success">Entrada</span>
                                                        <?php elseif (($movimiento['tipo'] ?? '') === 'salida'): ?>
                                                            <span class="badge bg-danger">Salida</span>
                                                        <?php elseif (($movimiento['tipo'] ?? '') === 'ajuste'): ?>
                                                            <span class="badge bg-info">Ajuste</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-secondary">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td>
                                                        <span class="<?= ($movimiento['tipo'] ?? '') === 'entrada' ? 'text-success' : 'text-danger' ?>">
                                                            <?= ($movimiento['tipo'] ?? '') === 'entrada' ? '+' : '-' ?><?= number_format($movimiento['cantidad'] ?? 0) ?>
                                                        </span>
                                                    </td>
                                                    <td><?= isset($movimiento['origen']) && $movimiento['origen'] !== null ? htmlspecialchars($movimiento['origen']) : '-' ?></td>
                                                    <td><?= isset($movimiento['usuario_nombre']) && $movimiento['usuario_nombre'] !== null ? htmlspecialchars($movimiento['usuario_nombre']) : '-' ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="text-center">
                                    <a href="?page=inventario&action=producto&id=<?= $producto['id_producto'] ?>" class="btn btn-outline-primary">
                                        <i class="fas fa-list"></i> Ver Todo el Historial
                                    </a>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> No hay movimientos de inventario registrados para este producto.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Información de Fechas -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h4 class="text-secondary border-bottom pb-2 mb-3">
                                <i class="fas fa-clock mr-2"></i>Información de Registro
                            </h4>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Fecha de Creación:</label>
                                        <p class="form-control-static">
                                            <i class="fas fa-calendar-plus mr-1"></i>
                                            <?php
                                            $fecha_creacion = $producto['fecha_creacion'] ?? $producto['fecha_registro'] ?? null;
                                            echo $fecha_creacion ? formatDate($fecha_creacion, DATETIME_FORMAT) : '<span class="text-muted">No disponible</span>';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Última Actualización:</label>
                                        <p class="form-control-static">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            <?php
                                            $fecha_actualizacion = $producto['fecha_actualizacion'] ?? $producto['updated_at'] ?? null;
                                            echo $fecha_actualizacion ? formatDate($fecha_actualizacion, DATETIME_FORMAT) : 'No actualizado';
                                            ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer">
                    <div class="row">
                        <div class="col-md-8">
                            <?php if ($this->auth->checkPermission('productos.update')): ?>
                                <a href="?page=productos&action=edit&id=<?= $producto['id_producto'] ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Editar Producto
                                </a>
                            <?php endif; ?>

                            <?php if ($this->auth->checkPermission('inventario.create')): ?>
                                <a href="?page=inventario&action=movimiento&id=<?= $producto['id_producto'] ?>" class="btn btn-success ml-2">
                                    <i class="fas fa-warehouse"></i> Gestionar Stock
                                </a>
                            <?php endif; ?>

                            <?php if ($this->auth->checkPermission('cotizaciones.create')): ?>
                                <a href="?page=cotizaciones&action=create&producto=<?= $producto['id_producto'] ?>" class="btn btn-info ml-2">
                                    <i class="fas fa-file-invoice"></i> Crear Cotización
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="?page=productos" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Volver al Listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php /* footer ya se incluye desde Controller->view */ ?>