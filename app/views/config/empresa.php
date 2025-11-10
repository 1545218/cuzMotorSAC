<?php
$title = 'Configuración de Empresa';
?>

<div class="main-content">
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1><?= $title ?></h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="?page=dashboard">Inicio</a></li>
                            <li class="breadcrumb-item"><a href="?page=config">Configuración</a></li>
                            <li class="breadcrumb-item active">Datos de Empresa</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-building mr-2"></i>
                                    Datos de la Empresa
                                </h3>
                            </div>
                            <form id="empresaForm">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="nombre">Nombre de la Empresa <span class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control"
                                                    id="nombre"
                                                    name="nombre"
                                                    value="<?= htmlspecialchars($empresa['nombre']) ?>"
                                                    required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="ruc">RUC <span class="text-danger">*</span></label>
                                                <input type="text"
                                                    class="form-control"
                                                    id="ruc"
                                                    name="ruc"
                                                    value="<?= htmlspecialchars($empresa['ruc']) ?>"
                                                    pattern="[0-9]{11}"
                                                    title="El RUC debe tener 11 dígitos"
                                                    required>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group">
                                        <label for="direccion">Dirección</label>
                                        <textarea class="form-control"
                                            id="direccion"
                                            name="direccion"
                                            rows="2"><?= htmlspecialchars($empresa['direccion']) ?></textarea>
                                    </div>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="telefono">Teléfono</label>
                                                <input type="text"
                                                    class="form-control"
                                                    id="telefono"
                                                    name="telefono"
                                                    value="<?= htmlspecialchars($empresa['telefono']) ?>">
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="form-group">
                                                <label for="email">Email</label>
                                                <input type="email"
                                                    class="form-control"
                                                    id="email"
                                                    name="email"
                                                    value="<?= htmlspecialchars($empresa['email']) ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-footer">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-2"></i>
                                        Guardar Cambios
                                    </button>
                                    <a href="?page=config" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left mr-2"></i>
                                        Volver
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Información
                                </h3>
                            </div>
                            <div class="card-body">
                                <p><strong>Configuración Actual:</strong></p>
                                <ul class="list-unstyled">
                                    <li><strong>Nombre:</strong> <?= htmlspecialchars($empresa['nombre']) ?></li>
                                    <li><strong>RUC:</strong> <?= htmlspecialchars($empresa['ruc']) ?></li>
                                    <li><strong>Dirección:</strong> <?= htmlspecialchars($empresa['direccion']) ?></li>
                                    <li><strong>Teléfono:</strong> <?= htmlspecialchars($empresa['telefono']) ?></li>
                                    <li><strong>Email:</strong> <?= htmlspecialchars($empresa['email']) ?></li>
                                </ul>

                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-exclamation-circle mr-2"></i>
                                    <strong>Nota:</strong> Los cambios en la configuración de la empresa afectarán todos los documentos generados por el sistema.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

<script>
    $(document).ready(function() {
        $('#empresaForm').on('submit', function(e) {
            e.preventDefault();

            // Validaciones
            const nombre = $('#nombre').val().trim();
            const ruc = $('#ruc').val().trim();

            if (!nombre) {
                Swal.fire('Error', 'El nombre de la empresa es requerido', 'error');
                return;
            }

            if (!ruc) {
                Swal.fire('Error', 'El RUC es requerido', 'error');
                return;
            }

            if (!/^\d{11}$/.test(ruc)) {
                Swal.fire('Error', 'El RUC debe tener exactamente 11 dígitos', 'error');
                return;
            }

            // Mostrar loading
            const submitBtn = $(this).find('button[type="submit"]');
            const originalText = submitBtn.html();
            submitBtn.html('<i class="fas fa-spinner fa-spin mr-2"></i>Guardando...').prop('disabled', true);

            // Enviar datos
            $.ajax({
                url: '?page=config&action=actualizarEmpresa',
                method: 'POST',
                data: $(this).serialize(),
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: '¡Éxito!',
                            text: response.message,
                            icon: 'success',
                            timer: 2000,
                            showConfirmButton: false
                        }).then(function() {
                            location.reload(); // Recargar para mostrar los nuevos datos
                        });
                    } else {
                        Swal.fire('Error', response.message, 'error');
                    }
                },
                error: function() {
                    Swal.fire('Error', 'Error de comunicación con el servidor', 'error');
                },
                complete: function() {
                    submitBtn.html(originalText).prop('disabled', false);
                }
            });
        });

        // Validación en tiempo real del RUC
        $('#ruc').on('input', function() {
            const value = $(this).val();
            if (value && !/^\d{0,11}$/.test(value)) {
                $(this).val(value.replace(/\D/g, '').substring(0, 11));
            }
        });
    });
</script>