<?php

/**
 * Controlador OrdenTrabajo - Gestión de órdenes de trabajo
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN DÍA 1 TARDE - Funciones básicas pero completas
 */

class OrdenTrabajoController extends Controller
{
    private $ordenModel;
    private $clienteModel;
    private $vehiculoModel;

    public function __construct()
    {
        parent::__construct();
        $this->ordenModel = new OrdenTrabajo();
        $this->clienteModel = new Cliente();
        $this->vehiculoModel = new Vehiculo();
    }

    /**
     * Mostrar listado de órdenes de trabajo
     */
    public function index()
    {
        try {
            $ordenes = $this->ordenModel->getAllWithDetails();
            $estadisticas = $this->ordenModel->getEstadisticas();

            $this->view('ordenes/index', [
                'title' => 'Órdenes de Trabajo',
                'ordenes' => $ordenes,
                'estadisticas' => $estadisticas
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar órdenes de trabajo']);
        }
    }

    /**
     * Mostrar formulario para crear orden
     */
    public function create()
    {
        try {
            // Inicializar arrays vacíos por defecto
            $clientes = [];
            $vehiculos = [];

            // Cargar clientes
            try {
                $clientes = $this->clienteModel->getActive();
            } catch (Exception $e) {
                Logger::error('Error cargando clientes: ' . $e->getMessage());
                $_SESSION['error'] = 'Error al cargar clientes: ' . $e->getMessage();
            }

            // Mostrar vista
            $this->view('ordenes/create', [
                'title' => 'Nueva Orden de Trabajo',
                'clientes' => $clientes,
                'vehiculos' => $vehiculos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::create: ' . $e->getMessage());

            // Mostrar vista con datos vacíos
            $this->view('ordenes/create', [
                'title' => 'Nueva Orden de Trabajo',
                'clientes' => [],
                'vehiculos' => [],
                'error' => 'Error interno: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Procesar creación de orden
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=ordenes');
                exit;
            }

            $data = [
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'id_vehiculo' => $_POST['id_vehiculo'] ?? null,
                'estado' => $_POST['estado'] ?? OrdenTrabajo::ESTADO_ABIERTA,
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            // Validaciones básicas
            if (empty($data['id_cliente'])) {
                $_SESSION['error'] = 'Cliente es obligatorio';
                header('Location: ?page=ordenes&action=create');
                exit;
            }

            // Crear orden
            $id_orden = $this->ordenModel->create($data);

            if ($id_orden) {
                $_SESSION['success'] = 'Orden de trabajo creada exitosamente';
                Logger::info('Orden de trabajo creada', [
                    'id' => $id_orden,
                    'cliente' => $data['id_cliente'],
                    'usuario' => $_SESSION['user_id'] ?? 'N/A'
                ]);
                header('Location: ?page=ordenes&action=show&id=' . $id_orden);
            } else {
                $_SESSION['error'] = 'Error al crear orden de trabajo';
                header('Location: ?page=ordenes&action=create');
            }
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::store', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);
            $_SESSION['error'] = 'Error interno del servidor: ' . $e->getMessage();
            header('Location: ?page=ordenes&action=create');
        }
        exit;
    }
    /**
     * Mostrar detalles de una orden
     */
    public function show($id)
    {
        try {
            $orden = $this->ordenModel->getByIdWithDetails($id);

            if (!$orden) {
                $_SESSION['error'] = 'Orden de trabajo no encontrada';
                header('Location: ?page=ordenes');
                exit;
            }

            $this->view('ordenes/view', [
                'title' => 'Orden de Trabajo #' . $orden['id_orden'],
                'orden' => $orden
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::show', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar orden']);
        }
    }

    /**
     * Mostrar formulario para editar orden
     */
    public function edit($id)
    {
        try {
            $orden = $this->ordenModel->getByIdWithDetails($id);

            if (!$orden) {
                $_SESSION['error'] = 'Orden de trabajo no encontrada';
                header('Location: ?page=ordenes');
                exit;
            }

            $clientes = $this->clienteModel->all('nombre ASC');
            $vehiculos = $this->vehiculoModel->getAllWithClientes();

            $this->view('ordenes/edit', [
                'title' => 'Editar Orden #' . $orden['id_orden'],
                'orden' => $orden,
                'clientes' => $clientes,
                'vehiculos' => $vehiculos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::edit', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar orden']);
        }
    }

    /**
     * Procesar actualización de orden
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=ordenes');
                exit;
            }

            $orden = $this->ordenModel->find($id);
            if (!$orden) {
                $_SESSION['error'] = 'Orden de trabajo no encontrada';
                header('Location: ?page=ordenes');
                exit;
            }

            $data = [
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'id_vehiculo' => $_POST['id_vehiculo'] ?? null,
                'estado' => $_POST['estado'] ?? OrdenTrabajo::ESTADO_ABIERTA,
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            // Validaciones básicas
            if (empty($data['id_cliente'])) {
                $_SESSION['error'] = 'Cliente es obligatorio';
                header('Location: ?page=ordenes&action=edit&id=' . $id);
                exit;
            }

            if ($this->ordenModel->update($id, $data)) {
                $_SESSION['success'] = 'Orden de trabajo actualizada exitosamente';
                Logger::info('Orden de trabajo actualizada', [
                    'id' => $id,
                    'usuario' => $_SESSION['user_id'] ?? 'N/A'
                ]);
                header('Location: ?page=ordenes&action=show&id=' . $id);
            } else {
                $_SESSION['error'] = 'Error al actualizar orden de trabajo';
                header('Location: ?page=ordenes&action=edit&id=' . $id);
            }
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::update', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data ?? []
            ]);
            $_SESSION['error'] = 'Error interno del servidor';
            header('Location: ?page=ordenes&action=edit&id=' . $id);
        }
        exit;
    }

    /**
     * Cambiar estado de orden (AJAX)
     */
    public function cambiarEstado($id)
    {
        try {
            header('Content-Type: application/json');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                echo json_encode(['success' => false, 'message' => 'Método no permitido']);
                exit;
            }

            $estado = $_POST['estado'] ?? '';
            $estados_validos = [
                OrdenTrabajo::ESTADO_ABIERTA,
                OrdenTrabajo::ESTADO_EN_PROCESO,
                OrdenTrabajo::ESTADO_CERRADA
            ];

            if (!in_array($estado, $estados_validos)) {
                echo json_encode(['success' => false, 'message' => 'Estado no válido']);
                exit;
            }

            if ($this->ordenModel->cambiarEstado($id, $estado)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Estado actualizado correctamente',
                    'estado' => $estado
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
            }
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::cambiarEstado', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
        exit;
    }

    /**
     * Eliminar orden de trabajo
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=ordenes');
                exit;
            }

            $orden = $this->ordenModel->find($id);
            if (!$orden) {
                $_SESSION['error'] = 'Orden de trabajo no encontrada';
                header('Location: ?page=ordenes');
                exit;
            }

            if ($this->ordenModel->delete($id)) {
                $_SESSION['success'] = 'Orden de trabajo eliminada exitosamente';
                Logger::info('Orden de trabajo eliminada', [
                    'id' => $id,
                    'usuario' => $_SESSION['user_id'] ?? 'N/A'
                ]);
            } else {
                $_SESSION['error'] = 'Error al eliminar orden de trabajo';
            }
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::delete', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            $_SESSION['error'] = 'Error interno del servidor';
        }

        header('Location: ?page=ordenes');
        exit;
    }

    /**
     * Obtener vehículos por cliente (AJAX)
     */
    public function vehiculosPorCliente($id_cliente)
    {
        try {
            // Limpiar cualquier salida previa (BOM, espacios, etc.)
            ob_clean();
            header('Content-Type: application/json');

            if (!$id_cliente || $id_cliente <= 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'ID de cliente inválido'
                ]);
                exit;
            }

            $vehiculos = $this->vehiculoModel->getByCliente($id_cliente);

            echo json_encode([
                'success' => true,
                'vehiculos' => $vehiculos
            ]);
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'message' => 'Error al cargar vehículos',
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Buscar órdenes (AJAX)
     */
    public function buscar()
    {
        try {
            header('Content-Type: application/json');

            $termino = $_GET['termino'] ?? '';

            if (empty($termino)) {
                $ordenes = $this->ordenModel->getAllWithDetails();
            } else {
                $ordenes = $this->ordenModel->buscar($termino);
            }

            echo json_encode([
                'success' => true,
                'ordenes' => $ordenes
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::buscar', [
                'error' => $e->getMessage()
            ]);
            echo json_encode(['success' => false, 'message' => 'Error en búsqueda']);
        }
        exit;
    }

    /**
     * Estadísticas básicas (AJAX)
     */
    public function estadisticas()
    {
        try {
            header('Content-Type: application/json');

            $estadisticas = $this->ordenModel->getEstadisticas();
            $recientes = $this->ordenModel->getRecientes(5);

            echo json_encode([
                'success' => true,
                'estadisticas' => $estadisticas,
                'recientes' => $recientes
            ]);
        } catch (Exception $e) {
            Logger::error('Error en OrdenTrabajoController::estadisticas', [
                'error' => $e->getMessage()
            ]);
            echo json_encode(['success' => false, 'message' => 'Error al cargar estadísticas']);
        }
        exit;
    }
}
