<?php

/**
 * Modelo RolUsuario - Gestión de roles de usuarios
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla roles_usuarios existente
 */

class RolUsuario extends Model
{
    protected $table = 'roles_usuarios';
    protected $primaryKey = 'id_rol';
    protected $fillable = [
        'nombre',
        'descripcion'
    ];

    /**
     * Obtener todos los roles activos
     */
    public function getAllRoles()
    {
        return $this->all('nombre ASC');
    }

    /**
     * Obtener rol por nombre
     */
    public function getByNombre($nombre)
    {
        return $this->where('nombre = ?', [$nombre]);
    }

    /**
     * Verificar si existe un rol
     */
    public function existeRol($nombre, $excludeId = null)
    {
        $query = "SELECT COUNT(*) as count FROM {$this->table} WHERE nombre = ?";
        $params = [$nombre];

        if ($excludeId) {
            $query .= " AND id_rol != ?";
            $params[] = $excludeId;
        }

        $result = $this->db->select($query, $params);
        return $result[0]['count'] > 0;
    }

    /**
     * Obtener estadísticas de roles
     */
    public function getEstadisticas()
    {
        return [
            'total_roles' => $this->count(),
            'roles_sistema' => $this->count('nombre IN (?)', ['administrador,mecanico,vendedor'])
        ];
    }

    /**
     * Validar datos del rol
     */
    public function validarDatos($data, $id = null)
    {
        $errores = [];

        // Validar nombre requerido
        if (empty($data['nombre'])) {
            $errores[] = 'El nombre del rol es obligatorio';
        } else {
            // Validar longitud
            if (strlen($data['nombre']) > 50) {
                $errores[] = 'El nombre no puede exceder 50 caracteres';
            }

            // Validar unicidad
            if ($this->existeRol($data['nombre'], $id)) {
                $errores[] = 'Ya existe un rol con ese nombre';
            }
        }

        // Validar descripción opcional
        if (!empty($data['descripcion']) && strlen($data['descripcion']) > 255) {
            $errores[] = 'La descripción no puede exceder 255 caracteres';
        }

        return $errores;
    }

    /**
     * Crear nuevo rol con validación
     */
    public function crearRol($data)
    {
        try {
            $errores = $this->validarDatos($data);
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }

            return $this->create([
                'nombre' => trim($data['nombre']),
                'descripcion' => trim($data['descripcion'] ?? '')
            ]);
        } catch (Exception $e) {
            Logger::error('Error al crear rol', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Actualizar rol existente
     */
    public function actualizarRol($id, $data)
    {
        try {
            $errores = $this->validarDatos($data, $id);
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }

            return $this->update($id, [
                'nombre' => trim($data['nombre']),
                'descripcion' => trim($data['descripcion'] ?? '')
            ]);
        } catch (Exception $e) {
            Logger::error('Error al actualizar rol', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            throw $e;
        }
    }

    /**
     * Eliminar rol (solo si no tiene usuarios asignados)
     */
    public function eliminarRol($id)
    {
        try {
            // Verificar si el rol tiene usuarios asignados
            $usuariosAsignados = $this->db->select(
                "SELECT COUNT(*) as count FROM usuarios WHERE id_rol = ?",
                [$id]
            );

            if ($usuariosAsignados[0]['count'] > 0) {
                throw new Exception('No se puede eliminar el rol porque tiene usuarios asignados');
            }

            return $this->delete($id);
        } catch (Exception $e) {
            Logger::error('Error al eliminar rol', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            throw $e;
        }
    }

    /**
     * Obtener roles con conteo de usuarios
     */
    public function getRolesConUsuarios()
    {
        return $this->db->select("
            SELECT r.*, 
                   COUNT(u.id_usuario) as total_usuarios
            FROM roles_usuarios r 
            LEFT JOIN usuarios u ON r.id_rol = u.id_rol 
            GROUP BY r.id_rol, r.nombre, r.descripcion
            ORDER BY r.nombre ASC
        ");
    }
}
