<?php

/**
 * Modelo Ubicacion - Gestión de ubicaciones físicas del inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Ubicacion extends Model
{
    protected $table = 'ubicaciones';
    protected $primaryKey = 'id_ubicacion';
    protected $fillable = ['nombre', 'descripcion'];

    /**
     * Obtiene ubicaciones con estadísticas
     */
    public function getUbicacionesWithStats()
    {
        return $this->db->select(
            "SELECT u.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(p.stock_actual), 0) as stock_total,
                    COALESCE(SUM(p.precio_unitario * p.stock_actual), 0) as valor_inventario
             FROM ubicaciones u
             LEFT JOIN productos p ON u.id_ubicacion = p.id_ubicacion AND p.estado = 'activo'
             GROUP BY u.id_ubicacion
             ORDER BY u.nombre ASC"
        );
    }

    /**
     * Obtiene ubicaciones para select
     */
    public function getForSelect()
    {
        return $this->db->select(
            "SELECT id_ubicacion, nombre 
             FROM ubicaciones 
             ORDER BY nombre ASC"
        );
    }

    /**
     * Obtiene ubicaciones con estadísticas
     */
    public function getWithStats()
    {
        return $this->db->select(
            "SELECT u.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(p.stock_actual), 0) as stock_total
             FROM ubicaciones u
             LEFT JOIN productos p ON u.id_ubicacion = p.id_ubicacion AND p.estado = 'activo'
             GROUP BY u.id_ubicacion
             ORDER BY u.nombre ASC"
        );
    }

    /**
     * Crea una nueva ubicación
     */
    public function createUbicacion($data)
    {
        // Validar nombre único
        if ($this->exists('nombre = ?', [$data['nombre']])) {
            throw new Exception('Ya existe una ubicación con ese nombre');
        }

        return $this->create($data);
    }

    /**
     * Actualiza una ubicación
     */
    public function updateUbicacion($id, $data)
    {
        // Validar nombre único (excluyendo la ubicación actual)
        if (isset($data['nombre'])) {
            $existing = $this->whereOne('nombre = ? AND id_ubicacion != ?', [$data['nombre'], $id]);
            if ($existing) {
                throw new Exception('Ya existe una ubicación con ese nombre');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Busca ubicaciones
     */
    public function searchUbicaciones($term, $tipo = null)
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($term)) {
            $conditions[] = "(u.nombre LIKE ? OR u.descripcion LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%"]);
        }

        // Comentado temporalmente: columna tipo no existe
        // if ($tipo) {
        //     $conditions[] = "u.tipo = ?";
        //     $params[] = $tipo;
        // }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT u.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(p.stock_actual), 0) as stock_total,
                    COALESCE(SUM(p.precio_unitario * p.stock_actual), 0) as valor_inventario
             FROM ubicaciones u
             LEFT JOIN productos p ON u.id_ubicacion = p.id_ubicacion AND p.estado = 'activo'
             WHERE {$whereClause}
             GROUP BY u.id_ubicacion
             ORDER BY u.nombre ASC",
            $params
        );
    }

    /**
     * Verifica si la ubicación puede ser eliminada
     */
    public function canDelete($ubicacionId)
    {
        return $this->db->count('productos', ['id_ubicacion = ? AND estado = ?'], [$ubicacionId, 'activo']) == 0;
    }

    /**
     * Obtiene ubicación con detalles
     */
    public function getUbicacionWithDetails($id)
    {
        return $this->db->selectOne(
            "SELECT u.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(p.stock_actual), 0) as stock_total,
                    COALESCE(SUM(p.precio_unitario * p.stock_actual), 0) as valor_inventario,
                    COALESCE(SUM(p.precio_unitario * p.stock_actual), 0) as valor_venta
             FROM ubicaciones u
             LEFT JOIN productos p ON u.id_ubicacion = p.id_ubicacion AND p.estado = 'activo'
             WHERE u.id_ubicacion = ?
             GROUP BY u.id_ubicacion",
            [$id]
        );
    }

    /**
     * Obtiene productos en una ubicación
     */
    public function getProductos($ubicacionId, $limit = 20)
    {
        return $this->db->select(
            "SELECT p.*, p.stock_actual, p.precio_unitario,
                    s.nombre as subcategoria_nombre, m.nombre as marca_nombre
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             WHERE p.id_ubicacion = ? AND p.estado = 'activo'
             ORDER BY p.nombre ASC
             LIMIT ?",
            [$ubicacionId, $limit]
        );
    }

    /**
     * Obtiene productos con bajo stock en una ubicación
     */
    public function getProductosBajoStock($ubicacionId, $limite = 10)
    {
        return $this->db->select(
            "SELECT p.*, p.stock_actual, p.stock_minimo, p.precio_unitario,
                    s.nombre as subcategoria_nombre, m.nombre as marca_nombre
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             WHERE p.id_ubicacion = ? AND p.stock_actual <= p.stock_minimo AND p.estado = 'activo'
             ORDER BY (p.stock_actual - p.stock_minimo) ASC
             LIMIT ?",
            [$ubicacionId, $limite]
        );
    }

    /**
     * Obtiene estadísticas de ubicaciones
     */
    public function getStats()
    {
        return [
            'total' => $this->count(),
            'con_productos' => $this->db->selectOne(
                "SELECT COUNT(DISTINCT u.id_ubicacion) as total
                 FROM ubicaciones u
                 JOIN productos p ON u.id_ubicacion = p.id_ubicacion
                 WHERE p.estado = 'activo'"
            )['total'] ?? 0,
            'sin_productos' => $this->db->selectOne(
                "SELECT COUNT(*) as total
                 FROM ubicaciones u
                 WHERE u.id_ubicacion NOT IN (
                     SELECT DISTINCT p.id_ubicacion 
                     FROM productos p 
                     WHERE p.estado = 'activo' AND p.id_ubicacion IS NOT NULL
                 )"
            )['total'] ?? 0,
            'tipos' => [] // Sin tipos disponibles por ahora
        ];
    }

    /**
     * Obtiene tipos de ubicación disponibles (funcionalidad simplificada)
     */
    public function getTipos()
    {
        return []; // Retorna array vacío ya que no hay columna tipo en la BD
    }

    /**
     * Transfiere stock entre ubicaciones (funcionalidad deshabilitada temporalmente)
     * TODO: Implementar cuando se tenga la tabla de inventario completa
     */
    public function transferirStock($productoId, $ubicacionOrigen, $ubicacionDestino, $cantidad, $motivo)
    {
        throw new Exception('Funcionalidad de transferencia de stock no disponible actualmente');
    }
}
