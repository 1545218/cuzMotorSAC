<?php

/**
 * Controlador Unidad - Gestión de unidades de medida
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class UnidadController extends Controller
{
    private $unidadModel;

    public function __construct()
    {
        parent::__construct();
        $this->unidadModel = new Unidad();
    }

    /**
     * Mostrar listado de unidades
     */
    public function index()
    {
        try {
            $search = $_GET['search'] ?? '';

            if (!empty($search)) {
                $unidades = $this->unidadModel->searchUnidades($search);
            } else {
                $unidades = $this->unidadModel->getUnidadesWithStats();
            }

            $stats = $this->unidadModel->getStats();

            $this->view('unidades/index', [
                'unidades' => $unidades,
                'stats' => $stats,
                'search' => $search,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::index', [
                'error' => $e->getMessage()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar unidades']);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $this->view('unidades/form', [
            'unidad' => null,
            'action' => '/unidades/store',
            'method' => 'POST',
            'title' => 'Nueva Unidad',
            'csrf_token' => $this->generateCSRF()
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
                $this->redirect('/unidades');
                return;
            }

            $unidad = $this->unidadModel->find($id);
            if (!$unidad) {
                $this->setFlash('error', 'Unidad no encontrada');
                $this->redirect('/unidades');
                return;
            }

            $this->view('unidades/form', [
                'unidad' => $unidad,
                'action' => '/unidades/update',
                'method' => 'POST',
                'title' => 'Editar Unidad',
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::edit', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar la unidad');
            $this->redirect('/unidades');
        }
    }

    /**
     * Guardar nueva unidad
     */
    public function store()
    {
        try {
            if (!$this->validateCSRF()) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
                return;
            }

            $validationRules = [
                'nombre' => 'required|max:50',
                'abreviatura' => 'max:10',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar que no exista unidad con el mismo nombre o abreviatura
            $existingNombre = $this->unidadModel->whereOne('nombre = ?', [$_POST['nombre']]);
            if ($existingNombre) {
                $this->json(['success' => false, 'message' => 'Ya existe una unidad con ese nombre'], 400);
                return;
            }

            $existingAbrev = $this->unidadModel->whereOne('abreviatura = ?', [$_POST['abreviatura']]);
            if ($existingAbrev) {
                $this->json(['success' => false, 'message' => 'Ya existe una unidad con esa abreviatura'], 400);
                return;
            }

            $unidadData = $this->sanitize($_POST);
            $result = $this->unidadModel->create($unidadData);

            if ($result) {
                Logger::info('Unidad creada exitosamente', [
                    'id_unidad' => $result,
                    'nombre' => $unidadData['nombre'],
                    'abreviatura' => $unidadData['abreviatura']
                ]);
                $this->json(['success' => true, 'id' => $result]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al crear la unidad'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::store', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Actualizar unidad existente
     */
    public function update()
    {
        try {
            if (!$this->validateCSRF()) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
                return;
            }

            $id = $_POST['id'] ?? null;
            if (!$id) {
                $this->json(['success' => false, 'message' => 'ID de unidad requerido'], 400);
                return;
            }

            $validationRules = [
                'nombre' => 'required|max:50',
                'abreviatura' => 'required|max:10',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar duplicados
            $existingNombre = $this->unidadModel->whereOne(
                'nombre = ? AND id_unidad != ?',
                [$_POST['nombre'], $id]
            );

            if ($existingNombre) {
                $this->json(['success' => false, 'message' => 'Ya existe una unidad con ese nombre'], 400);
                return;
            }

            $existingAbrev = $this->unidadModel->whereOne(
                'abreviatura = ? AND id_unidad != ?',
                [$_POST['abreviatura'], $id]
            );

            if ($existingAbrev) {
                $this->json(['success' => false, 'message' => 'Ya existe una unidad con esa abreviatura'], 400);
                return;
            }

            $unidadData = $this->sanitize($_POST);
            unset($unidadData['id']);

            $result = $this->unidadModel->update($id, $unidadData);
            if ($result) {
                Logger::info('Unidad actualizada exitosamente', [
                    'id_unidad' => $id,
                    'nombre' => $unidadData['nombre']
                ]);
                $this->json(['success' => true]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar la unidad'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::update', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Eliminar unidad
     */
    public function delete()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->json(['error' => 'Método no permitido'], 405);
                return;
            }

            $id = $_POST['id'] ?? null;
            if (!$id) {
                $this->json(['success' => false, 'message' => 'ID de unidad requerido'], 400);
                return;
            }

            // Verificar si tiene productos asociados
            if (!$this->unidadModel->canDelete($id)) {
                $this->json(['success' => false, 'message' => 'No se puede eliminar una unidad con productos asociados'], 400);
                return;
            }

            $result = $this->unidadModel->delete($id);
            if ($result) {
                Logger::info('Unidad eliminada exitosamente', ['id_unidad' => $id]);
                $this->json(['success' => true, 'message' => 'Unidad eliminada correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar la unidad'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::delete', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Ver detalles de una unidad
     */
    public function details()
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->redirect('/unidades');
                return;
            }

            $unidad = $this->unidadModel->getUnidadWithDetails($id);
            if (!$unidad) {
                $this->setFlash('error', 'Unidad no encontrada');
                $this->redirect('/unidades');
                return;
            }

            $productos = $this->unidadModel->getProductos($id, 20);

            $this->view('unidades/view', [
                'unidad' => $unidad,
                'productos' => $productos,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::view', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar la unidad');
            $this->redirect('/unidades');
        }
    }

    /**
     * Obtener unidades para select (AJAX)
     */
    public function getForSelect()
    {
        try {
            $unidades = $this->unidadModel->getForSelect();
            $this->json($unidades);
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::getForSelect', [
                'error' => $e->getMessage()
            ]);
            $this->json(['error' => 'Error al obtener unidades'], 500);
        }
    }

    /**
     * Inicializar unidades estándar
     */
    public function initializeStandard()
    {
        try {
            $this->requireRole(['admin']);

            $inserted = $this->unidadModel->initializeStandardUnits();

            Logger::info('Unidades estándar inicializadas', [
                'unidades_creadas' => $inserted
            ]);

            $this->json([
                'success' => true,
                'message' => "Se crearon {$inserted} unidades estándar",
                'inserted' => $inserted
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UnidadController::initializeStandard', [
                'error' => $e->getMessage()
            ]);
            $this->json(['success' => false, 'message' => 'Error al inicializar unidades estándar'], 500);
        }
    }
}
