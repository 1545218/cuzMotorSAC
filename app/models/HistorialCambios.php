<?php
require_once __DIR__ . '/../core/Model.php';

class HistorialCambios extends Model
{
    protected $table = 'historialcambios';
    protected $primaryKey = 'id_cambio';

    /**
     * Registra un cambio en el sistema
     */
    public function registrarCambio($idUsuario, $tablaAfectada, $registroId, $campoModificado, $valorAnterior, $valorNuevo)
    {
        $sql = "INSERT INTO historialcambios (id_usuario, tabla_afectada, registro_id, campo_modificado, valor_anterior, valor_nuevo, fecha) 
                VALUES (?, ?, ?, ?, ?, ?, NOW())";

        return $this->db->execute($sql, [
            $idUsuario,
            $tablaAfectada,
            $registroId,
            $campoModificado,
            $valorAnterior,
            $valorNuevo
        ]);
    }

    /**
     * Obtiene historial de cambios con filtros
     */
    public function getHistorial($filtros = [], $limite = 100, $offset = 0)
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tabla'])) {
            $where[] = "h.tabla_afectada = ?";
            $params[] = $filtros['tabla'];
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
            $params[] = "%{$filtros['usuario']}%";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['registro_id'])) {
            $where[] = "h.registro_id = ?";
            $params[] = $filtros['registro_id'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(h.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(h.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT h.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM historialcambios h
                LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                {$whereClause}
                ORDER BY h.fecha DESC
                LIMIT ? OFFSET ?";

        $params[] = $limite;
        $params[] = $offset;

        return $this->db->fetch($sql, $params);
    }

    /**
     * Obtiene historial de un registro específico
     */
    public function getHistorialRegistro($tabla, $registroId)
    {
        $sql = "SELECT h.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM historialcambios h
                LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                WHERE h.tabla_afectada = ? AND h.registro_id = ?
                ORDER BY h.fecha DESC";

        return $this->db->fetch($sql, [$tabla, $registroId]);
    }

    /**
     * Cuenta registros de historial
     */
    public function contarCambios($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tabla'])) {
            $where[] = "h.tabla_afectada = ?";
            $params[] = $filtros['tabla'];
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
            $params[] = "%{$filtros['usuario']}%";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['registro_id'])) {
            $where[] = "h.registro_id = ?";
            $params[] = $filtros['registro_id'];
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(h.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(h.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total
                FROM historialcambios h
                LEFT JOIN usuarios u ON h.id_usuario = u.id_usuario
                {$whereClause}";

        $result = $this->db->fetch($sql, $params);
        return $result[0]['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de cambios
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_cambios,
                    COUNT(DISTINCT tabla_afectada) as tablas_modificadas,
                    COUNT(DISTINCT id_usuario) as usuarios_activos,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as cambios_hoy,
                    SUM(CASE WHEN DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as cambios_semana
                FROM historialcambios";

        $result = $this->db->fetch($sql);
        return $result[0] ?? [];
    }

    /**
     * Limpia historial antiguo
     */
    public function limpiarAntiguos($diasAntiguedad = 180)
    {
        $sql = "DELETE FROM historialcambios WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$diasAntiguedad]);
    }

    /**
     * Registra cambios automáticamente comparando arrays
     */
    public function registrarCambiosArray($idUsuario, $tabla, $registroId, $valoresAnteriores, $valoresNuevos)
    {
        $cambiosRegistrados = 0;

        foreach ($valoresNuevos as $campo => $valorNuevo) {
            $valorAnterior = $valoresAnteriores[$campo] ?? null;

            // Solo registrar si hay cambio
            if ($valorAnterior !== $valorNuevo) {
                if ($this->registrarCambio($idUsuario, $tabla, $registroId, $campo, $valorAnterior, $valorNuevo)) {
                    $cambiosRegistrados++;
                }
            }
        }

        return $cambiosRegistrados;
    }
}
