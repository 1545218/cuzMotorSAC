<?php

/**
 * Modelo Usuario - Gestión de usuarios del sistema
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Usuario extends Model
{
    protected $table = 'usuarios';
    protected $primaryKey = 'id_usuario';
    protected $fillable = [
        'nombre',
        'apellido',
        'username',
        'password',
        'telefono',
        'email',
        'rol',
        'activo'
    ];

    /**
     * Obtiene todos los usuarios reales de la tabla
     */
    public function getAll()
    {
        // Solo los campos reales de la tabla usuarios
        $sql = "SELECT id_usuario, nombre, apellido, usuario, telefono, id_rol, estado FROM usuarios ORDER BY nombre ASC";
        return $this->db->select($sql);
    }

    /**
     * Cuenta usuarios por rol
     */
    public function countByRole($role)
    {
        return $this->count('rol = ? AND activo = ?', [$role, 1]);
    }

    /**
     * Busca un usuario por username
     */
    public function findByUsername($username)
    {
        return $this->whereOne('username = ?', [$username]);
    }

    /**
     * Busca un usuario por email
     */
    public function findByEmail($email)
    {
        return $this->whereOne('email = ?', [$email]);
    }

    /**
     * Obtiene todos los usuarios activos
     */
    public function getActiveUsers()
    {
        return $this->where('activo = ?', [1], 'nombre ASC');
    }

    /**
     * Obtiene usuarios por rol
     */
    public function getUsersByRole($role)
    {
        return $this->where('rol = ? AND activo = ?', [$role, 1], 'nombre ASC');
    }

    /**
     * Crea un nuevo usuario con contraseña hash
     */
    public function createUser($data)
    {
        // Hash de la contraseña
        if (isset($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        // Validar datos únicos
        if ($this->exists('username = ?', [$data['username']])) {
            throw new Exception('El nombre de usuario ya existe');
        }

        if (isset($data['email']) && !empty($data['email'])) {
            if ($this->exists('email = ?', [$data['email']])) {
                throw new Exception('El email ya está registrado');
            }
        }

        return $this->create($data);
    }

    /**
     * Actualiza un usuario
     */
    public function updateUser($id, $data)
    {
        // Hash de la contraseña si se está actualizando
        if (isset($data['password']) && !empty($data['password'])) {
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        } else {
            // No actualizar la contraseña si está vacía
            unset($data['password']);
        }

        // Validar username único (excluyendo el usuario actual)
        if (isset($data['username'])) {
            $existing = $this->whereOne('username = ? AND id_usuario != ?', [$data['username'], $id]);
            if ($existing) {
                throw new Exception('El nombre de usuario ya existe');
            }
        }

        // Validar email único (excluyendo el usuario actual)
        if (isset($data['email']) && !empty($data['email'])) {
            $existing = $this->whereOne('email = ? AND id_usuario != ?', [$data['email'], $id]);
            if ($existing) {
                throw new Exception('El email ya está registrado');
            }
        }

        return $this->update($id, $data);
    }

    /**
     * Cambia el estado de un usuario
     */
    public function toggleStatus($id)
    {
        $user = $this->find($id);
        if (!$user) {
            throw new Exception('Usuario no encontrado');
        }

        $newStatus = $user['activo'] ? 0 : 1;
        return $this->update($id, ['activo' => $newStatus]);
    }

    /**
     * Obtiene el nombre completo del usuario
     */
    public function getFullName($user)
    {
        if (is_array($user)) {
            return trim($user['nombre'] . ' ' . $user['apellido']);
        }
        return '';
    }

    /**
     * Obtiene estadísticas de usuarios
     */
    public function getStats()
    {
        return [
            'total' => $this->count(),
            'activos' => $this->count('activo = ?', [1]),
            'inactivos' => $this->count('activo = ?', [0]),
            'administradores' => $this->count('rol = ? AND activo = ?', ['admin', 1]),
            'vendedores' => $this->count('rol = ? AND activo = ?', ['vendedor', 1]),
            'mecanicos' => $this->count('rol = ? AND activo = ?', ['mecanico', 1])
        ];
    }

    /**
     * Buscar usuarios
     */
    public function searchUsers($term, $filters = [])
    {
        $conditions = [];
        $params = [];

        // Búsqueda por término
        if (!empty($term)) {
            $conditions[] = "(nombre LIKE ? OR apellido LIKE ? OR username LIKE ? OR email LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%", "%$term%", "%$term%"]);
        }

        // Filtros adicionales
        if (isset($filters['rol']) && !empty($filters['rol'])) {
            $conditions[] = "rol = ?";
            $params[] = $filters['rol'];
        }

        if (isset($filters['activo']) && $filters['activo'] !== '') {
            $conditions[] = "activo = ?";
            $params[] = (int)$filters['activo'];
        }

        $whereClause = !empty($conditions) ? implode(' AND ', $conditions) : '1=1';

        return $this->where($whereClause, $params, 'nombre ASC');
    }

    /**
     * Obtiene la última actividad del usuario
     */
    public function getLastActivity($userId)
    {
        try {
            return $this->db->selectOne(
                "SELECT accion, created_at FROM activity_logs 
                 WHERE usuario_id = ? 
                 ORDER BY created_at DESC 
                 LIMIT 1",
                [$userId]
            );
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Validar contraseña del usuario
     */
    public function validatePassword($userId, $password)
    {
        $user = $this->find($userId);
        if (!$user) {
            return false;
        }

        return password_verify($password, $user['password']);
    }

    /**
     * Obtener roles disponibles
     */
    public function getAvailableRoles()
    {
        return [
            'admin' => 'Administrador',
            'vendedor' => 'Vendedor',
            'mecanico' => 'Mecánico'
        ];
    }

    /**
     * Verificar si el usuario puede ser eliminado
     */
    public function canDelete($userId)
    {
        // No permitir eliminar si es el único administrador
        if ($this->count('rol = ? AND activo = ?', ['admin', 1]) <= 1) {
            $user = $this->find($userId);
            if ($user && $user['rol'] === 'admin') {
                return false;
            }
        }

        return true;
    }
}
