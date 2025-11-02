<?php // Vista incluida vía Controller->view; header ya fue cargado por el controlador 
?>


<div class="container py-4 productos-page">
    <div class="row justify-content-center">
        <div class="col-lg-10 col-md-12">
            <form method="POST" action="?page=productos&action=update&id=<?= $producto['id_producto'] ?>" class="bg-white rounded shadow-lg p-4 mb-4">
                <?php $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">

                <div class="row mb-3 align-items-center">
                    <div class="col-md-8">
                        <h3 class="mb-0 text-primary">
                            <i class="fas fa-edit mr-2"></i>Editar Producto: <?= htmlspecialchars($producto['nombre']) ?>
                        </h3>
                    </div>
                    <div class="col-md-4 text-end">
                        <a href="?page=productos&action=view&id=<?= $producto['id_producto'] ?>" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Ver Detalle
                        </a>
                        <a href="?page=productos" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Volver al Listado
                        </a>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-4">
                        <label for="nombre" class="form-label">Nombre del Producto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? $producto['nombre']) ?>" required>
                    </div>
                    <div class="col-md-2">
                        <label for="id_unidad" class="form-label">Unidad <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <select class="form-select" id="id_unidad" name="id_unidad" required>
                                <option value="">Seleccionar unidad...</option>
                                <?php foreach ($unidades as $unidad): ?>
                                    <option value="<?= $unidad['id_unidad'] ?>" <?= (($_POST['id_unidad'] ?? $producto['id_unidad']) == $unidad['id_unidad']) ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaUnidad">
                                <i class="fas fa-plus"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="codigo_barras" class="form-label">Código de Barras</label>
                        <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($_POST['codigo_barras'] ?? $producto['codigo_barras']) ?>">
                    </div>
                    <div class="col-md-3">
                        <label for="id_ubicacion" class="form-label">Ubicación</label>
                        <select class="form-select" id="id_ubicacion" name="id_ubicacion">
                            <option value="">Sin ubicación</option>
                            <?php foreach ($ubicaciones as $ubicacion): ?>
                                <option value="<?= $ubicacion['id_ubicacion'] ?>"
                                    <?= (($_POST['id_ubicacion'] ?? $producto['id_ubicacion'] ?? '') == $ubicacion['id_ubicacion']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($ubicacion['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="2"><?= htmlspecialchars($_POST['descripcion'] ?? $producto['descripcion']) ?></textarea>
                    </div>
                    <div class="col-md-3">
                        <label for="id_categoria" class="form-label">Categoría <span class="text-danger">*</span></label>
                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                            <option value="">Seleccionar categoría...</option>
                            <?php foreach ($categorias as $categoria): ?>
                                <option value="<?= $categoria['id_categoria'] ?>"
                                    <?= (($_POST['id_categoria'] ?? $producto['id_categoria'] ?? '') == $categoria['id_categoria']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="id_subcategoria" class="form-label">Subcategoría</label>
                        <select class="form-select" id="id_subcategoria" name="id_subcategoria" required>
                            <option value="0">Sin subcategoría</option>
                            <?php if (isset($subcategorias) && is_array($subcategorias)): ?>
                                <?php foreach ($subcategorias as $subcat): ?>
                                    <option value="<?= $subcat['id_subcategoria'] ?>"
                                        <?= (($_POST['id_subcategoria'] ?? $producto['id_subcategoria'] ?? '0') == $subcat['id_subcategoria']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($subcat['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-3">
                        <label for="id_marca" class="form-label">Marca</label>
                        <select class="form-select" id="id_marca" name="id_marca">
                            <option value="">Sin marca</option>
                            <?php foreach ($marcas as $marca): ?>
                                <option value="<?= $marca['id_marca'] ?>"
                                    <?= (($_POST['id_marca'] ?? $producto['id_marca'] ?? '') == $marca['id_marca']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($marca['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="precio_unitario" class="form-label">Precio Unitario <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">S/</span>
                            <input type="number" class="form-control" id="precio_unitario" name="precio_unitario"
                                step="0.01" min="0" value="<?= htmlspecialchars($_POST['precio_unitario'] ?? $producto['precio_unitario'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label for="estado" class="form-label">Estado</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="activo" <?= (($_POST['estado'] ?? $producto['estado'] ?? '') == 'activo') ? 'selected' : '' ?>>Activo</option>
                            <option value="inactivo" <?= (($_POST['estado'] ?? $producto['estado'] ?? '') == 'inactivo') ? 'selected' : '' ?>>Inactivo</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <div class="p-3 border rounded bg-white">
                            <label for="stock_actual" class="form-label fw-bold">
                                <i class="fas fa-warehouse me-1 text-secondary"></i> Stock Actual
                            </label>
                            <?php $stockValue = isset($stock_actual) ? $stock_actual : ($producto['stock_actual'] ?? 0); ?>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white text-secondary"><i class="fas fa-cubes"></i></span>
                                <input type="number" class="form-control text-center fw-bold" id="stock_actual" name="stock_actual"
                                    min="0" value="<?= htmlspecialchars($_POST['stock_actual'] ?? $stockValue) ?>">
                            </div>
                            <small class="form-text text-muted">
                                <i class="fas fa-info-circle"></i> Modifique el stock solo si corresponde a un ajuste real. Cada cambio será registrado como movimiento de inventario.
                            </small>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="stock_minimo" class="form-label">Stock Mínimo</label>
                        <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                            min="0" value="<?= htmlspecialchars($_POST['stock_minimo'] ?? $producto['stock_minimo'] ?? '') ?>">
                    </div>
                </div>

                <div class="row mt-4">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-primary btn-lg me-2">
                            <i class="fas fa-save"></i> Actualizar Producto
                        </button>
                        <a href="?page=productos&action=view&id=<?= $producto['id_producto'] ?>" class="btn btn-info me-2">
                            <i class="fas fa-eye"></i> Ver Detalle
                        </a>
                        <a href="?page=productos" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancelar
                        </a>
                        <?php if ($_SESSION['user_role'] === 'admin'): ?>
                            <form method="POST" action="?page=productos&action=delete&id=<?= $producto['id_producto'] ?>" style="display:inline-block;margin-left:.5rem;" onsubmit="return confirm('¿Confirmar eliminación del producto? Esta acción no se puede deshacer.');">
                                <?php $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                                $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                                <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">
                                <button type="submit" class="btn btn-danger ms-2"><i class="fas fa-trash"></i> Eliminar</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal para añadir nueva unidad -->
<div class="modal fade" id="modalNuevaUnidad" tabindex="-1" aria-labelledby="modalNuevaUnidadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaUnidadLabel">Añadir nueva unidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaUnidad">
                    <div class="mb-3">
                        <label for="nombreUnidad" class="form-label">Nombre de la unidad</label>
                        <input type="text" class="form-control" id="nombreUnidad" name="nombreUnidad" required>
                    </div>
                    <div class="mb-3">
                        <label for="abreviaturaUnidad" class="form-label">Abreviatura (opcional)</label>
                        <input type="text" class="form-control" id="abreviaturaUnidad" name="abreviaturaUnidad">
                    </div>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </form>
                <div id="unidadMsg" class="mt-2"></div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        var form = document.getElementById('formNuevaUnidad');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                var nombre = document.getElementById('nombreUnidad').value.trim();
                var abrev = document.getElementById('abreviaturaUnidad').value.trim();
                var msg = document.getElementById('unidadMsg');
                msg.textContent = '';
                if (!nombre) {
                    msg.textContent = 'El nombre de la unidad es obligatorio.';
                    msg.className = 'text-danger';
                    return;
                }
                var formData = new FormData();
                formData.append('nombre', nombre);
                formData.append('abreviatura', abrev);
                fetch('?page=unidades&action=create', {
                        method: 'POST',
                        body: formData
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            msg.textContent = 'Unidad creada correctamente.';
                            msg.className = 'text-success';
                            setTimeout(() => {
                                location.reload();
                            }, 1000);
                        } else {
                            msg.textContent = data.message || 'Error al crear la unidad.';
                            msg.className = 'text-danger';
                        }
                    })
                    .catch(() => {
                        msg.textContent = 'Error de red al crear la unidad.';
                        msg.className = 'text-danger';
                    });
            });
        }
    });
</script>

<script>
    // Cargar subcategorías al cambiar categoría
    document.getElementById('id_categoria').addEventListener('change', function() {
        const categoriaId = this.value;
        const subcategoriaSelect = document.getElementById('id_subcategoria');
        const currentSubcategoria = <?= json_encode($_POST['id_subcategoria'] ?? $producto['id_subcategoria'] ?? '') ?>;

        // Limpiar subcategorías
        subcategoriaSelect.innerHTML = '<option value="">Seleccionar subcategoría...</option>';

        if (categoriaId) {
            // Usar la ruta del router para obtener subcategorías (CategoriaController::getSubcategorias)
            fetch('?page=categorias&action=subcategorias&id=' + encodeURIComponent(categoriaId))
                .then(response => response.json())
                .then(payload => {
                    // El endpoint puede devolver directamente un array o un objeto { success: true, data: [...] }
                    let list = [];
                    if (Array.isArray(payload)) {
                        list = payload;
                    } else if (payload && Array.isArray(payload.data)) {
                        list = payload.data;
                    } else if (payload && payload.success === true && Array.isArray(payload.data)) {
                        list = payload.data;
                    } else {
                        console.warn('Formato inesperado al obtener subcategorías', payload);
                        return;
                    }

                    list.forEach(subcategoria => {
                        const option = document.createElement('option');
                        option.value = subcategoria.id_subcategoria;
                        option.textContent = subcategoria.nombre;
                        if (subcategoria.id_subcategoria == currentSubcategoria) {
                            option.selected = true;
                        }
                        subcategoriaSelect.appendChild(option);
                    });
                })
                .catch(error => console.error('Error:', error));
        }
    });

    // Cargar subcategorías al inicializar la página
    document.addEventListener('DOMContentLoaded', function() {
        const categoriaSelect = document.getElementById('id_categoria');
        if (categoriaSelect.value) {
            categoriaSelect.dispatchEvent(new Event('change'));
        }
    });
</script>

<?php // Footer ya fue cargado por el controlador 
?>

<!-- Modal para añadir nueva subcategoría -->
<div class="modal fade" id="modalNuevaSubcategoria" tabindex="-1" aria-labelledby="modalNuevaSubcategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaSubcategoriaLabel">Añadir nueva subcategoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaSubcategoria" onsubmit="event.preventDefault(); return false;">
                    <div class="mb-3">
                        <label for="nombreSubcategoria" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombreSubcategoria" name="nombre" required>
                    </div>
                    <input type="hidden" id="formSubcategoria_id_categoria" name="id_categoria" value="">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
    // Inicializar el initForm para subcategoría en esta página
    document.addEventListener('DOMContentLoaded', function() {
        // Reutilizamos la función initForm definida en create.php (si está incluido globalmente)
        if (typeof initForm === 'function') {
            initForm('formNuevaSubcategoria', 'subcategorias/create.php', 'id_subcategoria', 'modalNuevaSubcategoria');
        }

        // Copiar la categoría seleccionada al formulario de subcategoría cuando se abra el modal
        var modalEl = document.getElementById('modalNuevaSubcategoria');
        if (modalEl) {
            modalEl.addEventListener('show.bs.modal', function() {
                var cat = document.getElementById('id_categoria');
                var hidden = document.getElementById('formSubcategoria_id_categoria');
                if (cat && hidden) hidden.value = cat.value || '';
            });
        }
    });
</script>