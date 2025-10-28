<?php

class CategoriaController extends Controller
{
    private $categoriaModel;

    public function __construct()
    {
        parent::__construct();
        $this->categoriaModel = new Categoria();

        // Verificar autenticación para todas las acciones
        Auth::requireAuth();
    }

    /**
     * Mostrar lista de categorías
     */
    public function index()
    {
        try {
            $categorias = $this->categoriaModel->getAll();

            // Si es una petición AJAX para DataTables
            if (isset($_GET['draw'])) {
                $this->handleDataTablesRequest($categorias);
                return;
            }

            $this->view('categorias/index', [
                'title' => 'Gestión de Categorías',
                'categorias' => $categorias
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::index - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar categorías']);
        }
    }

    /**
     * Mostrar formulario para crear categoría
     */
    public function create()
    {
        try {
            $this->view('categorias/form', [
                'title' => 'Crear Categoría',
                'action' => 'create',
                'categoria' => null
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::create - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al mostrar formulario']);
        }
    }

    /**
     * Crear nueva categoría
     */
    public function store()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden crear categorías
            if (!in_array($_SESSION['user_role'], ['admin', 'almacen'])) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            // Validar datos
            $errors = $this->validateCategoriaData($_POST);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el nombre no exista
            if ($this->categoriaModel->findByName($_POST['nombre'])) {
                echo json_encode(['success' => false, 'errors' => ['nombre' => 'Ya existe una categoría con este nombre']]);
                return;
            }

            // Preparar datos
            $categoriaData = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'created_by' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->categoriaModel->create($categoriaData);

            if ($result) {
                Logger::info("Categoría creada: " . $_POST['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Categoría creada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al crear categoría']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::store - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Mostrar formulario para editar categoría
     */
    public function edit($id)
    {
        try {
            $categoria = $this->categoriaModel->find($id);

            if (!$categoria) {
                $this->view('errors/404', ['message' => 'Categoría no encontrada']);
                return;
            }

            $this->view('categorias/form', [
                'title' => 'Editar Categoría',
                'action' => 'edit',
                'categoria' => $categoria
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::edit - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar categoría']);
        }
    }

    /**
     * Actualizar categoría
     */
    public function update($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden actualizar categorías
            if (!in_array($_SESSION['user_role'], ['admin', 'almacen'])) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            $categoria = $this->categoriaModel->find($id);
            if (!$categoria) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Categoría no encontrada']]);
                return;
            }

            // Validar datos
            $errors = $this->validateCategoriaData($_POST, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el nombre no exista (excepto el actual)
            $existingCategoria = $this->categoriaModel->findByName($_POST['nombre']);
            if ($existingCategoria && $existingCategoria['id'] != $id) {
                echo json_encode(['success' => false, 'errors' => ['nombre' => 'Ya existe una categoría con este nombre']]);
                return;
            }

            // Preparar datos
            $categoriaData = [
                'nombre' => trim($_POST['nombre']),
                'descripcion' => trim($_POST['descripcion'] ?? ''),
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->categoriaModel->update($id, $categoriaData);

            if ($result) {
                Logger::info("Categoría actualizada: " . $_POST['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Categoría actualizada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al actualizar categoría']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::update - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Eliminar categoría
     */
    public function delete($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores pueden eliminar categorías
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
                return;
            }

            $categoria = $this->categoriaModel->find($id);
            if (!$categoria) {
                echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
                return;
            }

            // Verificar si la categoría tiene productos asociados
            if ($this->categoriaModel->hasProducts($id)) {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar una categoría que tiene productos asociados']);
                return;
            }

            $result = $this->categoriaModel->delete($id);

            if ($result) {
                Logger::info("Categoría eliminada: " . $categoria['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Categoría eliminada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar categoría']);
            }
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::delete - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Cambiar estado activo/inactivo de la categoría
     */
    public function toggleStatus($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden cambiar estado
            if (!in_array($_SESSION['user_role'], ['admin', 'almacen'])) {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
                return;
            }

            $categoria = $this->categoriaModel->find($id);
            if (!$categoria) {
                echo json_encode(['success' => false, 'message' => 'Categoría no encontrada']);
                return;
            }

            $newStatus = $categoria['activo'] ? 0 : 1;
            $result = $this->categoriaModel->update($id, [
                'activo' => $newStatus,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                $statusText = $newStatus ? 'activada' : 'desactivada';
                Logger::info("Categoría {$statusText}: " . $categoria['nombre'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => "Categoría {$statusText} exitosamente"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado de la categoría']);
            }
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::toggleStatus - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener categorías activas para select
     */
    public function getActive()
    {
        try {
            $categorias = $this->categoriaModel->getActive();
            echo json_encode(['success' => true, 'data' => $categorias]);
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::getActive - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener categorías']);
        }
    }

    /**
     * Obtener subcategorías por categoría
     */
    public function getSubcategorias($categoriaId)
    {
        try {
            $subcategorias = $this->categoriaModel->getSubcategorias($categoriaId);
            echo json_encode(['success' => true, 'data' => $subcategorias]);
        } catch (Exception $e) {
            Logger::error("Error en CategoriaController::getSubcategorias - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener subcategorías']);
        }
    }

    /**
     * Validar datos de la categoría
     */
    private function validateCategoriaData($data, $id = null)
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

        return $errors;
    }

    /**
     * Manejar peticiones AJAX para DataTables
     */
    private function handleDataTablesRequest($categorias)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $categorias = array_filter($categorias, function ($categoria) use ($search) {
                return stripos($categoria['nombre'], $search) !== false ||
                    stripos($categoria['descripcion'], $search) !== false;
            });
        }

        $totalRecords = count($categorias);
        $categorias = array_slice($categorias, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($categorias as $categoria) {
            $statusBadge = $categoria['activo'] ?
                '<span class="badge bg-success">Activa</span>' :
                '<span class="badge bg-danger">Inactiva</span>';

            $actions = '
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarCategoria(' . $categoria['id'] . ')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleCategoria(' . $categoria['id'] . ')" title="Cambiar Estado">
                        <i class="fas fa-toggle-' . ($categoria['activo'] ? 'on' : 'off') . '"></i>
                    </button>';

            // Solo administradores pueden eliminar
            if ($_SESSION['user_role'] === 'admin') {
                $actions .= '
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCategoria(' . $categoria['id'] . ')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>';
            }

            $actions .= '</div>';

            $data[] = [
                $categoria['nombre'],
                $categoria['descripcion'] ?? '',
                $statusBadge,
                date('d/m/Y H:i', strtotime($categoria['created_at'])),
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
