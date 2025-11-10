<?php

/**
 * Controlador Vehiculo - Gestión de vehículos de clientes
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - No modifica funcionalidad existente
 */

class VehiculoController extends Controller
{
    private $vehiculoModel;
    private $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->vehiculoModel = new Vehiculo();
        $this->clienteModel = new Cliente();
    }

    /**
     * Mostrar listado de vehículos
     */
    public function index()
    {
        try {
            $vehiculos = $this->vehiculoModel->getAllWithClientes();

            $this->view('vehiculos/index', [
                'title' => 'Gestión de Vehículos',
                'vehiculos' => $vehiculos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar vehículos']);
        }
    }

    /**
     * Mostrar formulario para crear vehículo
     */
    public function create()
    {
        try {
            $clientes = $this->clienteModel->all('nombre ASC');

            $this->view('vehiculos/create', [
                'title' => 'Registrar Vehículo',
                'clientes' => $clientes
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::create', [
                'error' => $e->getMessage()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar formulario']);
        }
    }

    /**
     * Procesar creación de vehículo
     */
    public function store()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=vehiculos');
                exit;
            }

            $data = [
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'placa' => strtoupper(trim($_POST['placa'] ?? '')),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? '')
            ];

            // Validaciones básicas
            if (empty($data['id_cliente']) || empty($data['placa'])) {
                $_SESSION['error'] = 'Cliente y placa son obligatorios';
                header('Location: ?page=vehiculos&action=create');
                exit;
            }

            // Verificar que no exista la placa
            if ($this->vehiculoModel->existePlaca($data['placa'])) {
                $_SESSION['error'] = 'Ya existe un vehículo con esa placa';
                header('Location: ?page=vehiculos&action=create');
                exit;
            }

            if ($this->vehiculoModel->create($data)) {
                $_SESSION['success'] = 'Vehículo registrado exitosamente';
                Logger::info('Vehículo creado', ['placa' => $data['placa'], 'usuario' => $_SESSION['user_id'] ?? 'N/A']);
            } else {
                $_SESSION['error'] = 'Error al registrar vehículo';
            }
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::store', [
                'error' => $e->getMessage(),
                'data' => $data ?? []
            ]);
            $_SESSION['error'] = 'Error interno del servidor';
        }

        header('Location: ?page=vehiculos');
        exit;
    }

    /**
     * Mostrar formulario para editar vehículo
     */
    public function edit($id)
    {
        try {
            $vehiculo = $this->vehiculoModel->getById($id);
            if (!$vehiculo) {
                $_SESSION['error'] = 'Vehículo no encontrado';
                header('Location: ?page=vehiculos');
                exit;
            }

            $clientes = $this->clienteModel->all('nombre ASC');

            $this->view('vehiculos/edit', [
                'title' => 'Editar Vehículo',
                'vehiculo' => $vehiculo,
                'clientes' => $clientes
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::edit', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar vehículo']);
        }
    }

    /**
     * Procesar actualización de vehículo
     */
    public function update($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=vehiculos');
                exit;
            }

            $vehiculo = $this->vehiculoModel->getById($id);
            if (!$vehiculo) {
                $_SESSION['error'] = 'Vehículo no encontrado';
                header('Location: ?page=vehiculos');
                exit;
            }

            $data = [
                'id_cliente' => $_POST['id_cliente'] ?? '',
                'placa' => strtoupper(trim($_POST['placa'] ?? '')),
                'marca' => trim($_POST['marca'] ?? ''),
                'modelo' => trim($_POST['modelo'] ?? '')
            ];

            // Validaciones básicas
            if (empty($data['id_cliente']) || empty($data['placa'])) {
                $_SESSION['error'] = 'Cliente y placa son obligatorios';
                header('Location: ?page=vehiculos&action=edit&id=' . $id);
                exit;
            }

            // Verificar que no exista la placa (excepto el actual)
            if ($this->vehiculoModel->existePlaca($data['placa'], $id)) {
                $_SESSION['error'] = 'Ya existe un vehículo con esa placa';
                header('Location: ?page=vehiculos&action=edit&id=' . $id);
                exit;
            }

            if ($this->vehiculoModel->update($id, $data)) {
                $_SESSION['success'] = 'Vehículo actualizado exitosamente';
                Logger::info('Vehículo actualizado', ['id' => $id, 'placa' => $data['placa'], 'usuario' => $_SESSION['user_id'] ?? 'N/A']);
            } else {
                $_SESSION['error'] = 'Error al actualizar vehículo';
            }
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::update', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data ?? []
            ]);
            $_SESSION['error'] = 'Error interno del servidor';
        }

        header('Location: ?page=vehiculos');
        exit;
    }

    /**
     * Eliminar vehículo
     */
    public function delete($id)
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                header('Location: ?page=vehiculos');
                exit;
            }

            $vehiculo = $this->vehiculoModel->getById($id);
            if (!$vehiculo) {
                $_SESSION['error'] = 'Vehículo no encontrado';
                header('Location: ?page=vehiculos');
                exit;
            }

            if ($this->vehiculoModel->delete($id)) {
                $_SESSION['success'] = 'Vehículo eliminado exitosamente';
                Logger::info('Vehículo eliminado', ['id' => $id, 'placa' => $vehiculo['placa'], 'usuario' => $_SESSION['user_id'] ?? 'N/A']);
            } else {
                $_SESSION['error'] = 'Error al eliminar vehículo';
            }
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::delete', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            $_SESSION['error'] = 'Error interno del servidor';
        }

        header('Location: ?page=vehiculos');
        exit;
    }

    /**
     * Obtener vehículos de un cliente (AJAX)
     */
    public function porCliente($id_cliente)
    {
        try {
            header('Content-Type: application/json');

            $vehiculos = $this->vehiculoModel->getByCliente($id_cliente);
            echo json_encode([
                'success' => true,
                'vehiculos' => $vehiculos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VehiculoController::porCliente', [
                'error' => $e->getMessage(),
                'id_cliente' => $id_cliente
            ]);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Error al cargar vehículos'
            ]);
        }
        exit;
    }
}
