<?php

/**
 * Modelo SesionUsuario - Gestión de sesiones de usuarios en base de datos
 * Sistema de Inventario Cruz Motor S.A.C.
 * 
 * Tabla: sesionusuarios
 * Campos: id_sesion, id_usuario, ip_address, inicio_sesion, fin_sesion, activo
 */

class SesionUsuario extends Model
{
    protected $tableName = 'sesionusuarios';
    protected $primaryKey = 'id_sesion';

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Iniciar nueva sesión para un usuario
     * 
     * @param int $userId ID del usuario
     * @param string $ipAddress IP del cliente
     * @param string $sessionId ID de la sesión de PHP
     * @return int|false ID de la sesión creada o false en error
     */
    public function iniciarSesion($userId, $ipAddress = null, $sessionId = null)
    {
        try {
            $ipAddress = $ipAddress ?: $this->obtenerIPCliente();

            // Crear nueva sesión
            $result = $this->db->insert($this->tableName, [
                'id_usuario' => $userId,
                'ip_address' => $ipAddress,
                'inicio_sesion' => date('Y-m-d H:i:s'),
                'activo' => 1
            ]);

            if ($result) {
                $sessionId = $this->db->lastInsertId();

                // Registrar en auditoría
                if (class_exists('HistorialCambios')) {
                    $historial = new HistorialCambios();
                    $historial->registrarCambio(
                        $this->tableName,
                        $sessionId,
                        'INSERT',
                        null,
                        ['usuario_inicio' => $userId, 'ip' => $ipAddress],
                        $userId
                    );
                }

                return $sessionId;
            }

            return false;
        } catch (Exception $e) {
            Logger::error("Error al iniciar sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finalizar sesión específica
     * 
     * @param int $sessionId ID de la sesión
     * @param int $userId ID del usuario (para verificación)
     * @return bool
     */
    public function finalizarSesion($sessionId, $userId = null)
    {
        try {
            $conditions = ['id_sesion' => $sessionId, 'activo' => 1];
            if ($userId) {
                $conditions['id_usuario'] = $userId;
            }

            $result = $this->db->update($this->tableName, [
                'fin_sesion' => date('Y-m-d H:i:s'),
                'activo' => 0
            ], $conditions);

            if ($result && class_exists('HistorialCambios') && $userId) {
                $historial = new HistorialCambios();
                $historial->registrarCambio(
                    $this->tableName,
                    $sessionId,
                    'UPDATE',
                    ['activo' => 1],
                    ['activo' => 0, 'fin_sesion' => date('Y-m-d H:i:s')],
                    $userId
                );
            }

            return $result !== false;
        } catch (Exception $e) {
            Logger::error("Error al finalizar sesión: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Finalizar todas las sesiones de un usuario
     * 
     * @param int $userId ID del usuario
     * @return bool
     */
    public function finalizarTodasLasSesiones($userId)
    {
        try {
            $result = $this->db->update($this->tableName, [
                'fin_sesion' => date('Y-m-d H:i:s'),
                'activo' => 0
            ], [
                'id_usuario' => $userId,
                'activo' => 1
            ]);

            return $result !== false;
        } catch (Exception $e) {
            Logger::error("Error al finalizar todas las sesiones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Verificar si el usuario tiene sesiones activas
     * 
     * @param int $userId ID del usuario
     * @return bool
     */
    public function tieneSesionesActivas($userId)
    {
        try {
            $sesiones = $this->db->select(
                "SELECT COUNT(*) as total FROM {$this->tableName} 
                 WHERE id_usuario = ? AND activo = 1",
                [$userId]
            );

            return ($sesiones[0]['total'] ?? 0) > 0;
        } catch (Exception $e) {
            Logger::error("Error al verificar sesiones activas: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener sesiones activas de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array
     */
    public function obtenerSesionesActivas($userId = null)
    {
        try {
            $query = "SELECT s.*, u.usuario, u.nombre, u.rol 
                      FROM {$this->tableName} s 
                      INNER JOIN usuarios u ON s.id_usuario = u.id_usuario 
                      WHERE s.activo = 1";
            $params = [];

            if ($userId) {
                $query .= " AND s.id_usuario = ?";
                $params[] = $userId;
            }

            $query .= " ORDER BY s.inicio_sesion DESC";

            return $this->db->select($query, $params);
        } catch (Exception $e) {
            Logger::error("Error al obtener sesiones activas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Contar sesiones concurrentes de un usuario
     * 
     * @param int $userId ID del usuario
     * @return int
     */
    public function contarSesionesConcurrentes($userId)
    {
        try {
            $result = $this->db->select(
                "SELECT COUNT(*) as total FROM {$this->tableName} 
                 WHERE id_usuario = ? AND activo = 1",
                [$userId]
            );

            return (int)($result[0]['total'] ?? 0);
        } catch (Exception $e) {
            Logger::error("Error al contar sesiones concurrentes: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Limpiar sesiones expiradas (más de X horas sin actividad)
     * 
     * @param int $horasVencimiento Horas después de las cuales marcar como expirada
     * @return int Número de sesiones limpiadas
     */
    public function limpiarSesionesExpiradas($horasVencimiento = 24)
    {
        try {
            $fechaLimite = date('Y-m-d H:i:s', strtotime("-{$horasVencimiento} hours"));

            $result = $this->db->update($this->tableName, [
                'fin_sesion' => date('Y-m-d H:i:s'),
                'activo' => 0
            ], [
                'activo' => 1,
                'inicio_sesion <' => $fechaLimite
            ]);

            return $this->db->affectedRows();
        } catch (Exception $e) {
            Logger::error("Error al limpiar sesiones expiradas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtener estadísticas de sesiones
     * 
     * @param string $periodo Período para estadísticas (today, week, month)
     * @return array
     */
    public function obtenerEstadisticas($periodo = 'today')
    {
        try {
            $fechaCondicion = '';
            switch ($periodo) {
                case 'today':
                    $fechaCondicion = "DATE(inicio_sesion) = CURDATE()";
                    break;
                case 'week':
                    $fechaCondicion = "inicio_sesion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
                    break;
                case 'month':
                    $fechaCondicion = "inicio_sesion >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
                    break;
                default:
                    $fechaCondicion = "1=1";
            }

            $estadisticas = [];

            // Sesiones totales
            $result = $this->db->select(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE {$fechaCondicion}"
            );
            $estadisticas['total_sesiones'] = (int)($result[0]['total'] ?? 0);

            // Sesiones activas
            $result = $this->db->select(
                "SELECT COUNT(*) as total FROM {$this->tableName} WHERE activo = 1 AND {$fechaCondicion}"
            );
            $estadisticas['sesiones_activas'] = (int)($result[0]['total'] ?? 0);

            // Usuarios únicos
            $result = $this->db->select(
                "SELECT COUNT(DISTINCT id_usuario) as total FROM {$this->tableName} WHERE {$fechaCondicion}"
            );
            $estadisticas['usuarios_unicos'] = (int)($result[0]['total'] ?? 0);

            // Duración promedio (solo sesiones finalizadas)
            $result = $this->db->select(
                "SELECT AVG(TIMESTAMPDIFF(MINUTE, inicio_sesion, fin_sesion)) as promedio 
                 FROM {$this->tableName} 
                 WHERE fin_sesion IS NOT NULL AND {$fechaCondicion}"
            );
            $estadisticas['duracion_promedio_minutos'] = round((float)($result[0]['promedio'] ?? 0), 2);

            return $estadisticas;
        } catch (Exception $e) {
            Logger::error("Error al obtener estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener IP del cliente
     * 
     * @return string
     */
    private function obtenerIPCliente()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Obtener lista de sesiones con filtros
     * 
     * @param array $filtros Filtros a aplicar
     * @param int $limite Límite de resultados
     * @param int $offset Offset para paginación
     * @return array
     */
    public function obtenerSesionesConFiltros($filtros = [], $limite = 50, $offset = 0)
    {
        try {
            $query = "SELECT s.*, u.usuario, u.nombre, u.rol,
                            TIMESTAMPDIFF(MINUTE, s.inicio_sesion, COALESCE(s.fin_sesion, NOW())) as duracion_minutos
                      FROM {$this->tableName} s 
                      INNER JOIN usuarios u ON s.id_usuario = u.id_usuario 
                      WHERE 1=1";
            $params = [];

            // Aplicar filtros
            if (!empty($filtros['usuario_id'])) {
                $query .= " AND s.id_usuario = ?";
                $params[] = $filtros['usuario_id'];
            }

            if (!empty($filtros['activo'])) {
                $query .= " AND s.activo = ?";
                $params[] = $filtros['activo'] === 'si' ? 1 : 0;
            }

            if (!empty($filtros['fecha_desde'])) {
                $query .= " AND DATE(s.inicio_sesion) >= ?";
                $params[] = $filtros['fecha_desde'];
            }

            if (!empty($filtros['fecha_hasta'])) {
                $query .= " AND DATE(s.inicio_sesion) <= ?";
                $params[] = $filtros['fecha_hasta'];
            }

            if (!empty($filtros['ip'])) {
                $query .= " AND s.ip_address LIKE ?";
                $params[] = '%' . $filtros['ip'] . '%';
            }

            $query .= " ORDER BY s.inicio_sesion DESC LIMIT ? OFFSET ?";
            $params[] = $limite;
            $params[] = $offset;

            return $this->db->select($query, $params);
        } catch (Exception $e) {
            Logger::error("Error al obtener sesiones con filtros: " . $e->getMessage());
            return [];
        }
    }
}
