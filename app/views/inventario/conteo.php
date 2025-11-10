<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-check"></i> Conteo Físico de Inventario
                    </h5>
                    <div>
                        <a href="?page=inventario&action=nuevoConteo" class="btn btn-primary">
                            <i class="fas fa-plus"></i> Nuevo Conteo
                        </a>
                        <a href="?page=inventario" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle"></i> <?= $_SESSION['success'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['success']); ?>
                    <?php endif; ?>

                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Estadísticas rápidas -->
                    <?php if (!empty($estadisticas)): ?>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="text-primary"><?= $estadisticas['total_conteos'] ?? 0 ?></h3>
                                        <p class="mb-0 text-muted">Total Conteos</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="text-success"><?= $estadisticas['conteos_hoy'] ?? 0 ?></h3>
                                        <p class="mb-0 text-muted">Hoy</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="text-info"><?= $estadisticas['conteos_mes'] ?? 0 ?></h3>
                                        <p class="mb-0 text-muted">Este Mes</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="text-warning"><?= $estadisticas['usuarios_diferentes'] ?? 0 ?></h3>
                                        <p class="mb-0 text-muted">Usuarios</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <!-- Tabla de conteos -->
                    <?php if (empty($conteos)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-clipboard-check fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No hay conteos físicos registrados</h5>
                            <p class="text-muted">Comienza realizando tu primer conteo físico de inventario.</p>
                            <a href="?page=inventario&action=nuevoConteo" class="btn btn-primary">
                                <i class="fas fa-plus"></i> Realizar Primer Conteo
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="thead-light">
                                    <tr>
                                        <th width="10%">#ID</th>
                                        <th width="15%">Fecha</th>
                                        <th width="15%">Usuario</th>
                                        <th width="10%">Productos</th>
                                        <th width="10%">Diferencias</th>
                                        <th width="25%">Observaciones</th>
                                        <th width="15%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($conteos as $conteo): ?>
                                        <tr>
                                            <td>
                                                <strong>#<?= $conteo['id_inventario'] ?></strong>
                                            </td>
                                            <td>
                                                <small class="text-muted">
                                                    <?= date('d/m/Y H:i', strtotime($conteo['fecha'])) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?= htmlspecialchars($conteo['usuario_nombre'] ?? 'N/A') ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= $conteo['total_productos'] ?? 0 ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php $diferencias = $conteo['productos_con_diferencias'] ?? 0; ?>
                                                <span class="badge <?= $diferencias > 0 ? 'badge-warning' : 'badge-success' ?>">
                                                    <?= $diferencias ?>
                                                </span>
                                            </td>
                                            <td>
                                                <small><?= htmlspecialchars(substr($conteo['observaciones'] ?? '', 0, 40)) ?><?= strlen($conteo['observaciones'] ?? '') > 40 ? '...' : '' ?></small>
                                            </td>
                                            <td>
                                                <div class="btn-group-vertical btn-group-sm" role="group">
                                                    <a href="?page=inventario&action=realizarConteo&id=<?= $conteo['id_inventario'] ?>"
                                                        class="btn btn-outline-primary"
                                                        title="Continuar conteo">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="?page=inventario&action=finalizarConteo&id=<?= $conteo['id_inventario'] ?>"
                                                        class="btn btn-outline-success"
                                                        title="Finalizar y ver diferencias">
                                                        <i class="fas fa-check"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    function waitForJQuery(callback) {
        if (typeof $ !== 'undefined') {
            callback();
        } else {
            setTimeout(function() {
                waitForJQuery(callback);
            }, 50);
        }
    }

    waitForJQuery(function() {
        $(document).ready(function() {
            // Auto-hide alerts
            setTimeout(function() {
                $('.alert').fadeOut('slow');
            }, 5000);
        });
    });
</script>