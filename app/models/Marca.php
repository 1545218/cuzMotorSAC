<?php

/**
 * Modelo Marca - Gestión de marcas de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Marca extends Model
{
    protected $table = 'marcas';
    protected $primaryKey = 'id_marca';
    protected $fillable = ['nombre', 'descripcion', 'pais_origen', 'sitio_web', 'activo'];

    /**
     * Obtiene todas las marcas
     */
    public function getAll()
    {
        return $this->all('nombre ASC');
    }

    /**
     * Busca una marca por nombre
     */
    public function findByName($nombre)
    {
        return $this->whereOne('nombre = ?', [$nombre]);
    }

    /**
     * Obtiene marcas activas
     */
    public function getActive()
    {
        return $this->where('activo = ?', [1], 'nombre ASC');
    }

    /**
     * Verifica si la marca tiene productos
     */
    public function hasProducts($marcaId)
    {
        $count = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM productos WHERE id_marca = ?",
            [$marcaId]
        );
        return $count['count'] > 0;
    }

    /**
     * Obtiene estadísticas de la marca
     */
    public function getStats($marcaId)
    {
        return $this->db->selectOne(
            "SELECT 
                COUNT(p.id_producto) as total_productos,
                SUM(p.stock_actual) as total_stock,
                AVG(p.precio_venta) as precio_promedio
             FROM productos p 
             WHERE p.id_marca = ? AND p.activo = 1",
            [$marcaId]
        );
    }

    /**
     * Obtiene marcas con estadísticas
     */
    public function getMarcasWithStats()
    {
        return $this->db->select(
            "SELECT m.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(i.stock_actual), 0) as stock_total
             FROM marcas m
             LEFT JOIN productos p ON m.id_marca = p.id_marca AND p.activo = 1
             LEFT JOIN inventario i ON p.id_producto = i.id_producto
             GROUP BY m.id_marca
             ORDER BY m.nombre ASC"
        );
    }

    /**
     * Obtiene marcas activas para select
     */
    public function getForSelect()
    {
        return $this->db->select(
            "SELECT id_marca, nombre 
             FROM marcas 
             ORDER BY nombre ASC"
        );
    }

    /**
     * Crea una nueva marca
     */
    public function createMarca($data)
    {
        // Validar nombre único
        if ($this->exists('nombre = ?', [$data['nombre']])) {
            throw new Exception('Ya existe una marca con ese nombre');
        }

        return $this->create($data);
    }

    /**
     * Actualiza una marca
     */
    public function updateMarca($id, $data)
    {
        // Validar nombre único (excluyendo la marca actual)
        if (isset($data['nombre'])) {
            $existing = $this->whereOne('nombre = ? AND id_marca != ?', [$data['nombre'], $id]);
            if ($existing) {
                throw new Exception('Ya existe una marca con ese nombre');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Busca marcas
     */
    public function searchMarcas($term)
    {
        return $this->db->select(
            "SELECT m.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(i.stock_actual), 0) as stock_total
             FROM marcas m
             LEFT JOIN productos p ON m.id_marca = p.id_marca AND p.activo = 1
             LEFT JOIN inventario i ON p.id_producto = i.id_producto
             WHERE m.nombre LIKE ? OR m.descripcion LIKE ?
             GROUP BY m.id_marca
             ORDER BY m.nombre ASC",
            ["%$term%", "%$term%"]
        );
    }

    /**
     * Verifica si la marca puede ser eliminada
     */
    public function canDelete($marcaId)
    {
        return $this->db->count('productos', ['id_marca = ? AND activo = ?'], [$marcaId, 1]) == 0;
    }

    /**
     * Obtiene marca con detalles
     */
    public function getMarcaWithDetails($id)
    {
        return $this->db->selectOne(
            "SELECT m.*, 
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(i.stock_actual), 0) as stock_total,
                    COALESCE(SUM(i.precio_costo * i.stock_actual), 0) as valor_inventario
             FROM marcas m
             LEFT JOIN productos p ON m.id_marca = p.id_marca AND p.activo = 1
             LEFT JOIN inventario i ON p.id_producto = i.id_producto
             WHERE m.id_marca = ?
             GROUP BY m.id_marca",
            [$id]
        );
    }

    /**
     * Obtiene productos de una marca
     */
    public function getProductos($marcaId, $limit = 10)
    {
        return $this->db->select(
            "SELECT p.*, c.nombre as categoria_nombre, s.nombre as subcategoria_nombre,
                    i.stock_actual, i.precio_venta
             FROM productos p
             LEFT JOIN categorias c ON p.id_categoria = c.id_categoria
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN inventario i ON p.id_producto = i.id_producto
             WHERE p.id_marca = ? AND p.activo = 1
             ORDER BY p.nombre ASC
             LIMIT ?",
            [$marcaId, $limit]
        );
    }

    /**
     * Obtiene marcas más populares (por cantidad de productos)
     */
    public function getTopMarcas($limit = 5)
    {
        return $this->db->select(
            "SELECT m.*, COUNT(p.id_producto) as total_productos
             FROM marcas m
             JOIN productos p ON m.id_marca = p.id_marca
             WHERE p.activo = 1
             GROUP BY m.id_marca
             ORDER BY total_productos DESC
             LIMIT ?",
            [$limit]
        );
    }
}
