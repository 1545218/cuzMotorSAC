<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title">
                        <i class="fas fa-warehouse mr-2"></i>Gestión de Inventario
                    </h3>
                    <div class="d-flex gap-2">
                        <a href="?page=productos&action=create" class="btn btn-primary">
                            <i class="fas fa-plus me-2"></i>Agregar Producto
                        </a>
                        <button type="button" class="btn btn-success" onclick="exportarExcel()">
                            <i class="fas fa-file-excel me-2"></i>Exportar Excel
                        </button>
                    </div>
                </div>

                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover align-middle" id="tablaInventario">
                            <thead class="table-light">
                                <tr>
                                    <th>Código</th>
                                    <th>Nombre del Producto</th>
                                    <th>Descripción</th>
                                    <th>Precio Unitario</th>
                                    <th>Stock Actual</th>
                                    <th>Stock Mínimo</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                // Obtener productos de la base de datos
                                try {
                                    $db = Database::getInstance();
                                    $conn = $db->getConnection();
                                    $stmt = $conn->prepare("
                                        SELECT 
                                            p.id_producto,
                                            p.nombre,
                                            p.descripcion,
                                            p.codigo_barras,
                                            p.precio_unitario,
                                            p.stock_actual,
                                            p.stock_minimo,
                                            p.estado
                                        FROM productos p 
                                        WHERE p.estado = 'activo'
                                        ORDER BY p.nombre ASC
                                    ");
                                    $stmt->execute();
                                    $productos = $stmt->fetchAll(PDO::FETCH_ASSOC);

                                    if (!empty($productos)):
                                        foreach ($productos as $producto):
                                            // Determinar color del stock
                                            $stockClass = '';
                                            if ($producto['stock_actual'] <= $producto['stock_minimo']) {
                                                $stockClass = 'text-danger fw-bold';
                                            } elseif ($producto['stock_actual'] <= ($producto['stock_minimo'] * 2)) {
                                                $stockClass = 'text-warning fw-bold';
                                            }
                                ?>
                                            <tr>
                                                <td><?= htmlspecialchars($producto['codigo_barras'] ?: $producto['id_producto']) ?></td>
                                                <td><?= htmlspecialchars($producto['nombre']) ?></td>
                                                <td><?= htmlspecialchars($producto['descripcion'] ?: '-') ?></td>
                                                <td>S/ <?= number_format($producto['precio_unitario'], 2) ?></td>
                                                <td class="<?= $stockClass ?>"><?= $producto['stock_actual'] ?></td>
                                                <td><?= $producto['stock_minimo'] ?></td>
                                                <td>
                                                    <?php if ($producto['estado'] == 'activo'): ?>
                                                        <span class="badge bg-success">Activo</span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary">Inactivo</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php
                                        endforeach;
                                    else:
                                        ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted py-4">
                                                <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                                No hay productos en el inventario
                                            </td>
                                        </tr>
                                <?php
                                    endif;
                                } catch (Exception $e) {
                                    echo '<tr><td colspan="7" class="text-center text-danger">Error al cargar inventario: ' . htmlspecialchars($e->getMessage()) . '</td></tr>';
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    // EXPORTAR A EXCEL
    function exportarExcel() {
        try {
            const tabla = document.getElementById('tablaInventario');
            if (!tabla) {
                alert('No se encontró la tabla de inventario');
                return;
            }

            const wb = XLSX.utils.book_new();
            const ws = XLSX.utils.table_to_sheet(tabla);
            XLSX.utils.book_append_sheet(wb, ws, 'Inventario');

            const fecha = new Date().toISOString().split('T')[0];
            const nombreArchivo = `inventario_cruz_motor_${fecha}.xlsx`;

            XLSX.writeFile(wb, nombreArchivo);
            console.log('Excel exportado exitosamente:', nombreArchivo);
        } catch (error) {
            console.error('Error al exportar Excel:', error);
            alert('Error al exportar a Excel: ' + error.message);
        }
    }
</script>