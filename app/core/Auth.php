<?php

/**
 * Clase Auth - Manejo de autenticación y autorización
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Auth
{
    private $db;
    private $sessionName;
    private $sesionUsuario;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->sessionName = SESSION_NAME;

        // Inicializar modelo de sesiones de usuario
        require_once __DIR__ . '/../models/SesionUsuario.php';
        $this->sesionUsuario = new SesionUsuario();

        // Configurar sesión
        $this->initSession();
    }

    /**
     * Inicializa la sesión con configuración segura
     */
    private function initSession()
    {
        if (session_status() === PHP_SESSION_NONE) {
            // Configuración segura de sesión
            ini_set('session.cookie_httponly', 1);
            ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
            ini_set('session.use_strict_mode', 1);

            session_name($this->sessionName);
            session_start();

            // Regenerar ID de sesión para prevenir ataques
            if (!isset($_SESSION['initiated'])) {
                session_regenerate_id();
                $_SESSION['initiated'] = true;
            }

            // Verificar timeout de sesión
            $this->checkSessionTimeout();
        }
    }

    /**
     * Verificar token CSRF (delegar a Security)
     */
    public static function verifyCSRFToken($token)
    {
        require_once __DIR__ . '/Security.php';
        return Security::verifyCSRFToken($token);
    }

    /**
     * Generar token CSRF (delegar a Security)
     */
    public static function generateCSRFToken()
    {
        require_once __DIR__ . '/Security.php';
        return Security::generateCSRFToken();
    }

    /**
     * Intenta autenticar un usuario con protección adicional
     */
    public function login($username, $password, $remember = false)
    {
        require_once __DIR__ . '/Security.php';

        try {
            // Rate limiting para prevenir ataques de fuerza bruta
            if (!Security::checkRateLimit('login_' . $username, 5, 300)) {
                Security::logSecurityEvent('LOGIN_RATE_LIMIT_EXCEEDED', ['username' => $username]);
                return [
                    'success' => false,
                    'message' => 'Demasiados intentos de login. Intente en 5 minutos.'
                ];
            }

            // Sanitizar entrada
            $username = Security::sanitizeInput($username, 'string');

            // Buscar usuario usando la estructura correcta de tu BD
            $user = $this->db->selectOne(
                "SELECT * FROM usuarios WHERE usuario = ? AND estado = 'activo'",
                [$username]
            );

            if (!$user) {
                Security::logSecurityEvent('LOGIN_USER_NOT_FOUND', ['username' => $username]);
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado o inactivo'
                ];
            }

            // Verificar contraseña usando password_hash seguro
            if (!Security::verifyPassword($password, $user['password_hash'])) {
                Security::logSecurityEvent('LOGIN_WRONG_PASSWORD', ['username' => $username, 'user_id' => $user['id_usuario']]);
                Logger::warning("Intento de login fallido", ['username' => $username]);
                return [
                    'success' => false,
                    'message' => 'Contraseña incorrecta'
                ];
            }

            // Autenticación exitosa

            // Verificar y gestionar sesiones concurrentes
            $controlConcurrente = $this->gestionarSesionesConcurrentes($user['id_usuario']);
            if (!$controlConcurrente['permitir']) {
                return [
                    'success' => false,
                    'message' => $controlConcurrente['mensaje']
                ];
            }

            // Configurar sesión de usuario
            $this->setUserSession($user);

            // Registrar sesión en base de datos
            $sessionDbId = $this->sesionUsuario->iniciarSesion(
                $user['id_usuario'],
                $this->obtenerIPCliente(),
                session_id()
            );

            // Guardar ID de sesión en sesión PHP
            if ($sessionDbId) {
                $_SESSION['db_session_id'] = $sessionDbId;
            }

            $this->updateLastLogin($user['id_usuario']);

            Logger::info("Login exitoso", [
                'username' => $username,
                'session_db_id' => $sessionDbId,
                'sesiones_concurrentes' => $controlConcurrente['sesiones_activas']
            ]);

            return [
                'success' => true,
                'message' => 'Bienvenido ' . $user['nombre'],
                'user' => $user,
                'sesiones_concurrentes' => $controlConcurrente['sesiones_activas']
            ];
        } catch (Exception $e) {
            Logger::error("Error en login: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del sistema'
            ];
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        // Finalizar sesión en base de datos
        if (isset($_SESSION['db_session_id']) && isset($_SESSION['user_id'])) {
            $this->sesionUsuario->finalizarSesion(
                $_SESSION['db_session_id'],
                $_SESSION['user_id']
            );
        }

        // Limpiar datos de sesión
        $_SESSION = [];

        // Destruir cookie de sesión
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(
                session_name(),
                '',
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        // Destruir sesión
        session_destroy();

        return true;
    }

    /**
     * Verifica si el usuario está autenticado
     */
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']) && isset($_SESSION['authenticated']);
    }

    /**
     * Requiere autenticación (método estático)
     */
    public static function requireAuth()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!isset($_SESSION['user_id']) || !isset($_SESSION['authenticated'])) {
            header('Location: ?page=login');
            exit;
        }
    }

    /**
     * Requiere rol específico (método estático)
     */
    public static function requireRole($allowedRoles = [])
    {
        self::requireAuth();

        if (!isset($_SESSION['rol'])) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: ?page=dashboard&error=access_denied');
            exit;
        }

        $userRole = $_SESSION['rol'];

        // Convertir a array si es un string
        if (is_string($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }

        // Verificar si el usuario tiene uno de los roles permitidos
        if (!in_array($userRole, $allowedRoles)) {
            header('HTTP/1.1 403 Forbidden');
            header('Location: ?page=dashboard&error=access_denied');
            exit;
        }
    }



    /**
     * Obtiene los datos del usuario actual
     */
    public function getUser()
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        // Verificar si ya están en sesión
        if (isset($_SESSION['user_data'])) {
            return $_SESSION['user_data'];
        }

        // Obtener datos actuales de la base de datos
        $user = $this->db->selectOne(
            "SELECT id_usuario as id, usuario, nombre, apellido, telefono, id_rol, estado, fecha_creacion 
             FROM usuarios WHERE id_usuario = ? AND estado = 'activo'",
            [$_SESSION['user_id']]
        );

        if ($user) {
            $_SESSION['user_data'] = $user;
            return $user;
        }

        // Si no se encuentra el usuario, cerrar sesión
        $this->logout();
        return null;
    }

    /**
     * Obtiene el rol del usuario actual
     */
    public function getUserRole()
    {
        if (!self::isLoggedIn()) {
            return null;
        }

        return $_SESSION['rol'] ?? null;
    }

    /**
     * Verifica si el usuario tiene un rol específico
     */
    public static function hasRole($role)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION['rol'] ?? null;

        // Si se pasa un array de roles, verificar si el usuario tiene alguno
        if (is_array($role)) {
            return in_array($userRole, $role);
        }

        // Si se pasa un solo rol, verificar coincidencia exacta
        return $userRole === $role;
    }

    /**
     * Verifica si el usuario puede realizar una acción específica
     */
    public function can($permission)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userRole = $_SESSION['rol'] ?? null;

        // Definir permisos por rol
        $permissions = [
            'admin' => ['*'], // Acceso completo
            'vendedor' => [
                'view_productos',
                'create_productos',
                'edit_productos',
                'view_cotizaciones',
                'create_cotizaciones',
                'edit_cotizaciones',
                'view_inventario'
            ],
            'mecanico' => [
                'view_productos',
                'view_inventario',
                'edit_inventario'
            ]
        ];

        $rolePermissions = $permissions[$userRole] ?? [];

        // Admin tiene acceso total
        if (in_array('*', $rolePermissions)) {
            return true;
        }

        // Verificar permiso específico
        return in_array($permission, $rolePermissions);
    }

    /**
     * Genera token CSRF (método de instancia)
     */
    public function getCSRFToken()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
            Logger::info('Sesión iniciada para generar token CSRF');
        }

        if (!isset($_SESSION['csrf_token'])) {
            Logger::info('Generando nuevo token CSRF');
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        } else {
            Logger::info('Usando token CSRF existente', ['csrf_token' => $_SESSION['csrf_token']]);
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Valida token CSRF
     */
    public function validateCSRFToken($token)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
            Logger::info('Sesión iniciada para validar token CSRF');
        }

        $isValid = isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
        Logger::info('Validando token CSRF', ['token_enviado' => $token, 'token_sesion' => $_SESSION['csrf_token'] ?? null, 'resultado' => $isValid]);
        return $isValid;
    }

    /**
     * Configura datos de sesión del usuario
     */
    private function setUserSession($user)
    {
        // Usar el rol directo de la base de datos o mapear id_rol si existe
        $roleName = 'vendedor'; // default

        if (isset($user['rol']) && !empty($user['rol'])) {
            $roleName = $user['rol'];
        } elseif (isset($user['id_rol'])) {
            // Mapear id_rol a nombre de rol (compatibilidad)
            $roleNames = [
                1 => 'administrador',
                2 => 'vendedor',
                3 => 'mecanico'
            ];
            $roleName = $roleNames[$user['id_rol']] ?? 'vendedor';
        }

        $_SESSION['user_id'] = $user['id_usuario'];
        $_SESSION['username'] = $user['usuario'];
        $_SESSION['user_role'] = $roleName;
        $_SESSION['rol'] = $roleName;
        $_SESSION['authenticated'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['user_data'] = [
            'id' => $user['id_usuario'],
            'usuario' => $user['usuario'],
            'nombre' => $user['nombre'],
            'apellido' => $user['apellido'] ?? '',
            'email' => $user['email'] ?? '',
            'rol' => $roleName,
            'telefono' => $user['telefono'] ?? ''
        ];

        // Regenerar ID de sesión por seguridad
        session_regenerate_id(true);
    }

    /**
     * Actualiza el último login del usuario
     */
    private function updateLastLogin($userId)
    {
        try {
            // Comentado temporalmente hasta verificar estructura de BD
            // $this->db->execute(
            //     "UPDATE usuarios SET ultimo_acceso = NOW() WHERE id_usuario = ?",
            //     [$userId]
            // );

            Logger::info("Usuario logueado exitosamente", ['user_id' => $userId]);
        } catch (Exception $e) {
            // Log error but don't fail login
            Logger::warning("No se pudo actualizar último acceso: " . $e->getMessage());
        }
    }

    /**
     * Verifica timeout de sesión
     */
    private function checkSessionTimeout()
    {
        if (isset($_SESSION['login_time'])) {
            $sessionLifetime = defined('SESSION_LIFETIME') ? SESSION_LIFETIME : 3600;
            if (time() - $_SESSION['login_time'] > $sessionLifetime) {
                $this->logout();
                return false;
            }
        }
        return true;
    }

    /**
     * Cambia la contraseña del usuario
     */
    public function changePassword($userId, $currentPassword, $newPassword)
    {
        try {
            // Obtener usuario
            $user = $this->db->selectOne(
                "SELECT password, password_hash FROM usuarios WHERE id_usuario = ?",
                [$userId]
            );

            if (!$user) {
                return [
                    'success' => false,
                    'message' => 'Usuario no encontrado'
                ];
            }

            // Verificar contraseña actual
            $passwordField = isset($user['password']) ? $user['password'] : $user['password_hash'];

            if (!password_verify($currentPassword, $passwordField)) {
                return [
                    'success' => false,
                    'message' => 'Contraseña actual incorrecta'
                ];
            }

            // Actualizar contraseña
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

            $this->db->execute(
                "UPDATE usuarios SET password = ?, password_hash = ? WHERE id_usuario = ?",
                [$hashedPassword, $hashedPassword, $userId]
            );

            Logger::info("Contraseña cambiada", ['user_id' => $userId]);

            return [
                'success' => true,
                'message' => 'Contraseña actualizada correctamente'
            ];
        } catch (Exception $e) {
            Logger::error("Error al cambiar contraseña: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error interno del sistema'
            ];
        }
    }

    /**
     * Verificar si el usuario es administrador
     */
    public static function isAdmin()
    {
        return self::hasRole('administrador');
    }

    /**
     * Verificar si el usuario es vendedor
     */
    public static function isVendedor()
    {
        return self::hasRole('vendedor');
    }

    /**
     * Requerir permisos de administrador
     */
    public static function requireAdmin()
    {
        self::requireAuth();

        if (!self::isAdmin()) {
            $_SESSION['error'] = 'No tienes permisos para acceder a esta sección';
            header('Location: /dashboard');
            exit;
        }
    }

    /**
     * Obtener permisos del usuario según su rol
     */
    public static function getUserPermissions()
    {
        if (!self::isLoggedIn()) {
            return [];
        }

        $role = $_SESSION['user_role'] ?? 'vendedor';

        $permissions = [
            'administrador' => [
                'dashboard' => true,
                'productos' => ['ver', 'crear', 'editar', 'eliminar'],
                'inventario' => ['ver', 'entradas', 'salidas', 'ajustes'],
                'cotizaciones' => ['ver', 'crear', 'editar', 'eliminar', 'pdf'],
                'clientes' => ['ver', 'crear', 'editar', 'eliminar'],
                'usuarios' => ['ver', 'crear', 'editar', 'eliminar'],
                'reportes' => ['ver', 'exportar'],
                'configuracion' => ['ver', 'editar'],
                'ventas' => ['ver', 'crear']
            ],
            'vendedor' => [
                'dashboard' => true,
                'productos' => ['ver'],
                'inventario' => ['ver'],
                'cotizaciones' => ['ver', 'crear', 'editar', 'pdf'],
                'clientes' => ['ver', 'crear', 'editar'],
                'usuarios' => [],
                'reportes' => [],
                'configuracion' => [],
                'ventas' => []
            ]
        ];

        return $permissions[$role] ?? $permissions['vendedor'];
    }

    /**
     * Compatibilidad: checkPermission acepta cadenas como 'modulo.accion' o arrays
     * Retorna true/false según permisos del usuario.
     */
    public function checkPermission($permission)
    {
        if (empty($permission)) return false;

        // Permiso dado como 'modulo.accion' (ej: 'productos.update')
        if (strpos($permission, '.') !== false) {
            list($module, $action) = explode('.', $permission, 2);
            return self::hasPermission($module, $action);
        }

        // Permiso simple: tratar como módulo con acción 'ver'
        return self::hasPermission($permission, 'ver');
    }

    /**
     * Verificar si el usuario tiene un permiso específico
     */
    public static function hasPermission($module, $action = 'ver')
    {
        $permissions = self::getUserPermissions();

        if (!isset($permissions[$module])) {
            return false;
        }

        if ($permissions[$module] === true) {
            return true;
        }

        if (is_array($permissions[$module])) {
            return in_array($action, $permissions[$module]);
        }

        return false;
    }

    // =============================================
    // FUNCIONES PARA CONTROL DE SESIONES AVANZADO
    // =============================================

    /**
     * Gestionar sesiones concurrentes de un usuario
     * 
     * @param int $userId ID del usuario
     * @return array Resultado de la gestión
     */
    private function gestionarSesionesConcurrentes($userId)
    {
        try {
            $sesionesActivas = $this->sesionUsuario->contarSesionesConcurrentes($userId);
            $maxSesiones = defined('MAX_SESIONES_CONCURRENTES') ? MAX_SESIONES_CONCURRENTES : 3;

            if ($sesionesActivas >= $maxSesiones) {
                // Política: cerrar sesión más antigua
                $this->cerrarSesionMasAntigua($userId);
                $sesionesActivas--;
            }

            return [
                'permitir' => true,
                'sesiones_activas' => $sesionesActivas + 1, // +1 porque la nueva sesión se contará
                'mensaje' => $sesionesActivas > 0 ?
                    "Tienes {$sesionesActivas} sesión(es) activa(s) adicional(es)" :
                    "Primera sesión activa"
            ];
        } catch (Exception $e) {
            Logger::error("Error al gestionar sesiones concurrentes: " . $e->getMessage());
            return [
                'permitir' => true,
                'sesiones_activas' => 1,
                'mensaje' => 'Control de sesiones no disponible'
            ];
        }
    }

    /**
     * Cerrar la sesión más antigua de un usuario
     * 
     * @param int $userId ID del usuario
     * @return bool
     */
    private function cerrarSesionMasAntigua($userId)
    {
        try {
            $sesionesActivas = $this->sesionUsuario->obtenerSesionesActivas($userId);

            if (!empty($sesionesActivas)) {
                // Ordenar por fecha y tomar la más antigua
                usort($sesionesActivas, function ($a, $b) {
                    return strtotime($a['inicio_sesion']) - strtotime($b['inicio_sesion']);
                });

                $sesionAntigua = $sesionesActivas[0];
                return $this->sesionUsuario->finalizarSesion($sesionAntigua['id_sesion'], $userId);
            }

            return true;
        } catch (Exception $e) {
            Logger::error("Error al cerrar sesión antigua: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener sesiones activas del usuario actual
     * 
     * @return array
     */
    public function obtenerMisSesionesActivas()
    {
        if (!self::isLoggedIn()) {
            return [];
        }

        return $this->sesionUsuario->obtenerSesionesActivas($_SESSION['user_id']);
    }

    /**
     * Cerrar una sesión específica del usuario actual
     * 
     * @param int $sessionId ID de la sesión
     * @return bool
     */
    public function cerrarMiSesion($sessionId)
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        return $this->sesionUsuario->finalizarSesion($sessionId, $_SESSION['user_id']);
    }

    /**
     * Cerrar todas las demás sesiones del usuario actual
     * 
     * @return bool
     */
    public function cerrarOtrasSesiones()
    {
        if (!self::isLoggedIn()) {
            return false;
        }

        $userId = $_SESSION['user_id'];
        $sesionActual = $_SESSION['db_session_id'] ?? null;

        try {
            $sesiones = $this->sesionUsuario->obtenerSesionesActivas($userId);
            $cerradas = 0;

            foreach ($sesiones as $sesion) {
                if ($sesion['id_sesion'] != $sesionActual) {
                    if ($this->sesionUsuario->finalizarSesion($sesion['id_sesion'], $userId)) {
                        $cerradas++;
                    }
                }
            }

            return $cerradas;
        } catch (Exception $e) {
            Logger::error("Error al cerrar otras sesiones: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtener estadísticas de sesiones del usuario actual
     * 
     * @return array
     */
    public function obtenerEstadisticasMisSesiones()
    {
        if (!self::isLoggedIn()) {
            return [];
        }

        try {
            $userId = $_SESSION['user_id'];
            $estadisticas = $this->sesionUsuario->obtenerEstadisticas('month');

            // Añadir información específica del usuario
            $sesionesUsuario = $this->sesionUsuario->obtenerSesionesActivas($userId);
            $estadisticas['mis_sesiones_activas'] = count($sesionesUsuario);

            return $estadisticas;
        } catch (Exception $e) {
            Logger::error("Error al obtener estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener IP del cliente
     * 
     * @return string
     */
    private function obtenerIPCliente()
    {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ip = $_SERVER[$key];
                // Si hay múltiples IPs, tomar la primera
                if (strpos($ip, ',') !== false) {
                    $ip = trim(explode(',', $ip)[0]);
                }
                // Validar que sea una IP válida
                if (filter_var($ip, FILTER_VALIDATE_IP)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    }

    /**
     * Limpiar sesiones expiradas automáticamente
     * 
     * @param int $horasVencimiento Horas de vencimiento
     * @return int Número de sesiones limpiadas
     */
    public function limpiarSesionesExpiradas($horasVencimiento = 24)
    {
        try {
            return $this->sesionUsuario->limpiarSesionesExpiradas($horasVencimiento);
        } catch (Exception $e) {
            Logger::error("Error al limpiar sesiones expiradas: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Verificar si la sesión actual es válida en base de datos
     * 
     * @return bool
     */
    public function verificarSesionBD()
    {
        if (!self::isLoggedIn() || !isset($_SESSION['db_session_id'])) {
            return false;
        }

        try {
            $sesiones = $this->sesionUsuario->obtenerSesionesActivas($_SESSION['user_id']);

            foreach ($sesiones as $sesion) {
                if ($sesion['id_sesion'] == $_SESSION['db_session_id']) {
                    return true;
                }
            }

            return false;
        } catch (Exception $e) {
            Logger::error("Error al verificar sesión BD: " . $e->getMessage());
            return false;
        }
    }
}
