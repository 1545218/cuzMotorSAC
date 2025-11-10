<?php /* header ya se incluye desde Controller->view */
// Asegurar variables por defecto para evitar notices
$cotizacion = isset($cotizacion) && is_array($cotizacion) ? $cotizacion : [];
$detalles = isset($detalles) && is_array($detalles) ? $detalles : [];
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-file-invoice mr-2"></i>Cotización N° <?= htmlspecialchars($cotizacion['numero_cotizacion'] ?? '') ?>
                    </h3>
                    <div>
                        <?php if ($this->auth->checkPermission('cotizaciones.update') && $cotizacion['estado'] == 'pendiente'): ?>
                            <a href="?page=cotizaciones&action=edit&id=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-warning">
                                <i class="fas fa-edit"></i> Editar
                            </a>
                        <?php endif; ?>

                        <?php if (in_array($cotizacion['estado'], ['pendiente', 'aprobada'])): ?>
                            <a href="?page=ventas&action=create-from-cotizacion&cotizacion=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-success ml-2">
                                <i class="fas fa-cash-register"></i> Crear Venta
                            </a>
                        <?php endif; ?>

                        <a href="?page=cotizaciones&action=pdf&id=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-danger ml-2" target="_blank">
                            <i class="fas fa-file-pdf"></i> Descargar PDF
                        </a>

                        <a href="?page=cotizaciones" class="btn btn-secondary ml-2">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    <div class="row">
                        <!-- Información de la Empresa -->
                        <div class="col-md-6">
                            <div class="text-center mb-4">
                                <h4 class="text-primary font-weight-bold"><?= COMPANY_NAME ?></h4>
                                <p class="mb-1">Venta de Repuestos y Accesorios Automotrices</p>
                                <p class="mb-1">RUC: <?= COMPANY_RUC ?></p>
                                <p class="mb-1">Dirección: <?= COMPANY_ADDRESS ?></p>
                                <p class="mb-0">Teléfono: <?= COMPANY_PHONE ?> | Email: <?= COMPANY_EMAIL ?></p>
                            </div>
                        </div>

                        <!-- Estado y Fechas -->
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-6">
                                            <strong>Estado:</strong><br>
                                            <?php
                                            $badge_class = [
                                                'pendiente' => 'warning',
                                                'aceptada' => 'success',
                                                'rechazada' => 'danger',
                                                'vencida' => 'secondary'
                                            ];
                                            $estado_display = [
                                                'pendiente' => 'Pendiente',
                                                'aceptada' => 'Aceptada',
                                                'rechazada' => 'Rechazada',
                                                'vencida' => 'Vencida'
                                            ];
                                            $fecha_venc = $cotizacion['fecha_vencimiento'] ?? null;
                                            $estado_actual = $cotizacion['estado'] ?? 'pendiente';
                                            $estado = ($fecha_venc && $fecha_venc < date('Y-m-d') && $estado_actual == 'pendiente') ? 'vencida' : $estado_actual;
                                            ?>
                                            <span class="badge badge-<?= $badge_class[$estado] ?? 'secondary' ?> badge-lg">
                                                <?= $estado_display[$estado] ?? ucfirst($estado) ?>
                                            </span>
                                        </div>
                                        <div class="col-6">
                                            <strong>Fecha:</strong><br>
                                            <?= isset($cotizacion['fecha_cotizacion']) ? formatDate($cotizacion['fecha_cotizacion']) : '-' ?>
                                        </div>
                                    </div>
                                    <div class="row mt-2">
                                        <div class="col-6">
                                            <strong>Válida hasta:</strong><br>
                                            <?= isset($cotizacion['fecha_vencimiento']) ? formatDate($cotizacion['fecha_vencimiento']) : '-' ?>
                                            <?php if (!empty($cotizacion['fecha_vencimiento']) && ($cotizacion['fecha_vencimiento'] < date('Y-m-d')) && (($cotizacion['estado'] ?? '') == 'pendiente')): ?>
                                                <br><small class="text-danger"><i class="fas fa-exclamation-triangle"></i> Vencida</small>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-6">
                                            <strong>Vendedor:</strong><br>
                                            <?= htmlspecialchars($cotizacion['vendedor_nombre'] ?? '') ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr>

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
                                <label class="font-weight-bold">Cliente:</label>
                                <p class="mb-1"><?= htmlspecialchars($cotizacion['cliente_nombre'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Documento:</label>
                                <p class="mb-1"><?= htmlspecialchars($cotizacion['cliente_tipo_documento'] ?? '') ?>: <?= htmlspecialchars($cotizacion['cliente_numero_documento'] ?? '') ?></p>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Tipo:</label>
                                <p class="mb-1">
                                    <span class="badge badge-<?= (isset($cotizacion['tipo_cliente']) && $cotizacion['tipo_cliente'] == 'mayorista') ? 'primary' : 'secondary' ?>">
                                        <?= isset($cotizacion['tipo_cliente']) ? ucfirst($cotizacion['tipo_cliente']) : '-' ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Email:</label>
                                <p class="mb-1"><?= !empty($cotizacion['cliente_email']) ? htmlspecialchars($cotizacion['cliente_email']) : '<span class="text-muted">No especificado</span>' ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Teléfono:</label>
                                <p class="mb-1"><?= !empty($cotizacion['cliente_telefono']) ? htmlspecialchars($cotizacion['cliente_telefono']) : '<span class="text-muted">No especificado</span>' ?></p>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Dirección:</label>
                                <p class="mb-1"><?= !empty($cotizacion['cliente_direccion']) ? htmlspecialchars($cotizacion['cliente_direccion']) : '<span class="text-muted">No especificada</span>' ?></p>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($cotizacion['observaciones'])): ?>
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label class="font-weight-bold">Observaciones:</label>
                                    <p class="mb-1"><?= nl2br(htmlspecialchars($cotizacion['observaciones'])) ?></p>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <hr>

                    <!-- Productos Cotizados -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="text-primary border-bottom pb-2 mb-3">
                                <i class="fas fa-box mr-2"></i>Productos Cotizados
                            </h5>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead class="thead-light">
                                <tr>
                                    <th>Ítem</th>
                                    <th>Producto</th>
                                    <th class="text-center">Cantidad</th>
                                    <th class="text-right">Precio Unit.</th>
                                    <th class="text-center">Descuento</th>
                                    <th class="text-right">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($detalles)): ?>
                                    <?php $item = 1; ?>
                                    <?php foreach ($detalles as $detalle): ?>
                                        <tr>
                                            <td class="text-center"><?= $item++ ?></td>
                                            <td>
                                                <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong><br>
                                                <small class="text-muted">
                                                    Código: <?= htmlspecialchars($detalle['producto_codigo']) ?><br>
                                                    Categoría: <?= htmlspecialchars($detalle['categoria_nombre']) ?>
                                                </small>
                                            </td>
                                            <td class="text-center"><?= number_format($detalle['cantidad'] ?? 0) ?></td>
                                            <td class="text-right"><?= formatCurrency($detalle['precio_unitario'] ?? 0) ?></td>
                                            <td class="text-center">
                                                <?php if (!empty($detalle['descuento']) && $detalle['descuento'] > 0): ?>
                                                    <?= number_format($detalle['descuento'], 2) ?>%<br>
                                                    <small class="text-success">-<?= formatCurrency(($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0) * (($detalle['descuento'] ?? 0) / 100)) ?></small>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-right">
                                                <strong><?= formatCurrency(($detalle['cantidad'] ?? 0) * ($detalle['precio_unitario'] ?? 0) * (1 - (($detalle['descuento'] ?? 0) / 100))) ?></strong>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Totales -->
                    <div class="row">
                        <div class="col-md-8"></div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>Subtotal:</span>
                                        <span><?= formatCurrency($cotizacion['subtotal'] ?? 0) ?></span>
                                    </div>
                                    <?php if (!empty($cotizacion['descuento_total']) && $cotizacion['descuento_total'] > 0): ?>
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Descuento Total:</span>
                                            <span class="text-success">-<?= formatCurrency($cotizacion['descuento_total']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <span>IGV (<?= DEFAULT_IVA ?>%):</span>
                                        <span><?= formatCurrency($cotizacion['igv'] ?? 0) ?></span>
                                    </div>
                                    <hr>
                                    <div class="d-flex justify-content-between">
                                        <strong class="h5">Total:</strong>
                                        <strong class="h5 text-primary"><?= formatCurrency($cotizacion['total'] ?? 0) ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Términos y Condiciones -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-secondary border-bottom pb-2 mb-3">
                                <i class="fas fa-info-circle mr-2"></i>Términos y Condiciones
                            </h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <ul class="list-unstyled small text-muted">
                                        <li><i class="fas fa-check text-success mr-2"></i>Precios incluyen IGV</li>
                                        <li><i class="fas fa-check text-success mr-2"></i>Validez de la oferta: hasta la fecha de vencimiento</li>
                                        <li><i class="fas fa-check text-success mr-2"></i>Stock sujeto a disponibilidad</li>
                                    </ul>
                                </div>
                                <div class="col-md-6">
                                    <ul class="list-unstyled small text-muted">
                                        <li><i class="fas fa-check text-success mr-2"></i>Garantía según especificaciones del fabricante</li>
                                        <li><i class="fas fa-check text-success mr-2"></i>Tiempo de entrega: 24-48 horas</li>
                                        <li><i class="fas fa-check text-success mr-2"></i>Modalidad de pago: contra entrega o transferencia</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Información de Fechas -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h6 class="text-secondary border-bottom pb-2 mb-3">
                                <i class="fas fa-clock mr-2"></i>Historial de la Cotización
                            </h6>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Fecha de Creación:</label>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-plus mr-1"></i>
                                            <?= isset($cotizacion['fecha_creacion']) ? formatDate($cotizacion['fecha_creacion'], DATETIME_FORMAT) : '-' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Última Actualización:</label>
                                        <p class="mb-0">
                                            <i class="fas fa-calendar-check mr-1"></i>
                                            <?= !empty($cotizacion['fecha_actualizacion']) ? formatDate($cotizacion['fecha_actualizacion'], DATETIME_FORMAT) : 'No actualizada' ?>
                                        </p>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label class="font-weight-bold">Creada por:</label>
                                        <p class="mb-0">
                                            <i class="fas fa-user mr-1"></i>
                                            <?= htmlspecialchars($cotizacion['vendedor_nombre'] ?? '') ?>
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
                            <?php if ($this->auth->checkPermission('cotizaciones.update') && $cotizacion['estado'] == 'pendiente'): ?>
                                <a href="?page=cotizaciones&action=edit&id=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-warning">
                                    <i class="fas fa-edit"></i> Editar Cotización
                                </a>

                                <div class="btn-group ml-2" role="group">
                                    <button type="button" class="btn btn-success dropdown-toggle"
                                        data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <i class="fas fa-check"></i> Cambiar Estado
                                    </button>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item text-success"
                                            href="?page=cotizaciones&action=aceptar&id=<?= $cotizacion['id_cotizacion'] ?>"
                                            onclick="return confirm('¿Está seguro de aceptar esta cotización?')">
                                            <i class="fas fa-check mr-2"></i>Aceptar Cotización
                                        </a>
                                        <a class="dropdown-item text-danger"
                                            href="?page=cotizaciones&action=rechazar&id=<?= $cotizacion['id_cotizacion'] ?>"
                                            onclick="return confirm('¿Está seguro de rechazar esta cotización?')">
                                            <i class="fas fa-times mr-2"></i>Rechazar Cotización
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>

                            <a href="?page=cotizaciones&action=pdf&id=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-danger ml-2" target="_blank">
                                <i class="fas fa-file-pdf"></i> Descargar PDF
                            </a>

                            <?php if ($cotizacion['estado'] == 'aceptada' && $this->auth->checkPermission('ventas.create')): ?>
                                <a href="?page=ventas&action=create&cotizacion=<?= $cotizacion['id_cotizacion'] ?>" class="btn btn-info ml-2">
                                    <i class="fas fa-shopping-cart"></i> Generar Venta
                                </a>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4 text-right">
                            <a href="?page=cotizaciones" class="btn btn-secondary">
                                <i class="fas fa-list"></i> Volver al Listado
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// El footer se incluye automáticamente desde el controlador