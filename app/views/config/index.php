<?php
$title = 'Configuración del Sistema';
?>

<div class="main-content">
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Configuración del Sistema</h1>
                    </div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="?page=dashboard">Inicio</a></li>
                            <li class="breadcrumb-item active">Configuración</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <section class="content">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Configuración General</h3>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="?page=config&action=empresa" class="list-group-item list-group-item-action">
                                        <i class="fas fa-building mr-2"></i>Datos de la Empresa
                                    </a>
                                    <a href="?page=config&action=almacen" class="list-group-item list-group-item-action">
                                        <i class="fas fa-warehouse mr-2"></i>Configuración de Almacenes
                                    </a>
                                    <a href="?page=config&action=sistema" class="list-group-item list-group-item-action">
                                        <i class="fas fa-cogs mr-2"></i>Parámetros del Sistema
                                    </a>
                                    <a href="?page=usuarios" class="list-group-item list-group-item-action">
                                        <i class="fas fa-users-cog mr-2"></i>Gestión de Usuarios
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                <h3 class="card-title">Mantenimiento</h3>
                            </div>
                            <div class="card-body">
                                <div class="list-group">
                                    <a href="?page=config&action=backup" class="list-group-item list-group-item-action">
                                        <i class="fas fa-database mr-2"></i>Respaldo de Base de Datos
                                    </a>
                                    <a href="?page=config&action=logs" class="list-group-item list-group-item-action">
                                        <i class="fas fa-file-alt mr-2"></i>Logs del Sistema
                                    </a>
                                    <a href="?page=config&action=cache" class="list-group-item list-group-item-action">
                                        <i class="fas fa-trash mr-2"></i>Limpiar Caché
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>
</div>

</div>
</div>
</div>