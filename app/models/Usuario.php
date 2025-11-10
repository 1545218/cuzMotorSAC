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
        'usuario',
        'password_hash',
        'telefono',
        'id_rol',
        'estado'
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
        return $this->count('rol = ? AND estado = ?', [$role, 'activo']);
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
        return $this->where('estado = ?', ['activo'], 'nombre ASC');
    }

    /**
     * Obtiene usuarios por rol
     */
    public function getUsersByRole($role)
    {
        return $this->where('rol = ? AND estado = ?', [$role, 'activo'], 'nombre ASC');
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

        $newStatus = $user['estado'] === 'activo' ? 'inactivo' : 'activo';
        return $this->update($id, ['estado' => $newStatus]);
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
            'activos' => $this->count('estado = ?', ['activo']),
            'inactivos' => $this->count('estado = ?', ['inactivo']),
            'administradores' => $this->count('rol = ? AND estado = ?', ['admin', 'activo']),
            'vendedores' => $this->count('rol = ? AND estado = ?', ['vendedor', 'activo']),
            'mecanicos' => $this->count('rol = ? AND estado = ?', ['mecanico', 'activo'])
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
            $conditions[] = "estado = ?";
            $params[] = $filters['activo'] == 1 ? 'activo' : 'inactivo';
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
        if ($this->count('rol = ? AND estado = ?', ['admin', 'activo']) <= 1) {
            $user = $this->find($userId);
            if ($user && $user['rol'] === 'admin') {
                return false;
            }
        }

        return true;
    }

    /**
     * Registra una acción del usuario en los logs del sistema
     */
    public function registrarLog($idUsuario, $accion, $descripcion = null)
    {
        try {
            require_once 'LogSistema.php';
            $logSistema = new LogSistema();
            return $logSistema->registrarAccion($idUsuario, $accion, $descripcion);
        } catch (Exception $e) {
            // Si falla el log, no interrumpir la operación principal
            error_log("Error al registrar log: " . $e->getMessage());
            return false;
        }
    }
}
