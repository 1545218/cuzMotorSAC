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

<div class="container-fluid">
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
                    <form method="POST" action="?page=productos&action=store">
                        <?php // Usar el nombre de token definido en config (CSRF_TOKEN_NAME)
                        $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
                        $csrfValue = $_SESSION['csrf_token'] ?? (new Auth())->getCSRFToken(); ?>
                        <input type="hidden" name="<?= htmlspecialchars($csrfName) ?>" value="<?= htmlspecialchars($csrfValue) ?>">

                        <div class="product-header">
                            <div class="product-col">
                                <label for="nombre">Nombre del Producto <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($_POST['nombre'] ?? '') ?>" required>
                            </div>
                            <div class="product-col">
                                <label for="id_unidad">Unidad <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <select class="form-control" id="id_unidad" name="id_unidad" required>
                                        <option value="">Seleccionar unidad...</option>
                                        <?php foreach ($unidades as $unidad): ?>
                                            <option value="<?= $unidad['id_unidad'] ?>" <?= (($_POST['id_unidad'] ?? '') == $unidad['id_unidad']) ? 'selected' : '' ?>><?= htmlspecialchars($unidad['nombre']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button type="button" class="btn btn-outline-primary btn-sm btn-compact" data-bs-toggle="modal" data-bs-target="#modalNuevaUnidad">
                                        <i class="fas fa-plus"></i> Añadir nueva
                                    </button>
                                </div>
                            </div>
                            <div class="product-col">
                                <label for="codigo_barras">Código de Barras</label>
                                <input type="text" class="form-control" id="codigo_barras" name="codigo_barras" value="<?= htmlspecialchars($_POST['codigo_barras'] ?? '') ?>">
                            </div>
                        </div>
                </div>

                <div class="form-group">
                    <label for="descripcion">Descripción</label>
                    <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?= htmlspecialchars($_POST['descripcion'] ?? '') ?></textarea>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_categoria" class="mb-1">Categoría <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <select class="form-control" id="id_categoria" name="id_categoria" required>
                                    <option value="">Seleccionar categoría...</option>
                                    <?php foreach ($categorias as $categoria): ?>
                                        <option value="<?= $categoria['id_categoria'] ?>"
                                            <?= ($_POST['id_categoria'] ?? '') == $categoria['id_categoria'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($categoria['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaCategoria">
                                    <i class="fas fa-plus"></i> Añadir nueva
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_subcategoria" class="mb-1">Subcategoría</label>
                            <div class="input-group">
                                <select class="form-control" id="id_subcategoria" name="id_subcategoria" required>
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
                                <button type="button" class="btn btn-outline-secondary" id="addSubcategory" onclick="openAddSubcategoryModal()">Añadir nueva</button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_marca" class="mb-1">Marca</label>
                            <div class="input-group">
                                <select class="form-control" id="id_marca" name="id_marca">
                                    <option value="">Sin marca</option>
                                    <?php foreach ($marcas as $marca): ?>
                                        <option value="<?= $marca['id_marca'] ?>"
                                            <?= ($_POST['id_marca'] ?? '') == $marca['id_marca'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($marca['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaMarca">
                                    <i class="fas fa-plus"></i> Añadir nueva
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="id_ubicacion" class="mb-1">Ubicación</label>
                            <div class="input-group">
                                <select class="form-control" id="id_ubicacion" name="id_ubicacion">
                                    <option value="">Sin ubicación</option>
                                    <?php foreach ($ubicaciones as $ubicacion): ?>
                                        <option value="<?= $ubicacion['id_ubicacion'] ?>"
                                            <?= ($_POST['id_ubicacion'] ?? '') == $ubicacion['id_ubicacion'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($ubicacion['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalNuevaUbicacion">
                                    <i class="fas fa-plus"></i> Añadir nueva
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="precio_unitario">Precio Unitario <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">S/</span>
                                </div>
                                <input type="number" class="form-control" id="precio_unitario" name="precio_unitario"
                                    step="0.01" min="0" value="<?= htmlspecialchars($_POST['precio_unitario'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label for="estado">Estado</label>
                            <select class="form-control" id="estado" name="estado">
                                <option value="activo" <?= ($_POST['estado'] ?? 'activo') == 'activo' ? 'selected' : '' ?>>Activo</option>
                                <option value="inactivo" <?= ($_POST['estado'] ?? '') == 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stock_actual">Stock Inicial</label>
                            <input type="number" class="form-control" id="stock_actual" name="stock_actual"
                                min="0" value="<?= htmlspecialchars($_POST['stock_actual'] ?? '0') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="stock_minimo">Stock Mínimo</label>
                            <input type="number" class="form-control" id="stock_minimo" name="stock_minimo"
                                min="0" value="<?= htmlspecialchars($_POST['stock_minimo'] ?? '0') ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Guardar Producto
                    </button>
                    <a href="?page=productos" class="btn btn-secondary ml-2">
                        <i class="fas fa-times"></i> Cancelar
                    </a>
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

<!-- No hay carga dinámica de subcategorías: campo eliminado -->