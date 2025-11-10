<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="?page=dashboard"><i class="fas fa-home"></i> Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="?page=cotizaciones"><i class="fas fa-file-invoice-dollar"></i> Cotizaciones</a></li>
                    <li class="breadcrumb-item active">Crear Venta</li>
                </ol>
            </nav>

            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-primary"><i class="fas fa-plus me-2"></i>Crear Venta desde Cotización</h2>
                <a href="?page=cotizaciones" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Volver
                </a>
            </div>

            <!-- Error Alert -->
            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($_GET['error']) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row">
                <!-- Cotización Info -->
                <div class="col-md-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <?php
                            // Variables defensivas: soportar distintos nombres de columnas entre instalaciones
                            $cot = $data['cotizacion'] ?? [];
                            $numero = $cot['numero_cotizacion'] ?? $cot['numero'] ?? $cot['numero_documento'] ?? ($cot['id_cotizacion'] ?? '');
                            $estado = $cot['estado'] ?? 'pendiente';
                            ?>
                            <h5><i class="fas fa-file-invoice-dollar me-2"></i>Cotización: <?= htmlspecialchars((string)$numero) ?></h5>
                            <span class="badge bg-<?= $estado === 'pendiente' ? 'warning' : 'success' ?>"><?= strtoupper(htmlspecialchars((string)$estado)) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <?php
                                    $fechaRaw = $cot['fecha_cotizacion'] ?? $cot['fecha'] ?? $cot['fecha_emision'] ?? null;
                                    $fechaDisplay = '-';
                                    if (!empty($fechaRaw)) {
                                        $ts = strtotime($fechaRaw);
                                        if ($ts !== false && $ts > 0) {
                                            $fechaDisplay = date('d/m/Y', $ts);
                                        }
                                    }

                                    $clienteNombre = $cot['cliente_nombre'] ?? $cot['nombre'] ?? '';
                                    $numeroDocumento = $cot['numero_documento'] ?? $cot['cliente_numero_documento'] ?? '';
                                    ?>
                                    <p><strong>Fecha:</strong> <?= htmlspecialchars($fechaDisplay) ?></p>
                                    <p><strong>Cliente:</strong> <?= htmlspecialchars((string)$clienteNombre) ?></p>
                                    <p><strong>Documento:</strong> <?= htmlspecialchars((string)$numeroDocumento) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Subtotal:</strong> S/ <?= number_format($cot['subtotal'] ?? 0, 2) ?></p>
                                    <p><strong>IGV:</strong> S/ <?= number_format($cot['igv'] ?? 0, 2) ?></p>
                                    <p><strong>Total:</strong> <span class="text-success fs-5">S/ <?= number_format($cot['total'] ?? 0, 2) ?></span></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Productos -->
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-box me-2"></i>Productos (<?= count($cot['detalles'] ?? []) ?> items)</h5>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Código</th>
                                            <th>Producto</th>
                                            <th>Cant.</th>
                                            <th>Precio</th>
                                            <th>Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($cot['detalles'] ?? [] as $detalle): ?>
                                            <tr>
                                                <td><code><?= htmlspecialchars((string)($detalle['producto_codigo'] ?? '')) ?></code></td>
                                                <td><?= htmlspecialchars((string)($detalle['producto_nombre'] ?? '')) ?></td>
                                                <td class="text-center"><?= number_format($detalle['cantidad'] ?? 0, 0) ?></td>
                                                <td class="text-end">S/ <?= number_format($detalle['precio_unitario'] ?? 0, 2) ?></td>
                                                <td class="text-end">S/ <?= number_format($detalle['subtotal'] ?? 0, 2) ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                    <tfoot class="table-light">
                                        <tr>
                                            <th colspan="4" class="text-end">TOTAL:</th>
                                            <th class="text-end">S/ <?= number_format($cot['total'] ?? 0, 2) ?></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Form -->
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-header">
                            <h5><i class="fas fa-cash-register me-2"></i>Datos de Venta</h5>
                        </div>
                        <div class="card-body">
                            <form id="ventaForm" method="POST" action="?page=ventas&action=store-from-cotizacion">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                                <input type="hidden" name="id_cotizacion" value="<?= htmlspecialchars($data['cotizacion']['id_cotizacion'] ?? $data['cotizacion']['id']) ?>">

                                <div class="mb-3">
                                    <label for="fecha_venta" class="form-label">Fecha de Venta</label>
                                    <input type="date" id="fecha_venta" name="fecha_venta" class="form-control" value="<?= date('Y-m-d') ?>" required>
                                </div>

                                <div class="mb-3">
                                    <label for="tipo_pago" class="form-label">Tipo de Pago</label>
                                    <select id="tipo_pago" name="tipo_pago" class="form-control" required>
                                        <option value="contado">Contado</option>
                                        <option value="credito">Crédito</option>
                                    </select>
                                </div>

                                <div class="mb-3">
                                    <label for="observaciones" class="form-label">Observaciones</label>
                                    <textarea id="observaciones" name="observaciones" class="form-control" rows="3"><?= htmlspecialchars($_POST['observaciones'] ?? '') ?></textarea>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check me-2"></i>Confirmar Cotización
                                    </button>

                                    <!-- Botón de Rechazar Cotización -->
                                    <a href="?page=cotizaciones&action=rechazar&id=<?= $cot['id_cotizacion'] ?? '' ?>"
                                        class="btn btn-danger"
                                        onclick="return confirm('¿Está seguro de que desea rechazar esta cotización?')">
                                        <i class="fas fa-times me-2"></i>Rechazar Cotización
                                    </a>

                                    <!-- Botón de Eliminar Cotización -->
                                    <a href="?page=cotizaciones&action=eliminar&id=<?= $cot['id_cotizacion'] ?? '' ?>"
                                        class="btn btn-outline-danger"
                                        onclick="return confirm('¿Está seguro de que desea ELIMINAR PERMANENTEMENTE esta cotización? Esta acción no se puede deshacer.')">
                                        <i class="fas fa-trash me-2"></i>Eliminar Cotización
                                    </a>

                                    <a href="?page=cotizaciones" class="btn btn-outline-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Cancelar
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('ventaForm');

        form.addEventListener('submit', function(e) {
            const fecha = new Date(document.getElementById('fecha_venta').value);
            const hoy = new Date();
            const tipoPago = document.getElementById('tipo_pago').value;

            if (fecha > hoy) {
                alert('La fecha no puede ser futura');
                e.preventDefault();
                return;
            }

            if (!tipoPago) {
                alert('Debe seleccionar un tipo de pago');
                e.preventDefault();
                return;
            }
        });

        // Auto-dismiss alerts
        setTimeout(() => {
            document.querySelectorAll('.alert').forEach(alert => {
                new bootstrap.Alert(alert).close();
            });
        }, 5000);
    });
</script>

</div>
</div>