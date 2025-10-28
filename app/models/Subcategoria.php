<?php

/**
 * Modelo Subcategoria - Gestión de subcategorías de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Subcategoria extends Model
{
    protected $table = 'subcategorias';
    protected $primaryKey = 'id_subcategoria';
    protected $fillable = ['id_categoria', 'nombre', 'descripcion'];

    /**
     * Obtiene subcategorías con información de categoría
     */
    public function getSubcategoriesWithCategory()
    {
        return $this->db->select(
            "SELECT s.*, c.nombre as categoria_nombre,
                    COUNT(p.id_producto) as total_productos
             FROM subcategorias s
             JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
             GROUP BY s.id_subcategoria
             ORDER BY c.nombre ASC, s.nombre ASC"
        );
    }

    /**
     * Obtiene subcategorías por categoría
     */
    public function getByCategory($categoryId)
    {
        return $this->db->select(
            "SELECT id_subcategoria, nombre FROM subcategorias 
             WHERE id_categoria = ? 
             ORDER BY nombre ASC",
            [$categoryId]
        );
    }

    /**
     * Obtiene subcategorías por categoría con estadísticas
     */
    public function getByCategoryWithStats($categoryId)
    {
        return $this->db->select(
            "SELECT s.*, COUNT(p.id_producto) as total_productos
             FROM subcategorias s
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
             WHERE s.id_categoria = ?
             GROUP BY s.id_subcategoria
             ORDER BY s.nombre ASC",
            [$categoryId]
        );
    }

    /**
     * Obtiene subcategorías activas de una categoría
     */
    public function getActiveByCategoryForSelect($categoryId)
    {
        return $this->db->select(
            "SELECT id_subcategoria, nombre 
             FROM subcategorias 
             WHERE id_categoria = ? 
             ORDER BY nombre ASC",
            [$categoryId]
        );
    }

    /**
     * Crea una nueva subcategoría
     */
    public function createSubcategory($data)
    {
        // Validar que la categoría existe
        $category = $this->db->selectOne(
            "SELECT id_categoria FROM categorias WHERE id_categoria = ?",
            [$data['id_categoria']]
        );

        if (!$category) {
            throw new Exception('La categoría seleccionada no existe');
        }

        // Validar nombre único dentro de la categoría
        if ($this->exists('nombre = ? AND id_categoria = ?', [$data['nombre'], $data['id_categoria']])) {
            throw new Exception('Ya existe una subcategoría con ese nombre en esta categoría');
        }

        return $this->create($data);
    }

    /**
     * Actualiza una subcategoría
     */
    public function updateSubcategory($id, $data)
    {
        // Validar que la categoría existe si se está cambiando
        if (isset($data['id_categoria'])) {
            $category = $this->db->selectOne(
                "SELECT id_categoria FROM categorias WHERE id_categoria = ?",
                [$data['id_categoria']]
            );

            if (!$category) {
                throw new Exception('La categoría seleccionada no existe');
            }
        }

        // Validar nombre único dentro de la categoría (excluyendo la subcategoría actual)
        if (isset($data['nombre']) && isset($data['id_categoria'])) {
            $existing = $this->whereOne(
                'nombre = ? AND id_categoria = ? AND id_subcategoria != ?',
                [$data['nombre'], $data['id_categoria'], $id]
            );
            if ($existing) {
                throw new Exception('Ya existe una subcategoría con ese nombre en esta categoría');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Busca subcategorías
     */
    public function searchSubcategories($term, $categoryId = null)
    {
        $conditions = ['1=1'];
        $params = [];

        if (!empty($term)) {
            $conditions[] = "(s.nombre LIKE ? OR s.descripcion LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%"]);
        }

        if ($categoryId) {
            $conditions[] = "s.id_categoria = ?";
            $params[] = $categoryId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT s.*, c.nombre as categoria_nombre,
                    COUNT(p.id_producto) as total_productos
             FROM subcategorias s
             JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
             WHERE {$whereClause}
             GROUP BY s.id_subcategoria
             ORDER BY c.nombre ASC, s.nombre ASC",
            $params
        );
    }

    /**
     * Verifica si la subcategoría puede ser eliminada
     */
    public function canDelete($subcategoryId)
    {
        return $this->db->count('productos', ['id_subcategoria = ? AND estado = ?'], [$subcategoryId, 'activo']) == 0;
    }

    /**
     * Obtiene subcategoría con detalles
     */
    public function getSubcategoryWithDetails($id)
    {
        return $this->db->selectOne(
            "SELECT s.*, c.nombre as categoria_nombre,
                    COUNT(p.id_producto) as total_productos
             FROM subcategorias s
             JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
             WHERE s.id_subcategoria = ?
             GROUP BY s.id_subcategoria",
            [$id]
        );
    }

    /**
     * Obtiene estadísticas de subcategorías
     */
    public function getStats()
    {
        return [
            'total' => $this->count(),
            'con_productos' => $this->db->selectOne(
                "SELECT COUNT(DISTINCT s.id_subcategoria) as total
                 FROM subcategorias s
                 JOIN productos p ON s.id_subcategoria = p.id_subcategoria
                 WHERE p.estado = 'activo'"
            )['total'] ?? 0,
            'sin_productos' => $this->db->selectOne(
                "SELECT COUNT(*) as total
                 FROM subcategorias s
                 WHERE s.id_subcategoria NOT IN (
                     SELECT DISTINCT p.id_subcategoria 
                     FROM productos p 
                     WHERE p.estado = 'activo'
                 )"
            )['total'] ?? 0
        ];
    }
}
