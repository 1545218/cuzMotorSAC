<?php

// Sistema de Inventario Cruz Motor S.A.C. - Punto de entrada
define('ROOT_PATH', __DIR__ . '/..');

// Iniciar sesión
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';

require_once APP_PATH . '/core/Logger.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Security.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Model.php';

// Autoloader para clases
spl_autoload_register(function ($className) {
    // Primero intentar en models
    $modelFile = APP_PATH . '/models/' . $className . '.php';
    if (file_exists($modelFile)) {
        require_once $modelFile;
        return;
    }

    // Luego en controllers
    $controllerFile = APP_PATH . '/controllers/' . $className . '.php';
    if (file_exists($controllerFile)) {
        require_once $controllerFile;
        return;
    }

    // Finalmente en core
    $coreFile = APP_PATH . '/core/' . $className . '.php';
    if (file_exists($coreFile)) {
        require_once $coreFile;
        return;
    }

    // Log para debug si no se encuentra la clase
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        error_log("Autoloader: No se pudo cargar la clase '$className' en ninguna ubicación");
    }
});

// Función para renderizar vistas
function renderView($viewPath, $data = [])
{
    extract($data);

    // Incluir header
    if (file_exists(APP_PATH . '/views/layout/header.php')) {
        include APP_PATH . '/views/layout/header.php';
    }

    // Incluir vista específica
    if (file_exists(APP_PATH . '/views/' . $viewPath . '.php')) {
        include APP_PATH . '/views/' . $viewPath . '.php';
    } else {
        echo "<h1>Vista no encontrada</h1>";
        echo "<p>La vista '$viewPath' no existe.</p>";
    }

    // Incluir footer
    if (file_exists(APP_PATH . '/views/layout/footer.php')) {
        include APP_PATH . '/views/layout/footer.php';
    }
}

/**
 * Manejo genérico de CRUD para controladores estándar
 */
function handleCRUD($controllerClass, $customActions = [])
{
    Auth::requireAuth();
    $controller = new $controllerClass();
    $action = $_GET['action'] ?? 'index';
    $id = $_GET['id'] ?? 0;

    // Manejar acciones personalizadas primero
    if (isset($customActions[$action])) {
        call_user_func($customActions[$action], $controller, $id);
        return;
    }

    // Acciones CRUD estándar
    switch ($action) {
        case 'create':
            $controller->create();
            break;
        case 'edit':
            // Algunos controladores usan $_GET['id'], otros parámetros
            if (method_exists($controller, 'edit') && (new ReflectionMethod($controller, 'edit'))->getNumberOfParameters() > 0) {
                $controller->edit($id);
            } else {
                $controller->edit();
            }
            break;
        case 'store':
            $controller->store();
            break;
        case 'update':
            // Algunos controladores usan $_GET['id'], otros parámetros
            if (method_exists($controller, 'update') && (new ReflectionMethod($controller, 'update'))->getNumberOfParameters() > 0) {
                $controller->update($id);
            } else {
                $controller->update();
            }
            break;
        case 'delete':
            // Algunos controladores usan $_GET['id'], otros parámetros
            if (method_exists($controller, 'delete') && (new ReflectionMethod($controller, 'delete'))->getNumberOfParameters() > 0) {
                $controller->delete($id);
            } else {
                $controller->delete();
            }
            break;
        case 'toggle':
            if (method_exists($controller, 'toggleStatus')) {
                $controller->toggleStatus($id);
            }
            break;
        default:
            $controller->index();
    }
}

// Obtener la página solicitada
$page = $_GET['page'] ?? '';
$action = $_GET['action'] ?? 'index';

try {
    // Log de la request entrante
    Logger::info("Request procesada", [
        'page' => $page,
        'action' => $action,
        'method' => $_SERVER['REQUEST_METHOD'],
        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
    ]);

    // Inicializar autenticación
    $auth = new Auth();

    // Si no hay página específica, redirigir según estado de autenticación
    if (empty($page)) {
        if ($auth->isLoggedIn()) {
            header("Location: ?page=dashboard");
        } else {
            header("Location: ?page=login");
        }
        exit;
    }

    // Rutas públicas (no requieren autenticación)
    $publicRoutes = ['login', 'auth'];

    // Verificar autenticación para rutas protegidas
    if (!in_array($page, $publicRoutes) && !$auth->isLoggedIn()) {
        header("Location: ?page=login&redirect=" . urlencode($page));
        exit;
    }

    // Router simplificado
    switch ($page) {
        case 'login':
        case 'auth':
            // Verificar si es logout específicamente
            if ($action === 'logout') {
                // Usar AuthController para logout
                require_once APP_PATH . '/controllers/AuthController.php';
                $authController = new AuthController();
                $authController->logout();
                exit;
            }

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                // Procesar login
                $username = $_POST['username'] ?? '';
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);

                if (!empty($username) && !empty($password)) {
                    try {
                        $result = $auth->login($username, $password, $remember);
                        if ($result['success']) {
                            $redirect = $_GET['redirect'] ?? 'dashboard';
                            header("Location: ?page=" . $redirect);
                            exit;
                        } else {
                            $error = $result['message'];
                        }
                    } catch (Exception $e) {
                        Logger::error("Error en login: " . $e->getMessage());
                        $error = "Error interno del sistema. Por favor, inténtelo más tarde.";
                        if (defined('DEBUG_MODE') && DEBUG_MODE) {
                            $error .= " Debug: " . $e->getMessage();
                        }
                    }
                } else {
                    $error = "Por favor, complete todos los campos.";
                }
            }

            // Mostrar formulario de login
            $data = [
                'title' => 'Iniciar Sesión',
                'error' => $error ?? null,
                'csrf_token' => $auth->getCSRFToken()
            ];

            // Usar vista específica para login sin layout completo
            extract($data);
            include APP_PATH . '/views/auth/login_layout.php';
            break;

        case 'dashboard':
            if (file_exists(APP_PATH . '/controllers/DashboardController.php')) {
                $controller = new DashboardController();
                $controller->index();
            } else {
                renderView('dashboard/index', ['title' => 'Dashboard']);
            }
            break;

        case 'usuarios':
            Auth::requireAuth();
            Auth::requireRole(['administrador']);
            $controller = new UsuarioController();
            switch ($action) {
                case 'create':
                    // Si el formulario hizo POST a action=create (por compatibilidad), procesar como store
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->store();
                    } else {
                        $controller->create();
                    }
                    break;
                case 'edit':
                    $id = $_GET['id'] ?? 0;
                    $controller->edit($id);
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'update':
                    $id = $_GET['id'] ?? 0;
                    $controller->update($id);
                    break;
                case 'delete':
                    $id = $_GET['id'] ?? 0;
                    $controller->delete($id);
                    break;
                case 'toggle':
                    $id = $_GET['id'] ?? 0;
                    $controller->toggleStatus($id);
                    break;
                case 'permisos':
                    $id = $_GET['id'] ?? 0;
                    $controller->permisos($id);
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'ajustes':
            Auth::requireAuth();
            Auth::requireRole(['administrador']);
            $controller = new AjusteController();
            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'show':
                    $id = $_GET['id'] ?? 0;
                    $controller->show($id);
                    break;
                case 'estadisticas':
                    $controller->estadisticas();
                    break;
                case 'api_productos':
                    $controller->apiProductos();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'productos':
            Auth::requireAuth();
            $controller = new ProductoController();
            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'edit':
                    $id = $_GET['id'] ?? 0;
                    $controller->edit($id);
                    break;
                case 'update':
                    // El método edit() ya maneja las actualizaciones vía POST
                    $id = $_GET['id'] ?? 0;
                    $controller->edit($id);
                    break;
                case 'view':
                    $id = $_GET['id'] ?? 0;
                    $controller->show($id);
                    break;
                case 'delete':
                    $id = $_GET['id'] ?? 0;
                    $controller->delete($id);
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'vehiculos':
            Auth::requireAuth();
            $controller = new VehiculoController();
            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'edit':
                    $id = $_GET['id'] ?? 0;
                    $controller->edit($id);
                    break;
                case 'update':
                    $id = $_GET['id'] ?? 0;
                    $controller->update($id);
                    break;
                case 'delete':
                    $id = $_GET['id'] ?? 0;
                    $controller->delete($id);
                    break;
                case 'porCliente':
                    $id = $_GET['id'] ?? 0;
                    $controller->porCliente($id);
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'ordenes':
            Auth::requireAuth();
            $controller = new OrdenTrabajoController();
            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'show':
                    $id = $_GET['id'] ?? 0;
                    $controller->show($id);
                    break;
                case 'edit':
                    $id = $_GET['id'] ?? 0;
                    $controller->edit($id);
                    break;
                case 'update':
                    $id = $_GET['id'] ?? 0;
                    $controller->update($id);
                    break;
                case 'delete':
                    $id = $_GET['id'] ?? 0;
                    $controller->delete($id);
                    break;
                case 'cambiarEstado':
                    $id = $_GET['id'] ?? 0;
                    $controller->cambiarEstado($id);
                    break;
                case 'vehiculosPorCliente':
                    $id = $_GET['id'] ?? 0;
                    $controller->vehiculosPorCliente($id);
                    break;
                case 'buscar':
                    $controller->buscar();
                    break;
                case 'estadisticas':
                    $controller->estadisticas();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'categorias':
            handleCRUD('CategoriaController', [
                'active' => function ($c, $id) {
                    $c->getActive();
                },
                'subcategorias' => function ($c, $id) {
                    $c->getSubcategorias($id);
                }
            ]);
            break;

        case 'marcas':
            handleCRUD('MarcaController', [
                'active' => function ($c, $id) {
                    $c->getActive();
                },
                'stats' => function ($c, $id) {
                    $c->stats($id);
                }
            ]);
            break;

        case 'inventario':
            Auth::requireAuth();
            $controller = new InventarioController();
            switch ($action) {
                case 'movimientos':
                    $controller->movimientos();
                    break;
                case 'alertas':
                    $controller->alertas();
                    break;
                case 'addCorreoNotificacion':
                    $controller->addCorreoNotificacion();
                    break;
                case 'movimiento':
                    $id = $_GET['id'] ?? 0;
                    $controller->movimiento($id);
                    break;
                case 'producto':
                    $id = $_GET['id'] ?? 0;
                    $controller->producto($id);
                    break;
                case 'entrada':
                    // Si es GET mostramos formulario simple, si es POST procesamos
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->registrarEntrada();
                    } else {
                        $id = $_GET['id'] ?? 0;
                        $controller->movimiento($id);
                    }
                    break;
                case 'salida':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->registrarSalida();
                    } else {
                        $id = $_GET['id'] ?? 0;
                        $controller->movimiento($id);
                    }
                    break;
                case 'ajuste':
                    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                        $controller->registrarAjuste();
                    } else {
                        $id = $_GET['id'] ?? 0;
                        $controller->movimiento($id);
                    }
                    break;
                case 'registrar-entrada':
                    $controller->registrarEntrada();
                    break;
                case 'registrar-salida':
                    $controller->registrarSalida();
                    break;
                case 'registrar-ajuste':
                    $controller->registrarAjuste();
                    break;
                case 'get-stock':
                    $id = $_GET['id'] ?? 0;
                    $controller->getStock($id);
                    break;
                case 'productos-bajo':
                    $controller->getProductosStockBajo();
                    break;
                case 'reporte':
                    $controller->reporte();
                    break;
                case 'detalle':
                    $controller->detalle();
                    break;
                case 'conteo':
                    $controller->conteo();
                    break;
                case 'nuevoConteo':
                    $controller->nuevoConteo();
                    break;
                case 'realizarConteo':
                    $id = $_GET['id'] ?? 0;
                    $controller->realizarConteo($id);
                    break;
                case 'guardarConteoProducto':
                    $controller->guardarConteoProducto();
                    break;
                case 'finalizarConteo':
                    $id = $_GET['id'] ?? 0;
                    $controller->finalizarConteo($id);
                    break;
                case 'aplicarAjustes':
                    $controller->aplicarAjustes();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'cotizaciones':
            Auth::requireAuth();
            $controller = new CotizacionController();
            switch ($action) {
                case 'create':
                case 'nueva': // sinónimo usado en algunos scripts
                    $controller->create();
                    break;
                case 'view':
                case 'detalle': // sinónimo usado por algunos JS
                    $id = $_GET['id'] ?? 0;
                    $controller->viewCotizacion($id);
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'edit':
                case 'editar': // sinónimo por compatibilidad con JS antiguo
                    $id = $_GET['id'] ?? 0;
                    // Llamamos a edit() y permitimos que el método lea $_GET['id'] si así está implementado
                    if (method_exists($controller, 'edit') && (new ReflectionMethod($controller, 'edit'))->getNumberOfParameters() > 0) {
                        $controller->edit($id);
                    } else {
                        $controller->edit();
                    }
                    break;
                case 'update':
                    // Algunas llamadas hacen POST a update
                    if (method_exists($controller, 'update') && (new ReflectionMethod($controller, 'update'))->getNumberOfParameters() > 0) {
                        $id = $_GET['id'] ?? 0;
                        $controller->update($id);
                    } else {
                        $controller->update();
                    }
                    break;
                case 'change-status':
                case 'cambiar-estado':
                    $id = $_GET['id'] ?? 0;
                    $controller->changeStatus($id);
                    break;
                case 'aceptar':
                    $controller->aceptar();
                    break;
                case 'rechazar':
                    $controller->rechazar();
                    break;
                case 'eliminar':
                case 'delete':
                    $id = $_GET['id'] ?? 0;
                    $controller->delete($id);
                    break;
                case 'duplicate':
                    $id = $_GET['id'] ?? 0;
                    $controller->duplicate($id);
                    break;
                case 'pdf':
                case 'generar-pdf':
                    $id = $_GET['id'] ?? 0;
                    $controller->pdf($id);
                    break;
                case 'stats':
                    $controller->stats();
                    break;
                case 'vencidas':
                    $controller->vencidas();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'clientes':
            Auth::requireAuth();
            $controller = new ClienteController();
            $action = $_GET['action'] ?? 'index';

            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                case 'get-for-select':
                    $controller->getForSelect();
                    break;
                case 'vehiculos':
                    $controller->vehiculos();
                    break;
                case 'agregarVehiculo':
                    $controller->agregarVehiculo();
                    break;
                case 'eliminarVehiculo':
                    $controller->eliminarVehiculo();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'ventas':
            Auth::requireAuth();
            $controller = new VentaController();
            $action = $_GET['action'] ?? 'index';
            switch ($action) {
                case 'create-from-cotizacion':
                case 'create_from_cotizacion':
                    // Mostrar formulario para crear venta desde cotización
                    $controller->createFromCotizacion();
                    break;
                case 'store-from-cotizacion':
                case 'store_from_cotizacion':
                    // Procesar creación (POST)
                    $controller->storeFromCotizacion();
                    break;
                case 'view':
                    $id = $_GET['id'] ?? 0;
                    // Si existe un método 'view' en el controller, invocarlo de forma dinámica
                    if (is_callable([$controller, 'view'])) {
                        call_user_func([$controller, 'view'], $id);
                    } else {
                        $controller->index();
                    }
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'reportes':
            Auth::requireAuth();
            Auth::requireRole(['administrador']); // Solo administradores
            $controller = new ReporteController();
            switch ($action) {
                case 'ventas':
                    $controller->ventas();
                    break;
                case 'stock':
                    $controller->stock();
                    break;
                case 'movimientos':
                    $controller->movimientos();
                    break;
                case 'consumo':
                    $controller->consumo();
                    break;
                case 'dashboard':
                    $controller->dashboard();
                    break;
                case 'movimientos-inventario':
                    $controller->movimientosInventario();
                    break;
                case 'consumo-periodo':
                    $controller->consumoPorPeriodo();
                    break;
                case 'estado-stock':
                    $controller->estadoStock();
                    break;
                case 'programar-reportes':
                    $controller->programarReportes();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'alertas':
            Auth::requireAuth();
            Auth::requireRole(['administrador']); // Solo administradores
            $controller = new AlertaController();
            switch ($action) {
                case 'verificarStock':
                    $controller->verificarStock();
                    break;
                case 'marcarResuelta':
                    $controller->marcarResuelta();
                    break;
                case 'contarPendientes':
                    $controller->contarPendientes();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'notificaciones':
            Auth::requireAuth();
            Auth::requireRole(['administrador']); // Solo administradores
            $controller = new NotificacionController();
            switch ($action) {
                case 'add':
                    $controller->add();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'backups':
            Auth::requireAuth();
            Auth::requireRole(['administrador']); // Solo administradores
            $controller = new BackupSistemaController();
            switch ($action) {
                case 'crear':
                    $controller->crear();
                    break;
                case 'eliminar':
                    $controller->eliminar();
                    break;
                case 'restaurar':
                    $controller->restaurar();
                    break;
                case 'descargar':
                    $controller->descargar();
                    break;
                case 'limpiar':
                    $controller->limpiar();
                    break;
                case 'verificarIntegridad':
                    $controller->verificarIntegridad();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'subcategorias':
            Auth::requireAuth();
            $controller = new SubcategoriaController();
            switch ($action) {
                case 'create':
                    $controller->create();
                    break;
                case 'store':
                    $controller->store();
                    break;
                case 'edit':
                    $controller->edit();
                    break;
                case 'update':
                    $controller->update();
                    break;
                case 'delete':
                    $controller->delete();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'ubicaciones':
            handleCRUD('UbicacionController');
            break;

        case 'unidades':
            handleCRUD('UnidadController');
            break;

        case 'config':
            Auth::requireAuth();
            Auth::requireRole(['administrador']);
            $controller = new ConfigController();
            $action = $_GET['action'] ?? 'index';

            switch ($action) {
                case 'update':
                    $controller->update();
                    break;
                case 'backup':
                    $controller->backup();
                    break;
                case 'clear-cache':
                    $controller->clearCache();
                    break;
                case 'almacen':
                    $controller->almacen();
                    break;
                case 'createAlmacen':
                    $controller->createAlmacen();
                    break;
                case 'editAlmacen':
                    $controller->editAlmacen();
                    break;
                case 'deleteAlmacen':
                    $controller->deleteAlmacen();
                    break;
                case 'sistema':
                    $controller->sistema();
                    break;
                case 'updateSistema':
                    $controller->updateSistema();
                    break;
                case 'createParametro':
                    $controller->createParametro();
                    break;
                case 'empresa':
                    $controller->empresa();
                    break;
                case 'actualizarEmpresa':
                    $controller->actualizarEmpresa();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'auditoria':
            Auth::requireAuth();
            Auth::requireRole(['administrador']);
            $controller = new AuditoriaController();
            $action = $_GET['action'] ?? 'index';

            switch ($action) {
                case 'estadisticas':
                    $controller->estadisticas();
                    break;
                case 'buscar':
                    $controller->buscar();
                    break;
                case 'registro':
                    $controller->registro();
                    break;
                case 'limpiar':
                    $controller->limpiar();
                    break;
                case 'exportar':
                    $controller->exportar();
                    break;
                case 'api_recientes':
                    $controller->api_recientes();
                    break;
                case 'dashboard-seguridad':
                    $controller->dashboard_seguridad();
                    break;
                case 'monitoreo-sesiones':
                    $controller->monitoreo_sesiones();
                    break;
                case 'detectar-anomalias':
                    $controller->detectar_anomalias();
                    break;
                case 'reporte-seguridad':
                    $controller->generar_reporte_seguridad();
                    break;
                default:
                    $controller->index();
            }
            break;

        case 'sesiones':
            Auth::requireAuth();
            $controller = new SesionController();
            $action = $_GET['action'] ?? 'index';

            switch ($action) {
                case 'estadisticas':
                    Auth::requireRole(['administrador']);
                    $controller->estadisticas();
                    break;
                case 'cerrar':
                    $controller->cerrar();
                    break;
                case 'cerrarOtras':
                    $controller->cerrarOtras();
                    break;
                case 'limpiar':
                    Auth::requireRole(['administrador']);
                    $controller->limpiar();
                    break;
                case 'cerrarUsuario':
                    Auth::requireRole(['administrador']);
                    $controller->cerrarUsuario();
                    break;
                case 'activas':
                    $controller->activas();
                    break;
                default:
                    $controller->index();
            }
            break;

        default:
            // Página no encontrada
            http_response_code(404);
            renderView('errors/404', ['title' => 'Página no encontrada']);
            break;
    }
} catch (Exception $e) {
    // Log del error
    Logger::error("Error en index.php: " . $e->getMessage(), [
        'page' => $page,
        'action' => $action,
        'file' => $e->getFile(),
        'line' => $e->getLine(),
        'trace' => $e->getTraceAsString()
    ]);

    // Mostrar error apropiado
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo "<h1>Error del Sistema</h1>";
        echo "<p><strong>Mensaje:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . "</p>";
        echo "<p><strong>Línea:</strong> " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        renderView('errors/500', [
            'title' => 'Error interno del servidor',
            'message' => 'Ha ocurrido un error inesperado. Por favor, inténtelo más tarde.'
        ]);
    }
}
