<?php
$title = 'Gestión de Ventas';
$breadcrumb = [
    ['title' => 'Ventas']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-cash-register me-2"></i>Gestión de Ventas
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.location.href='<?= BASE_PATH ?>/public/?page=cotizaciones'">
                        <i class="fas fa-plus me-1"></i>Ver Cotizaciones
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="ventasTable" class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th>
                                <th>Cliente</th>
                                <th>Fecha</th>
                                <th>Total</th>
                                <th>Estado</th>
                                <th>Observaciones</th>
                                <th>Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (isset($ventas) && !empty($ventas)): ?>
                                <?php foreach ($ventas as $venta): ?>
                                    <tr>
                                        <td><?= $venta['id_cotizacion'] ?? 'N/A' ?></td>
                                        <td><?= $venta['cliente_nombre'] ?? 'Sin cliente' ?></td>
                                        <td><?= isset($venta['fecha']) ? date('d/m/Y', strtotime($venta['fecha'])) : '-' ?></td>
                                        <td><?= isset($venta['total']) ? 'S/ ' . number_format($venta['total'], 2) : 'S/ 0.00' ?></td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'pendiente' => 'warning',
                                                'aprobada' => 'success',
                                                'rechazada' => 'danger'
                                            ];
                                            $estado = $venta['estado'] ?? 'pendiente';
                                            $class = $badge_class[$estado] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>"><?= ucfirst($estado) ?></span>
                                        </td>
                                        <td>
                                            <?= isset($venta['observaciones']) ? htmlspecialchars($venta['observaciones']) : '-' ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="verVenta(<?= $venta['id_cotizacion'] ?>)" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="generarFactura(<?= $venta['id_cotizacion'] ?>)" title="PDF de Cotización">
                                                    <i class="fas fa-file-invoice"></i>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <br>No hay ventas registradas
                                        <br>Las cotizaciones aprobadas aparecerán aquí como ventas
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#ventasTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json'
            },
            responsive: true,
            order: [
                [2, 'desc']
            ] // Ordenar por fecha descendente
        });
    });

    function verVenta(id) {
        // Redirige a la vista de la cotización correspondiente
        window.location.href = '<?= BASE_PATH ?>/public/?page=cotizaciones&action=view&id=' + id;
    }

    function generarFactura(id) {
        // Genera el PDF de la cotización correspondiente
        window.open('<?= BASE_PATH ?>/public/?page=cotizaciones&action=pdf&id=' + id, '_blank');
    }
</script>