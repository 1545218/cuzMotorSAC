<?php
require_once __DIR__ . '/../core/Model.php';

class SesionUsuario extends Model
{
    protected $table = 'sesionusuarios';
    protected $primaryKey = 'id_sesion';

    /**
     * Registra el inicio de una sesión
     */
    public function iniciarSesion($idUsuario, $ipAddress = null)
    {
        if (!$ipAddress) {
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        }

        $sql = "INSERT INTO sesionusuarios (id_usuario, ip_address, inicio_sesion, activo) 
                VALUES (?, ?, NOW(), 1)";
        $result = $this->db->execute($sql, [$idUsuario, $ipAddress]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Finaliza una sesión
     */
    public function finalizarSesion($idSesion)
    {
        $sql = "UPDATE sesionusuarios SET fin_sesion = NOW(), activo = 0 WHERE id_sesion = ?";
        return $this->db->execute($sql, [$idSesion]);
    }

    /**
     * Finaliza todas las sesiones activas de un usuario
     */
    public function finalizarSesionesUsuario($idUsuario)
    {
        $sql = "UPDATE sesionusuarios SET fin_sesion = NOW(), activo = 0 
                WHERE id_usuario = ? AND activo = 1";
        return $this->db->execute($sql, [$idUsuario]);
    }

    /**
     * Obtiene sesiones activas
     */
    public function getSesionesActivas()
    {
        $sql = "SELECT s.*, u.nombre, u.apellido, u.usuario 
                FROM sesionusuarios s
                INNER JOIN usuarios u ON s.id_usuario = u.id_usuario
                WHERE s.activo = 1
                ORDER BY s.inicio_sesion DESC";

        return $this->db->fetch($sql);
    }

    /**
     * Obtiene historial de sesiones de un usuario
     */
    public function getHistorialUsuario($idUsuario, $limite = 50)
    {
        $sql = "SELECT * FROM sesionusuarios 
                WHERE id_usuario = ? 
                ORDER BY inicio_sesion DESC 
                LIMIT ?";

        return $this->db->fetch($sql, [$idUsuario, $limite]);
    }

    /**
     * Cuenta sesiones activas
     */
    public function contarActivas()
    {
        $sql = "SELECT COUNT(*) as total FROM sesionusuarios WHERE activo = 1";
        $result = $this->db->fetch($sql);
        return $result[0]['total'] ?? 0;
    }

    /**
     * Obtiene estadísticas de sesiones
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_sesiones,
                    COUNT(DISTINCT id_usuario) as usuarios_unicos,
                    SUM(CASE WHEN activo = 1 THEN 1 ELSE 0 END) as sesiones_activas,
                    SUM(CASE WHEN DATE(inicio_sesion) = CURDATE() THEN 1 ELSE 0 END) as sesiones_hoy,
                    AVG(CASE 
                        WHEN fin_sesion IS NOT NULL 
                        THEN TIMESTAMPDIFF(MINUTE, inicio_sesion, fin_sesion) 
                        ELSE NULL 
                    END) as duracion_promedio_minutos
                FROM sesionusuarios";

        $result = $this->db->fetch($sql);
        return $result[0] ?? [];
    }

    /**
     * Limpia sesiones antiguas
     */
    public function limpiarSesionesAntiguas($diasAntiguedad = 30)
    {
        $sql = "DELETE FROM sesionusuarios 
                WHERE inicio_sesion < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$diasAntiguedad]);
    }

    /**
     * Verifica si una sesión está activa
     */
    public function estaActiva($idSesion)
    {
        $sql = "SELECT activo FROM sesionusuarios WHERE id_sesion = ?";
        $result = $this->db->fetch($sql, [$idSesion]);
        return ($result[0]['activo'] ?? 0) == 1;
    }
}
