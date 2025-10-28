<?php

/**
 * Clase Controller - Clase base para todos los controladores
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Controller
{
    protected $db;
    protected $auth;
    protected $logger;
    protected $data = [];

    public function __construct()
    {
        // Incluir archivos de configuración
        require_once ROOT_PATH . '/config/config.php';

        // Inicializar base de datos
        try {
            $this->db = Database::getInstance();
            Logger::debug("Controller inicializado correctamente", ['class' => get_class($this)]);
        } catch (Exception $e) {
            Logger::error("Error al inicializar Controller: " . $e->getMessage(), ['class' => get_class($this)]);
            throw $e;
        }

        // Inicializar autenticación
        $this->auth = new Auth();

        // Inicializar logger
        $this->logger = new Logger();

        // Verificar sesión para rutas protegidas
        $this->checkAuthentication();
    }

    /**
     * Carga una vista
     */
    protected function view($view, $data = [])
    {
        // Extraer datos para usar en la vista
        extract($data);

        // Datos globales disponibles en todas las vistas
        $auth = $this->auth; // Objeto Auth para usar en vistas
        $user = $this->auth->getUser();
        $isLoggedIn = $this->auth->isLoggedIn();
        $userRole = $this->auth->getUserRole();
        $notifications = $this->getNotifications();
        $config = $this->getAppConfig();

        // Cargar header
        include APP_PATH . '/views/layout/header.php';

        // Cargar vista principal
        $viewFile = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            Logger::error("Vista no encontrada: {$view}", [
                'view_file' => $viewFile,
                'controller' => get_class($this)
            ]);
            throw new Exception("Vista no encontrada: {$view}");
        }

        // Cargar footer
        include APP_PATH . '/views/layout/footer.php';
    }

    /**
     * Carga una vista parcial (sin header/footer)
     */
    protected function partial($view, $data = [])
    {
        extract($data);

        $viewFile = APP_PATH . '/views/' . $view . '.php';
        if (file_exists($viewFile)) {
            include $viewFile;
        } else {
            Logger::error("Vista parcial no encontrada: {$view}", [
                'view_file' => $viewFile,
                'controller' => get_class($this)
            ]);
            throw new Exception("Vista parcial no encontrada: {$view}");
        }
    }

    /**
     * Devuelve respuesta JSON
     */
    protected function json($data, $statusCode = 200)
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    /**
     * Redirecciona a una URL
     */
    protected function redirect($url, $statusCode = 302)
    {
        if (strpos($url, 'http') !== 0) {
            $url = BASE_PATH . '/' . ltrim($url, '/');
        }

        http_response_code($statusCode);
        header("Location: {$url}");
        exit;
    }

    /**
     * Establece un mensaje flash
     */
    protected function setFlash($type, $message)
    {
        if (!isset($_SESSION['flash_messages'])) {
            $_SESSION['flash_messages'] = [];
        }

        $_SESSION['flash_messages'][] = [
            'type' => $type,
            'message' => $message
        ];
    }

    /**
     * Obtiene y limpia mensajes flash
     */
    protected function getFlashMessages()
    {
        $messages = isset($_SESSION['flash_messages']) ? $_SESSION['flash_messages'] : [];
        unset($_SESSION['flash_messages']);
        return $messages;
    }

    /**
     * Valida token CSRF
     */
    protected function validateCSRF()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // Intentar obtener token desde varias fuentes para compatibilidad:
            // 1) nombre configurado (CSRF_TOKEN_NAME)
            // 2) campo legacy 'csrf_token'
            // 3) header X-CSRF-Token
            // 4) body JSON (si se envió JSON)
            $token = $_POST[CSRF_TOKEN_NAME] ?? $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';

            if (empty($token)) {
                // Intentar leer body JSON si existe (no romperá otros usos)
                $raw = @file_get_contents('php://input');
                if ($raw) {
                    $json = json_decode($raw, true);
                    if (json_last_error() === JSON_ERROR_NONE) {
                        $token = $json[CSRF_TOKEN_NAME] ?? $json['csrf_token'] ?? $token;
                    }
                }
            }

            // Validar token usando Auth
            $isValid = $this->auth->validateCSRFToken($token);
            if (!$isValid) {
                // Detectar petición AJAX/JSON
                $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
                    || (isset($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false);

                if ($isAjax) {
                    // Para llamadas AJAX devolvemos JSON y terminamos
                    $this->json(['error' => 'Token CSRF inválido'], 403);
                    // json() sale con exit
                } else {
                    // Para peticiones normales guardamos mensaje y redirigimos a la página anterior
                    $this->setFlash('error', 'Token CSRF inválido. Intente nuevamente.');
                    $redirect = $_SERVER['HTTP_REFERER'] ?? '?page=dashboard';
                    // Evitar bucles de redirect hacia la misma ruta 'store'
                    if (strpos($redirect, 'action=store') !== false || strpos($redirect, 'action=update') !== false) {
                        $redirect = '?page=' . ($_GET['page'] ?? 'dashboard');
                    }
                    $this->redirect($redirect);
                    // redirect() hace exit
                }

                // En el improbable caso de que no hayamos salido, devolvemos false para que el llamador lo maneje
                return false;
            }
        }

        return true;
    }

    /**
     * Genera token CSRF
     */
    protected function generateCSRF()
    {
        return $this->auth->generateCSRFToken();
    }

    /**
     * Valida entrada de datos
     */
    protected function validate($data, $rules)
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            $ruleArray = explode('|', $rule);

            foreach ($ruleArray as $singleRule) {
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = "El campo {$field} es requerido";
                    break;
                }

                if (strpos($singleRule, 'min:') === 0 && !empty($value)) {
                    $min = (int) substr($singleRule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
                        break;
                    }
                }

                if (strpos($singleRule, 'max:') === 0 && !empty($value)) {
                    $max = (int) substr($singleRule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = "El campo {$field} no puede tener más de {$max} caracteres";
                        break;
                    }
                }

                if ($singleRule === 'email' && !empty($value)) {
                    if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[$field] = "El campo {$field} debe ser un email válido";
                        break;
                    }
                }

                if ($singleRule === 'numeric' && !empty($value)) {
                    if (!is_numeric($value)) {
                        $errors[$field] = "El campo {$field} debe ser numérico";
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Sanitiza entrada de datos
     */
    protected function sanitize($data)
    {
        if (is_array($data)) {
            return array_map([$this, 'sanitize'], $data);
        }

        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Verifica autenticación para rutas protegidas
     */
    private function checkAuthentication()
    {
        // Rutas públicas que no requieren autenticación
        $publicRoutes = [
            'auth/login',
            'auth/logout',
            '/',
            ''
        ];

        $currentRoute = $this->getCurrentRoute();

        if (!in_array($currentRoute, $publicRoutes) && !$this->auth->isLoggedIn()) {
            $this->redirect('auth/login');
        }
    }

    /**
     * Obtiene la ruta actual
     */
    private function getCurrentRoute()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        return ltrim(str_replace(BASE_PATH, '', $path), '/');
    }

    /**
     * Obtiene notificaciones del sistema
     */
    private function getNotifications()
    {
        $notifications = [];

        if ($this->auth->isLoggedIn()) {
            // Verificar productos con stock bajo
            try {
                $lowStockProducts = $this->db->select(
                    "SELECT COUNT(*) as count FROM productos WHERE stock_actual <= stock_minimo AND estado = 'activo'"
                );

                if ($lowStockProducts[0]['count'] > 0) {
                    $notifications[] = [
                        'type' => 'warning',
                        'message' => "Hay {$lowStockProducts[0]['count']} productos con stock bajo",
                        'icon' => 'fas fa-exclamation-triangle',
                        'url' => 'productos/stock-bajo'
                    ];
                }
            } catch (Exception $e) {
                // Log error pero no romper la página
                Logger::warning("Error al verificar productos con stock bajo: " . $e->getMessage(), [
                    'controller' => get_class($this)
                ]);
            }
        }

        return $notifications;
    }

    /**
     * Obtiene configuración de la aplicación
     */
    private function getAppConfig()
    {
        return [
            'app_name' => APP_NAME,
            'company_name' => COMPANY_NAME,
            'app_version' => APP_VERSION,
            'base_path' => BASE_PATH,
            'csrf_token' => $this->generateCSRF()
        ];
    }

    /**
     * Verifica permisos de usuario
     */
    protected function requireRole($role)
    {
        if (!$this->auth->hasRole($role)) {
            $this->setFlash('error', 'No tienes permisos para acceder a esta sección');
            $this->redirect('dashboard');
        }
    }

    /**
     * Log de actividades del usuario
     */
    protected function logActivity($action, $details = '')
    {
        if ($this->auth->isLoggedIn()) {
            $user = $this->auth->getUser();
            try {
                $this->db->insert(
                    "INSERT INTO activity_logs (usuario_id, accion, detalles, ip_address, user_agent, created_at) 
                     VALUES (?, ?, ?, ?, ?, NOW())",
                    [
                        $user['id'],
                        $action,
                        $details,
                        $_SERVER['REMOTE_ADDR'],
                        $_SERVER['HTTP_USER_AGENT']
                    ]
                );
            } catch (Exception $e) {
                error_log("Error logging activity: " . $e->getMessage());
            }
        }
    }

    /**
     * Formatea números para mostrar
     */
    protected function formatNumber($number, $decimals = 2)
    {
        return number_format((float)$number, $decimals, '.', ',');
    }

    /**
     * Valida fecha
     */
    protected function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
    }

    /**
     * Verifica si una ruta está activa
     */
    protected function isActiveRoute($route)
    {
        $currentPath = $_SERVER['REQUEST_URI'];
        $basePath = BASE_PATH;
        $currentRoute = str_replace($basePath, '', $currentPath);
        $currentRoute = ltrim($currentRoute, '/');
        $currentRoute = strtok($currentRoute, '?'); // Remover query string

        return strpos($currentRoute, $route) === 0;
    }

    /**
     * Método compatibilidad: renderiza el header por separado.
     * Algunas vistas antiguas llaman a $this->renderHeader() por lo que
     * añadimos este método para mantener compatibilidad hacia atrás.
     */
    protected function renderHeader($title = '')
    {
        // Preparar variables usadas por el header
        $auth = $this->auth;
        $user = $this->auth->getUser();
        $isLoggedIn = $this->auth->isLoggedIn();
        $userRole = $this->auth->getUserRole();
        $notifications = $this->getNotifications();
        $config = $this->getAppConfig();

        // Hacer disponible el título en el header si la vista lo usa
        $page_title = $title;

        include APP_PATH . '/views/layout/header.php';
    }

    /**
     * Método compatibilidad: renderiza el footer por separado.
     */
    protected function renderFooter()
    {
        include APP_PATH . '/views/layout/footer.php';
    }
}
