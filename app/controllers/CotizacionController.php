<?php

class CotizacionController extends Controller
{
    private $cotizacionModel;
    private $clienteModel;
    private $productoModel;

    public function __construct()
    {
        parent::__construct();

        // Los modelos se cargan automáticamente por el autoloader
        $this->cotizacionModel = new Cotizacion();
        $this->clienteModel = new Cliente();
        $this->productoModel = new Producto();

        // Verificar autenticación para todas las acciones
        Auth::requireAuth();
    }

    /**
     * Mostrar lista de cotizaciones
     */
    public function index()
    {
        try {
            $cotizaciones = $this->cotizacionModel->getAll();

            // Si es una petición AJAX para DataTables
            if (isset($_GET['draw'])) {
                $this->handleDataTablesRequest($cotizaciones);
                return;
            }

            $this->view('cotizaciones/index', [
                'title' => 'Gestión de Cotizaciones',
                'cotizaciones' => $cotizaciones
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::index - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar cotizaciones']);
        }
    }

    /**
     * Mostrar formulario para crear cotización
     */
    public function create()
    {
        try {
            $clientes = $this->clienteModel->getActive();
            $productos = $this->productoModel->getActive();

            $this->view('cotizaciones/create', [
                'title' => 'Crear Cotización',
                'clientes' => $clientes,
                'productos' => $productos
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::create - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al mostrar formulario']);
        }
    }

    /**
     * Crear nueva cotización
     */
    public function store()
    {
        try {
            // Normalizar nombre del campo cliente: aceptar `id_cliente` (vista) o `cliente_id` (API)
            if (isset($_POST['id_cliente']) && !isset($_POST['cliente_id'])) {
                $_POST['cliente_id'] = $_POST['id_cliente'];
            }

            Logger::info("Inicio de creación de cotización", [
                'post_data' => $_POST
            ]);

            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                Logger::error("Token CSRF inválido", [
                    'csrf_token' => $_POST['csrf_token'] ?? null
                ]);
                throw new Exception('Token CSRF inválido');
            }

            // Validar datos
            $errors = $this->validateCotizacionData($_POST);
            if (!empty($errors)) {
                Logger::warning("Errores de validación en cotización", [
                    'errors' => $errors
                ]);
                echo json_encode(['success' => false, 'errors' => $errors]);
                return;
            }

            // Verificar que el cliente exista
            $cliente = $this->clienteModel->find($_POST['cliente_id']);
            if (!$cliente) {
                Logger::error("Cliente no encontrado", [
                    'cliente_id' => $_POST['cliente_id']
                ]);
                echo json_encode(['success' => false, 'errors' => ['cliente_id' => 'Cliente no encontrado']]);
                return;
            }

            // Validar productos (aceptar array enviado por form o JSON)
            if (isset($_POST['productos'])) {
                if (is_string($_POST['productos'])) {
                    $productos = json_decode($_POST['productos'], true);
                } elseif (is_array($_POST['productos'])) {
                    $productos = array_values($_POST['productos']);
                } else {
                    $productos = [];
                }
            } else {
                $productos = [];
            }

            if (empty($productos)) {
                Logger::warning("Productos no proporcionados", [
                    'productos' => $_POST['productos'] ?? null
                ]);
                echo json_encode(['success' => false, 'errors' => ['productos' => 'Debe agregar al menos un producto']]);
                return;
            }

            Logger::info("Validando productos recibidos para cotización", ['productos' => $productos]);
            $errorsProductos = $this->validateProductos($productos);
            if (!empty($errorsProductos)) {
                Logger::warning("Errores en productos", [
                    'errors' => $errorsProductos
                ]);
                echo json_encode(['success' => false, 'errors' => $errorsProductos]);
                return;
            }

            // Calcular totales
            $subtotal = 0;
            foreach ($productos as $producto) {
                $subtotal += $producto['cantidad'] * $producto['precio_unitario'];
            }

            $descuento = $_POST['descuento'] ?? 0;
            $igv = ($subtotal - $descuento) * 0.18;
            $total = $subtotal - $descuento + $igv;

            // Generar número de cotización
            $numeroCotizacion = $this->cotizacionModel->generateNumber();

            // Preparar datos de la cotización (campos que existen en la tabla `cotizaciones`)
            $cotizacionData = [
                'id_cliente' => $_POST['cliente_id'],
                'fecha' => date('Y-m-d'),
                'total' => $total,
                'estado' => 'pendiente',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            Logger::info("Datos preparados para cotización", [
                'cotizacion_data' => $cotizacionData
            ]);

            // Asegurarnos de que $productos sea un array (el formulario envía arrays, pero soportamos JSON por compatibilidad)
            if (isset($_POST['productos']) && is_array($_POST['productos'])) {
                $productos = array_values($_POST['productos']);
            } elseif (!empty($_POST['productos']) && is_string($_POST['productos'])) {
                $productos = json_decode($_POST['productos'], true);
            }

            $cotizacionId = $this->cotizacionModel->create($cotizacionData, $productos);

            if ($cotizacionId) {
                Logger::info("Cotización creada exitosamente", [
                    'cotizacion_id' => $cotizacionId,
                    'numero' => $numeroCotizacion
                ]);

                // Si la petición es XHR, devolver JSON; si no, redirigir a la vista de la cotización
                $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isXhr) {
                    echo json_encode(['success' => true, 'message' => 'Cotización creada exitosamente', 'cotizacion_id' => $cotizacionId]);
                } else {
                    header('Location: ?page=cotizaciones&action=view&id=' . $cotizacionId);
                    exit;
                }
            } else {
                Logger::error("Error al crear cotización", [
                    'cotizacion_data' => $cotizacionData
                ]);
                $isXhr = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
                if ($isXhr) {
                    echo json_encode(['success' => false, 'errors' => ['general' => 'Error al crear cotización']]);
                } else {
                    // Redirigir de vuelta al formulario con un query param simple (se puede mejorar con flash messages)
                    header('Location: ?page=cotizaciones&action=create&error=1');
                    exit;
                }
            }
        } catch (Exception $e) {
            Logger::error("Excepción en CotizacionController::store", [
                'exception' => $e->getMessage()
            ]);
            echo json_encode(['success' => false, 'errors' => ['general' => 'Error interno del servidor']]);
        }
    }

    /**
     * Ver cotización
     */
    public function viewCotizacion($id)
    {
        try {
            $cotizacion = $this->cotizacionModel->getWithDetails($id);

            if (!$cotizacion) {
                $this->view('errors/404', ['message' => 'Cotización no encontrada']);
                return;
            }

            $this->view('cotizaciones/view', [
                'title' => 'Ver Cotización',
                'cotizacion' => $cotizacion
            ]);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::view - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar cotización']);
        }
    }

    /**
     * Mostrar formulario de edición de cotización
     */
    public function edit()
    {
        try {
            $id = $_GET['id'] ?? null;
            if (!$id) {
                $this->redirect('?page=cotizaciones');
                return;
            }

            $cotizacion = $this->cotizacionModel->getWithDetails($id);
            if (!$cotizacion) {
                $this->setFlash('error', 'Cotización no encontrada');
                $this->redirect('?page=cotizaciones');
                return;
            }

            $detalles = $this->cotizacionModel->getProductos($id);
            $clientes = $this->clienteModel->getActive();
            $productos = $this->productoModel->getActive();

            $this->view('cotizaciones/create', [
                'title' => 'Editar Cotización',
                'clientes' => $clientes,
                'productos' => $productos,
                'cotizacion' => $cotizacion,
                'detalles' => $detalles,
                'form_action' => '?page=cotizaciones&action=update'
            ]);
        } catch (Exception $e) {
            Logger::error('Error en CotizacionController::edit - ' . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al cargar cotización para edición']);
        }
    }

    /**
     * Actualizar cotización existente
     */
    public function update()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlash('error', 'Método no permitido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            // CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->setFlash('error', 'Token CSRF inválido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            $id = $_POST['id_cotizacion'] ?? null;
            if (!$id) {
                $this->setFlash('error', 'ID de cotización requerido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            // Normalizar cliente
            if (isset($_POST['id_cliente']) && !isset($_POST['cliente_id'])) {
                $_POST['cliente_id'] = $_POST['id_cliente'];
            }

            $errors = $this->validateCotizacionData($_POST);
            if (!empty($errors)) {
                $_SESSION['form_errors'] = $errors;
                $_SESSION['old_input'] = $_POST;
                $this->setFlash('error', 'Hay errores en el formulario');
                $this->redirect('?page=cotizaciones&action=edit&id=' . $id);
                return;
            }

            // Productos
            $productos = [];
            if (!empty($_POST['productos']) && is_array($_POST['productos'])) {
                foreach ($_POST['productos'] as $p) {
                    $productos[] = [
                        'id_producto' => $p['id_producto'] ?? $p['id'] ?? null,
                        'cantidad' => $p['cantidad'] ?? 0,
                        'precio_unitario' => $p['precio_unitario'] ?? ($p['precio'] ?? 0),
                        'descripcion_servicio' => $p['descripcion_servicio'] ?? ($p['descripcion'] ?? null)
                    ];
                }
            }

            $cotizacionData = [
                'id_cliente' => $_POST['cliente_id'],
                'fecha' => date('Y-m-d'),
                'total' => $_POST['total'] ?? 0,
                'estado' => $_POST['estado'] ?? 'pendiente',
                'observaciones' => trim($_POST['observaciones'] ?? '')
            ];

            $result = $this->cotizacionModel->updateWithDetails($id, $cotizacionData, $productos);

            if ($result) {
                $this->setFlash('success', 'Cotización actualizada exitosamente');
                $this->redirect('?page=cotizaciones&action=view&id=' . $id);
            } else {
                $this->setFlash('error', 'Error al actualizar cotización');
                $this->redirect('?page=cotizaciones&action=edit&id=' . $id);
            }
        } catch (Exception $e) {
            Logger::error('Excepción en CotizacionController::update - ' . $e->getMessage());
            $this->setFlash('error', 'Error interno del servidor');
            $this->redirect('?page=cotizaciones');
        }
    }

    /**
     * Cambiar estado de la cotización
     */
    public function changeStatus($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $cotizacion = $this->cotizacionModel->find($id);
            if (!$cotizacion) {
                echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
                return;
            }

            $nuevoEstado = $_POST['estado'];
            $estadosValidos = ['pendiente', 'aprobada', 'rechazada', 'vencida'];

            if (!in_array($nuevoEstado, $estadosValidos)) {
                echo json_encode(['success' => false, 'message' => 'Estado no válido']);
                return;
            }

            $result = $this->cotizacionModel->changeStatus($id, $nuevoEstado, $_SESSION['user_id']);

            if ($result) {
                Logger::info("Estado de cotización cambiado: {$cotizacion['numero']} a {$nuevoEstado} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Estado actualizado exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al actualizar estado']);
            }
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::changeStatus - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Eliminar cotización
     */
    public function delete($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            // Solo administradores pueden eliminar cotizaciones
            if ($_SESSION['user_role'] !== 'admin') {
                echo json_encode(['success' => false, 'message' => 'No tienes permisos para esta acción']);
                return;
            }

            $cotizacion = $this->cotizacionModel->find($id);
            if (!$cotizacion) {
                echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
                return;
            }

            // No permitir eliminar cotizaciones aprobadas
            if ($cotizacion['estado'] === 'aprobada') {
                echo json_encode(['success' => false, 'message' => 'No se puede eliminar una cotización aprobada']);
                return;
            }

            $result = $this->cotizacionModel->delete($id);

            if ($result) {
                Logger::info("Cotización eliminada: {$cotizacion['numero']} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Cotización eliminada exitosamente']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al eliminar cotización']);
            }
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::delete - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Generar PDF de la cotización
     */
    public function pdf($id)
    {
        try {
            // Validar ID
            if (!$id || !is_numeric($id)) {
                $this->view('errors/404', ['message' => 'ID de cotización inválido']);
                return;
            }

            // Obtener datos de la cotización
            $cotizacion = $this->cotizacionModel->getWithDetails($id);

            if (!$cotizacion) {
                $this->view('errors/404', ['message' => 'Cotización no encontrada']);
                return;
            }

            // Obtener datos del cliente
            $cliente = $this->clienteModel->obtenerPorId($cotizacion['id_cliente']);

            if (!$cliente) {
                throw new Exception('Cliente no encontrado');
            }

            // Obtener productos de la cotización
            $productos = $this->cotizacionModel->getProductos($id);

            if (empty($productos)) {
                throw new Exception('No se encontraron productos en la cotización');
            }

            // Cargar la clase PDF
            require_once __DIR__ . '/../core/CotizacionPDF.php';

            // Generar PDF
            $pdf = new CotizacionPDF();
            $pdf->descargar($cotizacion, $cliente, $productos);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::pdf - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar PDF: ' . $e->getMessage()]);
        }
    }

    /**
     * Duplicar cotización
     */
    public function duplicate($id)
    {
        try {
            // Verificar token CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception('Token CSRF inválido');
            }

            $cotizacion = $this->cotizacionModel->getWithDetails($id);
            if (!$cotizacion) {
                echo json_encode(['success' => false, 'message' => 'Cotización no encontrada']);
                return;
            }

            $nuevaCotizacionId = $this->cotizacionModel->duplicate($id, $_SESSION['user_id']);

            if ($nuevaCotizacionId) {
                Logger::info("Cotización duplicada: {$cotizacion['numero']} por usuario ID: " . $_SESSION['user_id']);
                echo json_encode(['success' => true, 'message' => 'Cotización duplicada exitosamente', 'cotizacion_id' => $nuevaCotizacionId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Error al duplicar cotización']);
            }
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::duplicate - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error interno del servidor']);
        }
    }

    /**
     * Obtener estadísticas de cotizaciones
     */
    public function stats()
    {
        try {
            $periodo = $_GET['periodo'] ?? 'mes';
            $stats = $this->cotizacionModel->getStats($periodo);
            echo json_encode(['success' => true, 'data' => $stats]);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::stats - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener estadísticas']);
        }
    }

    /**
     * Obtener cotizaciones vencidas
     */
    public function vencidas()
    {
        try {
            $cotizaciones = $this->cotizacionModel->getVencidas();
            echo json_encode(['success' => true, 'data' => $cotizaciones]);
        } catch (Exception $e) {
            Logger::error("Error en CotizacionController::vencidas - " . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Error al obtener cotizaciones vencidas']);
        }
    }

    /**
     * Validar datos de la cotización
     */
    private function validateCotizacionData($data)
    {
        $errors = [];

        // Validar cliente
        if (empty($data['cliente_id'])) {
            $errors['cliente_id'] = 'El cliente es obligatorio';
        }

        // Validar fecha de vencimiento
        if (empty($data['fecha_vencimiento'])) {
            $errors['fecha_vencimiento'] = 'La fecha de vencimiento es obligatoria';
        } elseif (strtotime($data['fecha_vencimiento']) < strtotime(date('Y-m-d'))) {
            $errors['fecha_vencimiento'] = 'La fecha de vencimiento no puede ser anterior a hoy';
        }

        // Validar descuento
        if (!empty($data['descuento'])) {
            if (!is_numeric($data['descuento']) || $data['descuento'] < 0) {
                $errors['descuento'] = 'El descuento debe ser un número positivo';
            }
        }

        return $errors;
    }

    /**
     * Validar productos de la cotización
     */
    private function validateProductos($productos)
    {
        $errors = [];

        foreach ($productos as $index => $producto) {
            // Compatibilidad: el formulario puede enviar 'id' o 'id_producto'
            $productId = $producto['id'] ?? $producto['id_producto'] ?? null;
            // Verificar que el producto exista
            $productoDb = $productId ? $this->productoModel->find($productId) : false;
            if (!$productoDb) {
                $errors["producto_{$index}"] = 'Producto no encontrado';
                continue;
            }

            // Validar cantidad
            if (empty($producto['cantidad']) || $producto['cantidad'] <= 0) {
                $errors["cantidad_{$index}"] = 'La cantidad debe ser mayor a 0';
            }

            // Validar precio
            if (empty($producto['precio_unitario']) || $producto['precio_unitario'] <= 0) {
                $errors["precio_{$index}"] = 'El precio debe ser mayor a 0';
            }
        }

        return $errors;
    }

    /**
     * Manejar peticiones AJAX para DataTables
     */
    private function handleDataTablesRequest($cotizaciones)
    {
        $draw = intval($_GET['draw']);
        $start = intval($_GET['start']);
        $length = intval($_GET['length']);
        $search = $_GET['search']['value'] ?? '';

        // Filtrar datos si hay búsqueda
        if (!empty($search)) {
            $cotizaciones = array_filter($cotizaciones, function ($cotizacion) use ($search) {
                return stripos($cotizacion['numero'], $search) !== false ||
                    stripos($cotizacion['cliente_nombre'], $search) !== false ||
                    stripos($cotizacion['estado'], $search) !== false;
            });
        }

        $totalRecords = count($cotizaciones);
        $cotizaciones = array_slice($cotizaciones, $start, $length);

        // Formatear datos para DataTables
        $data = [];
        foreach ($cotizaciones as $cotizacion) {
            $estadoBadge = match ($cotizacion['estado']) {
                'pendiente' => '<span class="badge bg-warning">Pendiente</span>',
                'aprobada' => '<span class="badge bg-success">Aprobada</span>',
                'rechazada' => '<span class="badge bg-danger">Rechazada</span>',
                'vencida' => '<span class="badge bg-secondary">Vencida</span>',
                default => '<span class="badge bg-secondary">' . ucfirst($cotizacion['estado']) . '</span>'
            };

            $actions = '
                <div class="btn-group" role="group">
                    <button type="button" class="btn btn-sm btn-outline-info" onclick="verCotizacion(' . $cotizacion['id'] . ')" title="Ver">
                        <i class="fas fa-eye"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-primary" onclick="duplicarCotizacion(' . $cotizacion['id'] . ')" title="Duplicar">
                        <i class="fas fa-copy"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success" onclick="generarPDF(' . $cotizacion['id'] . ')" title="PDF">
                        <i class="fas fa-file-pdf"></i>
                    </button>';

            if ($cotizacion['estado'] === 'pendiente') {
                $actions .= '
                    <button type="button" class="btn btn-sm btn-outline-warning" onclick="cambiarEstado(' . $cotizacion['id'] . ')" title="Cambiar Estado">
                        <i class="fas fa-edit"></i>
                    </button>';
            }

            // Solo administradores pueden eliminar
            if ($_SESSION['user_role'] === 'admin' && $cotizacion['estado'] !== 'aprobada') {
                $actions .= '
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="eliminarCotizacion(' . $cotizacion['id'] . ')" title="Eliminar">
                        <i class="fas fa-trash"></i>
                    </button>';
            }

            $actions .= '</div>';

            $data[] = [
                $cotizacion['numero'],
                $cotizacion['cliente_nombre'],
                date('d/m/Y', strtotime($cotizacion['fecha_emision'])),
                date('d/m/Y', strtotime($cotizacion['fecha_vencimiento'])),
                'S/ ' . number_format($cotizacion['total'], 2),
                $estadoBadge,
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
