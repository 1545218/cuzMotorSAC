<div class="container-fluid">
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1>Permisos de Usuario</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="?page=dashboard">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="?page=usuarios">Usuarios</a></li>
                        <li class="breadcrumb-item active">Permisos</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>

    <section class="content">
        <div class="container-fluid">
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">
                                Permisos para: <?= $usuario['nombre'] ?? 'Usuario' ?> <?= $usuario['apellido'] ?? '' ?>
                            </h3>
                        </div>
                        <div class="card-body">
                            <form id="formPermisos" method="POST">
                                <input type="hidden" name="usuario_id" value="<?= $usuario_id ?>">
                                <input type="hidden" name="csrf_token" value="<?= Auth::generateCSRFToken() ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <h5>Módulos del Sistema</h5>
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_productos" name="permisos[]" value="productos">
                                                <label class="form-check-label" for="perm_productos">
                                                    Gestión de Productos
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_inventario" name="permisos[]" value="inventario">
                                                <label class="form-check-label" for="perm_inventario">
                                                    Control de Inventario
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_cotizaciones" name="permisos[]" value="cotizaciones">
                                                <label class="form-check-label" for="perm_cotizaciones">
                                                    Gestión de Cotizaciones
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_clientes" name="permisos[]" value="clientes">
                                                <label class="form-check-label" for="perm_clientes">
                                                    Gestión de Clientes
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <h5>Configuración</h5>
                                        <div class="form-group">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_usuarios" name="permisos[]" value="usuarios">
                                                <label class="form-check-label" for="perm_usuarios">
                                                    Gestión de Usuarios
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_reportes" name="permisos[]" value="reportes">
                                                <label class="form-check-label" for="perm_reportes">
                                                    Reportes del Sistema
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="perm_config" name="permisos[]" value="config">
                                                <label class="form-check-label" for="perm_config">
                                                    Configuración del Sistema
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="form-group mt-3">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Guardar Permisos
                                    </button>
                                    <a href="?page=usuarios" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Volver
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</div>
</div>

});
</script>