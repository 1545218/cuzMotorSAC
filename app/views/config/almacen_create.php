<?php include_once '../app/views/layout/header.php'; ?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <?php foreach ($breadcrumb as $item): ?>
                        <?php if (isset($item['url'])): ?>
                            <li class="breadcrumb-item">
                                <a href="<?= $item['url'] ?>"><?= htmlspecialchars($item['title']) ?></a>
                            </li>
                        <?php else: ?>
                            <li class="breadcrumb-item active"><?= htmlspecialchars($item['title']) ?></li>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </ol>
            </nav>

            <!-- Título -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3"><?= htmlspecialchars($title) ?></h1>
            </div>

            <!-- Formulario -->
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-warehouse"></i> Datos del Almacén
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="?page=config&action=createAlmacen">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="nombre_almacen" class="form-label">
                                                <i class="fas fa-warehouse"></i> Nombre del Almacén *
                                            </label>
                                            <input type="text"
                                                class="form-control"
                                                id="nombre_almacen"
                                                name="nombre_almacen"
                                                required
                                                maxlength="100"
                                                placeholder="Ej: Almacén Central">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="capacidad_maxima" class="form-label">
                                                <i class="fas fa-cubes"></i> Capacidad Máxima
                                            </label>
                                            <input type="number"
                                                class="form-control"
                                                id="capacidad_maxima"
                                                name="capacidad_maxima"
                                                min="1"
                                                placeholder="Número de unidades">
                                            <small class="form-text text-muted">Opcional - Cantidad máxima de productos</small>
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="horario_apertura" class="form-label">
                                                <i class="fas fa-clock"></i> Horario de Apertura
                                            </label>
                                            <input type="time"
                                                class="form-control"
                                                id="horario_apertura"
                                                name="horario_apertura">
                                        </div>
                                    </div>

                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label for="horario_cierre" class="form-label">
                                                <i class="fas fa-clock"></i> Horario de Cierre
                                            </label>
                                            <input type="time"
                                                class="form-control"
                                                id="horario_cierre"
                                                name="horario_cierre">
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label for="responsable" class="form-label">
                                        <i class="fas fa-user"></i> Responsable del Almacén
                                    </label>
                                    <select class="form-select" id="responsable" name="responsable">
                                        <option value="">Seleccionar responsable...</option>
                                        <?php foreach ($usuarios as $usuario): ?>
                                            <option value="<?= $usuario['id_usuario'] ?>">
                                                <?= htmlspecialchars($usuario['nombre'] . ' ' . $usuario['apellido']) ?>
                                                (<?= htmlspecialchars($usuario['email']) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="form-text text-muted">Opcional - Usuario responsable de este almacén</small>
                                </div>

                                <hr>

                                <div class="d-flex justify-content-between">
                                    <a href="?page=config&action=almacen" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left"></i> Cancelar
                                    </a>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save"></i> Crear Configuración
                                    </button>
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
    // Validar que hora apertura sea menor que hora cierre
    document.getElementById('horario_cierre').addEventListener('change', function() {
        const apertura = document.getElementById('horario_apertura').value;
        const cierre = this.value;

        if (apertura && cierre && apertura >= cierre) {
            alert('El horario de cierre debe ser posterior al horario de apertura');
            this.value = '';
        }
    });

    document.getElementById('horario_apertura').addEventListener('change', function() {
        const apertura = this.value;
        const cierre = document.getElementById('horario_cierre').value;

        if (apertura && cierre && apertura >= cierre) {
            alert('El horario de apertura debe ser anterior al horario de cierre');
            document.getElementById('horario_cierre').value = '';
        }
    });
</script>

<?php include_once '../app/views/layout/footer.php'; ?>