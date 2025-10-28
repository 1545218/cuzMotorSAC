<?php

/**
 * Modelo Categoria - Gestión de categorías de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Categoria extends Model
{
    protected $table = 'categorias';
    protected $primaryKey = 'id_categoria';
    protected $fillable = ['nombre', 'descripcion'];

    /**
     * Obtiene todas las categorías
     */
    public function getAll()
    {
        return $this->all('nombre ASC');
    }

    /**
     * Busca una categoría por nombre
     */
    public function findByName($nombre)
    {
        return $this->whereOne('nombre = ?', [$nombre]);
    }

    /**
     * Obtiene categorías activas (todas las categorías ya que no hay campo activo)
     */
    public function getActive()
    {
        return $this->all('nombre ASC');
    }

    /**
     * Alias para getActive() - obtiene categorías activas
     */
    public function active()
    {
        return $this->all('nombre ASC');
    }

    /**
     * Verifica si la categoría tiene productos
     */
    public function hasProducts($categoriaId)
    {
        $count = $this->db->selectOne(
            "SELECT COUNT(*) as count FROM productos p
             INNER JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             WHERE s.id_categoria = ?",
            [$categoriaId]
        );
        return $count['count'] > 0;
    }

    /**
     * Obtiene subcategorías de una categoría
     */
    public function getSubcategorias($categoriaId)
    {
        return $this->db->select(
            "SELECT * FROM subcategorias WHERE id_categoria = ? ORDER BY nombre ASC",
            [$categoriaId]
        );
    }

    /**
     * Obtiene categorías con conteo de productos
     */
    public function getCategoriesWithProductCount()
    {
        return $this->db->select(
            "SELECT c.*, 
                    COUNT(CASE WHEN p.activo = 1 THEN 1 END) as total_productos,
                    COUNT(CASE WHEN p.activo = 1 AND p.stock_actual <= p.stock_minimo THEN 1 END) as productos_stock_bajo
             FROM categorias c
             LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria
             WHERE c.activo = 1
             GROUP BY c.id_categoria, c.nombre, c.descripcion, c.activo
             ORDER BY c.nombre ASC"
        );
    }

    /**
     * Obtiene categorías activas
     */
    public function getActiveCategories()
    {
        return $this->where('activo = ?', [1], 'nombre ASC');
    }

    /**
     * Obtiene una categoría con sus subcategorías
     */
    public function getCategoryWithSubcategories($id)
    {
        $category = $this->find($id);
        if (!$category) {
            return null;
        }

        $subcategories = $this->db->select(
            "SELECT s.*, COUNT(p.id_producto) as total_productos
             FROM subcategorias s
             LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.activo = 1
             WHERE s.id_categoria = ?
             GROUP BY s.id_subcategoria
             ORDER BY s.nombre ASC",
            [$id]
        );

        $category['subcategorias'] = $subcategories;
        return $category;
    }

    /**
     * Crea una nueva categoría
     */
    public function createCategory($data)
    {
        // Validar nombre único
        if ($this->exists('nombre = ? AND activo = ?', [$data['nombre'], 1])) {
            throw new Exception('Ya existe una categoría con ese nombre');
        }

        return $this->create($data);
    }

    /**
     * Actualiza una categoría
     */
    public function updateCategory($id, $data)
    {
        // Validar nombre único (excluyendo la categoría actual)
        if (isset($data['nombre'])) {
            $existing = $this->whereOne('nombre = ? AND activo = ? AND id_categoria != ?', [$data['nombre'], 1, $id]);
            if ($existing) {
                throw new Exception('Ya existe una categoría con ese nombre');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Busca categorías
     */
    public function searchCategories($term)
    {
        if (empty($term)) {
            return $this->getActiveCategories();
        }

        return $this->where(
            'activo = 1 AND (nombre LIKE ? OR descripcion LIKE ?)',
            ["%$term%", "%$term%"],
            'nombre ASC'
        );
    }

    /**
     * Verifica si la categoría puede ser eliminada
     */
    public function canDelete($categoryId)
    {
        // Verificar si tiene subcategorías
        $hasSubcategories = $this->db->count('subcategorias', ['id_categoria = ?'], [$categoryId]) > 0;

        // Verificar si tiene productos (a través de subcategorías)
        $hasProducts = $this->db->selectOne(
            "SELECT COUNT(*) as total 
             FROM productos p 
             JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria 
             WHERE s.id_categoria = ? AND p.activo = 1",
            [$categoryId]
        );

        return !($hasSubcategories || ($hasProducts && $hasProducts['total'] > 0));
    }

    /**
     * Eliminación suave de la categoría
     */
    public function softDelete($categoryId)
    {
        // También desactivar subcategorías relacionadas
        $this->db->update(
            "UPDATE subcategorias SET activo = 0 WHERE id_categoria = ?",
            [$categoryId]
        );

        return $this->update($categoryId, ['activo' => 0]);
    }

    /**
     * Obtiene estadísticas de categorías
     */
    public function getStats()
    {
        return [
            'total' => $this->count('activo = ?', [1]),
            'con_productos' => $this->db->selectOne(
                "SELECT COUNT(DISTINCT c.id_categoria) as total
                 FROM categorias c
                 JOIN subcategorias s ON c.id_categoria = s.id_categoria
                 JOIN productos p ON s.id_subcategoria = p.id_subcategoria
                 WHERE c.activo = 1 AND p.activo = 1"
            )['total'] ?? 0,
            'sin_productos' => $this->db->selectOne(
                "SELECT COUNT(*) as total
                 FROM categorias c
                 WHERE c.activo = 1 
                 AND c.id_categoria NOT IN (
                     SELECT DISTINCT s.id_categoria 
                     FROM subcategorias s 
                     JOIN productos p ON s.id_subcategoria = p.id_subcategoria 
                     WHERE p.activo = 1
                 )"
            )['total'] ?? 0
        ];
    }

    /**
     * Obtiene las categorías más utilizadas
     */
    public function getTopCategories($limit = 5)
    {
        return $this->db->select(
            "SELECT c.*, COUNT(p.id_producto) as total_productos
             FROM categorias c
             JOIN subcategorias s ON c.id_categoria = s.id_categoria
             JOIN productos p ON s.id_subcategoria = p.id_subcategoria
             WHERE c.activo = 1 AND p.activo = 1
             GROUP BY c.id_categoria
             ORDER BY total_productos DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene categorías para select
     */
    public function getForSelect()
    {
        return $this->db->select(
            "SELECT id_categoria, nombre FROM categorias ORDER BY nombre ASC"
        );
    }
}
