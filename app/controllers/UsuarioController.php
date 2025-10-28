<?php

class UsuarioController extends Controller
{
    private $usuarioModel;

    public function __construct()
    {
        parent::__construct();
        $this->usuarioModel = new Usuario();

        // Verificar autenticación para todas las acciones
        Auth::requireAuth();

        // Solo permitir acceso a usuarios con rol 'administrador'
        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'administrador') {
            header('Location: ?page=dashboard&error=access_denied');
            exit;
        }
    }

    /**
     * Mostrar lista de usuarios
     */
    public function index()
    {
        try {
            $usuarios = $this->usuarioModel->getAll();

            // Si es una petición AJAX para DataTables
            if (isset($_GET['draw'])) {
                $this->handleDataTablesRequest($usuarios);
                return;
            }

            $this->view('usuarios/index', [
                'title' => 'Gestión de Usuarios',
                'usuarios' => $usuarios
            ]);
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::index - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar usuarios']);
        }
    }

    /**
     * Mostrar formulario para crear usuario
     */
    public function create()
    {
        try {
            $this->view('usuarios/form', [
                'title' => 'Crear Usuario',
                'action' => 'create',
                'usuario' => null
            ]);
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::create - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al mostrar formulario']);
        }
    }

    /**
     * Crear nuevo usuario
     */
    public function store()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Validar datos
            $errors = $this->validateUsuarioData($_POST);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el username no exista
            if ($this->usuarioModel->findByUsername($_POST['username'])) {
                echo json_encode(['success' => false, 'errors' => ['username' => 'El nombre de usuario ya existe']]);
                return;
            }

            // Preparar datos
            $userData = [
                'username' => trim($_POST['username']),
                'password' => password_hash($_POST['password'], PASSWORD_DEFAULT),
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'rol' => $_POST['rol'],
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'created_by' => $_SESSION['user_id'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $result = $this->usuarioModel->create($userData);

            if ($result) {
                Logger::info("Usuario creado: " . $_POST['username'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Usuario creado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al crear usuario']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::store - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Mostrar formulario para editar usuario
     */
    public function edit($id)
    {
        try {
            $usuario = $this->usuarioModel->find($id);

            if (!$usuario) {
                $this->view('errors/404', ['message' => 'Usuario no encontrado']);
                return;
            }

            $this->view('usuarios/form', [
                'title' => 'Editar Usuario',
                'action' => 'edit',
                'usuario' => $usuario
            ]);
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::edit - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar usuario']);
        }
    }

    /**
     * Actualizar usuario
     */
    public function update($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $usuario = $this->usuarioModel->find($id);
            if (!$usuario) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Usuario no encontrado']]);
                return;
            }

            // Validar datos
            $errors = $this->validateUsuarioData($_POST, $id);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el username no exista (excepto el actual)
            $existingUser = $this->usuarioModel->findByUsername($_POST['username']);
            if ($existingUser && $existingUser['id'] != $id) {
                echo json_encode(['success' => false, 'errors' => ['username' => 'El nombre de usuario ya existe']]);
                return;
            }

            // Preparar datos
            $userData = [
                'username' => trim($_POST['username']),
                'nombre' => trim($_POST['nombre']),
                'email' => trim($_POST['email']),
                'rol' => $_POST['rol'],
                'activo' => isset($_POST['activo']) ? 1 : 0,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ];

            // Solo actualizar password si se proporcionó
            if (!empty($_POST['password'])) {
                $userData['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }

            $result = $this->usuarioModel->update($id, $userData);

            if ($result) {
                Logger::info("Usuario actualizado: " . $_POST['username'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Usuario actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al actualizar usuario']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::update - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Eliminar usuario
     */
    public function delete($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $usuario = $this->usuarioModel->find($id);
            if (!$usuario) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                return;
            }

            // No permitir eliminar al propio usuario
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'No puedes eliminar tu propio usuario']);
                return;
            }

            // No permitir eliminar el último administrador
            if ($usuario['rol'] === 'admin') {
                $adminCount = $this->usuarioModel->countByRole('admin');
                if ($adminCount <= 1) {
                    echo json_encode(['success' => false, 'message' => 'No se puede eliminar el último administrador']);
                    return;
                }
            }

            $result = $this->usuarioModel->delete($id);

            if ($result) {
                Logger::info("Usuario eliminado: " . $usuario['username'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Usuario eliminado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar usuario']);
            }
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::delete - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Cambiar estado activo/inactivo del usuario
     */
    public function toggleStatus($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $usuario = $this->usuarioModel->find($id);
            if (!$usuario) {
                echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
                return;
            }

            // No permitir desactivar al propio usuario
            if ($id == $_SESSION['user_id']) {
                echo json_encode(['success' => false, 'message' => 'No puedes desactivar tu propio usuario']);
                return;
            }

            $newStatus = $usuario['activo'] ? 0 : 1;
            $result = $this->usuarioModel->update($id, [
                'activo' => $newStatus,
                'updated_by' => $_SESSION['user_id'],
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if ($result) {
                $statusText = $newStatus ? 'activado' : 'desactivado';
                Logger::info("Usuario {$statusText}: " . $usuario['username'] . " por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => "Usuario {$statusText} exitosamente"]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al cambiar estado del usuario']);
            }
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::toggleStatus - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Mostrar formulario de permisos
     */
    public function permisos($id)
    {
        try {
            $usuario = $this->usuarioModel->find($id);

            if (!$usuario) {
                $this->view('errors/404', ['message' => 'Usuario no encontrado']);
                return;
            }

            $this->view('usuarios/permisos', [
                'title' => 'Permisos de Usuario',
                'usuario' => $usuario
            ]);
        } catch (Exception $e) {
            Logger::error("Error en UsuarioController::permisos - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar permisos']);
        }
    }

    /**
     * Validar datos del usuario
     */
    private function validateUsuarioData($data, $id = null)
    {
        $errors = [];

        // Validar username
        if (empty($data['username'])) {
            $errors['username'] = 'El nombre de usuario es obligatorio';
        } elseif (strlen($data['username']) < 3) {
            $errors['username'] = 'El nombre de usuario debe tener al menos 3 caracteres';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
            $errors['username'] = 'El nombre de usuario solo puede contener letras, números y guiones bajos';
        }

        // Validar password (solo obligatorio al crear)
        if ($id === null) { // Crear nuevo usuario
            if (empty($data['password'])) {
                $errors['password'] = 'La contraseña es obligatoria';
            } elseif (strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
            }
        } else { // Actualizar usuario
            if (!empty($data['password']) && strlen($data['password']) < 6) {
                $errors['password'] = 'La contraseña debe tener al menos 6 caracteres';
            }
        }

        // Validar nombre
        if (empty($data['nombre'])) {
            $errors['nombre'] = 'El nombre es obligatorio';
        } elseif (strlen($data['nombre']) < 2) {
            $errors['nombre'] = 'El nombre debe tener al menos 2 caracteres';
        }

        // Validar email
        if (empty($data['email'])) {
            $errors['email'] = 'El email es obligatorio';
        } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'El email no es válido';
        }

        // Validar rol
        if (empty($data['rol'])) {
            $errors['rol'] = 'El rol es obligatorio';
        } elseif (!in_array($data['rol'], ['admin', 'vendedor', 'almacen'])) {
            $errors['rol'] = 'El rol seleccionado no es válido';
        }

        return $errors;
    }

    /**
     * Manejar peticiones AJAX para DataTables
     */
    private function handleDataTablesRequest($usuarios)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $usuarios = array_filter($usuarios, function ($usuario) use ($search) {
                return stripos($usuario['username'], $search) !== false ||
                    stripos($usuario['nombre'], $search) !== false ||
                    stripos($usuario['email'], $search) !== false ||
                    stripos($usuario['rol'], $search) !== false;
            });
        }

        $totalRecords = count($usuarios);
        $usuarios = array_slice($usuarios, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($usuarios as $usuario) {
            $statusBadge = $usuario['activo'] ?
                '<span class="badge bg-success">Activo</span>' :
                '<span class="badge bg-danger">Inactivo</span>';

            $roleBadge = match ($usuario['rol']) {
                'admin' => '<span class="badge bg-primary">Administrador</span>',
                'vendedor' => '<span class="badge bg-info">Vendedor</span>',
                'almacen' => '<span class="badge bg-warning">Almacén</span>',
                default => '<span class="badge bg-secondary">' . ucfirst($usuario['rol']) . '</span>'
            };

            $actions = '
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="editarUsuario(' . $usuario['id'] . ')" title="Editar">
                        <i class="fas fa-edit"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="verPermisos(' . $usuario['id'] . ')" title="Permisos">
                        <i class="fas fa-user-shield"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="toggleUsuario(' . $usuario['id'] . ')" title="Cambiar Estado">
                        <i class="fas fa-toggle-' . ($usuario['activo'] ? 'on' : 'off') . '"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarUsuario(' . $usuario['id'] . ')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>
                </div>';

            $data[] = [
                $usuario['username'],
                $usuario['nombre'],
                $usuario['email'],
                $roleBadge,
                $statusBadge,
                date('d/m/Y H:i', strtotime($usuario['created_at'])),
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
