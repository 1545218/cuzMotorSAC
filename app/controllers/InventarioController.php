<?php

class InventarioController extends Controller
{
    private $inventarioModel;
    private $productoModel;

    public function __construct()
    {
        parent::__construct();
        $this->inventarioModel = new Inventario();
        $this->productoModel = new Producto();

        // Verificar autenticación para todas las acciones
        Auth::requireAuth();
    }
    
    /**
     * Validar token CSRF para endpoints AJAX
     */
    private function validateCSRFToken()
    {
        $csrfName = defined('CSRF_TOKEN_NAME') ? CSRF_TOKEN_NAME : 'csrf_token';
        $receivedToken = $_POST[$csrfName] ?? '';
        $sessionToken = $_SESSION['csrf_token'] ?? '';
        
        return !empty($receivedToken) && !empty($sessionToken) && hash_equals($sessionToken, $receivedToken);
    }

    /**
     * Mostrar formulario de movimiento (entrada/salida/ajuste) para un producto específico
     */
    public function movimiento($id = 0)
    {
        $producto = $this->productoModel->find($id);
        if (!$producto) {
            $this->setFlash('error', 'Producto no encontrado');
            $this->redirect('?page=productos');
            return;
        }

        $this->view('inventario/movimiento', [
            'producto' => $producto,
            'title' => 'Registrar movimiento'
        ]);
    }

    /**
     * Mostrar vista de producto en contexto de inventario (historial y acciones)
     */
    public function producto($id = 0)
    {
        $producto = $this->productoModel->getProductoCompleto($id);
        if (!$producto) {
            $this->setFlash('error', 'Producto no encontrado');
            $this->redirect('?page=productos');
            return;
        }

        $movimientos = [];
        try {
            $movimientos = $this->inventarioModel->getMovimientos($id);
        } catch (Exception $e) {
            $movimientos = [];
        }

        $this->view('inventario/producto', [
            'producto' => $producto,
            'movimientos' => $movimientos,
            'title' => 'Producto - Inventario'
        ]);
    }

    /**
     * Mostrar resumen del inventario
     */
    public function index()
    {
        try {
            // Inventario súper simple - solo mostrar la vista
            $this->view('inventario/index', [
                'title' => 'Gestión de Inventario'
            ]);
        } catch (Exception $e) {
            $this->view('errors/500', ['error' => 'Error al cargar inventario']);
        }
    }

    /**
     * Registrar entrada de productos al inventario
     */
    public function registrarEntrada()
    {
        // Limpiar cualquier salida previa y establecer headers JSON
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar CSRF si existe
            if (!$this->validateCSRFToken()) {
                throw new Exception('Token de seguridad inválido');
            }

            $producto_id = (int)($_POST['producto_id'] ?? 0);
            $cantidad = (int)($_POST['cantidad'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? 'Entrada manual');
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            if ($cantidad <= 0) {
                throw new Exception('La cantidad debe ser mayor a 0');
            }

            // Obtener producto actual
            $producto = $this->productoModel->obtenerPorId($producto_id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            $stock_anterior = (int)($producto['stock_actual'] ?? 0);
            $stock_nuevo = $stock_anterior + $cantidad;

            // Actualizar stock del producto
            if (!$this->productoModel->actualizarStock($producto_id, $stock_nuevo)) {
                throw new Exception('Error al actualizar stock del producto');
            }

            // Registrar movimiento
            $movimiento_data = [
                'producto_id' => $producto_id,
                'tipo_movimiento' => 'entrada',
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'usuario_id' => $_SESSION['user_id'] ?? 1
            ];

            if (!$this->inventarioModel->registrarMovimiento($movimiento_data)) {
                throw new Exception('Error al registrar el movimiento');
            }
            
            Logger::info("Entrada registrada: Producto {$producto_id}, Cantidad {$cantidad}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Entrada registrada correctamente',
                'stock_nuevo' => $stock_nuevo
            ]);
            
        } catch (Exception $e) {
            Logger::error("Error en registrarEntrada: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Registrar salida de productos del inventario
     */
    public function registrarSalida()
    {
        // Limpiar cualquier salida previa y establecer headers JSON
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar CSRF si existe
            if (!$this->validateCSRFToken()) {
                throw new Exception('Token de seguridad inválido');
            }

            $producto_id = (int)($_POST['producto_id'] ?? 0);
            $cantidad = (int)($_POST['cantidad'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? 'Salida manual');
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            if ($cantidad <= 0) {
                throw new Exception('La cantidad debe ser mayor a 0');
            }

            // Obtener producto actual
            $producto = $this->productoModel->obtenerPorId($producto_id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            $stock_anterior = (int)($producto['stock_actual'] ?? 0);

            // Verificar stock suficiente
            if ($stock_anterior < $cantidad) {
                throw new Exception("Stock insuficiente. Disponible: {$stock_anterior}, Solicitado: {$cantidad}");
            }

            $stock_nuevo = $stock_anterior - $cantidad;

            // Actualizar stock del producto
            if (!$this->productoModel->actualizarStock($producto_id, $stock_nuevo)) {
                throw new Exception('Error al actualizar stock del producto');
            }

            // Registrar movimiento
            $movimiento_data = [
                'producto_id' => $producto_id,
                'tipo_movimiento' => 'salida',
                'cantidad' => $cantidad,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $stock_nuevo,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'usuario_id' => $_SESSION['user_id'] ?? 1
            ];

            if (!$this->inventarioModel->registrarMovimiento($movimiento_data)) {
                throw new Exception('Error al registrar el movimiento');
            }
            
            Logger::info("Salida registrada: Producto {$producto_id}, Cantidad {$cantidad}");
            
            // Verificar si quedó en stock bajo
            $alerta_stock = '';
            if (isset($producto['stock_minimo']) && $stock_nuevo <= (int)$producto['stock_minimo']) {
                $alerta_stock = "⚠️ ALERTA: El producto quedó con stock bajo ({$stock_nuevo} unidades)";
            }
            
            echo json_encode([
                'success' => true, 
                'message' => 'Salida registrada correctamente',
                'stock_nuevo' => $stock_nuevo,
                'alerta' => $alerta_stock
            ]);
            
        } catch (Exception $e) {
            Logger::error("Error en registrarSalida: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Registrar ajuste de inventario
     */
    public function registrarAjuste()
    {
        // Limpiar cualquier salida previa y establecer headers JSON
        ob_clean();
        header('Content-Type: application/json; charset=utf-8');
        
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar CSRF si existe
            if (!$this->validateCSRFToken()) {
                throw new Exception('Token de seguridad inválido');
            }

            $producto_id = (int)($_POST['producto_id'] ?? 0);
            $cantidad = (int)($_POST['cantidad'] ?? 0);
            $motivo = trim($_POST['motivo'] ?? 'Ajuste de inventario');
            $observaciones = trim($_POST['observaciones'] ?? '');

            // Validaciones
            if ($producto_id <= 0) {
                throw new Exception('ID de producto inválido');
            }
            
            if ($cantidad < 0) {
                throw new Exception('La cantidad no puede ser negativa');
            }

            // Obtener producto actual
            $producto = $this->productoModel->obtenerPorId($producto_id);
            if (!$producto) {
                throw new Exception('Producto no encontrado');
            }

            $stock_anterior = (int)($producto['stock_actual'] ?? 0);
            $diferencia = $cantidad - $stock_anterior;

            // Si no hay cambio, no hacer nada
            if ($diferencia == 0) {
                echo json_encode([
                    'success' => true, 
                    'message' => 'No hay cambios en el stock',
                    'stock_nuevo' => $stock_anterior
                ]);
                exit;
            }

            // Actualizar stock del producto
            if (!$this->productoModel->actualizarStock($producto_id, $cantidad)) {
                throw new Exception('Error al actualizar stock del producto');
            }

            // Registrar movimiento
            $tipo_movimiento = $diferencia > 0 ? 'entrada' : 'salida';
            $cantidad_movimiento = abs($diferencia);

            $movimiento_data = [
                'producto_id' => $producto_id,
                'tipo_movimiento' => 'ajuste',
                'cantidad' => $cantidad_movimiento,
                'stock_anterior' => $stock_anterior,
                'stock_nuevo' => $cantidad,
                'motivo' => $motivo,
                'observaciones' => $observaciones,
                'usuario_id' => $_SESSION['user_id'] ?? 1
            ];

            if (!$this->inventarioModel->registrarMovimiento($movimiento_data)) {
                throw new Exception('Error al registrar el movimiento');
            }
            
            Logger::info("Ajuste registrado: Producto {$producto_id}, Stock anterior {$stock_anterior}, Stock nuevo {$cantidad}");
            
            echo json_encode([
                'success' => true, 
                'message' => 'Ajuste registrado correctamente',
                'stock_nuevo' => $cantidad,
                'diferencia' => $diferencia
            ]);
            
        } catch (Exception $e) {
            Logger::error("Error en registrarAjuste: " . $e->getMessage());
            echo json_encode([
                'success' => false, 
                'error' => $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Mostrar alertas de stock bajo
     */
    public function alertas()
    {
        try {
            $alertas = [];
            $correos_notificacion = [];
            try {
                $alertas = $this->inventarioModel->getAlertasStockBajo();
                if (!is_array($alertas)) {
                    $alertas = [];
                }
                // Obtener correos de notificación
                require_once APP_PATH . '/models/NotificacionCorreo.php';
                $correoModel = new NotificacionCorreo();
                // Intentar obtener correos desde el modelo
                try {
                    $correos_notificacion = $correoModel->getAll();
                    // Registrar en logs para depuración sin afectar la ejecución
                    if (class_exists('Logger')) {
                        Logger::info('InventarioController::alertas - NotificacionCorreo::getAll returned count=' . (is_array($correos_notificacion) ? count($correos_notificacion) : 'null'));
                    }
                } catch (Exception $e) {
                    $correos_notificacion = [];
                    if (class_exists('Logger')) {
                        Logger::error('InventarioController::alertas - Error al obtener correos desde modelo: ' . $e->getMessage());
                    }
                }

                // Si el array viene vacío, realizar una consulta directa a la DB para verificar
                if (empty($correos_notificacion)) {
                    try {
                        if (class_exists('Database')) {
                            $db = Database::getInstance()->getConnection();
                            $stmt = $db->prepare('SELECT id, email FROM notificacion_correos ORDER BY id ASC');
                            $stmt->execute();
                            $direct = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            if (class_exists('Logger')) {
                                Logger::info('InventarioController::alertas - direct DB query returned count=' . (is_array($direct) ? count($direct) : 'null'));
                                Logger::info('InventarioController::alertas - direct DB sample: ' . json_encode(array_slice($direct, 0, 5)));
                            }
                            // Si la consulta directa retorna datos, asignarlos para que la vista muestre
                            if (!empty($direct) && is_array($direct)) {
                                $correos_notificacion = $direct;
                            }
                        }
                    } catch (Exception $ex) {
                        if (class_exists('Logger')) {
                            Logger::error('InventarioController::alertas - Error en consulta directa: ' . $ex->getMessage());
                        }
                    }
                }
            } catch (Exception $ex) {
                Logger::error("Error seguro obteniendo alertas de stock bajo: " . $ex->getMessage());
                $alertas = [];
                $correos_notificacion = [];
            }
            $this->view('inventario/alertas', [
                'title' => 'Alertas de Stock',
                'alertas' => $alertas,
                'correos_notificacion' => $correos_notificacion
            ]);
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::alertas - " . $e->getMessage());
            $this->view('inventario/alertas', [
                'title' => 'Alertas de Stock',
                'alertas' => [],
                'correos_notificacion' => []
            ]);
        }
    }

    /**
     * Mostrar historial de movimientos
     */
    public function movimientos()
    {
        try {
            $movimientos = $this->inventarioModel->getMovimientos();

            // Si es una petición AJAX para DataTables
            if (isset($_GET['draw'])) {
                $this->handleMovimientosDataTables($movimientos);
                return;
            }

            $this->view('inventario/movimientos', [
                'title' => 'Movimientos de Inventario',
                'movimientos' => $movimientos
            ]);
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::movimientos - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar movimientos']);
        }
    }

    /**
     * Registrar entrada de inventario
     */
    public function entrada()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores y almacén pueden registrar entradas
            if (!in_array($_SESSION['user_role'], ['admin', 'almacen'])) {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            // Validar datos
            $errors = $this->validateMovimientoData($_POST, 'entrada');
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            $producto = $this->productoModel->find($_POST['producto_id']);
            if (!$producto) {
                echo json_encode(['success' => false, 'errors' => ['producto_id' => 'Producto no encontrado']]);
                return;
            }

            // Preparar datos del movimiento
            $movimientoData = [
                'producto_id' => $_POST['producto_id'],
                'tipo_movimiento' => 'entrada',
                'cantidad' => $_POST['cantidad'],
                'precio_unitario' => $_POST['precio_unitario'] ?? null,
                'motivo' => trim($_POST['motivo']),
                'referencia' => trim($_POST['referencia'] ?? ''),
                'user_id' => $_SESSION['user_id'],
                'fecha' => date('Y-m-d H:i:s')
            ];

            $result = $this->inventarioModel->registrarMovimiento($movimientoData);

            if ($result) {
                Logger::info("Entrada de inventario registrada - Producto: {$producto['nombre']}, Cantidad: {$_POST['cantidad']} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Entrada registrada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al registrar entrada']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::entrada - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Registrar salida de inventario
     */
    public function salida()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Todos los usuarios autenticados pueden registrar salidas

            // Validar datos
            $errors = $this->validateMovimientoData($_POST, 'salida');
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            $producto = $this->productoModel->find($_POST['producto_id']);
            if (!$producto) {
                echo json_encode(['success' => false, 'errors' => ['producto_id' => 'Producto no encontrado']]);
                return;
            }

            // Verificar stock disponible
            $stockActual = $this->inventarioModel->getStockActual($_POST['producto_id']);
            if ($stockActual < $_POST['cantidad']) {
                echo json_encode(['success' => false, 'errors' => ['cantidad' => 'Stock insuficiente. Stock actual: ' . $stockActual]]);
                return;
            }

            // Preparar datos del movimiento
            $movimientoData = [
                'producto_id' => $_POST['producto_id'],
                'tipo_movimiento' => 'salida',
                'cantidad' => $_POST['cantidad'],
                'precio_unitario' => $_POST['precio_unitario'] ?? null,
                'motivo' => trim($_POST['motivo']),
                'referencia' => trim($_POST['referencia'] ?? ''),
                'user_id' => $_SESSION['user_id'],
                'fecha' => date('Y-m-d H:i:s')
            ];

            $result = $this->inventarioModel->registrarMovimiento($movimientoData);

            if ($result) {
                Logger::info("Salida de inventario registrada - Producto: {$producto['nombre']}, Cantidad: {$_POST['cantidad']} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Salida registrada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al registrar salida']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::salida - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Ajustar stock de un producto
     */
    public function ajuste()
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores pueden hacer ajustes
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'errors' => ['general' => 'No tienes permisos para esta acción']]);
                return;
            }

            // Validar datos
            $errors = $this->validateAjusteData($_POST);
            if (!empty($errors)) {
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            $producto = $this->productoModel->find($_POST['producto_id']);
            if (!$producto) {
                echo json_encode(['success' => false, 'errors' => ['producto_id' => 'Producto no encontrado']]);
                return;
            }

            $stockActual = $this->inventarioModel->getStockActual($_POST['producto_id']);
            $nuevoStock = $_POST['nuevo_stock'];
            $diferencia = $nuevoStock - $stockActual;

            if ($diferencia == 0) {
                echo json_encode(['success' => false, 'errors' => ['nuevo_stock' => 'El nuevo stock es igual al actual']]);
                return;
            }

            // Preparar datos del movimiento
            $movimientoData = [
                'producto_id' => $_POST['producto_id'],
                'tipo_movimiento' => 'ajuste',
                'cantidad' => abs($diferencia),
                'precio_unitario' => null,
                'motivo' => trim($_POST['motivo']),
                'referencia' => 'Ajuste de stock',
                'user_id' => $_SESSION['user_id'],
                'fecha' => date('Y-m-d H:i:s'),
                'observaciones' => "Stock anterior: {$stockActual}, Stock nuevo: {$nuevoStock}"
            ];

            $result = $this->inventarioModel->registrarAjuste($movimientoData, $nuevoStock);

            if ($result) {
                Logger::info("Ajuste de stock registrado - Producto: {$producto['nombre']}, Stock anterior: {$stockActual}, Stock nuevo: {$nuevoStock} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Ajuste registrado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'errors' => ['general' => 'Error al registrar ajuste']]);
            }
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::ajuste - " . $e->getMessage());
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Obtener stock actual de un producto
     */
    public function getStock($productoId)
    {
        try {
            $stock = $this->inventarioModel->getStockActual($productoId);
            $producto = $this->productoModel->find($productoId);

            if (!$producto) {
                echo json_encode(['success' => false, 'message' => 'Producto no encontrado']);
                return;
            }

            echo json_encode([
                'success' => true,
                'stock' => $stock,
                'producto' => $producto['nombre'],
                'stock_minimo' => $producto['stock_minimo']
            ]);
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::getStock - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener stock']);
        }
    }

    /**
     * Obtener productos con stock bajo
     */
    public function getProductosStockBajo()
    {
        try {
            $productos = $this->inventarioModel->getProductosStockBajo();
            echo json_encode(['success' => true, 'data' => $productos]);
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::getProductosStockBajo - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener productos con stock bajo']);
        }
    }

    /**
     * Generar reporte de inventario
     */
    public function reporte()
    {
        try {
            $tipo = $_GET['tipo'] ?? 'general';
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

            switch ($tipo) {
                case 'movimientos':
                    $data = $this->inventarioModel->getReporteMovimientos($fechaInicio, $fechaFin);
                    break;
                case 'stock_bajo':
                    $data = $this->inventarioModel->getProductosStockBajo();
                    break;
                case 'valorizado':
                    $data = $this->inventarioModel->getInventarioValorizado();
                    break;
                default:
                    $data = $this->inventarioModel->getResumenGeneral();
            }

            echo json_encode(['success' => true, 'data' => $data]);
        } catch (Exception $e) {
            Logger::error("Error en InventarioController::reporte - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al generar reporte']);
        }
    }

    /**
     * Validar datos de movimiento
     */
    private function validateMovimientoData($data, $tipo)
    {
        $errors = [];

        // Validar producto
        if (empty($data['producto_id'])) {
            $errors['producto_id'] = 'El producto es obligatorio';
        }

        // Validar cantidad
        if (empty($data['cantidad'])) {
            $errors['cantidad'] = 'La cantidad es obligatoria';
        } elseif (!is_numeric($data['cantidad']) || $data['cantidad'] <= 0) {
            $errors['cantidad'] = 'La cantidad debe ser un número positivo';
        }

        // Validar motivo
        if (empty($data['motivo'])) {
            $errors['motivo'] = 'El motivo es obligatorio';
        } elseif (strlen($data['motivo']) < 5) {
            $errors['motivo'] = 'El motivo debe tener al menos 5 caracteres';
        }

        // Validar precio unitario (opcional para algunas operaciones)
        if (!empty($data['precio_unitario'])) {
            if (!is_numeric($data['precio_unitario']) || $data['precio_unitario'] < 0) {
                $errors['precio_unitario'] = 'El precio debe ser un número válido';
            }
        }

        return $errors;
    }

    /**
     * Validar datos de ajuste
     */
    private function validateAjusteData($data)
    {
        $errors = [];

        // Validar producto
        if (empty($data['producto_id'])) {
            $errors['producto_id'] = 'El producto es obligatorio';
        }

        // Validar nuevo stock
        if (!isset($data['nuevo_stock']) || $data['nuevo_stock'] === '') {
            $errors['nuevo_stock'] = 'El nuevo stock es obligatorio';
        } elseif (!is_numeric($data['nuevo_stock']) || $data['nuevo_stock'] < 0) {
            $errors['nuevo_stock'] = 'El nuevo stock debe ser un número positivo o cero';
        }

        // Validar motivo
        if (empty($data['motivo'])) {
            $errors['motivo'] = 'El motivo es obligatorio';
        } elseif (strlen($data['motivo']) < 5) {
            $errors['motivo'] = 'El motivo debe tener al menos 5 caracteres';
        }

        return $errors;
    }

    /**
     * Manejar peticiones AJAX para DataTables de inventario
     */
    private function handleDataTablesRequest($productos)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $productos = array_filter($productos, function ($producto) use ($search) {
                return stripos($producto['nombre'], $search) !== false ||
                    stripos($producto['codigo'], $search) !== false ||
                    stripos($producto['categoria'], $search) !== false ||
                    stripos($producto['marca'], $search) !== false;
            });
        }

        $totalRecords = count($productos);
        $productos = array_slice($productos, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($productos as $producto) {
            $stockStatus = '';
            if ($producto['stock_actual'] <= 0) {
                $stockStatus = '<span class="badge bg-danger">Sin Stock</span>';
            } elseif ($producto['stock_actual'] <= $producto['stock_minimo']) {
                $stockStatus = '<span class="badge bg-warning">Stock Bajo</span>';
            } else {
                $stockStatus = '<span class="badge bg-success">Stock OK</span>';
            }

            $actions = '
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="registrarEntrada(' . $producto['id'] . ')" title="Entrada">
                        <i class="fas fa-plus"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="registrarSalida(' . $producto['id'] . ')" title="Salida">
                        <i class="fas fa-minus"></i>
                    </button>';

            // Solo administradores pueden ajustar
            if ($_SESSION['user_role'] === 'admin') {
                $actions .= '
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="ajustarStock(' . $producto['id'] . ')" title="Ajustar">
                        <i class="fas fa-edit"></i>
                    </button>';
            }

            $actions .= '</div>';

            $data[] = [
                $producto['codigo'],
                $producto['nombre'],
                $producto['categoria'] ?? '',
                $producto['marca'] ?? '',
                number_format($producto['stock_actual'], 0),
                number_format($producto['stock_minimo'], 0),
                $stockStatus,
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

    /**
     * Manejar peticiones AJAX para DataTables de movimientos
     */
    private function handleMovimientosDataTables($movimientos)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $movimientos = array_filter($movimientos, function ($movimiento) use ($search) {
                return stripos($movimiento['producto_nombre'], $search) !== false ||
                    stripos($movimiento['tipo_movimiento'], $search) !== false ||
                    stripos($movimiento['motivo'], $search) !== false ||
                    stripos($movimiento['usuario_nombre'], $search) !== false;
            });
        }

        $totalRecords = count($movimientos);
        $movimientos = array_slice($movimientos, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($movimientos as $movimiento) {
            $tipoBadge = match ($movimiento['tipo_movimiento']) {
                'entrada' => '<span class="badge bg-success">Entrada</span>',
                'salida' => '<span class="badge bg-danger">Salida</span>',
                'ajuste' => '<span class="badge bg-primary">Ajuste</span>',
                default => '<span class="badge bg-secondary">' . ucfirst($movimiento['tipo_movimiento']) . '</span>'
            };

            $data[] = [
                date('d/m/Y H:i', strtotime($movimiento['fecha'])),
                $movimiento['producto_nombre'],
                $tipoBadge,
                number_format($movimiento['cantidad'], 0),
                $movimiento['motivo'],
                $movimiento['referencia'] ?? '',
                $movimiento['usuario_nombre']
            ];
        }

        echo json_encode([
            'draw' => $draw,
            'recordsTotal' => $totalRecords,
            'recordsFiltered' => $totalRecords,
            'data' => $data
        ]);
    }

    // Alta de correo de notificación
    public function addCorreoNotificacion()
    {
        $msg = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['email'])) {
            $email = trim($_POST['email']);
            require_once APP_PATH . '/models/NotificacionCorreo.php';
            $correoModel = new NotificacionCorreo();
            if (filter_var($email, FILTER_VALIDATE_EMAIL) && !$correoModel->exists($email)) {
                $correoModel->add($email);
                $msg = 'Correo añadido correctamente.';
            } else {
                $msg = 'El correo ya está registrado o no es válido.';
            }
        }
        $_SESSION['alerta_correo'] = $msg;
        $this->redirect('?page=inventario&action=alertas');
    }

    // Baja de correo de notificación
    public function deleteCorreoNotificacion()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['id'])) {
            $id = (int)$_POST['id'];
            require_once APP_PATH . '/models/NotificacionCorreo.php';
            $correoModel = new NotificacionCorreo();
            $correoModel->delete($id);
        }
        $this->redirect('?page=inventario&action=alertas');
    }
}
