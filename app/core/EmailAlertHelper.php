<?php
// app/core/EmailAlertHelper.php
require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../models/Usuario.php';

class EmailAlertHelper
{
    /**
     * Envía alertas del sistema por correo a los administradores
     * @param array $alerts
     */
    public static function sendSystemAlerts($alerts)
    {
        if (empty($alerts)) return;
        $usuarioModel = new Usuario();
        // Buscar todos los administradores activos (id_rol = 1)
        $admins = $usuarioModel->where('id_rol = ? AND estado = ?', [1, 'activo']);
        if (!$admins) return;
        $subject = 'Alertas del Sistema - CruzMotorSAC';
        $body = "<h3>Alertas del Sistema</h3><ul>";
        foreach ($alerts as $alert) {
            $body .= "<li><strong>" . htmlspecialchars($alert['title']) . ":</strong> " . htmlspecialchars($alert['message']) . "</li>";
        }
        $body .= "</ul>";
        foreach ($admins as $admin) {
            if (!empty($admin['email'])) {
                Mailer::send($admin['email'], $subject, $body);
            }
        }
    }
}
