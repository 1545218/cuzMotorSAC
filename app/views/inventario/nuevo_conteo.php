<div class="container-fluid">
    <div class="row">
        <div class="col-md-8 offset-md-2">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-plus"></i> Iniciar Nuevo Conteo Físico
                    </h5>
                </div>
                <div class="card-body">
                    <!-- Mensajes -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle"></i> <?= $_SESSION['error'] ?>
                            <button type="button" class="close" data-dismiss="alert">
                                <span>&times;</span>
                            </button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>

                    <!-- Información importante -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información Importante</h6>
                        <ul class="mb-0">
                            <li>El conteo físico permite verificar las diferencias entre el stock del sistema y el stock real.</li>
                            <li>Una vez iniciado el conteo, podrás registrar las cantidades físicas de cada producto.</li>
                            <li>Al finalizar, el sistema mostrará las diferencias encontradas.</li>
                            <li>Podrás aplicar automáticamente los ajustes necesarios.</li>
                        </ul>
                    </div>

                    <form method="POST" action="?page=inventario&action=nuevoConteo">
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label for="observaciones" class="form-label">
                                    Observaciones <span class="text-muted">(Opcional)</span>
                                </label>
                                <textarea class="form-control" id="observaciones" name="observaciones"
                                    rows="3" placeholder="Describe el motivo del conteo, ubicación específica, etc."></textarea>
                                <small class="form-text text-muted">
                                    Ej: "Conteo mensual completo", "Verificación después de inventario", etc.
                                </small>
                            </div>
                        </div>

                        <!-- Información del conteo -->
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <div class="card border-light bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-user"></i> Usuario:
                                        </h6>
                                        <p class="card-text">
                                            <?= htmlspecialchars($_SESSION['nombre'] ?? 'Usuario') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card border-light bg-light">
                                    <div class="card-body">
                                        <h6 class="card-title">
                                            <i class="fas fa-calendar"></i> Fecha:
                                        </h6>
                                        <p class="card-text">
                                            <?= date('d/m/Y H:i') ?>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-play"></i> Iniciar Conteo Físico
                                </button>
                                <a href="?page=inventario&action=conteo" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                            </div>
                        </div>
                    </form>
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

            // Focus en textarea
            $('#observaciones').focus();
        });
    });
</script>