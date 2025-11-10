<?php

/**
 * NotificacionController - Gestión de correos para notificaciones de stock
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class NotificacionController extends Controller
{
    private $notificacionModel;

    public function __construct()
    {
        parent::__construct();
        require_once APP_PATH . '/models/NotificacionCorreo.php';
        $this->notificacionModel = new NotificacionCorreo();
    }

    /**
     * Muestra la página de gestión de correos de notificación
     */
    public function index()
    {
        $this->requireRole(['administrador']);

        $emails = $this->notificacionModel->getAll();

        $this->view('notificaciones/index', [
            'title' => 'Notificaciones por Correo',
            'emails' => $emails
        ]);
    }

    /**
     * Agrega un nuevo correo para notificaciones
     */
    public function add()
    {
        $this->requireRole(['administrador']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $email = trim($_POST['email'] ?? '');

            if (empty($email)) {
                $this->setFlash('error', 'El correo electrónico es requerido');
                $this->redirect('?page=notificaciones');
                return;
            }

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->setFlash('error', 'El correo electrónico no es válido');
                $this->redirect('?page=notificaciones');
                return;
            }

            if ($this->notificacionModel->exists($email)) {
                $this->setFlash('warning', 'Este correo ya está registrado para notificaciones');
                $this->redirect('?page=notificaciones');
                return;
            }

            try {
                if ($this->notificacionModel->add($email)) {
                    $this->setFlash('success', 'Correo agregado exitosamente para recibir notificaciones');
                } else {
                    $this->setFlash('error', 'Error al agregar el correo electrónico');
                }
            } catch (Exception $e) {
                $this->setFlash('error', 'Error: ' . $e->getMessage());
            }
        }

        $this->redirect('?page=notificaciones');
    }

    /**
     * Elimina un correo de las notificaciones
     */
    public function delete()
    {
        $this->requireRole(['administrador']);

        $id = intval($_POST['id'] ?? 0);

        if ($id <= 0) {
            $this->setFlash('error', 'ID de correo inválido');
            $this->redirect('?page=notificaciones');
            return;
        }

        try {
            if ($this->notificacionModel->delete($id)) {
                $this->setFlash('success', 'Correo eliminado de las notificaciones');
            } else {
                $this->setFlash('error', 'Error al eliminar el correo');
            }
        } catch (Exception $e) {
            $this->setFlash('error', 'Error: ' . $e->getMessage());
        }

        $this->redirect('?page=notificaciones');
    }
}
