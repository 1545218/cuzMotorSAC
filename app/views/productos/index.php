<div class="container-fluid productos-page">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-box mr-2"></i>Gestión de Productos
                    </h3>
                    <a href="?page=productos&action=create" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Nuevo Producto
                    </a>
                </div>

                <div class="card-body">
                    <!-- Filtros -->
                    <form id="filtrosProductosForm" method="GET" action="http://localhost/CruzMotorSAC/public/?page=productos" class="mb-3">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small text-muted">Buscar producto:</label>
                                <input type="text" name="search" class="form-control" placeholder="Buscar por nombre, código de barras, descripción..." value="<?= htmlspecialchars($search) ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Estado:</label>
                                <select name="estado" class="form-control">
                                    <option value="activo" <?= $estado_selected == 'activo' ? 'selected' : '' ?>>Activos</option>
                                    <option value="inactivo" <?= $estado_selected == 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                                    <option value="" <?= $estado_selected == '' ? 'selected' : '' ?>>Todos</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">Stock:</label>
                                <select name="stock_filter" class="form-control">
                                    <option value="">Todos</option>
                                    <option value="bajo" <?= ($stock_filter_selected ?? '') == 'bajo' ? 'selected' : '' ?>>Stock bajo</option>
                                    <option value="sin_stock" <?= ($stock_filter_selected ?? '') == 'sin_stock' ? 'selected' : '' ?>>Sin stock</option>
                                    <option value="disponible" <?= ($stock_filter_selected ?? '') == 'disponible' ? 'selected' : '' ?>>Con stock</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">&nbsp;</label>
                                <button type="submit" class="btn btn-success w-100 d-block"><i class="fas fa-search"></i> Buscar</button>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label small text-muted">&nbsp;</label>
                                <a href="?page=productos" class="btn btn-outline-secondary w-100 d-block"><i class="fas fa-undo"></i> Limpiar</a>
                            </div>
                        </div>
                    </form>

                    <!-- Handler seguro: construir la URL en el cliente y navegar (evita pérdida de ?page) -->
                    <script>
                        (function() {
                            var form = document.getElementById('filtrosProductosForm');
                            if (!form) return;
                            form.addEventListener('submit', function(e) {
                                // Previene envío normal para construir URL segura
                                e.preventDefault();
                                var origin = window.location.origin || (window.location.protocol + '//' + window.location.host);
                                var path = window.location.pathname.replace(/\/index\.php$/i, '/index.php');
                                // Obtener valores
                                var search = encodeURIComponent((form.querySelector('input[name="search"]') || {
                                    value: ''
                                }).value || '');
                                var estado = encodeURIComponent((form.querySelector('select[name="estado"]') || {
                                    value: ''
                                }).value || '');
                                var url = origin + path + '?page=productos';
                                if (search) url += '&search=' + search;
                                // Siempre incluir estado (aunque sea vacío) para que el servidor pueda distinguir "Todos"
                                url += '&estado=' + estado;
                                // Navegar a la URL construida
                                window.location.href = url;
                            });
                        })();
                    </script>

                    <!-- Tabla de productos -->
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead class="thead-dark">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre</th>
                                    <th>Categoría</th>
                                    <th>Marca</th>
                                    <th>Stock</th>
                                    <th>Precio</th>
                                    <th>Estado</th>
                                    <th>Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($productos)): ?>
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-3x mb-3"></i><br>
                                            No se encontraron productos
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($productos as $producto): ?>
                                        <tr>
                                            <td>
                                                <code><?= htmlspecialchars($producto['codigo_barras'] ?? '-') ?></code>
                                            </td>
                                            <td>
                                                <strong><?= htmlspecialchars($producto['nombre']) ?></strong>
                                                <?php if ($producto['descripcion']): ?>
                                                    <br><small class="text-muted"><?= htmlspecialchars(substr($producto['descripcion'], 0, 50)) ?>...</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">
                                                    <?= htmlspecialchars($producto['categoria_nombre'] ?? '-') ?>
                                                </span>
                                                <?php if ($producto['subcategoria_nombre']): ?>
                                                    <br><small><?= htmlspecialchars($producto['subcategoria_nombre']) ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= htmlspecialchars($producto['marca_nombre'] ?? '-') ?></td>
                                            <td>
                                                <?php
                                                $stock = isset($producto['stock_actual']) ? (int)$producto['stock_actual'] : 0;
                                                $stockMin = isset($producto['stock_minimo']) ? (int)$producto['stock_minimo'] : 0;

                                                if ($stock === 0) {
                                                    echo '<span class="badge bg-danger"><i class="fas fa-exclamation-triangle"></i> Sin stock</span>';
                                                } elseif ($stockMin > 0 && $stock <= $stockMin) {
                                                    echo '<span class="badge bg-warning text-dark"><i class="fas fa-exclamation-circle"></i> ' . number_format($stock) . ' (Bajo)</span>';
                                                } else {
                                                    echo '<span class="badge bg-success"><i class="fas fa-check-circle"></i> ' . number_format($stock) . '</span>';
                                                }
                                                ?>
                                                <br><small class="text-muted">Mín: <?= number_format($stockMin) ?></small>
                                            </td>
                                            <td>
                                                <strong><?= formatCurrency($producto['precio_unitario']) ?></strong>
                                            </td>
                                            <td>
                                                <?php if ($producto['estado'] == 'activo'): ?>
                                                    <span class="badge bg-success">Activo</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Inactivo</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="?page=productos&action=view&id=<?= $producto['id_producto'] ?>"
                                                        class="btn btn-info" title="Ver">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="?page=productos&action=edit&id=<?= $producto['id_producto'] ?>"
                                                        class="btn btn-warning" title="Editar">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <?php if ($_SESSION['user_role'] === 'admin'): ?>
                                                        <?php if ($producto['estado'] == 'activo'): ?>
                                                            <a href="?page=productos&action=toggleEstado&id=<?= $producto['id_producto'] ?>&estado=inactivo"
                                                                class="btn btn-secondary" title="Inactivar">
                                                                <i class="fas fa-ban"></i>
                                                            </a>
                                                        <?php else: ?>
                                                            <a href="?page=productos&action=toggleEstado&id=<?= $producto['id_producto'] ?>&estado=activo"
                                                                class="btn btn-success" title="Activar">
                                                                <i class="fas fa-check"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                        <button type="button" class="btn btn-danger"
                                                            onclick="confirmarEliminacion(<?= $producto['id_producto'] ?>)"
                                                            title="Eliminar">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>