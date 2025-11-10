<?php

/**
 * Modelo NotificacionUsuario - Notificaciones específicas por usuario
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla notificacionesusuarios existente
 */

class NotificacionUsuario extends Model
{
    protected $table = 'notificacionesusuarios';
    protected $primaryKey = 'id_notificacion_usuario';
    protected $fillable = [
        'id_notificacion',
        'id_usuario',
        'leida',
        'fecha_lectura'
    ];

    /**
     * Obtener notificaciones para un usuario
     */
    public function getByUsuario($idUsuario, $soloNoLeidas = false)
    {
        $whereClause = "nu.id_usuario = ?";
        $params = [$idUsuario];

        if ($soloNoLeidas) {
            $whereClause .= " AND nu.leida = 0";
        }

        return $this->db->select("
            SELECT nu.*, n.titulo, n.mensaje, n.tipo, n.fecha_creacion
            FROM notificacionesusuarios nu
            JOIN notificaciones n ON nu.id_notificacion = n.id_notificacion
            WHERE {$whereClause}
            ORDER BY n.fecha_creacion DESC
        ", $params);
    }

    /**
     * Marcar notificación como leída
     */
    public function marcarComoLeida($idNotificacionUsuario)
    {
        return $this->update($idNotificacionUsuario, [
            'leida' => 1,
            'fecha_lectura' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Obtener conteo de notificaciones no leídas
     */
    public function getNoLeidasCount($idUsuario)
    {
        $result = $this->db->select(
            "SELECT COUNT(*) as count FROM notificacionesusuarios WHERE id_usuario = ? AND leida = 0",
            [$idUsuario]
        );
        return $result[0]['count'] ?? 0;
    }

    /**
     * Enviar notificación a usuario
     */
    public function enviarNotificacion($idNotificacion, $idUsuario)
    {
        return $this->create([
            'id_notificacion' => $idNotificacion,
            'id_usuario' => $idUsuario,
            'leida' => 0,
            'fecha_lectura' => null
        ]);
    }
}
