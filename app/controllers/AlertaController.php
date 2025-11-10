<?php

class AlertaController extends Controller
{
    private $alertaModel;

    public function __construct()
    {
        parent::__construct(); // Llamar al constructor padre
        $this->alertaModel = new Alerta();
    }

    public function index()
    {
        try {
            $alertas = $this->alertaModel->getAlertasPendientes();
            $this->view('alertas/index', [
                'alertas' => $alertas,
                'title' => 'Alertas del Sistema'
            ]);
        } catch (Exception $e) {
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    public function verificarStock()
    {
        try {
            $cantidadProductos = $this->alertaModel->verificarTodasLasAlertas();
            echo json_encode([
                'success' => true,
                'mensaje' => "Se verificaron todas las condiciones. Se generaron alertas para $cantidadProductos situaciones."
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error al verificar alertas: ' . $e->getMessage()
            ]);
        }
    }

    public function marcarResuelta()
    {
        try {
            $id = $_POST['id_alerta'] ?? null;

            if (!$id) {
                throw new Exception('ID de alerta requerido');
            }

            $this->alertaModel->marcarResuelta($id);

            echo json_encode([
                'success' => true,
                'mensaje' => 'Alerta marcada como resuelta'
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function contarPendientes()
    {
        try {
            $total = $this->alertaModel->contarPendientes();
            echo json_encode([
                'success' => true,
                'total' => $total
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
