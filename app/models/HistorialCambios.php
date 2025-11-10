<?php

class HistorialCambios extends Model
{
    protected $table = 'historialcambios';
    protected $primaryKey = 'id_cambio';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Registra un cambio en el historial de auditoría
     */
    public function registrarCambio($idUsuario, $tablaAfectada, $registroId, $campoModificado, $valorAnterior, $valorNuevo)
    {
        try {
            // Validar parámetros requeridos
            if (empty($idUsuario) || empty($tablaAfectada) || empty($campoModificado)) {
                throw new Exception("Parámetros requeridos faltantes para registrar cambio");
            }

            $sql = "INSERT INTO historialcambios (id_usuario, tabla_afectada, registro_id, campo_modificado, valor_anterior, valor_nuevo, fecha) 
                    VALUES (?, ?, ?, ?, ?, ?, NOW())";

            $params = [
                $idUsuario,
                $tablaAfectada,
                $registroId,
                $campoModificado,
                $valorAnterior,
                $valorNuevo
            ];

            $result = $this->db->execute($sql, $params);

            if ($result) {
                Logger::info("Cambio registrado en auditoría", [
                    'tabla' => $tablaAfectada,
                    'registro_id' => $registroId,
                    'campo' => $campoModificado,
                    'usuario_id' => $idUsuario
                ]);
            }

            return $result;
        } catch (Exception $e) {
            Logger::error("Error registrando cambio en auditoría: " . $e->getMessage(), [
                'tabla' => $tablaAfectada ?? 'desconocida',
                'campo' => $campoModificado ?? 'desconocido',
                'usuario_id' => $idUsuario ?? 'desconocido'
            ]);
            return false;
        }
    }

    /**
     * Obtiene el historial de cambios con filtros
     */
    public function getHistorialConFiltros($filtros = [])
    {
        try {
            $whereConditions = [];
            $params = [];

            if (!empty($filtros['tabla'])) {
                $whereConditions[] = "hc.tabla_afectada = ?";
                $params[] = $filtros['tabla'];
            }

            if (!empty($filtros['usuario']) && is_numeric($filtros['usuario'])) {
                $whereConditions[] = "hc.id_usuario = ?";
                $params[] = (int)$filtros['usuario'];
            }

            if (!empty($filtros['fecha_desde'])) {
                // Validar formato de fecha
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_desde'])) {
                    $whereConditions[] = "DATE(hc.fecha) >= ?";
                    $params[] = $filtros['fecha_desde'];
                }
            }

            if (!empty($filtros['fecha_hasta'])) {
                // Validar formato de fecha
                if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $filtros['fecha_hasta'])) {
                    $whereConditions[] = "DATE(hc.fecha) <= ?";
                    $params[] = $filtros['fecha_hasta'];
                }
            }

            $whereClause = empty($whereConditions) ? '' : 'WHERE ' . implode(' AND ', $whereConditions);

            $sql = "SELECT hc.*, u.nombre, u.apellido, u.email 
                    FROM historialcambios hc 
                    LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario 
                    {$whereClause}
                    ORDER BY hc.fecha DESC 
                    LIMIT 1000";

            return $this->db->select($sql, $params);
        } catch (Exception $e) {
            Logger::error("Error obteniendo historial con filtros: " . $e->getMessage(), [
                'filtros' => $filtros
            ]);
            return [];
        }
    }

    /**
     * Obtiene estadísticas de cambios por tabla
     */
    public function getEstadisticasPorTabla($diasAtras = 30)
    {
        try {
            // Validar parámetro
            $diasAtras = is_numeric($diasAtras) && $diasAtras > 0 ? min((int)$diasAtras, 365) : 30;

            $sql = "SELECT tabla_afectada, COUNT(*) as total_cambios,
                           COUNT(DISTINCT id_usuario) as usuarios_activos,
                           MAX(fecha) as ultimo_cambio
                    FROM historialcambios 
                    WHERE fecha >= DATE_SUB(NOW(), INTERVAL ? DAY)
                    GROUP BY tabla_afectada 
                    ORDER BY total_cambios DESC";

            return $this->db->select($sql, [$diasAtras]);
        } catch (Exception $e) {
            Logger::error("Error obteniendo estadísticas por tabla: " . $e->getMessage(), [
                'dias_atras' => $diasAtras
            ]);
            return [];
        }
    }

    /**
     * Obtiene los cambios más recientes
     */
    public function getCambiosRecientes($limite = 50)
    {
        try {
            // Validar límite
            $limite = is_numeric($limite) && $limite > 0 ? min((int)$limite, 1000) : 50;

            $sql = "SELECT hc.*, u.nombre, u.apellido 
                    FROM historialcambios hc 
                    LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario 
                    ORDER BY hc.fecha DESC 
                    LIMIT ?";

            return $this->db->select($sql, [$limite]);
        } catch (Exception $e) {
            Logger::error("Error obteniendo cambios recientes: " . $e->getMessage(), [
                'limite' => $limite
            ]);
            return [];
        }
    }

    /**
     * Obtiene el historial de un registro específico
     */
    public function getHistorialRegistro($tabla, $registroId)
    {
        try {
            // Validar parámetros
            if (empty($tabla) || !is_numeric($registroId)) {
                throw new Exception("Parámetros inválidos para obtener historial del registro");
            }

            $sql = "SELECT hc.*, u.nombre, u.apellido 
                    FROM historialcambios hc 
                    LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario 
                    WHERE hc.tabla_afectada = ? AND hc.registro_id = ? 
                    ORDER BY hc.fecha DESC";

            return $this->db->select($sql, [$tabla, (int)$registroId]);
        } catch (Exception $e) {
            Logger::error("Error obteniendo historial del registro: " . $e->getMessage(), [
                'tabla' => $tabla ?? 'desconocida',
                'registro_id' => $registroId ?? 'desconocido'
            ]);
            return [];
        }
    }

    /**
     * Limpia registros antiguos (mantiene solo los últimos X días)
     */
    public function limpiarRegistrosAntiguos($diasMantenimiento = 365)
    {
        try {
            $sql = "DELETE FROM historialcambios 
                    WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";

            $stmt = $this->db->getConnection()->prepare($sql);
            $result = $stmt->execute([$diasMantenimiento]);

            if ($result) {
                $eliminados = $stmt->rowCount();
                Logger::info("Limpieza de auditoría completada", ['registros_eliminados' => $eliminados]);
                return $eliminados;
            }

            return 0;
        } catch (Exception $e) {
            Logger::error("Error en limpieza de auditoría: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene las tablas más auditadas
     */
    public function getTablasActividad()
    {
        try {
            $sql = "SELECT tabla_afectada, 
                           COUNT(*) as total_cambios,
                           COUNT(DISTINCT DATE(fecha)) as dias_activos,
                           MIN(fecha) as primer_cambio,
                           MAX(fecha) as ultimo_cambio
                    FROM historialcambios 
                    GROUP BY tabla_afectada 
                    ORDER BY total_cambios DESC";

            return $this->db->select($sql);
        } catch (Exception $e) {
            Logger::error("Error obteniendo actividad de tablas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Busca cambios por contenido
     */
    public function buscarCambios($busqueda)
    {
        try {
            // Sanitizar la búsqueda
            $busqueda = trim($busqueda);
            if (empty($busqueda) || strlen($busqueda) < 2) {
                return [];
            }

            // Escapar caracteres especiales para LIKE
            $termino = '%' . str_replace(['%', '_'], ['\%', '\_'], $busqueda) . '%';

            $sql = "SELECT hc.*, u.nombre, u.apellido 
                    FROM historialcambios hc 
                    LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario 
                    WHERE hc.campo_modificado LIKE ? 
                       OR hc.valor_anterior LIKE ? 
                       OR hc.valor_nuevo LIKE ?
                       OR hc.tabla_afectada LIKE ?
                    ORDER BY hc.fecha DESC 
                    LIMIT 200";

            return $this->db->select($sql, [$termino, $termino, $termino, $termino]);
        } catch (Exception $e) {
            Logger::error("Error en búsqueda de cambios: " . $e->getMessage(), [
                'busqueda' => $busqueda ?? 'vacia'
            ]);
            return [];
        }
    }

    /**
     * Obtiene conteo total de registros
     */
    public function getConteoTotal()
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM historialcambios";
            $result = $this->db->selectOne($sql);
            return $result ? (int)$result['total'] : 0;
        } catch (Exception $e) {
            Logger::error("Error obteniendo conteo total: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtiene cambios por usuario específico
     */
    public function getCambiosPorUsuario($idUsuario, $limite = 100)
    {
        try {
            if (!is_numeric($idUsuario) || $idUsuario <= 0) {
                throw new Exception("ID de usuario inválido");
            }

            $limite = is_numeric($limite) && $limite > 0 ? min((int)$limite, 1000) : 100;

            $sql = "SELECT hc.*, u.nombre, u.apellido 
                    FROM historialcambios hc 
                    LEFT JOIN usuarios u ON hc.id_usuario = u.id_usuario 
                    WHERE hc.id_usuario = ? 
                    ORDER BY hc.fecha DESC 
                    LIMIT ?";

            return $this->db->select($sql, [(int)$idUsuario, $limite]);
        } catch (Exception $e) {
            Logger::error("Error obteniendo cambios por usuario: " . $e->getMessage(), [
                'usuario_id' => $idUsuario ?? 'desconocido'
            ]);
            return [];
        }
    }

    /**
     * Verifica si una tabla está siendo auditada
     */
    public function estaAuditada($tabla)
    {
        try {
            if (empty($tabla)) {
                return false;
            }

            $sql = "SELECT COUNT(*) as total FROM historialcambios WHERE tabla_afectada = ? LIMIT 1";
            $result = $this->db->selectOne($sql, [$tabla]);
            return $result && (int)$result['total'] > 0;
        } catch (Exception $e) {
            Logger::error("Error verificando si tabla está auditada: " . $e->getMessage(), [
                'tabla' => $tabla ?? 'desconocida'
            ]);
            return false;
        }
    }
}
