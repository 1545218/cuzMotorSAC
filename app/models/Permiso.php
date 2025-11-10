<?php

/**
 * Modelo Permiso - Gestión de permisos por rol
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla permisos existente
 */

class Permiso extends Model
{
    protected $table = 'permisos';
    protected $primaryKey = 'id_permiso';
    protected $fillable = [
        'id_rol',
        'modulo',
        'puede_leer',
        'puede_escribir',
        'puede_eliminar'
    ];

    // Módulos disponibles en el sistema
    const MODULOS = [
        'productos' => 'Gestión de Productos',
        'inventario' => 'Control de Inventario',
        'clientes' => 'Gestión de Clientes',
        'vehiculos' => 'Gestión de Vehículos',
        'ordenes' => 'Órdenes de Trabajo',
        'cotizaciones' => 'Cotizaciones',
        'ventas' => 'Gestión de Ventas',
        'reportes' => 'Reportes',
        'usuarios' => 'Gestión de Usuarios',
        'configuracion' => 'Configuración del Sistema',
        'alertas' => 'Alertas y Notificaciones',
        'backups' => 'Respaldos del Sistema'
    ];

    /**
     * Obtener permisos por rol
     */
    public function getPermisosByRol($idRol)
    {
        return $this->where('id_rol = ?', [$idRol], 'modulo ASC');
    }

    /**
     * Obtener permisos de un rol con información del módulo
     */
    public function getPermisosCompletos($idRol)
    {
        $permisos = $this->getPermisosByRol($idRol);
        $resultado = [];

        foreach (self::MODULOS as $modulo => $descripcion) {
            $permiso = array_filter($permisos, function ($p) use ($modulo) {
                return $p['modulo'] === $modulo;
            });

            if (!empty($permiso)) {
                $permiso = array_values($permiso)[0];
            } else {
                // Permiso por defecto (solo lectura)
                $permiso = [
                    'modulo' => $modulo,
                    'puede_leer' => 1,
                    'puede_escribir' => 0,
                    'puede_eliminar' => 0
                ];
            }

            $permiso['descripcion'] = $descripcion;
            $resultado[] = $permiso;
        }

        return $resultado;
    }

    /**
     * Verificar si un rol tiene permiso específico
     */
    public function tienePermiso($idRol, $modulo, $accion = 'leer')
    {
        $permiso = $this->where('id_rol = ? AND modulo = ?', [$idRol, $modulo]);

        if (empty($permiso)) {
            // Si no hay permiso específico, permitir solo lectura para algunos módulos básicos
            return in_array($modulo, ['productos', 'inventario']) && $accion === 'leer';
        }

        $permiso = $permiso[0];

        switch ($accion) {
            case 'leer':
                return (bool)$permiso['puede_leer'];
            case 'escribir':
                return (bool)$permiso['puede_escribir'];
            case 'eliminar':
                return (bool)$permiso['puede_eliminar'];
            default:
                return false;
        }
    }

    /**
     * Configurar permisos para un rol
     */
    public function configurarPermisos($idRol, $permisos)
    {
        try {
            $this->db->beginTransaction();

            // Eliminar permisos existentes
            $this->db->delete("DELETE FROM {$this->table} WHERE id_rol = ?", [$idRol]);

            // Insertar nuevos permisos
            foreach ($permisos as $modulo => $config) {
                if (array_key_exists($modulo, self::MODULOS)) {
                    $this->create([
                        'id_rol' => $idRol,
                        'modulo' => $modulo,
                        'puede_leer' => (bool)($config['leer'] ?? false),
                        'puede_escribir' => (bool)($config['escribir'] ?? false),
                        'puede_eliminar' => (bool)($config['eliminar'] ?? false)
                    ]);
                }
            }

            $this->db->commit();

            Logger::info('Permisos configurados', [
                'id_rol' => $idRol,
                'modulos' => array_keys($permisos)
            ]);

            return true;
        } catch (Exception $e) {
            $this->db->rollback();

            Logger::error('Error al configurar permisos', [
                'error' => $e->getMessage(),
                'id_rol' => $idRol,
                'permisos' => $permisos
            ]);

            throw $e;
        }
    }

    /**
     * Obtener permisos predeterminados por rol
     */
    public function getPermisosPredeterminados($nombreRol)
    {
        $permisos = [];

        switch (strtolower($nombreRol)) {
            case 'administrador':
                // Administrador tiene todos los permisos
                foreach (self::MODULOS as $modulo => $descripcion) {
                    $permisos[$modulo] = [
                        'leer' => true,
                        'escribir' => true,
                        'eliminar' => true
                    ];
                }
                break;

            case 'mecanico':
                // Mecánico tiene permisos limitados
                $permisos = [
                    'productos' => ['leer' => true, 'escribir' => false, 'eliminar' => false],
                    'inventario' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'clientes' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'vehiculos' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'ordenes' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'cotizaciones' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'reportes' => ['leer' => true, 'escribir' => false, 'eliminar' => false]
                ];
                break;

            case 'vendedor':
                // Vendedor tiene permisos muy limitados
                $permisos = [
                    'productos' => ['leer' => true, 'escribir' => false, 'eliminar' => false],
                    'inventario' => ['leer' => true, 'escribir' => false, 'eliminar' => false],
                    'clientes' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'cotizaciones' => ['leer' => true, 'escribir' => true, 'eliminar' => false],
                    'ventas' => ['leer' => true, 'escribir' => true, 'eliminar' => false]
                ];
                break;

            default:
                // Rol desconocido: solo lectura básica
                $permisos = [
                    'productos' => ['leer' => true, 'escribir' => false, 'eliminar' => false],
                    'inventario' => ['leer' => true, 'escribir' => false, 'eliminar' => false]
                ];
        }

        return $permisos;
    }

    /**
     * Inicializar permisos para un nuevo rol
     */
    public function inicializarPermisos($idRol, $nombreRol)
    {
        $permisosPredeterminados = $this->getPermisosPredeterminados($nombreRol);
        return $this->configurarPermisos($idRol, $permisosPredeterminados);
    }

    /**
     * Obtener estadísticas de permisos
     */
    public function getEstadisticas()
    {
        $stats = $this->db->select("
            SELECT 
                modulo,
                SUM(puede_leer) as total_lectura,
                SUM(puede_escribir) as total_escritura,
                SUM(puede_eliminar) as total_eliminacion,
                COUNT(*) as total_permisos
            FROM {$this->table}
            GROUP BY modulo
            ORDER BY modulo
        ");

        return $stats;
    }
}
