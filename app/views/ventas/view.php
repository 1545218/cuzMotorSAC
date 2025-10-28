<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Navegación breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item">
                        <a href="?page=dashboard" class="text-decoration-none">
                            <i class="fas fa-home"></i> Dashboard
                        </a>
                    </li>
                    <li class="breadcrumb-item">
                        <a href="?page=ventas" class="text-decoration-none">
                            <i class="fas fa-cash-register"></i> Ventas
                        </a>
                    </li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?= htmlspecialchars($data['venta']['numero_venta']) ?>
                    </li>
                </ol>
            </nav>

            <!-- Encabezado con acciones -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary mb-0">
                    <i class="fas fa-receipt me-2"></i>
                    Venta <?= htmlspecialchars($data['venta']['numero_venta']) ?>
                </h2>
                <div class="d-flex gap-2">
                    <a href="?page=ventas&action=pdf&id=<?= $data['venta']['id_venta'] ?>"
                        class="btn btn-danger"
                        target="_blank">
                        <i class="fas fa-file-pdf me-2"></i>Descargar PDF
                    </a>
                    <?php if ($data['venta']['estado'] === 'completada'): ?>
                        <button type="button"
                            class="btn btn-warning"
                            onclick="confirmarAnulacion(<?= $data['venta']['id_venta'] ?>, '<?= htmlspecialchars($data['venta']['numero_venta']) ?>')">
                            <i class="fas fa-ban me-2"></i>Anular Venta
                        </button>
                    <?php endif; ?>
                    <a href="?page=ventas" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Volver
                    </a>
                </div>
            </div>

            <!-- Mensajes de alerta -->
            <?php if (isset($_GET['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?= htmlspecialchars($_GET['success']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Información general -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-info-circle me-2"></i>Información de la Venta
                            </h5>
                            <span class="badge bg-<?= $data['venta']['estado'] === 'completada' ? 'success' : 'danger' ?> fs-6">
                                <?= $data['venta']['estado'] === 'completada' ? 'COMPLETADA' : 'ANULADA' ?>
                            </span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted"><strong>Número de Venta:</strong></td>
                                            <td><?= htmlspecialchars($data['venta']['numero_venta']) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Fecha:</strong></td>
                                            <td><?= date('d/m/Y H:i', strtotime($data['venta']['fecha_venta'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Vendedor:</strong></td>
                                            <td>
                                                <span class="badge bg-secondary">
                                                    <?= htmlspecialchars($data['venta']['vendedor_nombre']) ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php if (!empty($data['venta']['numero_cotizacion'])): ?>
                                            <tr>
                                                <td class="text-muted"><strong>Cotización:</strong></td>
                                                <td>
                                                    <a href="?page=cotizaciones&action=view&id=<?= $data['venta']['id_cotizacion'] ?>"
                                                        class="text-decoration-none">
                                                        <?= htmlspecialchars($data['venta']['numero_cotizacion']) ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td class="text-muted"><strong>Tipo de Pago:</strong></td>
                                            <td>
                                                <?php
                                                $tipos_pago = [
                                                    'contado' => ['icon' => 'fa-money-bill', 'color' => 'success', 'text' => 'Contado'],
                                                    'credito' => ['icon' => 'fa-credit-card', 'color' => 'warning', 'text' => 'Crédito'],
                                                    'transferencia' => ['icon' => 'fa-exchange-alt', 'color' => 'info', 'text' => 'Transferencia'],
                                                    'tarjeta' => ['icon' => 'fa-credit-card', 'color' => 'primary', 'text' => 'Tarjeta']
                                                ];
                                                $tipo = $tipos_pago[$data['venta']['tipo_pago']] ?? ['icon' => 'fa-question', 'color' => 'secondary', 'text' => $data['venta']['tipo_pago']];
                                                ?>
                                                <span class="badge bg-<?= $tipo['color'] ?>">
                                                    <i class="fas <?= $tipo['icon'] ?> me-1"></i>
                                                    <?= $tipo['text'] ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Subtotal:</strong></td>
                                            <td>S/ <?= number_format($data['venta']['subtotal'], 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>IGV (18%):</strong></td>
                                            <td>S/ <?= number_format($data['venta']['igv'], 2) ?></td>
                                        </tr>
                                        <tr>
                                            <td class="text-muted"><strong>Total:</strong></td>
                                            <td><strong class="text-success fs-5">S/ <?= number_format($data['venta']['total'], 2) ?></strong></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <?php if (!empty($data['venta']['observaciones'])): ?>
                                <div class="mt-3">
                                    <strong class="text-muted">Observaciones:</strong>
                                    <p class="mt-2 p-3 bg-light rounded">
                                        <?= nl2br(htmlspecialchars($data['venta']['observaciones'])) ?>
                                    </p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Detalles de productos -->
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-box me-2"></i>
                                Productos Vendidos (<?= count($data['venta']['detalles']) ?> items)
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Unidad</th>
                                            <th class="text-center">Cantidad</th>
                                            <th class="text-end">Precio Unit.</th>
                                            <th class="text-end">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($data['venta']['detalles'] as $detalle): ?>
                                            <tr>
                                                <td>
                                                    <code><?= htmlspecialchars($detalle['producto_codigo']) ?></code>
                                                </td>
                                                <td>
                                                    <strong><?= htmlspecialchars($detalle['producto_nombre']) ?></strong>
                                                </td>
                                                <td>
                                                    <span class="badge bg-light text-dark">
                                                        <?= htmlspecialchars($detalle['unidad'] ?? 'UND') ?>
                                                    </span>
                                                </td>
                                                <td class="text-center">
                                                    <span class="badge bg-primary">
                                                        <?= number_format($detalle['cantidad'], 0) ?>
                                                    </span>
                                                </td>
                                                <td class="text-end">
                                                    S/ <?= number_format($detalle['precio_unitario'], 2) ?>
                                                </td>
                                                <td class="text-end">
                                                    <strong>S/ <?= number_format($detalle['subtotal'], 2) ?></strong>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="5" class="text-end">Subtotal:</th>
                                            <th class="text-end">S/ <?= number_format($data['venta']['subtotal'], 2) ?></th>
                                        </tr>
                                        <tr>
                                            <th colspan="5" class="text-end">IGV (18%):</th>
                                            <th class="text-end">S/ <?= number_format($data['venta']['igv'], 2) ?></th>
                                        </tr>
                                        <tr class="table-success">
                                            <th colspan="5" class="text-end">TOTAL:</th>
                                            <th class="text-end">
                                                <span class="fs-5">S/ <?= number_format($data['venta']['total'], 2) ?></span>
                                            </th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información del cliente -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-user me-2"></i>Información del Cliente
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-3">
                                <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center"
                                    style="width: 60px; height: 60px;">
                                    <i class="fas fa-user fa-2x"></i>
                                </div>
                            </div>

                            <table class="table table-borderless table-sm">
                                <tr>
                                    <td class="text-muted"><strong>Nombre:</strong></td>
                                    <td><?= htmlspecialchars($data['venta']['cliente_nombre']) ?></td>
                                </tr>
                                <tr>
                                    <td class="text-muted"><strong>Documento:</strong></td>
                                    <td><?= htmlspecialchars($data['venta']['numero_documento']) ?></td>
                                </tr>
                                <?php if (!empty($data['venta']['cliente_telefono'])): ?>
                                    <tr>
                                        <td class="text-muted"><strong>Teléfono:</strong></td>
                                        <td>
                                            <a href="tel:<?= htmlspecialchars($data['venta']['cliente_telefono']) ?>"
                                                class="text-decoration-none">
                                                <i class="fas fa-phone me-1"></i>
                                                <?= htmlspecialchars($data['venta']['cliente_telefono']) ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($data['venta']['cliente_email'])): ?>
                                    <tr>
                                        <td class="text-muted"><strong>Email:</strong></td>
                                        <td>
                                            <a href="mailto:<?= htmlspecialchars($data['venta']['cliente_email']) ?>"
                                                class="text-decoration-none">
                                                <i class="fas fa-envelope me-1"></i>
                                                <?= htmlspecialchars($data['venta']['cliente_email']) ?>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                                <?php if (!empty($data['venta']['cliente_direccion'])): ?>
                                    <tr>
                                        <td class="text-muted"><strong>Dirección:</strong></td>
                                        <td><?= nl2br(htmlspecialchars($data['venta']['cliente_direccion'])) ?></td>
                                    </tr>
                                <?php endif; ?>
                            </table>

                            <div class="mt-3">
                                <a href="?page=clientes&action=edit&id=<?= $data['venta']['id_cliente'] ?>"
                                    class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-edit me-2"></i>Editar Cliente
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Acciones rápidas -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="fas fa-tools me-2"></i>Acciones Rápidas
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <a href="?page=ventas&action=pdf&id=<?= $data['venta']['id_venta'] ?>"
                                    class="btn btn-danger btn-sm"
                                    target="_blank">
                                    <i class="fas fa-file-pdf me-2"></i>Generar PDF
                                </a>

                                <?php if ($data['venta']['estado'] === 'completada'): ?>
                                    <button type="button"
                                        class="btn btn-warning btn-sm"
                                        onclick="confirmarAnulacion(<?= $data['venta']['id_venta'] ?>, '<?= htmlspecialchars($data['venta']['numero_venta']) ?>')">
                                        <i class="fas fa-ban me-2"></i>Anular Venta
                                    </button>
                                <?php endif; ?>

                                <a href="?page=ventas" class="btn btn-outline-secondary btn-sm">
                                    <i class="fas fa-list me-2"></i>Ver Todas las Ventas
                                </a>

                                <a href="?page=cotizaciones&action=create" class="btn btn-outline-primary btn-sm">
                                    <i class="fas fa-plus me-2"></i>Nueva Cotización
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script>
    function confirmarAnulacion(id, numero) {
        if (confirm(`¿Estás seguro de que deseas anular la venta "${numero}"?\n\nEsta acción restaurará el stock de los productos y no se puede deshacer.`)) {
            window.location.href = '?page=ventas&action=anular&id=' + id;
        }
    }

    // Auto-dismiss alerts
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
</script>

</div>
</div>