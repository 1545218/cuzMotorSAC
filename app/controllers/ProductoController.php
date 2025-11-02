<?php

/**
 * Controlador de Productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class ProductoController extends Controller
{
    public function toggleEstado()
    {
        $this->requireRole(['admin']);
        $id = $_GET['id'] ?? 0;
        $estado = $_GET['estado'] ?? '';
        if (!$id || ($estado !== 'activo' && $estado !== 'inactivo')) {
            $this->setFlash('error', 'Datos inválidos para cambiar estado');
            $this->redirect('?page=productos');
            return;
        }
        try {
            $this->productoModel->cambiarEstado($id, $estado);
            $this->setFlash('success', 'Estado actualizado correctamente');
        } catch (Exception $e) {
            $this->setFlash('error', 'Error al actualizar estado: ' . $e->getMessage());
        }
        $this->redirect('?page=productos');
    }
    private $productoModel;
    private $categoriaModel;
    private $subcategoriaModel;
    private $marcaModel;
    private $ubicacionModel;
    private $unidadModel;
    private $inventarioModel;

    public function __construct()
    {
        parent::__construct();

        try {
            $this->productoModel = new Producto();
            $this->categoriaModel = new Categoria();
            $this->subcategoriaModel = new Subcategoria();
            $this->marcaModel = new Marca();
            $this->ubicacionModel = new Ubicacion();
            $this->unidadModel = new Unidad();
            $this->inventarioModel = new Inventario();

            Logger::debug("ProductoController inicializado correctamente");
        } catch (Exception $e) {
            Logger::error("Error al inicializar ProductoController: " . $e->getMessage());
            throw $e;
        }
    }

    public function index()
    {
        $search = $_GET['search'] ?? '';
        $categoria = $_GET['categoria'] ?? '';
        $subcategoria = $_GET['subcategoria'] ?? '';
        $marca = $_GET['marca'] ?? '';
        $estado = $_GET['estado'] ?? 'activo';
        $stock_filter = $_GET['stock_filter'] ?? '';

        // Construir filtros para el modelo
        $filters = [
            'categoria' => $categoria,
            'subcategoria' => $subcategoria,
            'marca' => $marca,
            'estado' => $estado
        ];
        
        // Filtros de stock mejorados
        if ($stock_filter === 'bajo') $filters['stock_bajo'] = true;
        if ($stock_filter === 'sin_stock') $filters['sin_stock'] = true;
        if ($stock_filter === 'disponible') $filters['con_stock'] = true;

        $productos = $this->productoModel->searchProducts($search, $filters);

        $categorias = $this->categoriaModel->getForSelect();
        $subcategorias = $this->subcategoriaModel->getSubcategoriesWithCategory();
        $marcas = $this->marcaModel->getAll();

        // Determinar título según filtro
        $titulo = 'Gestión de Productos';
        if ($stock_filter === 'disponible') $titulo = 'Productos en Stock';
        elseif ($stock_filter === 'bajo') $titulo = 'Productos con Stock Bajo';
        elseif ($stock_filter === 'sin_stock') $titulo = 'Productos sin Stock';

        $this->view('productos/index', [
            'productos' => $productos,
            'categorias' => $categorias,
            'subcategorias' => $subcategorias,
            'marcas' => $marcas,
            'search' => $search,
            'categoria_selected' => $categoria,
            'subcategoria_selected' => $subcategoria,
            'marca_selected' => $marca,
            'estado_selected' => $estado,
            'stock_filter_selected' => $stock_filter,
            'titulo' => $titulo
        ]);
    }

    public function create()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                // determinar id_unidad: usar valor enviado, sino usar primer id disponible o crear una por defecto
                $defaultUnidad = null;
                $unidadesList = $this->unidadModel->getForSelect();
                if (!empty($unidadesList)) {
                    $defaultUnidad = $unidadesList[0]['id_unidad'];
                } else {
                    // Intentar crear una unidad por defecto si no existen unidades
                    try {
                        $this->unidadModel->createUnidad(['nombre' => 'Unidad']);
                        $unidadesList = $this->unidadModel->getForSelect();
                        if (!empty($unidadesList)) $defaultUnidad = $unidadesList[0]['id_unidad'];
                    } catch (Exception $e) {
                        Logger::warning('No se pudo crear unidad por defecto: ' . $e->getMessage());
                    }
                }

                $id_subcategoria = isset($_POST['id_subcategoria']) && $_POST['id_subcategoria'] !== '' ? (int)$_POST['id_subcategoria'] : null;
                $id_categoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;

                // Manejar ausencia de subcategorías
                if (empty($id_subcategoria) && $id_categoria) {
                    $subs = $this->subcategoriaModel->getByCategory($id_categoria);
                    if (!empty($subs)) {
                        $id_subcategoria = (int)$subs[0]['id_subcategoria'];
                    } else {
                        Logger::info('No se encontraron subcategorías para la categoría con ID: ' . $id_categoria);
                        try {
                            $newId = $this->subcategoriaModel->createSubcategory([
                                'id_categoria' => $id_categoria,
                                'nombre' => 'General',
                                'descripcion' => 'Subcategoría general creada automáticamente'
                            ]);
                            if ($newId) {
                                $id_subcategoria = (int)$newId;
                                Logger::info('Subcategoría "General" creada automáticamente con ID: ' . $newId);
                            }
                        } catch (Exception $e) {
                            Logger::error('Error al crear subcategoría "General": ' . $e->getMessage());
                            $id_subcategoria = null; // Evitar bloqueos
                        }
                    }
                }

                $_POST['id_subcategoria'] = $id_subcategoria;

                $data = [
                    'nombre' => $_POST['nombre'],
                    'descripcion' => $_POST['descripcion'] ?? null,
                    'codigo_barras' => $_POST['codigo_barras'] ?? null,
                    'precio_unitario' => (float)($_POST['precio_unitario'] ?? 0),
                    'stock_actual' => (int)($_POST['stock_actual'] ?? 0),
                    'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
                    'id_subcategoria' => $id_subcategoria,
                    'id_marca' => (int)($_POST['id_marca'] ?? 0) ?: null,
                    'id_ubicacion' => (int)($_POST['id_ubicacion'] ?? 0) ?: null,
                    'id_unidad' => isset($_POST['id_unidad']) && $_POST['id_unidad'] !== '' ? (int)$_POST['id_unidad'] : $defaultUnidad,
                    'estado' => 'activo'
                ];

                // Si id_unidad sigue sin valor válido, lanzar excepción amigable para evitar error SQL 1048
                if (empty($data['id_unidad'])) {
                    throw new Exception('No existe una unidad válida. Por favor cree al menos una unidad en el sistema.');
                }

                $id = $this->productoModel->create($data);
                Logger::info("Producto creado exitosamente", [
                    'producto_id' => $id,
                    'nombre' => $data['nombre'],
                    'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
                ]);
                $this->setFlash('success', 'Producto creado exitosamente');
                $this->redirect('?page=productos');
            } catch (Exception $e) {
                Logger::error("Error al crear producto: " . $e->getMessage(), [
                    'data' => $data ?? [],
                    'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
                ]);
                $this->setFlash('error', $e->getMessage());
            }
        }

        $id_categoria = $_GET['categoria'] ?? null;
        $subcategorias = $this->subcategoriaModel->getByCategory($id_categoria) ?? [];

        $this->view('productos/create', [
            'categorias' => $this->categoriaModel->getForSelect(),
            'marcas' => $this->marcaModel->getForSelect(),
            'ubicaciones' => $this->ubicacionModel->getForSelect(),
            'unidades' => $this->unidadModel->getForSelect(),
            'subcategorias' => $subcategorias,
            'subcategoria_selected' => $id_subcategoria ?? null
        ]);
    }

    public function store()
    {
        // Alias para create() cuando se usa POST
        $this->create();
    }

    public function show($id)
    {
        $producto = $this->productoModel->getProductoCompleto($id);
        if (!$producto) {
            $this->redirect('/productos', 'error', 'Producto no encontrado');
        }

        // Intentar obtener movimientos desde el modelo de Inventario.
        // Si falla (por ejemplo porque la tabla `movimientos_inventario` no existe),
        // usamos un fallback seguro que lee desde `registrosstock` (tabla presente en el dump).
        $movimientos = [];
        try {
            $movimientos = $this->inventarioModel->getMovimientos($id);
            if (!is_array($movimientos)) $movimientos = [];
        } catch (Exception $e) {
            // Fallback: leer registros desde registrosstock
            try {
                $movimientos = $this->db->select(
                    "SELECT rs.*, u.nombre as usuario_nombre
                     FROM registrosstock rs
                     LEFT JOIN usuarios u ON rs.id_usuario = u.id_usuario
                     WHERE rs.id_producto = ?
                     ORDER BY rs.fecha DESC
                     LIMIT 50",
                    [$id]
                );
            } catch (Exception $ex) {
                $movimientos = [];
            }
        }

        $this->view('productos/view', ['producto' => $producto, 'movimientos' => $movimientos]);
    }

    public function edit($id)
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            $this->redirect('/productos', 'error', 'Producto no encontrado');
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $defaultUnidad = null;
                $unidadesList = $this->unidadModel->getForSelect();
                if (!empty($unidadesList)) {
                    $defaultUnidad = $unidadesList[0]['id_unidad'];
                } else {
                    try {
                        $this->unidadModel->createUnidad(['nombre' => 'Unidad']);
                        $unidadesList = $this->unidadModel->getForSelect();
                        if (!empty($unidadesList)) $defaultUnidad = $unidadesList[0]['id_unidad'];
                    } catch (Exception $e) {
                        Logger::warning('No se pudo crear unidad por defecto: ' . $e->getMessage());
                    }
                }

                $id_subcategoria = isset($_POST['id_subcategoria']) && $_POST['id_subcategoria'] !== '' ? (int)$_POST['id_subcategoria'] : null;
                $id_categoria = isset($_POST['id_categoria']) && $_POST['id_categoria'] !== '' ? (int)$_POST['id_categoria'] : null;

                // Si no se proporcionó subcategoría, dejarla como null (opcional)
                if (empty($id_subcategoria)) {
                    $id_subcategoria = null;
                }

                $data = [
                    'nombre' => $_POST['nombre'],
                    'descripcion' => $_POST['descripcion'] ?? null,
                    'codigo_barras' => $_POST['codigo_barras'] ?? null,
                    'precio_unitario' => (float)($_POST['precio_unitario'] ?? 0),
                    'stock_actual' => (int)($_POST['stock_actual'] ?? 0),
                    'stock_minimo' => (int)($_POST['stock_minimo'] ?? 0),
                    'id_subcategoria' => $id_subcategoria,
                    'id_marca' => (int)($_POST['id_marca'] ?? 0) ?: null,
                    'id_ubicacion' => (int)($_POST['id_ubicacion'] ?? 0) ?: null,
                    'id_unidad' => isset($_POST['id_unidad']) && $_POST['id_unidad'] !== '' ? (int)$_POST['id_unidad'] : $defaultUnidad,
                    'estado' => $_POST['estado']
                ];

                if (empty($data['id_unidad'])) {
                    throw new Exception('No existe una unidad válida. Por favor cree al menos una unidad en el sistema.');
                }

                // Manejar actualización de stock de forma segura: si el stock cambia, usar updateStock
                $postedStock = isset($_POST['stock_actual']) ? (int)$_POST['stock_actual'] : (int)($producto['stock_actual'] ?? 0);
                $oldStock = (int)($producto['stock_actual'] ?? 0);

                if ($postedStock !== $oldStock) {
                    // No actualizar stock directamente en el update para que el flujo de actualización
                    // y registro quede centralizado en updateStock
                    unset($data['stock_actual']);
                }

                $this->productoModel->update($id, $data);

                // Registrar ajuste de stock si aplica
                if ($postedStock !== $oldStock) {
                    try {
                        $userId = $_SESSION['user_id'] ?? ($this->auth->getUser()['id'] ?? null);
                        $this->productoModel->updateStock($id, $postedStock, 'Edición de producto', $userId);
                    } catch (Exception $e) {
                        Logger::error("Error al actualizar stock via updateStock: " . $e->getMessage());
                        // No detener el flujo: el producto ya fue actualizado en campos no-stock
                    }
                }

                Logger::info("Producto actualizado exitosamente", [
                    'producto_id' => $id,
                    'nombre' => $data['nombre'],
                    'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
                ]);
                $this->setFlash('success', 'Producto actualizado exitosamente');
                $this->redirect('?page=productos&action=view&id=' . $id);
            } catch (Exception $e) {
                Logger::error("Error al actualizar producto: " . $e->getMessage(), [
                    'producto_id' => $id,
                    'data' => $data ?? [],
                    'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
                ]);
                $this->setFlash('error', $e->getMessage());
            }
        }

        $this->view('productos/edit', [
            'producto' => $producto,
            'categorias' => $this->categoriaModel->getForSelect(),
            'marcas' => $this->marcaModel->getForSelect(),
            'ubicaciones' => $this->ubicacionModel->getForSelect(),
            'unidades' => $this->unidadModel->getForSelect(),
            // Obtener stock actual desde Inventario (fuente autorizada: registrosstock)
            'stock_actual' => (function () use ($id, $producto) {
                try {
                    $inv = new Inventario();
                    $s = $inv->getStockActual($id);
                    return is_numeric($s) ? (int)$s : ($producto['stock_actual'] ?? 0);
                } catch (Exception $e) {
                    // fallback al valor almacenado en productos
                    return $producto['stock_actual'] ?? 0;
                }
            })()
        ]);
    }

    public function update()
    {
        $id = $_GET['id'] ?? $_POST['id'] ?? 0;
        $this->edit($id); // Redirigir al método edit que ya maneja POST
    }

    public function delete($id)
    {
        $this->requireRole(['admin']);
        $this->validateCSRF();

        try {
            $producto = $this->productoModel->find($id);
            if (!$this->productoModel->canDelete($id)) {
                throw new Exception('No se puede eliminar el producto porque tiene movimientos registrados');
            }

            $this->productoModel->delete($id);
            Logger::warning("Producto eliminado", [
                'producto_id' => $id,
                'nombre' => $producto['nombre'] ?? 'unknown',
                'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
            ]);
            $this->setFlash('success', 'Producto eliminado exitosamente');
        } catch (Exception $e) {
            Logger::error("Error al eliminar producto: " . $e->getMessage(), [
                'producto_id' => $id,
                'user' => $this->auth->getUser()['usuario'] ?? 'unknown'
            ]);
            $this->setFlash('error', $e->getMessage());
        }

        $this->redirect('/productos');
    }

    public function getSubcategorias($categoriaId)
    {
        header('Content-Type: application/json');
        try {
            $data = $this->subcategoriaModel->getByCategory($categoriaId);
            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(['success' => false, 'error' => 'Error al obtener subcategorías', 'details' => $e->getMessage()]);
        }
    }

    public function searchAjax()
    {
        header('Content-Type: application/json');
        $term = $_GET['term'] ?? '';
        $productos = $this->productoModel->searchForSelect($term);
        echo json_encode($productos);
    }
}
