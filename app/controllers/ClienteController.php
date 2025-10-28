<?php

/**
 * Controlador Cliente - Gestión de clientes del sistema
 * Sistema de Inventario Cruz Motor S.A.C.
 * Versión optimizada
 */

class ClienteController extends Controller
{
    private $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->clienteModel = new Cliente();
    }

    /**
     * Mostrar listado de clientes
     */
    public function index()
    {
        try {
            $params = $this->buildSearchParams();
            $clientes = $this->getClientesList($params);
            $total = $this->getClientesCount($params);

            // Calcular paginación
            $perPage = (int)$params['length'];
            $currentPage = floor((int)$params['start'] / $perPage) + 1;
            $totalPages = ceil($total / $perPage);

            $this->view('clientes/index', [
                'clientes' => $clientes,
                'total' => $total,
                'search' => $params['search'],
                'estado' => $params['estado'],
                'currentPage' => $currentPage,
                'totalPages' => $totalPages,
                'perPage' => $perPage,
                'draw' => $_GET['draw'] ?? 1,
                'recordsTotal' => $total,
                'recordsFiltered' => $total,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ClienteController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->json(['error' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        // Recuperar errores/old input en sesión si existen (provenientes de una validación previa)
        $formErrors = $_SESSION['form_errors'] ?? null;
        $oldInput = $_SESSION['old_input'] ?? null;
        unset($_SESSION['form_errors'], $_SESSION['old_input']);

        $csrfToken = $this->auth->getCSRFToken();
        $this->view('clientes/form', [
            'cliente' => null,
            'action' => 'create',             // modo del formulario (create|edit)
            'form_action' => '?page=clientes&action=store', // URL real para el form POST
            'method' => 'POST',
            'title' => 'Nuevo Cliente',
            'csrf_token' => $csrfToken,
            'form_errors' => $formErrors,
            'old_input' => $oldInput
        ]);
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit()
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->redirect('/clientes');
                return;
            }

            $cliente = $this->clienteModel->obtenerPorId($id);
            if (!$cliente) {
                $this->setFlash('error', 'Cliente no encontrado');
                $this->redirect('/clientes');
                return;
            }

            $this->view('clientes/form', [
                'cliente' => $cliente,
                'action' => 'edit',
                'form_action' => '?page=clientes&action=update',
                'method' => 'POST',
                'title' => 'Editar Cliente',
                'csrf_token' => $this->generateCSRF(),
                'form_errors' => $_SESSION['form_errors'] ?? null,
                'old_input' => $_SESSION['old_input'] ?? null
            ]);
            unset($_SESSION['form_errors'], $_SESSION['old_input']);
        } catch (Exception $e) {
            Logger::error('Error en ClienteController::edit', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar el cliente');
            $this->redirect('/clientes');
        }
    }

    /**
     * Guardar nuevo cliente
     */
    public function store()
    {
        Logger::info('Iniciando método store en ClienteController');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Logger::error('Método HTTP no permitido', ['method' => $_SERVER['REQUEST_METHOD']]);
            http_response_code(405);
            echo 'Método no permitido';
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (!(new Auth())->validateCSRFToken($csrfToken)) {
            Logger::error('Fallo en la validación del token CSRF', ['csrf_token' => $csrfToken]);
            http_response_code(403);
            echo 'Token CSRF inválido';
            return;
        }

        Logger::info('Token CSRF validado correctamente');

        try {
            Logger::info('ClienteController::store invoked', ['method' => $_SERVER['REQUEST_METHOD'], 'post_data' => $_POST, 'session_data' => $_SESSION]);
            // Nota: no llamar a $this->validateCSRF() dentro de un if porque ese método
            // hace echo/json y exit en caso de fallo y no devuelve true en caso de éxito.
            // Ya validamos el token arriba con Auth::validateCSRFToken(), por lo que
            // continuamos con el procesamiento.

            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            $validationRules = [
                'nombre' => 'required|max:100',
                'numero_documento' => 'required|max:20',
                'tipo_documento' => 'required|in:DNI,RUC,CE,PASAPORTE',
                'telefono' => 'max:20',
                'email' => 'email|max:100',
                'direccion' => 'max:200',
                'distrito' => 'max:100',
                'provincia' => 'max:100',
                'departamento' => 'max:100',
                'fecha_nacimiento' => 'date'
            ];

            $inputForValidation = $_POST;
            if (empty($inputForValidation['numero_documento']) && !empty($inputForValidation['documento'])) {
                $inputForValidation['numero_documento'] = $inputForValidation['documento'];
            }

            Logger::debug('Input for validation', $inputForValidation);

            $errors = $this->validate($inputForValidation, $validationRules);
            if (!empty($errors)) {
                Logger::warning('Validation errors detected', $errors);
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->setFlash('error', 'Hay errores en el formulario. Revise los campos e intente nuevamente.');
                $this->redirect('?page=clientes&action=create');
                return;
            }

            Logger::info('Validation passed, proceeding to create client');

            $clienteData = [
                'nombre' => $inputForValidation['nombre'],
                'apellido' => $inputForValidation['apellido'] ?? null,
                'email' => $inputForValidation['email'] ?? null,
                'telefono' => $inputForValidation['telefono'] ?? null,
                'estado' => 'activo',
                'numero_documento' => $inputForValidation['numero_documento'],
                'tipo_documento' => $inputForValidation['tipo_documento'],
                'direccion' => $inputForValidation['direccion'] ?? null,
                'distrito' => $inputForValidation['distrito'] ?? null,
                'provincia' => $inputForValidation['provincia'] ?? null,
                'departamento' => $inputForValidation['departamento'] ?? null,
                'fecha_nacimiento' => $inputForValidation['fecha_nacimiento'] ?? null,
                'fecha_registro' => date('Y-m-d H:i:s')
            ];

            $clienteId = $this->clienteModel->create($clienteData);
            if ($clienteId) {
                Logger::info('Cliente creado exitosamente', ['id_cliente' => $clienteId]);
                $this->setFlash('success', 'Cliente registrado correctamente.');
                $this->redirect('?page=clientes');
            } else {
                throw new Exception('Error al registrar el cliente');
            }
        } catch (Exception $e) {
            Logger::error('Unhandled exception in ClienteController::store', ['exception' => $e->getMessage()]);
            $this->setFlash('error', 'Ocurrió un error inesperado. Intente nuevamente.');
            $this->redirect('?page=clientes&action=create');
        }
    }

    /**
     * Actualizar cliente existente
     */
    public function update()
    {
        try {
            Logger::info('Iniciando método update en ClienteController', ['method' => $_SERVER['REQUEST_METHOD'], 'post_keys' => array_keys($_POST)]);

            // Validar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                Logger::error('Método HTTP no permitido en update', ['method' => $_SERVER['REQUEST_METHOD']]);
                http_response_code(405);
                echo 'Método no permitido';
                return;
            }

            // Validar CSRF usando la misma estrategia que en store() (evitar redirects automáticos)
            $csrfToken = $_POST['csrf_token'] ?? '';
            if (!(new Auth())->validateCSRFToken($csrfToken)) {
                Logger::error('Fallo en la validación del token CSRF en update', ['csrf_token' => $csrfToken]);

                // Detectar si la petición viene por AJAX/JSON para responder adecuadamente
                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

                if ($isAjax) {
                    $this->json(['error' => 'Token CSRF inválido'], 403);
                } else {
                    $this->setFlash('error', 'Token CSRF inválido. Intente nuevamente.');
                    $this->redirect('?page=clientes');
                }
                return;
            }

            Logger::info('CSRF validado en update, continuando', ['post_data' => $_POST, 'session_data' => $_SESSION]);

            // Detectar si la petición viene por AJAX/JSON
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            $id = $_POST['id'] ?? null;
            if (!$id) {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'ID de cliente requerido'], 400);
                } else {
                    $this->setFlash('error', 'ID de cliente requerido');
                    $this->redirect('?page=clientes');
                }
                return;
            }

            // Aceptar y normalizar 'numero_documento' (compatibilidad con la vista)
            $validationRules = [
                'nombre' => 'required|max:100',
                'numero_documento' => 'required|max:20',
                'tipo_documento' => 'required|in:DNI,RUC,CE',
                'telefono' => 'max:20',
                'email' => 'email|max:100',
                'direccion' => 'max:200'
            ];

            $inputForValidation = $_POST;
            if (empty($inputForValidation['numero_documento']) && !empty($inputForValidation['documento'])) {
                $inputForValidation['numero_documento'] = $inputForValidation['documento'];
            }

            $errors = $this->validate($inputForValidation, $validationRules);
            if (!empty($errors)) {
                if ($isAjax) {
                    $this->json(['success' => false, 'errors' => $errors], 400);
                } else {
                    $_SESSION['form_errors'] = $errors;
                    $_SESSION['old_input'] = $_POST;
                    $this->setFlash('error', 'Hay errores en el formulario');
                    $this->redirect('?page=clientes&action=edit&id=' . $id);
                }
                return;
            }

            $clienteData = $this->sanitize($inputForValidation);
            unset($clienteData['id']);

            Logger::info('Ejecutando clienteModel->update', ['id' => $id, 'data' => $clienteData]);
            $result = $this->clienteModel->update($id, $clienteData);
            Logger::info('Resultado de clienteModel->update', ['id' => $id, 'result' => $result]);
            if ($result) {
                Logger::info('Cliente actualizado exitosamente', [
                    'id_cliente' => $id,
                    'nombre' => $clienteData['nombre']
                ]);
                if ($isAjax) {
                    $this->json(['success' => true]);
                } else {
                    $this->setFlash('success', 'Cliente actualizado exitosamente');
                    $this->redirect('?page=clientes');
                }
            } else {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'Error al actualizar el cliente'], 500);
                } else {
                    $this->setFlash('error', 'Error al actualizar el cliente');
                    $this->redirect('?page=clientes');
                }
            }
        } catch (Exception $e) {
            Logger::error('Error en ClienteController::update', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null,
                'data' => $_POST
            ]);
            if (!empty($isAjax)) {
                $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
            } else {
                $this->setFlash('error', 'Error interno del servidor');
                $this->redirect('?page=clientes');
            }
        }
    }

    /**
     * Eliminar cliente
     */
    public function delete()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Método no permitido'], 405);
                return;
            }

            // Detectar petición AJAX
            $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

            $id = $_POST['id'] ?? null;
            if (!$id) {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'ID de cliente requerido'], 400);
                } else {
                    $this->setFlash('error', 'ID de cliente requerido');
                    $this->redirect('?page=clientes');
                }
                return;
            }

            // Verificar si tiene ventas o cotizaciones asociadas
            $hasMovements = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM (
                    SELECT id_cliente FROM ventas WHERE id_cliente = ? 
                    UNION ALL 
                    SELECT id_cliente FROM cotizaciones WHERE id_cliente = ?
                ) movements",
                [$id, $id]
            );

            if ($hasMovements && $hasMovements['total'] > 0) {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'No se puede eliminar un cliente con movimientos asociados'], 400);
                } else {
                    $this->setFlash('error', 'No se puede eliminar un cliente con movimientos asociados');
                    $this->redirect('?page=clientes');
                }
                return;
            }

            $result = $this->clienteModel->delete($id);
            if ($result) {
                Logger::info('Cliente eliminado exitosamente', ['id_cliente' => $id]);
                if ($isAjax) {
                    $this->json(['success' => true, 'message' => 'Cliente eliminado correctamente']);
                } else {
                    $this->setFlash('success', 'Cliente eliminado correctamente');
                    $this->redirect('?page=clientes');
                }
            } else {
                if ($isAjax) {
                    $this->json(['success' => false, 'message' => 'Error al eliminar el cliente'], 500);
                } else {
                    $this->setFlash('error', 'Error al eliminar el cliente');
                    $this->redirect('?page=clientes');
                }
            }
        } catch (Exception $e) {
            Logger::error('Error en ClienteController::delete', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            if (!empty($isAjax)) {
                $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
            } else {
                $this->setFlash('error', 'Error interno del servidor');
                $this->redirect('?page=clientes');
            }
        }
    }

    /**
     * Obtener clientes para select
     */
    public function getForSelect()
    {
        try {
            $clientes = $this->clienteModel->getActive();
            $this->json($clientes);
        } catch (Exception $e) {
            Logger::error('Error en ClienteController::getForSelect', [
                'error' => $e->getMessage()
            ]);
            $this->json(['error' => 'Error al obtener clientes'], 500);
        }
    }

    /**
     * Métodos privados de apoyo
     */
    private function buildSearchParams()
    {
        return [
            'search' => $_GET['search'] ?? '',
            'estado' => $_GET['estado'] ?? '',
            'start' => $_GET['start'] ?? 0,
            'length' => $_GET['length'] ?? 10
        ];
    }

    private function getClientesList($params)
    {
        $sql = "SELECT * FROM clientes WHERE 1=1";
        $sqlParams = [];

        if (!empty($params['search'])) {
            $sql .= " AND (nombre LIKE ? OR numero_documento LIKE ? OR email LIKE ?)";
            $search = '%' . $params['search'] . '%';
            $sqlParams = array_merge($sqlParams, [$search, $search, $search]);
        }

        if ($params['estado'] !== '') {
            $sql .= " AND estado = ?";
            $sqlParams[] = $params['estado'];
        }

        $sql .= " ORDER BY nombre ASC LIMIT ? OFFSET ?";
        $sqlParams[] = (int)$params['length'];
        $sqlParams[] = (int)$params['start'];

        return $this->db->select($sql, $sqlParams);
    }

    private function getClientesCount($params)
    {
        $sql = "SELECT COUNT(*) as total FROM clientes WHERE 1=1";
        $sqlParams = [];

        if (!empty($params['search'])) {
            $sql .= " AND (nombre LIKE ? OR numero_documento LIKE ? OR email LIKE ?)";
            $search = '%' . $params['search'] . '%';
            $sqlParams = array_merge($sqlParams, [$search, $search, $search]);
        }

        if ($params['estado'] !== '') {
            $sql .= " AND estado = ?";
            $sqlParams[] = $params['estado'];
        }

        $result = $this->db->selectOne($sql, $sqlParams);
        return $result['total'] ?? 0;
    }
}
