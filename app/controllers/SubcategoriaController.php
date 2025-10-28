<?php

/**
 * Controlador Subcategoria - Gestión de subcategorías del sistema
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class SubcategoriaController extends Controller
{
    private $subcategoriaModel;
    private $categoriaModel;

    public function __construct()
    {
        parent::__construct();
        $this->subcategoriaModel = new Subcategoria();
        $this->categoriaModel = new Categoria();
    }

    /**
     * Mostrar listado de subcategorías
     */
    public function index()
    {
        try {
            $search = $_GET['search'] ?? '';
            $categoria = $_GET['categoria'] ?? '';

            if (!empty($search) || !empty($categoria)) {
                $subcategorias = $this->getFilteredSubcategorias($search, $categoria);
            } else {
                $subcategorias = $this->subcategoriaModel->getSubcategoriesWithCategory();
            }

            $categorias = $this->categoriaModel->active();

            $this->view('subcategorias/index', [
                'subcategorias' => $subcategorias,
                'categorias' => $categorias,
                'search' => $search,
                'categoria_selected' => $categoria,
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::index', [
                'error' => $e->getMessage()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar subcategorías']);
        }
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        $categorias = $this->categoriaModel->active();

        $this->view('subcategorias/form', [
            'subcategoria' => null,
            'categorias' => $categorias,
            'action' => '/subcategorias/store',
            'method' => 'POST',
            'title' => 'Nueva Subcategoría',
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
                $this->redirect('/subcategorias');
                return;
            }

            $subcategoria = $this->subcategoriaModel->find($id);
            if (!$subcategoria) {
                $this->setFlash('error', 'Subcategoría no encontrada');
                $this->redirect('/subcategorias');
                return;
            }

            $categorias = $this->categoriaModel->active();

            $this->view('subcategorias/form', [
                'subcategoria' => $subcategoria,
                'categorias' => $categorias,
                'action' => '/subcategorias/update',
                'method' => 'POST',
                'title' => 'Editar Subcategoría',
                'csrf_token' => $this->generateCSRF()
            ]);
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::edit', [
                'error' => $e->getMessage(),
                'id' => $id ?? null
            ]);
            $this->setFlash('error', 'Error al cargar la subcategoría');
            $this->redirect('/subcategorias');
        }
    }

    /**
     * Guardar nueva subcategoría
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
                'id_categoria' => 'required|numeric',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar que no exista subcategoría con el mismo nombre en la categoría
            $existing = $this->subcategoriaModel->whereOne(
                'nombre = ? AND id_categoria = ?',
                [$_POST['nombre'], $_POST['id_categoria']]
            );

            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya existe una subcategoría con ese nombre en esta categoría'], 400);
                return;
            }

            $subcategoriaData = $this->sanitize($_POST);
            $result = $this->subcategoriaModel->create($subcategoriaData);

            if ($result) {
                Logger::info('Subcategoría creada exitosamente', [
                    'id_subcategoria' => $result,
                    'nombre' => $subcategoriaData['nombre'],
                    'categoria' => $subcategoriaData['id_categoria']
                ]);
                $this->json(['success' => true, 'id' => $result]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al crear la subcategoría'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::store', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Actualizar subcategoría existente
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
                $this->json(['success' => false, 'message' => 'ID de subcategoría requerido'], 400);
                return;
            }

            $validationRules = [
                'nombre' => 'required|max:100',
                'id_categoria' => 'required|numeric',
                'descripcion' => 'max:255'
            ];

            $validation = $this->validate($_POST, $validationRules);
            if (!$validation['valid']) {
                $this->json(['success' => false, 'errors' => $validation['errors']], 400);
                return;
            }

            // Verificar duplicados
            $existing = $this->subcategoriaModel->whereOne(
                'nombre = ? AND id_categoria = ? AND id_subcategoria != ?',
                [$_POST['nombre'], $_POST['id_categoria'], $id]
            );

            if ($existing) {
                $this->json(['success' => false, 'message' => 'Ya existe una subcategoría con ese nombre en esta categoría'], 400);
                return;
            }

            $subcategoriaData = $this->sanitize($_POST);
            unset($subcategoriaData['id']);

            $result = $this->subcategoriaModel->update($id, $subcategoriaData);
            if ($result) {
                Logger::info('Subcategoría actualizada exitosamente', [
                    'id_subcategoria' => $id,
                    'nombre' => $subcategoriaData['nombre']
                ]);
                $this->json(['success' => true]);
            } else {
                $this->json(['success' => false, 'message' => 'Error al actualizar la subcategoría'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::update', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Eliminar subcategoría
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
                $this->json(['success' => false, 'message' => 'ID de subcategoría requerido'], 400);
                return;
            }

            // Verificar si tiene productos asociados
            $hasProducts = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos WHERE id_subcategoria = ? AND estado = 'activo'",
                [$id]
            );

            if ($hasProducts && $hasProducts['total'] > 0) {
                $this->json(['success' => false, 'message' => 'No se puede eliminar una subcategoría con productos asociados'], 400);
                return;
            }

            $result = $this->subcategoriaModel->delete($id);
            if ($result) {
                Logger::info('Subcategoría eliminada exitosamente', ['id_subcategoria' => $id]);
                $this->json(['success' => true, 'message' => 'Subcategoría eliminada correctamente']);
            } else {
                $this->json(['success' => false, 'message' => 'Error al eliminar la subcategoría'], 500);
            }
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::delete', [
                'error' => $e->getMessage(),
                'id' => $_POST['id'] ?? null
            ]);
            $this->json(['success' => false, 'message' => 'Error interno del servidor'], 500);
        }
    }

    /**
     * Obtener subcategorías por categoría (AJAX)
     */
    public function getByCategory()
    {
        try {
            $id_categoria = $_GET['id'] ?? null;
            if (!$id_categoria) {
                $this->json(['error' => 'ID de categoría requerido'], 400);
                return;
            }

            $subcategorias = $this->subcategoriaModel->getByCategory($id_categoria);
            $this->json($subcategorias);
        } catch (Exception $e) {
            Logger::error('Error en SubcategoriaController::getByCategory', [
                'error' => $e->getMessage(),
                'id_categoria' => $_GET['id'] ?? null
            ]);
            $this->json(['error' => 'Error al obtener subcategorías'], 500);
        }
    }

    /**
     * Métodos privados de apoyo
     */
    private function getFilteredSubcategorias($search, $categoria)
    {
        $sql = "SELECT s.*, c.nombre as categoria_nombre,
                       COUNT(p.id_producto) as total_productos
                FROM subcategorias s
                JOIN categorias c ON s.id_categoria = c.id_categoria
                LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
                WHERE 1=1";

        $params = [];

        if (!empty($search)) {
            $sql .= " AND (s.nombre LIKE ? OR s.descripcion LIKE ?)";
            $search = '%' . $search . '%';
            $params = array_merge($params, [$search, $search]);
        }

        if (!empty($categoria)) {
            $sql .= " AND s.id_categoria = ?";
            $params[] = $categoria;
        }

        $sql .= " GROUP BY s.id_subcategoria ORDER BY c.nombre ASC, s.nombre ASC";

        return $this->db->select($sql, $params);
    }
}
