<?php

/**
 * Controlador AjusteController - Gestión de ajustes de inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Solo para administradores
 */

class AjusteController extends Controller
{
    private $ajusteModel;
    private $productoModel;

    public function __construct()
    {
        // Solo permitir acceso a administradores
        Auth::requireRole(['administrador']);

        $this->ajusteModel = new AjusteInventario();
        $this->productoModel = new Producto();
    }

    /**
     * Mostrar listado de ajustes
     */
    public function index()
    {
        try {
            $ajustes = $this->ajusteModel->getAllWithProductos();

            renderView('ajustes/index', [
                'title' => 'Ajustes de Inventario',
                'ajustes' => $ajustes
            ]);
        } catch (Exception $e) {
            Logger::error('Error en AjusteController::index', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al cargar ajustes';
            header('Location: ?page=dashboard');
            exit;
        }
    }

    /**
     * Mostrar formulario para nuevo ajuste
     */
    public function create()
    {
        try {
            $productos = $this->productoModel->getActive();

            renderView('ajustes/create', [
                'title' => 'Nuevo Ajuste de Inventario',
                'productos' => $productos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en AjusteController::create', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al cargar formulario';
            header('Location: ?page=ajustes');
            exit;
        }
    }

    /**
     * Procesar creación de ajuste
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=ajustes');
                exit;
            }

            $data = [
                'id_producto' => $_POST['id_producto'] ?? '',
                'tipo' => $_POST['tipo'] ?? '',
                'cantidad' => $_POST['cantidad'] ?? '',
                'motivo' => $_POST['motivo'] ?? ''
            ];

            $idAjuste = $this->ajusteModel->crearAjuste($data);

            $_SESSION['success'] = 'Ajuste de inventario registrado exitosamente';
            header('Location: ?page=ajustes&action=view&id=' . $idAjuste);
            exit;
        } catch (Exception $e) {
            Logger::error('Error en AjusteController::store', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);

            $_SESSION['error'] = $e->getMessage();
            header('Location: ?page=ajustes&action=create');
            exit;
        }
    }

    /**
     * Mostrar detalles de un ajuste
     */
    public function show($id)
    {
        try {
            $ajuste = $this->ajusteModel->find($id);

            if (!$ajuste) {
                $_SESSION['error'] = 'Ajuste no encontrado';
                header('Location: ?page=ajustes');
                exit;
            }

            renderView('ajustes/view', [
                'title' => 'Detalle del Ajuste',
                'ajuste' => $ajuste
            ]);
        } catch (Exception $e) {
            Logger::error('Error en AjusteController::show', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);

            $_SESSION['error'] = 'Error al cargar ajuste';
            header('Location: ?page=ajustes');
            exit;
        }
    }

    /**
     * API: Obtener productos con stock actual
     */
    public function apiProductos()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                http_response_code(405);
                echo json_encode(['error' => 'Método no permitido']);
                exit;
            }

            $db = Database::getInstance();
            $productos = $db->select("
                SELECT id_producto, nombre, codigo_barras, stock_actual, stock_minimo
                FROM productos 
                WHERE estado = 'activo'
                ORDER BY nombre ASC
            ");

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'productos' => $productos
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode([
                'success' => false,
                'error' => 'Error interno del servidor'
            ]);
        }
        exit;
    }

    /**
     * Obtener estadísticas de ajustes
     */
    public function estadisticas()
    {
        try {
            $stats = $this->ajusteModel->getEstadisticas();
            $productosMasAjustados = $this->ajusteModel->getProductosMasAjustados(10);

            renderView('ajustes/estadisticas', [
                'title' => 'Estadísticas de Ajustes',
                'estadisticas' => $stats,
                'productos_mas_ajustados' => $productosMasAjustados
            ]);
        } catch (Exception $e) {
            Logger::error('Error en AjusteController::estadisticas', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al cargar estadísticas';
            header('Location: ?page=ajustes');
            exit;
        }
    }
}
