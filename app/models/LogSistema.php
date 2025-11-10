<?php
require_once __DIR__ . '/../core/Model.php';

class LogSistema extends Model
{
    protected $table = 'logssistema';
    protected $primaryKey = 'id_log';

    public function registrarAccion($idUsuario, $accion, $descripcion = null, $ipAddress = null)
    {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? null;
        }

        $sql = "INSERT INTO logssistema (id_usuario, accion, descripcion, fecha, ip_address) 
                VALUES (?, ?, ?, NOW(), ?)";

        return $this->db->execute($sql, [$idUsuario, $accion, $descripcion, $ipAddress]);
    }

    public function obtenerLogs($limite = 100, $offset = 0, $filtros = [])
    {
        $where = [];
        $params = [];

        // Filtros opcionales
        if (!empty($filtros['usuario'])) {
            $where[] = "u.nombre LIKE ?";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['accion'])) {
            $where[] = "l.accion LIKE ?";
            $params[] = "%{$filtros['accion']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(l.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(l.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT l.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM logssistema l
                LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario
                {$whereClause}
                ORDER BY l.fecha DESC
                LIMIT ? OFFSET ?";

        $params[] = $limite;
        $params[] = $offset;

        return $this->db->select($sql, $params);
    }

    public function contarLogs($filtros = [])
    {
        $where = [];
        $params = [];

        // Aplicar los mismos filtros que en obtenerLogs
        if (!empty($filtros['usuario'])) {
            $where[] = "u.nombre LIKE ?";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['accion'])) {
            $where[] = "l.accion LIKE ?";
            $params[] = "%{$filtros['accion']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(l.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(l.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT COUNT(*) as total
                FROM logssistema l
                LEFT JOIN usuarios u ON l.id_usuario = u.id_usuario
                {$whereClause}";

        $result = $this->db->selectOne($sql, $params);
        return $result['total'];
    }

    public function limpiarLogsAntiguos($diasAntiguedad = 90)
    {
        $sql = "DELETE FROM logssistema WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$diasAntiguedad]);
    }
}
