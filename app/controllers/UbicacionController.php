<?php

/**
 * Controlador Ubicacion - Gestión de ubicaciones físicas del inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class UbicacionController extends Controller
{
    private $ubicacionModel;

    public function __construct()
    {
        parent::__construct();
        $this->ubicacionModel = new Ubicacion();
    }

    /**
     * Mostrar listado de ubicaciones
     */
    public function index()
    {
        try {
            $search = $_GET['search'] ?? '';
            $tipo = $_GET['tipo'] ?? '';

            if (!empty($search) || !empty($tipo)) {
                $ubicaciones = $this->getFilteredUbicaciones($search, $tipo);
            } else {
                $ubicaciones = $this->ubicacionModel->getUbicacionesWithStats();
            }

            $tipos = $this->ubicacionModel->getTipos();

            $this->view('ubicaciones/index', [
                'ubicaciones' => $ubicaciones,
                'tipos' => $tipos,
                'search' => $search,
                'tipo_selected' => $tipo,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::index', [
                'error' => $e->getMessage()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar ubicaciones']);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $this->view('ubicaciones/form', [
            'ubicacion' => null,
            'action' => '/ubicaciones/store',
            'method' => 'POST',
            'title' => 'Nueva Ubicación',
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
                $this->redirect('/ubicaciones');
                return;
            }

            $ubicacion = $this->ubicacionModel->find($id);
            if (!$ubicacion) {
                $this->setFlash('error', 'Ubicación no encontrada');
                $this->redirect('/ubicaciones');
                return;
            }

            $this->view('ubicaciones/form', [
                'ubicacion' => $ubicacion,
                'action' => '/ubicaciones/update',
                'method' => 'POST',
                'title' => 'Editar Ubicación',
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::edit', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar la ubicación');
            $this->redirect('/ubicaciones');
        }
    }

    /**
     * Guardar nueva ubicación
     */
    public function store()
    {
        try {
            if (!$this->validateCSRF()) {
                $this->json(['success' => false, 'message' => 'Token de seguridad inválido'], 403);
                return;
            }

            $validationRules = [
                'nombre' => 'required|max:100',
                'tipo' => 'required|in:almacen,estante,pasillo,seccion,zona',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar que no exista ubicación con el mismo nombre
            $existing = $this->ubicacionModel->whereOne('nombre = ?', [$_POST['nombre']]);
            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya existe una ubicación con ese nombre'], 400);
                return;
            }

            $ubicacionData = $this->sanitize($_POST);
            $result = $this->ubicacionModel->create($ubicacionData);

            if ($result) {
                Logger::info('Ubicación creada exitosamente', [
                    'id_ubicacion' => $result,
                    'nombre' => $ubicacionData['nombre'],
                    'tipo' => $ubicacionData['tipo']
                ]);
                $this->json(['success' => true, 'id' => $result]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al crear la ubicación'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::store', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Actualizar ubicación existente
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
                $this->json(['success' => false, 'message' => 'ID de ubicación requerido'], 400);
                return;
            }

            $validationRules = [
                'nombre' => 'required|max:100',
                'tipo' => 'required|in:almacen,estante,pasillo,seccion,zona',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar duplicados
            $existing = $this->ubicacionModel->whereOne(
                'nombre = ? AND id_ubicacion != ?',
                [$_POST['nombre'], $id]
            );

            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya existe una ubicación con ese nombre'], 400);
                return;
            }

            $ubicacionData = $this->sanitize($_POST);
            unset($ubicacionData['id']);

            $result = $this->ubicacionModel->update($id, $ubicacionData);
            if ($result) {
                Logger::info('Ubicación actualizada exitosamente', [
                    'id_ubicacion' => $id,
                    'nombre' => $ubicacionData['nombre']
                ]);
                $this->json(['success' => true]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar la ubicación'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::update', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Eliminar ubicación
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
                $this->json(['success' => false, 'message' => 'ID de ubicación requerido'], 400);
                return;
            }

            // Verificar si tiene productos asociados
            $hasProducts = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM inventario WHERE id_ubicacion = ?",
                [$id]
            );

            if ($hasProducts && $hasProducts['total'] > 0) {
                $this->json(['success' => false, 'message' => 'No se puede eliminar una ubicación con productos en inventario'], 400);
                return;
            }

            $result = $this->ubicacionModel->delete($id);
            if ($result) {
                Logger::info('Ubicación eliminada exitosamente', ['id_ubicacion' => $id]);
                $this->json(['success' => true, 'message' => 'Ubicación eliminada correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar la ubicación'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::delete', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Ver detalles de una ubicación
     */
    public function details()
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->redirect('/ubicaciones');
                return;
            }

            $ubicacion = $this->ubicacionModel->find($id);
            if (!$ubicacion) {
                $this->setFlash('error', 'Ubicación no encontrada');
                $this->redirect('/ubicaciones');
                return;
            }

            $productos = $this->ubicacionModel->getProductos($id, 20);
            $stats = $this->ubicacionModel->getStats();

            $this->view('ubicaciones/view', [
                'ubicacion' => $ubicacion,
                'productos' => $productos,
                'stats' => $stats,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::view', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar la ubicación');
            $this->redirect('/ubicaciones');
        }
    }

    /**
     * Obtener ubicaciones para select (AJAX)
     */
    public function getForSelect()
    {
        try {
            $ubicaciones = $this->ubicacionModel->getForSelect();
            $this->json($ubicaciones);
        } catch (Exception $e) {
            Logger::error('Error en UbicacionController::getForSelect', [
                'error' => $e->getMessage()
            ]);
            $this->json(['error' => 'Error al obtener ubicaciones'], 500);
        }
    }

    /**
     * Métodos privados de apoyo
     */
    private function getFilteredUbicaciones($search, $tipo)
    {
        $sql = "SELECT u.*, 
                       COUNT(i.id_inventario) as total_productos,
                       COALESCE(SUM(i.stock_actual), 0) as stock_total
                FROM ubicaciones u
                LEFT JOIN inventario i ON u.id_ubicacion = i.id_ubicacion
                LEFT JOIN productos p ON i.id_producto = p.id_producto AND p.estado = 'activo'
                WHERE 1=1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (u.nombre LIKE ? OR u.descripcion LIKE ?)";
            $search = '%' . $search . '%';
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($tipo)) {
            $sql .= " AND u.tipo = ?";
            $params[] = $tipo;
        }

        $sql .= " GROUP BY u.id_ubicacion ORDER BY u.tipo ASC, u.nombre ASC";

        return $this->db->select($sql, $params);
    }
}
