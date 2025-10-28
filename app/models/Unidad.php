<?php

/**
 * Modelo Unidad - Gestión de unidades de medida
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Unidad extends Model
{
    protected $table = 'unidades';
    protected $primaryKey = 'id_unidad';
    protected $fillable = ['nombre', 'abreviatura', 'descripcion'];

    /**
     * Obtiene unidades con estadísticas
     */
    public function getUnidadesWithStats()
    {
        return $this->db->select(
            "SELECT u.*, COUNT(p.id_producto) as total_productos
             FROM unidades u
             LEFT JOIN productos p ON u.id_unidad = p.id_unidad AND p.estado = 'activo'
             GROUP BY u.id_unidad
             ORDER BY u.nombre ASC"
        );
    }

    /**
     * Obtiene unidades para select
     */
    public function getForSelect()
    {
        return $this->db->select(
            "SELECT id_unidad, nombre 
             FROM unidades 
             ORDER BY nombre ASC"
        );
    }

    /**
     * Crea una nueva unidad
     */
    public function createUnidad($data)
    {
        // Validar nombre único
        if ($this->exists('nombre = ?', [$data['nombre']])) {
            throw new Exception('Ya existe una unidad con ese nombre');
        }

        // Validar abreviatura única si se proporciona
        if (!empty($data['abreviatura']) && $this->exists('abreviatura = ?', [$data['abreviatura']])) {
            throw new Exception('Ya existe una unidad con esa abreviatura');
        }

        return $this->create($data);
    }

    /**
     * Actualiza una unidad
     */
    public function updateUnidad($id, $data)
    {
        // Validar nombre único (excluyendo la unidad actual)
        if (isset($data['nombre'])) {
            $existing = $this->whereOne('nombre = ? AND id_unidad != ?', [$data['nombre'], $id]);
            if ($existing) {
                throw new Exception('Ya existe una unidad con ese nombre');
            }
        }

        // Validar abreviatura única (excluyendo la unidad actual)
        if (isset($data['abreviatura']) && !empty($data['abreviatura'])) {
            $existing = $this->whereOne('abreviatura = ? AND id_unidad != ?', [$data['abreviatura'], $id]);
            if ($existing) {
                throw new Exception('Ya existe una unidad con esa abreviatura');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Busca unidades
     */
    public function searchUnidades($term)
    {
        return $this->db->select(
            "SELECT u.*, COUNT(p.id_producto) as total_productos
             FROM unidades u
             LEFT JOIN productos p ON u.id_unidad = p.id_unidad AND p.estado = 'activo'
             WHERE u.nombre LIKE ? OR u.abreviatura LIKE ? OR u.descripcion LIKE ?
             GROUP BY u.id_unidad
             ORDER BY u.nombre ASC",
            ["%$term%", "%$term%", "%$term%"]
        );
    }

    /**
     * Verifica si la unidad puede ser eliminada
     */
    public function canDelete($unidadId)
    {
        return $this->db->count('productos', ['id_unidad = ? AND estado = ?'], [$unidadId, 'activo']) == 0;
    }

    /**
     * Obtiene unidad con detalles
     */
    public function getUnidadWithDetails($id)
    {
        return $this->db->selectOne(
            "SELECT u.*, COUNT(p.id_producto) as total_productos
             FROM unidades u
             LEFT JOIN productos p ON u.id_unidad = p.id_unidad AND p.estado = 'activo'
             WHERE u.id_unidad = ?
             GROUP BY u.id_unidad",
            [$id]
        );
    }

    /**
     * Obtiene productos de una unidad
     */
    public function getProductos($unidadId, $limit = 10)
    {
        return $this->db->select(
            "SELECT p.*, s.nombre as subcategoria_nombre, m.nombre as marca_nombre,
                    p.stock_actual, p.precio_unitario
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             WHERE p.id_unidad = ? AND p.estado = 'activo'
             ORDER BY p.nombre ASC
             LIMIT ?",
            [$unidadId, $limit]
        );
    }

    /**
     * Obtiene estadísticas de unidades
     */
    public function getStats()
    {
        return [
            'total' => $this->count(),
            'con_productos' => $this->db->selectOne(
                "SELECT COUNT(DISTINCT u.id_unidad) as total
                 FROM unidades u
                 JOIN productos p ON u.id_unidad = p.id_unidad
                 WHERE p.estado = 'activo'"
            )['total'] ?? 0,
            'sin_productos' => $this->db->selectOne(
                "SELECT COUNT(*) as total
                 FROM unidades u
                 WHERE u.id_unidad NOT IN (
                     SELECT DISTINCT p.id_unidad 
                     FROM productos p 
                     WHERE p.estado = 'activo' AND p.id_unidad IS NOT NULL
                 )"
            )['total'] ?? 0
        ];
    }

    /**
     * Obtiene unidades más utilizadas
     */
    public function getTopUnidades($limit = 5)
    {
        return $this->db->select(
            "SELECT u.*, COUNT(p.id_producto) as total_productos
             FROM unidades u
             JOIN productos p ON u.id_unidad = p.id_unidad
             WHERE p.estado = 'activo'
             GROUP BY u.id_unidad
             ORDER BY total_productos DESC
             LIMIT ?",
            [$limit]
        );
    }

    /**
     * Obtiene unidades estándar comunes
     */
    public static function getUnidadesEstandar()
    {
        return [
            ['nombre' => 'Unidad', 'abreviatura' => 'Und', 'descripcion' => 'Unidad individual'],
            ['nombre' => 'Kilogramo', 'abreviatura' => 'Kg', 'descripcion' => 'Unidad de peso'],
            ['nombre' => 'Gramo', 'abreviatura' => 'g', 'descripcion' => 'Unidad de peso menor'],
            ['nombre' => 'Litro', 'abreviatura' => 'L', 'descripcion' => 'Unidad de volumen'],
            ['nombre' => 'Metro', 'abreviatura' => 'm', 'descripcion' => 'Unidad de longitud'],
            ['nombre' => 'Centímetro', 'abreviatura' => 'cm', 'descripcion' => 'Unidad de longitud menor'],
            ['nombre' => 'Caja', 'abreviatura' => 'Cja', 'descripcion' => 'Unidad de empaque'],
            ['nombre' => 'Paquete', 'abreviatura' => 'Paq', 'descripcion' => 'Unidad de empaque'],
            ['nombre' => 'Par', 'abreviatura' => 'Par', 'descripcion' => 'Dos unidades'],
            ['nombre' => 'Juego', 'abreviatura' => 'Jgo', 'descripcion' => 'Conjunto de piezas'],
            ['nombre' => 'Galón', 'abreviatura' => 'Gal', 'descripcion' => 'Unidad de volumen'],
            ['nombre' => 'Rollo', 'abreviatura' => 'Rllo', 'descripcion' => 'Unidad en rollo']
        ];
    }

    /**
     * Inicializa unidades estándar
     */
    public function initializeStandardUnits()
    {
        $unidadesEstandar = self::getUnidadesEstandar();
        $inserted = 0;

        foreach ($unidadesEstandar as $unidad) {
            // Verificar si ya existe
            $exists = $this->exists('nombre = ? OR abreviatura = ?', [$unidad['nombre'], $unidad['abreviatura']]);

            if (!$exists) {
                $this->create($unidad);
                $inserted++;
            }
        }

        return $inserted;
    }
}
