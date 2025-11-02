<div id="alert-nuevo-elemento" class="alert alert-success d-none" role="alert" style="position:fixed;top:20px;right:20px;z-index:2000;min-width:200px;text-align:center;">
    ¡Agregado exitosamente!
</div>
<script>
    // Función genérica para AJAX de creación y actualización de select
    function handleNuevoElemento(formId, url, selectId, modalId) {
        document.getElementById(formId).addEventListener('submit', function(e) {
            e.preventDefault();
            const form = this;
            const nombre = form.querySelector('input[name="nombre"]').value.trim();
            if (!nombre) return;
            fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        nombre
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.id) {
                        // Agregar opción al select y seleccionarla
                        const select = document.getElementById(selectId);
                        const option = document.createElement('option');
                        option.value = data.id;
                        option.textContent = nombre;
                        modal.hide();
                        form.reset();
                    } else {
                        alert(data.error || 'No se pudo agregar.');
                    }
                })
                .catch(() => alert('Error de conexión.'));
        });
    }

    // Inicializar AJAX para cada modal
    document.addEventListener('DOMContentLoaded', function() {
        // Construir dinámicamente la base de la API intentando localizar la carpeta /public en la ruta actual
        const phpBasePath = '<?= rtrim(BASE_PATH, '/') ?>'; // '/CruzMotorSAC' (si está definido)
        const origin = window.location.origin; // e.g. http://localhost

        function detectApiBase() {
            try {
                const pathParts = window.location.pathname.split('/').filter(p => p.length);
                const pubIndex = pathParts.indexOf('public');
                if (pubIndex !== -1) {
                    // Reconstruir hasta /public
                    const upToPublic = '/' + pathParts.slice(0, pubIndex + 1).join('/') + '/api';
                    return origin.replace(/\/$/, '') + upToPublic;
                }
            } catch (e) {
                console.warn('Error detectando /public en la ruta actual', e);
            }

            // Fallbacks basados en configuración PHP o en /public
            if (phpBasePath && phpBasePath !== '') {
                return origin.replace(/\/$/, '') + phpBasePath + '/public/api';
            }

            return origin.replace(/\/$/, '') + '/public/api';
        }

        const apiBase = detectApiBase();

        function tryUrlsSequential(urls, payload) {
            // Mantener la función pero con mejor logging: intenta cada URL y si la respuesta no es JSON incluye el body en el log
            return new Promise((resolve, reject) => {
                let i = 0;
                const next = () => {
                    if (i >= urls.length) return reject(new Error('No reachable URL'));
                    const url = urls[i++];
                    fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json'
                            },
                            body: JSON.stringify(payload)
                        })
                        .then(res => {
                            if (!res.ok) {
                                // intentar leer el body para ayudar debugging
                                return res.text().then(text => {
                                    console.warn('Respuesta no OK desde', url, res.status, text);
                                    return next();
                                }).catch(() => {
                                    console.warn('Respuesta no OK desde', url, res.status);
                                    return next();
                                });
                            }
                            return res.text().then(text => {
                                try {
                                    const data = JSON.parse(text);
                                    return resolve({
                                        data,
                                        url
                                    });
                                } catch (e) {
                                    console.warn('Respuesta no es JSON desde', url, text);
                                    return next();
                                }
                            });
                        })
                        .catch(err => {
                            console.warn('Error fetch a', url, err);
                            next();
                        });
                };
                next();
            });
        }

        function initForm(formId, endpointName, selectId, modalId) {
            const form = document.getElementById(formId);
            if (!form) return;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                const nombre = this.querySelector('input[name="nombre"]').value.trim();
                if (!nombre) return;
                const payload = {
                    nombre
                };
                const urls = [apiBase.replace(/\/$/, '') + '/' + endpointName];
                console.info('Intentando POST a', urls[0], 'payload', payload);
                tryUrlsSequential(urls, payload)
                    .then(({
                        data,
                        url
                    }) => {
                        if (data && data.success && data.id) {
                            const select = document.getElementById(selectId);
                            const option = document.createElement('option');
                            option.value = data.id;
                            option.textContent = nombre;
                            select.appendChild(option);
                            select.value = data.id;
                            const alertBox = document.getElementById('alert-nuevo-elemento');
                            alertBox.classList.remove('d-none');
                            setTimeout(() => alertBox.classList.add('d-none'), 2000);
                            const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById(modalId));
                            modal.hide();
                            form.reset();
                            console.info('Elemento creado en', url, data);
                        } else {
                            console.warn('API responded with error', data, url);
                            alert((data && data.error) ? data.error : 'Error al agregar elemento');
                        }
                    })
                    .catch(err => {
                        console.error('No se pudo conectar con ninguna API candidate', err);
                        alert('No se pudo conectar con el servidor para agregar el elemento. Revise la consola de desarrollador.');
                    });
            });
        }

        initForm('formNuevaCategoria', 'categorias/create.php', 'id_categoria', 'modalNuevaCategoria');
        initForm('formNuevaMarca', 'marcas/create.php', 'id_marca', 'modalNuevaMarca');
        initForm('formNuevaUbicacion', 'ubicaciones/create.php', 'id_ubicacion', 'modalNuevaUbicacion');
        initForm('formNuevaUnidad', 'unidades/create.php', 'id_unidad', 'modalNuevaUnidad');
        initForm('formNuevaSubcategoria', 'subcategorias/create.php', 'id_subcategoria', 'modalNuevaSubcategoria');
    });
</script>

<script>
    // Cargar subcategorías al cambiar categoría (mismo comportamiento que en edit.php)
    document.getElementById('id_categoria').addEventListener('change', function() {
        const categoriaId = this.value;
        const subcategoriaSelect = document.getElementById('id_subcategoria');

        // Limpiar subcategorías
        subcategoriaSelect.innerHTML = '<option value="">Seleccionar subcategoría...</option>';

        if (categoriaId) {
            fetch('?page=categorias&action=subcategorias&id=' + encodeURIComponent(categoriaId))
                .then(response => response.json())
                .then(payload => {
                    let list = [];
                    if (Array.isArray(payload)) list = payload;
                    else if (payload && Array.isArray(payload.data)) list = payload.data;
                    else if (payload && payload.success === true && Array.isArray(payload.data)) list = payload.data;

                    list.forEach(subcategoria => {
                        const option = document.createElement('option');
                        option.value = subcategoria.id_subcategoria;
                        option.textContent = subcategoria.nombre;
                        subcategoriaSelect.appendChild(option);
                    });

                    // Si sólo hay una subcategoría, seleccionarla automáticamente
                    if (list.length === 1) {
                        subcategoriaSelect.value = list[0].id_subcategoria;
                    }
                })
                .catch(error => console.error('Error al cargar subcategorías:', error));
        }
    });

    // Al cargar la página, si ya hay categoría seleccionada, disparar change para precargar subcategorías
    document.addEventListener('DOMContentLoaded', function() {
        const categoriaSelect = document.getElementById('id_categoria');
        if (categoriaSelect && categoriaSelect.value) {
            categoriaSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
<?php $title = 'Crear Producto';
include_once __DIR__ . '/../layout/header.php'; ?>

<div class="container-fluid productos-page">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-plus mr-2"></i>Crear Nuevo Producto
                    </h3>
                    <a href="?page=productos" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Volver al Listado
                    </a>
                </div>

                <div class="card-body product-form-card">
                    <!-- Usar routing por query string para compatibilidad con index.php -->
                    <form method="POST" action="?page=productos&action=store" class="needs-validation" novalidate>
                        <?php // Usar el nombre de token definido en config (CSRF_TOKEN_NAME)
                        $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                        $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                        <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">

                        <!-- SECCIÓN 1: INFORMACIÓN BÁSICA -->
                        <div class="form-section">
                            <div class="section-header">
                                <h5 class="section-title">
                                    <i class="fas fa-info-circle text-primary"></i>
                                    Información Básica
                                </h5>
                                <small class="text-muted">Datos principales del producto</small>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control" 
                                               id="nombre" 
                                               name="nombre" 
                                               placeholder="Ej: Aceite Motor 15W40"
                                               value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" 
                                               required>
                                        <label for="nombre">
                                            <i class="fas fa-tag text-primary me-1"></i>
                                            Nombre del Producto <span class="text-danger">*</span>
                                        </label>
                                        <div class="invalid-feedback">
                                            Por favor, ingrese el nombre del producto.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <label for="id_unidad" class="form-label">
                                        <i class="fas fa-ruler text-primary me-1"></i>
                                        Unidad <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select class="form-select" id="id_unidad" name="id_unidad" required>
                                            <option value="">Seleccionar...</option>
                                            <?php foreach ($unidades as $unidad): ?>
                                                <option value="<?= $unidad['id_unidad'] ?>" <?= (($_POST['id_unidad'] ?? '') == $unidad['id_unidad']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($unidad['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalNuevaUnidad" title="Agregar nueva unidad">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Seleccione una unidad.
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="form-floating">
                                        <input type="text" 
                                               class="form-control" 
                                               id="codigo_barras" 
                                               name="codigo_barras" 
                                               placeholder="Código de barras"
                                               value="<?= htmlspecialchars($_POST['codigo_barras'] ?? '') ?>">
                                        <label for="codigo_barras">
                                            <i class="fas fa-barcode text-primary me-1"></i>
                                            Código de Barras
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row g-3 mt-2">
                                <div class="col-12">
                                    <div class="form-floating">
                                        <textarea class="form-control" 
                                                  id="descripcion" 
                                                  name="descripcion" 
                                                  placeholder="Descripción detallada del producto"
                                                  style="height: 100px"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                                        <label for="descripcion">
                                            <i class="fas fa-align-left text-primary me-1"></i>
                                            Descripción
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 2: CATEGORIZACIÓN -->
                        <div class="form-section">
                            <div class="section-header">
                                <h5 class="section-title">
                                    <i class="fas fa-sitemap text-success"></i>
                                    Categorización
                                </h5>
                                <small class="text-muted">Organización y clasificación del producto</small>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="id_categoria" class="form-label">
                                        <i class="fas fa-folder text-success me-1"></i>
                                        Categoría <span class="text-danger">*</span>
                                    </label>
                                    <div class="input-group">
                                        <select class="form-select" id="id_categoria" name="id_categoria" required>
                                            <option value="">Seleccionar categoría...</option>
                                            <?php foreach ($categorias as $categoria): ?>
                                                <option value="<?= $categoria['id_categoria'] ?>"
                                                    <?= ($_POST['id_categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($categoria['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria" title="Agregar nueva categoría">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div class="invalid-feedback">
                                        Seleccione una categoría.
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="id_subcategoria" class="form-label">
                                        <i class="fas fa-folder-open text-success me-1"></i>
                                        Subcategoría
                                    </label>
                                    <div class="input-group">
                                        <select class="form-select" id="id_subcategoria" name="id_subcategoria">
                                            <option value="">Seleccionar subcategoría...</option>
                                            <?php if (isset($subcategorias) && is_array($subcategorias) && !empty($subcategorias)): ?>
                                                <?php foreach ($subcategorias as $subcat): ?>
                                                    <option value="<?= $subcat['id_subcategoria'] ?>"
                                                        <?= (($_POST['id_subcategoria'] ?? '') == $subcat['id_subcategoria']) ? 'selected' : '' ?>>
                                                        <?= htmlspecialchars($subcat['nombre']) ?>
                                                    </option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="general">General (creada automáticamente)</option>
                                            <?php endif; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" id="addSubcategory" onclick="openAddSubcategoryModal()" title="Agregar nueva subcategoría">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="id_marca" class="form-label">
                                        <i class="fas fa-trademark text-success me-1"></i>
                                        Marca
                                    </label>
                                    <div class="input-group">
                                        <select class="form-select" id="id_marca" name="id_marca">
                                            <option value="">Sin marca</option>
                                            <?php foreach ($marcas as $marca): ?>
                                                <option value="<?= $marca['id_marca'] ?>"
                                                    <?= ($_POST['id_marca'] ?? '') == $marca['id_marca'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($marca['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#modalNuevaMarca" title="Agregar nueva marca">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 3: UBICACIÓN Y PRECIOS -->
                        <div class="form-section">
                            <div class="section-header">
                                <h5 class="section-title">
                                    <i class="fas fa-dollar-sign text-warning"></i>
                                    Ubicación y Precios
                                </h5>
                                <small class="text-muted">Configuración de almacenamiento y valores</small>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="id_ubicacion" class="form-label">
                                        <i class="fas fa-map-marker-alt text-warning me-1"></i>
                                        Ubicación
                                    </label>
                                    <div class="input-group">
                                        <select class="form-select" id="id_ubicacion" name="id_ubicacion">
                                            <option value="">Sin ubicación</option>
                                            <?php foreach ($ubicaciones as $ubicacion): ?>
                                                <option value="<?= $ubicacion['id_ubicacion'] ?>"
                                                    <?= ($_POST['id_ubicacion'] ?? '') == $ubicacion['id_ubicacion'] ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($ubicacion['nombre']) ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#modalNuevaUbicacion" title="Agregar nueva ubicación">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control" 
                                               id="precio_unitario" 
                                               name="precio_unitario"
                                               step="0.01" 
                                               min="0" 
                                               placeholder="0.00"
                                               value="<?= htmlspecialchars($_POST['precio_unitario'] ?? '') ?>" 
                                               required>
                                        <label for="precio_unitario">
                                            <i class="fas fa-tag text-warning me-1"></i>
                                            Precio Unitario S/ <span class="text-danger">*</span>
                                        </label>
                                        <div class="invalid-feedback">
                                            Ingrese un precio válido.
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
                                    <label for="estado" class="form-label">
                                        <i class="fas fa-toggle-on text-warning me-1"></i>
                                        Estado
                                    </label>
                                    <select class="form-select" id="estado" name="estado">
                                        <option value="activo" <?= ($_POST['estado'] ?? 'activo') == 'activo' ? 'selected' : '' ?>>
                                            <i class="fas fa-check-circle"></i> Activo
                                        </option>
                                        <option value="inactivo" <?= ($_POST['estado'] ?? '') == 'inactivo' ? 'selected' : '' ?>>
                                            <i class="fas fa-times-circle"></i> Inactivo
                                        </option>
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- SECCIÓN 4: INVENTARIO -->
                        <div class="form-section">
                            <div class="section-header">
                                <h5 class="section-title">
                                    <i class="fas fa-boxes text-info"></i>
                                    Control de Inventario
                                </h5>
                                <small class="text-muted">Configuración inicial de stock</small>
                            </div>
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control" 
                                               id="stock_actual" 
                                               name="stock_actual"
                                               min="0" 
                                               placeholder="0"
                                               value="<?= htmlspecialchars($_POST['stock_actual'] ?? '0') ?>">
                                        <label for="stock_actual">
                                            <i class="fas fa-cubes text-info me-1"></i>
                                            Stock Inicial
                                        </label>
                                        <div class="form-text">
                                            <i class="fas fa-info-circle"></i>
                                            Cantidad inicial del producto en almacén
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="form-floating">
                                        <input type="number" 
                                               class="form-control" 
                                               id="stock_minimo" 
                                               name="stock_minimo"
                                               min="0" 
                                               placeholder="10"
                                               value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '0') ?>">
                                        <label for="stock_minimo">
                                            <i class="fas fa-exclamation-triangle text-info me-1"></i>
                                            Stock Mínimo
                                        </label>
                                        <div class="form-text">
                                            <i class="fas fa-bell"></i>
                                            Se alertará cuando el stock esté por debajo de este valor
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- BOTONES DE ACCIÓN -->
                        <div class="form-actions">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="?page=productos" class="btn btn-light btn-lg">
                                    <i class="fas fa-arrow-left me-2"></i>
                                    Cancelar
                                </a>
                                
                                <div class="action-buttons">
                                    <button type="reset" class="btn btn-outline-secondary btn-lg me-2">
                                        <i class="fas fa-eraser me-2"></i>
                                        Limpiar
                                    </button>
                                    <button type="submit" class="btn btn-primary btn-lg">
                                        <i class="fas fa-save me-2"></i>
                                        Guardar Producto
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modales para añadir nueva categoría, marca, unidad y ubicación (fuera del form) -->
<div class="modal fade" id="modalNuevaCategoria" tabindex="-1" aria-labelledby="modalNuevaCategoriaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaCategoriaLabel">Añadir nueva categoría</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaCategoria" onsubmit="event.preventDefault(); return false;">
                    <div class="mb-3">
                        <label for="nombreCategoria" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombreCategoria" name="nombre" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaMarca" tabindex="-1" aria-labelledby="modalNuevaMarcaLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaMarcaLabel">Añadir nueva marca</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaMarca" onsubmit="event.preventDefault(); return false;">
                    <div class="mb-3">
                        <label for="nombreMarca" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombreMarca" name="nombre" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaUnidad" tabindex="-1" aria-labelledby="modalNuevaUnidadLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaUnidadLabel">Añadir nueva unidad</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaUnidad" onsubmit="event.preventDefault(); return false;">
                    <div class="mb-3">
                        <label for="nombreUnidad" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombreUnidad" name="nombre" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalNuevaUbicacion" tabindex="-1" aria-labelledby="modalNuevaUbicacionLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalNuevaUbicacionLabel">Añadir nueva ubicación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="formNuevaUbicacion" onsubmit="event.preventDefault(); return false;">
                    <div class="mb-3">
                        <label for="nombreUbicacion" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombreUbicacion" name="nombre" required>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary">Guardar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

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

<!-- Script de validación y mejoras del formulario -->
<script>
// Validación en tiempo real del formulario
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('.needs-validation');
    const submitBtn = form.querySelector('button[type="submit"]');
    
    // Validación automática al escribir
    const inputs = form.querySelectorAll('input[required], select[required]');
    inputs.forEach(input => {
        input.addEventListener('input', validateField);
        input.addEventListener('blur', validateField);
    });
    
    function validateField(e) {
        const field = e.target;
        const isValid = field.checkValidity();
        
        field.classList.remove('is-valid', 'is-invalid');
        
        if (field.value.trim()) {
            field.classList.add(isValid ? 'is-valid' : 'is-invalid');
        }
        
        updateSubmitButton();
    }
    
    function updateSubmitButton() {
        const isFormValid = form.checkValidity();
        submitBtn.disabled = !isFormValid;
        
        if (isFormValid) {
            submitBtn.classList.remove('btn-secondary');
            submitBtn.classList.add('btn-primary');
        } else {
            submitBtn.classList.remove('btn-primary');
            submitBtn.classList.add('btn-secondary');
        }
    }
    
    // Validación al enviar
    form.addEventListener('submit', function(e) {
        if (!form.checkValidity()) {
            e.preventDefault();
            e.stopPropagation();
            
            // Mostrar primera sección con error
            const firstInvalid = form.querySelector('.is-invalid');
            if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                firstInvalid.focus();
            }
        }
        
        form.classList.add('was-validated');
    });
    
    // Mejorar experiencia con tooltips
    const buttons = document.querySelectorAll('[title]');
    buttons.forEach(btn => {
        new bootstrap.Tooltip(btn);
    });
    
    // Auto-generar código de barras si está vacío
    const nombreInput = document.getElementById('nombre');
    const codigoInput = document.getElementById('codigo_barras');
    
    if (nombreInput && codigoInput) {
        nombreInput.addEventListener('blur', function() {
            if (!codigoInput.value && nombreInput.value) {
                const codigo = generateBarcode(nombreInput.value);
                codigoInput.value = codigo;
            }
        });
    }
    
    function generateBarcode(nombre) {
        // Generar código de barras basado en nombre + timestamp
        const cleanName = nombre.toUpperCase()
            .replace(/[^A-Z0-9]/g, '')
            .substring(0, 6);
        const timestamp = Date.now().toString().slice(-6);
        return cleanName + timestamp;
    }
    
    // Mejora para el campo de precio
    const precioInput = document.getElementById('precio_unitario');
    if (precioInput) {
        precioInput.addEventListener('input', function() {
            let value = this.value;
            if (value && !isNaN(value)) {
                this.value = parseFloat(value).toFixed(2);
            }
        });
    }
    
    // Actualizar estado inicial
    updateSubmitButton();
    
    // Animar las secciones al cargar
    const sections = document.querySelectorAll('.form-section');
    sections.forEach((section, index) => {
        setTimeout(() => {
            section.style.opacity = '0';
            section.style.transform = 'translateY(20px)';
            section.offsetHeight; // Trigger reflow
            section.style.transition = 'all 0.5s ease';
            section.style.opacity = '1';
            section.style.transform = 'translateY(0)';
        }, index * 100);
    });
});

// Función para mostrar notificaciones de éxito
function showSuccessNotification(message) {
    const alert = document.createElement('div');
    alert.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alert.style.cssText = 'top: 20px; right: 20px; z-index: 2000; min-width: 300px;';
    alert.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    document.body.appendChild(alert);
    
    setTimeout(() => {
        alert.remove();
    }, 5000);
}
</script>

<!-- No hay carga dinámica de subcategorías: campo eliminado -->