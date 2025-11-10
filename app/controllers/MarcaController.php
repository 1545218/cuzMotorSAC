<?php

class MarcaController extends Controller
{
    private $marcaModel;

    public function __construct()
    {
        parent::__construct();
        $this->marcaModel = new Marca();

        // Verificar autenticación para todas las acciones
        Auth::requireAuth();
    }

    /**
     * Mostrar lista de marcas
     */
    public function index()
    {
        try {
            $marcas = $this->marcaModel->getAll();

            // Si es una petición AJAX para DataTables
            if (isset($_GET['draw'])) {
                $this->handleDataTablesRequest($marcas);
                return;
            }

            $this->view('marcas/index', [
                'title' => 'Gestión de Marcas',
                'marcas' => $marcas
            ]);
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::index - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar marcas']);
        }
    }

    /**
     * Mostrar formulario para crear marca
     */
    public function create()
    {
        try {
            $this->view('marcas/form', [
                'title' => 'Crear Marca',
                'action' => 'create',
                'marca' => null
            ]);
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::create - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al mostrar formulario']);
        }
    }

    /**
     * Crear nueva marca
     */
    public function store()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden crear marcas
            if (!in_array($_SESSION['rol'] ?? '', ['administrador', 'almacen'])) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            // Validar datos
            $errors = $this->validateMarcaData($_POST);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el nombre no exista
            if ($this->marcaModel->findByName($_POST['nombre'])) {
                echo json_encode(['success' => false, 'errors' => ['nombre' => 'Ya existe una marca con este nombre']]);
                return;
            }

            // Preparar datos
            $marcaData = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'pais_origen' => trim($_POST['pais_origen'] ?? ''),
                'sitio_web' => trim($_POST['sitio_web'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'created_by' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->marcaModel->create($marcaData);

            if ($result) {
                Logger::info("Marca creada: " . $_POST['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Marca creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al crear marca']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::store - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Mostrar formulario para editar marca
     */
    public function edit($id)
    {
        try {
            $marca = $this->marcaModel->find($id);

            if (!$marca) {
                $this->view('errors/404', ['message' => 'Marca no encontrada']);
                return;
            }

            $this->view('marcas/form', [
                'title' => 'Editar Marca',
                'action' => 'edit',
                'marca' => $marca
            ]);
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::edit - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar marca']);
        }
    }

    /**
     * Actualizar marca
     */
    public function update($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden actualizar marcas
            if (!in_array($_SESSION['rol'] ?? '', ['administrador', 'almacen'])) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            $marca = $this->marcaModel->find($id);
            if (!$marca) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Marca no encontrada']]);
                return;
            }

            // Validar datos
            $errors = $this->validateMarcaData($_POST, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el nombre no exista (excepto el actual)
            $existingMarca = $this->marcaModel->findByName($_POST['nombre']);
            if ($existingMarca && $existingMarca['id'] != $id) {
                echo json_encode(['success' => false, 'errors' => ['nombre' => 'Ya existe una marca con este nombre']]);
                return;
            }

            // Preparar datos
            $marcaData = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'pais_origen' => trim($_POST['pais_origen'] ?? ''),
                'sitio_web' => trim($_POST['sitio_web'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->marcaModel->update($id, $marcaData);

            if ($result) {
                Logger::info("Marca actualizada: " . $_POST['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Marca actualizada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al actualizar marca']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::update - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Eliminar marca
     */
    public function delete($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores pueden eliminar marcas
            if ($_SESSION['rol'] ?? '' !== 'administrador') {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
                return;
            }

            $marca = $this->marcaModel->find($id);
            if (!$marca) {
                echo json_encode(['success' => false, 'message' => 'Marca no encontrada']);
                return;
            }

            // Verificar si la marca tiene productos asociados
            if ($this->marcaModel->hasProducts($id)) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar una marca que tiene productos asociados']);
                return;
            }

            $result = $this->marcaModel->delete($id);

            if ($result) {
                Logger::info("Marca eliminada: " . $marca['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Marca eliminada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar marca']);
            }
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::delete - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Cambiar estado activo/inactivo de la marca
     */
    public function toggleStatus($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden cambiar estado
            if (!in_array($_SESSION['rol'] ?? '', ['administrador', 'almacen'])) {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
                return;
            }

            $marca = $this->marcaModel->find($id);
            if (!$marca) {
                echo json_encode(['success' => false, 'message' => 'Marca no encontrada']);
                return;
            }

            $newStatus = $marca['activo'] ? 0 : 1;
            $result = $this->marcaModel->update($id, [
                'activo' => $newStatus,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                $statusText = $newStatus ? 'activada' : 'desactivada';
                Logger::info("Marca {$statusText}: " . $marca['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => "Marca {$statusText} exitosamente"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado de la marca']);
            }
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::toggleStatus - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener marcas activas para select
     */
    public function getActive()
    {
        try {
            $marcas = $this->marcaModel->getActive();
            echo json_encode(['success' => true, 'data' => $marcas]);
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::getActive - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener marcas']);
        }
    }

    /**
     * Obtener estadísticas de la marca
     */
    public function stats($id)
    {
        try {
            $marca = $this->marcaModel->find($id);
            if (!$marca) {
                echo json_encode(['success' => false, 'message' => 'Marca no encontrada']);
                return;
            }

            $stats = $this->marcaModel->getStats($id);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            Logger::error("Error en MarcaController::stats - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
        }
    }

    /**
     * Validar datos de la marca
     */
    private function validateMarcaData($data, $id = null)
    {
        $errors = [];

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        } elseif (strlen($data['nombre']) > 100) {
            $errors['nombre'] = 'El nombre no puede tener más de 100 caracteres';
        }

        // Validar descripción (opcional)
        if (!empty($data['descripcion']) && strlen($data['descripcion']) > 500) {
            $errors['descripcion'] = 'La descripción no puede tener más de 500 caracteres';
        }

        // Validar país de origen (opcional)
        if (!empty($data['pais_origen']) && strlen($data['pais_origen']) > 100) {
            $errors['pais_origen'] = 'El país de origen no puede tener más de 100 caracteres';
        }

        // Validar sitio web (opcional)
        if (!empty($data['sitio_web'])) {
            if (strlen($data['sitio_web']) > 200) {
                $errors['sitio_web'] = 'El sitio web no puede tener más de 200 caracteres';
            } elseif (!filter_var($data['sitio_web'], FILTER_VALIDATE_URL)) {
                $errors['sitio_web'] = 'El sitio web debe ser una URL válida';
            }
        }

        return $errors;
    }

    /**
     * Manejar peticiones AJAX para DataTables
     */
    private function handleDataTablesRequest($marcas)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $marcas = array_filter($marcas, function ($marca) use ($search) {
                return stripos($marca['nombre'], $search) !== false ||
                    stripos($marca['descripcion'], $search) !== false ||
                    stripos($marca['pais_origen'], $search) !== false;
            });
        }

        $totalRecords = count($marcas);
        $marcas = array_slice($marcas, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($marcas as $marca) {
            $statusBadge = $marca['activo'] ?
                '<span class="badge bg-success">Activa</span>' :
                '<span class="badge bg-danger">Inactiva</span>';

            $sitioWeb = $marca['sitio_web'] ?
                '<a href="' . $marca['sitio_web'] . '" target="_blank" class="text-decoration-none"><i class="fas fa-external-link-alt"></i></a>' :
                '-';

            $actions = '
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarMarca(' . $marca['id'] . ')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="verStats(' . $marca['id'] . ')" title="Estadísticas">
                        <i class="fas fa-chart-bar"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleMarca(' . $marca['id'] . ')" title="Cambiar Estado">
                        <i class="fas fa-toggle-' . ($marca['activo'] ? 'on' : 'off') . '"></i>
                    </button>';

            // Solo administradores pueden eliminar
            if ($_SESSION['rol'] ?? '' === 'administrador') {
                $actions .= '
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarMarca(' . $marca['id'] . ')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>';
            }

            $actions .= '</div>';

            $data[] = [
                $marca['nombre'],
                $marca['descripcion'] ?? '',
                $marca['pais_origen'] ?? '',
                $sitioWeb,
                $statusBadge,
                date('d/m/Y H:i', strtotime($marca['created_at'])),
                $actions
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }
}
