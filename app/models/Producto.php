<?php

/**
 * Modelo Producto - Gestión de productos del inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Producto extends Model
{
    /**
     * Cambia el estado de un producto (activo/inactivo)
     */
    public function cambiarEstado($id, $estado)
    {
        if ($estado !== 'activo' && $estado !== 'inactivo') {
            throw new Exception('Estado inválido');
        }
        $sql = "UPDATE productos SET estado = ? WHERE id_producto = ?";
        $this->db->update($sql, [$estado, $id]);
    }
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    protected $fillable = [
        'id_subcategoria',
        'id_unidad',
        'id_marca',
        'id_ubicacion',
        'nombre',
        'descripcion',
        'codigo_barras',
        'precio_unitario',
        'stock_actual',
        'stock_minimo',
        'estado'
    ];

    /**
     * Obtiene productos activos
     */
    public function getActive()
    {
        return $this->where("estado = ?", ['activo'], 'nombre ASC');
    }

    /**
     * Obtiene productos con información relacionada
     */
    public function getProductsWithDetails($conditions = '1=1', $params = [], $orderBy = 'p.nombre ASC', $limit = null)
    {
        $sql = "SELECT p.*, 
               s.nombre as subcategoria_nombre,
               c.nombre as categoria_nombre,
               m.nombre as marca_nombre,
               u.nombre as unidad_nombre,
               ub.nombre as ubicacion_nombre
        FROM productos p
        LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
        LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
        LEFT JOIN marcas m ON p.id_marca = m.id_marca
        LEFT JOIN unidades u ON p.id_unidad = u.id_unidad
        LEFT JOIN ubicaciones ub ON p.id_ubicacion = ub.id_ubicacion
        WHERE {$conditions}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->select($sql, $params);
    }

    /**
     * Obtiene un producto con todos sus detalles
     */
    public function getProductWithDetails($id)
    {
        $products = $this->getProductsWithDetails('p.id_producto = ?', [$id]);
        return !empty($products) ? $products[0] : null;
    }

    /**
     * Busca productos por término
     */
    public function searchProducts($term, $filters = [])
    {
        $conditions = [];
        $params = [];
        // Filtro de estado
        if (isset($filters['estado']) && $filters['estado'] !== '') {
            $conditions[] = "p.estado = ?";
            $params[] = $filters['estado'];
        }

        // Búsqueda por término
        if (!empty($term)) {
            $conditions[] = "(p.nombre LIKE ? OR p.codigo_barras LIKE ? OR p.descripcion LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%", "%$term%"]);
        }

        // Filtros adicionales
        if (isset($filters['categoria']) && !empty($filters['categoria'])) {
            $conditions[] = "c.id_categoria = ?";
            $params[] = $filters['categoria'];
        }

        if (isset($filters['subcategoria']) && !empty($filters['subcategoria'])) {
            $conditions[] = "p.id_subcategoria = ?";
            $params[] = $filters['subcategoria'];
        }

        if (isset($filters['marca']) && !empty($filters['marca'])) {
            $conditions[] = "p.id_marca = ?";
            $params[] = $filters['marca'];
        }

        if (isset($filters['ubicacion']) && !empty($filters['ubicacion'])) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $filters['ubicacion'];
        }

        if (isset($filters['stock_bajo']) && $filters['stock_bajo']) {
            $conditions[] = "p.stock_actual <= p.stock_minimo";
        }

        if (isset($filters['sin_stock']) && $filters['sin_stock']) {
            $conditions[] = "p.stock_actual = 0";
        }

        if (isset($filters['con_stock']) && $filters['con_stock']) {
            $conditions[] = "p.stock_actual > 0";
        }

        $whereClause = implode(' AND ', $conditions);
        if (trim($whereClause) === '') {
            $whereClause = '1=1';
        }

        return $this->getProductsWithDetails($whereClause, $params);
    }

    /**
     * Obtiene productos con stock bajo
     */
    public function getLowStockProducts($limit = null)
    {
        $conditions = "p.estado = 'activo' AND p.stock_actual <= p.stock_minimo";
        $orderBy = '(p.stock_actual / p.stock_minimo) ASC, p.stock_actual ASC';

        return $this->getProductsWithDetails($conditions, [], $orderBy, $limit);
    }

    /**
     * Obtiene productos sin stock
     */
    public function getOutOfStockProducts($limit = null)
    {
        $conditions = "p.estado = 'activo' AND p.stock_actual = 0";
        $orderBy = 'p.nombre ASC';

        return $this->getProductsWithDetails($conditions, [], $orderBy, $limit);
    }

    /**
     * Obtiene un producto por su ID
     */
    public function obtenerPorId($id)
    {
        $products = $this->getProductsWithDetails("p.id_producto = ?", [$id]);
        return !empty($products) ? $products[0] : null;
    }

    /**
     * Crea un nuevo producto
     */
    public function createProduct($data)
    {
        // Generar código automático si no se proporciona
        if (empty($data['codigo'])) {
            $data['codigo'] = $this->generateProductCode($data);
        }

        // Validar código único
        if ($this->exists('codigo = ?', [$data['codigo']])) {
            throw new Exception('El código del producto ya existe');
        }

        // Validar código de barras único si se proporciona
        if (!empty($data['codigo_barras'])) {
            if ($this->exists('codigo_barras = ?', [$data['codigo_barras']])) {
                throw new Exception('El código de barras ya existe');
            }
        }

        // Sincronizar stocks
        if (isset($data['stock'])) {
            $data['stock_actual'] = $data['stock'];
        }

        return $this->create($data);
    }

    /**
     * Actualiza un producto
     */
    public function updateProduct($id, $data)
    {
        // Validar código único (excluyendo el producto actual)
        if (isset($data['codigo'])) {
            $existing = $this->whereOne('codigo = ? AND id_producto != ?', [$data['codigo'], $id]);
            if ($existing) {
                throw new Exception('El código del producto ya existe');
            }
        }

        // Validar código de barras único (excluyendo el producto actual)
        if (isset($data['codigo_barras']) && !empty($data['codigo_barras'])) {
            $existing = $this->whereOne('codigo_barras = ? AND id_producto != ?', [$data['codigo_barras'], $id]);
            if ($existing) {
                throw new Exception('El código de barras ya existe');
            }
        }

        // Sincronizar stocks
        if (isset($data['stock'])) {
            $data['stock_actual'] = $data['stock'];
        }

        return $this->update($id, $data);
    }

    /**
     * Actualiza el stock de un producto
     */
    public function updateStock($productId, $newStock, $motivo = null, $userId = null)
    {
        $product = $this->find($productId);
        if (!$product) {
            throw new Exception('Producto no encontrado');
        }

        $stockAnterior = $product['stock_actual'];

        // Actualizar stock
        $updated = $this->update($productId, [
            'stock_actual' => $newStock
        ]);

        if ($updated) {
            // Registrar movimiento
            $this->registrarMovimiento($productId, 'ajuste', abs($newStock - $stockAnterior), $stockAnterior, $newStock, $motivo, $userId);
        }

        return $updated;
    }

    /**
     * Registra un movimiento de inventario
     */
    public function registrarMovimiento($productId, $tipo, $cantidad, $stockAnterior = null, $stockNuevo = null, $motivo = null, $userId = null, $referenciaId = null, $referenciaTipo = null)
    {
        // Mapear a la tabla registrosstock definida en la base de datos
        try {
            // Insert básico en registrosstock
            $this->db->execute(
                "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) VALUES (?, ?, ?, NOW(), ?, ?, ?)",
                [$productId, $tipo, $cantidad, $referenciaTipo ?? $motivo ?? 'sistema', $referenciaId, $userId]
            );

            // Si hay información de stock anterior/nuevo y se trata de un ajuste, guardar en ajustesinventario
            if (($stockAnterior !== null || $stockNuevo !== null) && $tipo === 'ajuste') {
                try {
                    $this->db->execute(
                        "INSERT INTO ajustesinventario (id_producto, tipo, cantidad, motivo, fecha, id_usuario) VALUES (?, ?, ?, ?, NOW(), ?)",
                        [$productId, ($stockNuevo > $stockAnterior ? 'aumento' : 'disminucion'), abs($stockNuevo - $stockAnterior), $motivo, $userId]
                    );
                } catch (Exception $e) {
                    // No bloquear el flujo por fallo en logging de ajuste
                    Logger::error("No se pudo registrar ajuste en ajustesinventario: " . $e->getMessage());
                }
            }

            return true;
        } catch (Exception $e) {
            Logger::error("Error registrando movimiento (registrosstock): " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el historial de movimientos de un producto
     */
    public function getMovements($productId, $limit = 50)
    {
        // Leer movimientos desde registrosstock y combinar con información de usuario
        return $this->db->select(
            "SELECT rs.*, u.nombre as usuario_nombre, rs.fecha as fecha_movimiento, rs.origen as motivo
             FROM registrosstock rs
             LEFT JOIN usuarios u ON rs.id_usuario = u.id_usuario
             WHERE rs.id_producto = ?
             ORDER BY rs.fecha DESC
             LIMIT ?",
            [$productId, $limit]
        );
    }

    /**
     * Genera un código automático para el producto
     */
    private function generateProductCode($data)
    {
        $prefix = 'PROD';

        // Intentar usar subcategoría para el prefijo
        if (isset($data['id_subcategoria'])) {
            $subcategoria = $this->db->selectOne(
                "SELECT s.nombre, c.nombre as categoria 
                 FROM subcategorias s 
                 JOIN categorias c ON s.id_categoria = c.id_categoria 
                 WHERE s.id_subcategoria = ?",
                [$data['id_subcategoria']]
            );

            if ($subcategoria) {
                $prefix = strtoupper(substr($subcategoria['categoria'], 0, 2) . substr($subcategoria['nombre'], 0, 2));
            }
        }

        // Obtener el siguiente número
        $lastProduct = $this->db->selectOne(
            "SELECT codigo FROM productos WHERE codigo LIKE ? ORDER BY id_producto DESC LIMIT 1",
            [$prefix . '%']
        );

        $number = 1;
        if ($lastProduct && preg_match('/(\d+)$/', $lastProduct['codigo'], $matches)) {
            $number = (int)$matches[1] + 1;
        }

        return $prefix . str_pad($number, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtiene estadísticas de productos
     */
    public function getStats()
    {
        $stats = [
            'total' => $this->count("estado = ?", ['activo']),
            'stock_bajo' => $this->count("estado = 'activo' AND stock_actual <= stock_minimo"),
            'sin_stock' => $this->count("estado = 'activo' AND stock_actual = 0"),
            'inactivos' => $this->count("estado = ?", ['inactivo'])
        ];

        // Valor total del inventario
        $valorInventario = $this->db->selectOne(
            "SELECT SUM(stock_actual * precio_unitario) as total FROM productos WHERE estado = 'activo'"
        );
        $stats['valor_inventario'] = $valorInventario['total'] ?? 0;

        return $stats;
    }

    /**
     * Obtiene productos por categoría
     */
    public function getProductsByCategory($categoryId)
    {
        return $this->getProductsWithDetails(
            "c.id_categoria = ? AND p.estado = 'activo'",
            [$categoryId],
            'p.nombre ASC'
        );
    }

    /**
     * Obtiene productos por marca
     */
    public function getProductsByBrand($brandId)
    {
        return $this->getProductsWithDetails(
            "p.id_marca = ? AND p.estado = 'activo'",
            [$brandId],
            'p.nombre ASC'
        );
    }

    /**
     * Verifica si el producto puede ser eliminado
     */
    public function canDelete($productId)
    {
        // Verificar si está en cotizaciones
        $hasQuotes = $this->db->count('detallecotizacion', ['id_producto = ?'], [$productId]) > 0;

        // Verificar si tiene movimientos de stock
        $hasMovements = $this->db->count('registrosstock', ['id_producto = ?'], [$productId]) > 0;

        return !($hasQuotes || $hasMovements);
    }

    /**
     * Eliminación suave del producto
     */
    public function softDelete($productId)
    {
        return $this->update($productId, ['estado' => 'inactivo']);
    }

    /**
     * Busca productos con filtros
     */
    public function searchProductos($term, $categoriaId = null, $estado = 'activo', $stockFilter = '')
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($term)) {
            $conditions[] = "(p.nombre LIKE ? OR p.descripcion LIKE ? OR p.codigo_barras LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%", "%$term%"]);
        }

        if ($categoriaId) {
            $conditions[] = "c.id_categoria = ?";
            $params[] = $categoriaId;
        }

        if ($estado) {
            $conditions[] = "p.estado = ?";
            $params[] = $estado;
        }

        // Filtros de stock
        if ($stockFilter === '1') { // Productos en stock
            $conditions[] = "p.stock_actual > 0";
        } elseif ($stockFilter === '0') { // Sin stock
            $conditions[] = "(p.stock_actual = 0 OR p.stock_actual IS NULL)";
        } elseif ($stockFilter === 'bajo') { // Stock bajo
            $conditions[] = "p.stock_actual > 0 AND p.stock_actual <= p.stock_minimo";
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre,
                    m.nombre as marca_nombre, u.nombre as ubicacion_nombre,
                    un.nombre as unidad_nombre
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             LEFT JOIN unidades un ON p.id_unidad = un.id_unidad
             WHERE {$whereClause}
             ORDER BY p.nombre ASC",
            $params
        );
    }

    /**
     * Obtiene productos con detalles completos
     */
    public function getProductosWithDetails($estado = 'activo')
    {
        return $this->db->select(
            "SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre,
                    m.nombre as marca_nombre, u.nombre as ubicacion_nombre,
                    un.nombre as unidad_nombre
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             LEFT JOIN unidades un ON p.id_unidad = un.id_unidad
             WHERE p.estado = ?
             ORDER BY p.nombre ASC",
            [$estado]
        );
    }

    /**
     * Busca productos para select
     */
    public function searchForSelect($term, $limit = 10)
    {
        return $this->db->select(
            "SELECT id_producto, nombre, codigo_barras, precio_unitario
             FROM productos
             WHERE (nombre LIKE ? OR codigo_barras LIKE ?) AND estado = 'activo'
             ORDER BY nombre ASC
             LIMIT ?",
            ["%$term%", "%$term%", $limit]
        );
    }

    /**
     * Obtiene producto completo con relaciones
     */
    public function getProductoCompleto($id)
    {
        return $this->db->selectOne(
            "SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre,
                    m.nombre as marca_nombre, u.nombre as ubicacion_nombre,
                    un.nombre as unidad_nombre
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             LEFT JOIN unidades un ON p.id_unidad = un.id_unidad
             WHERE p.id_producto = ?",
            [$id]
        );
    }

    /**
     * Actualiza el stock actual de un producto
     */
    public function actualizarStock($productoId, $nuevoStock)
    {
        try {
            return $this->db->execute(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$nuevoStock, $productoId]
            );
        } catch (Exception $e) {
            Logger::error("Error al actualizar stock: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene el stock actual de un producto
     */
    public function getStockActual($productoId)
    {
        $result = $this->db->selectOne(
            "SELECT stock_actual FROM productos WHERE id_producto = ?",
            [$productoId]
        );
        return $result ? (int)$result['stock_actual'] : 0;
    }
}
