<?php
// Modelo para gestionar correos de notificación de bajo stock
require_once __DIR__ . '/../core/Database.php';

class NotificacionCorreo
{
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance();
    }

    public function getAll()
    {
        $stmt = $this->db->getConnection()->prepare("SELECT id, email FROM notificacion_correos ORDER BY id ASC");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function add($email)
    {
        $stmt = $this->db->getConnection()->prepare("INSERT INTO notificacion_correos (email) VALUES (:email)");
        return $stmt->execute(['email' => $email]);
    }

    public function delete($id)
    {
        $stmt = $this->db->getConnection()->prepare("DELETE FROM notificacion_correos WHERE id = :id");
        return $stmt->execute(['id' => $id]);
    }

    public function exists($email)
    {
        $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM notificacion_correos WHERE email = :email");
        $stmt->execute(['email' => $email]);
        return $stmt->fetchColumn() > 0;
    }
}
