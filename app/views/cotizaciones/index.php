<?php
$title = 'Gestión de Cotizaciones';
$breadcrumb = [
    ['title' => 'Cotizaciones']
];
?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-file-invoice-dollar me-2"></i>Gestión de Cotizaciones
                </h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-primary btn-sm" onclick="window.location.href='<?= BASE_PATH ?>/public/?page=cotizaciones&action=create'">
                        <i class="fas fa-plus me-1"></i>Nueva Cotización
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table id="cotizacionesTable" class="table table-striped table-hover">
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
                            <?php
                            // Debug: mostrar información de la variable
                            // echo "Cotizaciones: " . print_r($cotizaciones ?? [], true);
                            ?>
                            <?php if (isset($cotizaciones) && !empty($cotizaciones)): ?>
                                <?php foreach ($cotizaciones as $cotizacion): ?>
                                    <tr>
                                        <td><?= $cotizacion['id_cotizacion'] ?? 'N/A' ?></td>
                                        <td><?= $cotizacion['cliente_nombre'] ?? 'Sin cliente' ?></td>
                                        <td><?= isset($cotizacion['fecha']) ? date('d/m/Y', strtotime($cotizacion['fecha'])) : '-' ?></td>
                                        <td><?= isset($cotizacion['total']) ? 'S/ ' . number_format($cotizacion['total'], 2) : 'S/ 0.00' ?></td>
                                        <td>
                                            <?php
                                            $badge_class = [
                                                'pendiente' => 'warning',
                                                'aprobada' => 'success',
                                                'rechazada' => 'danger'
                                            ];
                                            $estado = $cotizacion['estado'] ?? 'pendiente';
                                            $class = $badge_class[$estado] ?? 'secondary';
                                            ?>
                                            <span class="badge bg-<?= $class ?>"><?= ucfirst($estado) ?></span>
                                        </td>
                                        <td>
                                            <?= isset($cotizacion['observaciones']) ? htmlspecialchars($cotizacion['observaciones']) : '-' ?>
                                        </td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-info" onclick="verCotizacion(<?= $cotizacion['id_cotizacion'] ?>)" title="Ver">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" onclick="generarPDF(<?= $cotizacion['id_cotizacion'] ?>)" title="PDF">
                                                    <i class="fas fa-file-pdf"></i>
                                                </button>
                                                <?php if (($cotizacion['estado'] ?? '') === 'pendiente'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCotizacion(<?= $cotizacion['id_cotizacion'] ?>)" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted">
                                        <i class="fas fa-inbox fa-3x mb-3"></i>
                                        <br>No hay cotizaciones registradas
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
        $('#cotizacionesTable').DataTable({
            language: {
                url: 'https://cdn.datatables.net/plug-ins/1.13.6/i18n/Spanish.json'
            },
            responsive: true,
            order: [
                [2, 'desc']
            ] // Ordenar por fecha descendente
        });
    });

    function verCotizacion(id) {
        window.location.href = '<?= BASE_PATH ?>/public/?page=cotizaciones&action=view&id=' + id;
    }

    function editarCotizacion(id) {
        window.location.href = '<?= BASE_PATH ?>/public/?page=cotizaciones&action=edit&id=' + id;
    }

    function generarPDF(id) {
        window.open('<?= BASE_PATH ?>/public/?page=cotizaciones&action=pdf&id=' + id, '_blank');
    }
</script>