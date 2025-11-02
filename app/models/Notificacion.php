<?php
require_once __DIR__ . '/../core/Model.php';

class Notificacion extends Model
{
    protected $table = 'notificaciones';
    protected $primaryKey = 'id_notificacion';

    /**
     * Crea una nueva notificación
     */
    public function crear($titulo, $mensaje, $tipo = 'web', $idAlerta = null)
    {
        $sql = "INSERT INTO notificaciones (id_alerta, titulo, mensaje, tipo, fecha) VALUES (?, ?, ?, ?, NOW())";
        $result = $this->db->execute($sql, [$idAlerta, $titulo, $mensaje, $tipo]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Asigna una notificación a usuarios específicos
     */
    public function asignarAUsuarios($idNotificacion, $usuarios)
    {
        if (!is_array($usuarios)) {
            $usuarios = [$usuarios];
        }

        foreach ($usuarios as $idUsuario) {
            $sql = "INSERT INTO notificacionesusuarios (id_notificacion, id_usuario, leida, fecha_leida) VALUES (?, ?, 0, NULL)";
            $this->db->execute($sql, [$idNotificacion, $idUsuario]);
        }
        return true;
    }

    /**
     * Asigna una notificación a todos los usuarios activos
     */
    public function asignarATodosUsuarios($idNotificacion)
    {
        $sql = "SELECT id_usuario FROM usuarios WHERE estado = 'activo'";
        $usuarios = $this->db->fetch($sql);

        $idsUsuarios = array_column($usuarios, 'id_usuario');
        return $this->asignarAUsuarios($idNotificacion, $idsUsuarios);
    }

    /**
     * Obtiene notificaciones de un usuario
     */
    public function getNotificacionesUsuario($idUsuario, $soloNoLeidas = false)
    {
        $whereLeida = $soloNoLeidas ? "AND nu.leida = 0" : "";

        $sql = "SELECT n.*, nu.leida, nu.fecha_leida 
                FROM notificaciones n
                INNER JOIN notificacionesusuarios nu ON n.id_notificacion = nu.id_notificacion
                WHERE nu.id_usuario = ? {$whereLeida}
                ORDER BY n.fecha DESC";

        return $this->db->fetch($sql, [$idUsuario]);
    }

    /**
     * Marca una notificación como leída
     */
    public function marcarComoLeida($idNotificacion, $idUsuario)
    {
        $sql = "UPDATE notificacionesusuarios SET leida = 1, fecha_leida = NOW() 
                WHERE id_notificacion = ? AND id_usuario = ?";
        return $this->db->execute($sql, [$idNotificacion, $idUsuario]);
    }

    /**
     * Cuenta notificaciones no leídas de un usuario
     */
    public function contarNoLeidas($idUsuario)
    {
        $sql = "SELECT COUNT(*) as total FROM notificacionesusuarios 
                WHERE id_usuario = ? AND leida = 0";
        $result = $this->db->fetch($sql, [$idUsuario]);
        return $result[0]['total'] ?? 0;
    }

    /**
     * Crea notificación desde alerta
     */
    public function crearDesdeAlerta($alerta)
    {
        $titulo = "Alerta: " . ucfirst($alerta['tipo']);
        $idNotificacion = $this->crear($titulo, $alerta['mensaje'], 'web', $alerta['id_alerta']);

        if ($idNotificacion) {
            // Asignar a todos los administradores
            $sql = "SELECT u.id_usuario FROM usuarios u 
                    INNER JOIN roles_usuarios r ON u.id_rol = r.id_rol 
                    WHERE r.nombre = 'Administrador' AND u.estado = 'activo'";
            $admins = $this->db->fetch($sql);

            if (!empty($admins)) {
                $idsAdmins = array_column($admins, 'id_usuario');
                $this->asignarAUsuarios($idNotificacion, $idsAdmins);
            }
        }

        return $idNotificacion;
    }

    /**
     * Elimina notificaciones antiguas
     */
    public function limpiarAntiguos($diasAntiguedad = 30)
    {
        $sql = "DELETE n, nu FROM notificaciones n 
                LEFT JOIN notificacionesusuarios nu ON n.id_notificacion = nu.id_notificacion
                WHERE n.fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
        return $this->db->execute($sql, [$diasAntiguedad]);
    }
}
