<?php

/**
 * AuthController - Controlador de autenticación
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class AuthController extends Controller
{

    public function __construct()
    {
        // No llamar al constructor padre para evitar verificación de autenticación en login
        require_once ROOT_PATH . '/config/config.php';
        try {
            $this->db = Database::getInstance();
            $this->auth = new Auth();
            Logger::debug("AuthController inicializado correctamente");
        } catch (Exception $e) {
            Logger::error("Error al inicializar AuthController: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Muestra el formulario de login
     */
    public function showLogin()
    {
        // Si ya está autenticado, redirigir al dashboard
        if ($this->auth->isLoggedIn()) {
            $this->redirectWithFlash('dashboard', 'info', 'Ya tienes una sesión activa');
            return;
        }

        // Verificar remember token si existe
        if (method_exists($this->auth, 'checkRememberToken') && $this->auth->checkRememberToken() && $this->auth->isLoggedIn()) {
            $this->redirectWithFlash('dashboard', 'success', 'Sesión restaurada automáticamente');
            return;
        }

        $data = [
            'title' => 'Iniciar Sesión - Sistema de Inventario',
            'csrf_token' => $this->auth->getCSRFToken(),
            'flash_messages' => $this->getFlashMessages(),
            'username_value' => $this->getRememberedUsername()
        ];

        $this->renderLoginView($data);
    }

    /**
     * Procesa el login del usuario
     */
    public function login()
    {
        // Validar método HTTP de manera más robusta
        if (!$this->isPostRequest()) {
            $this->redirectWithFlash('auth/login', 'error', 'Método no permitido');
            return;
        }

        // Validar CSRF con manejo de errores
        if (!$this->validateCSRF()) {
            $this->redirectWithFlash('auth/login', 'error', 'Token de seguridad inválido');
            return;
        }

        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validar entrada con mensajes más específicos
        $errors = $this->validateLoginInput($username, $password);

        if (!empty($errors)) {
            Logger::warning("Intento de login con datos inválidos", [
                'username' => $username,
                'errors' => $errors,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->redirectWithFlash('auth/login', 'error', implode(', ', $errors));
            return;
        }

        // Intentar autenticación
        $result = $this->auth->login($username, $password, $remember);

        if ($result['success']) {
            Logger::info("Login exitoso", [
                'username' => $username,
                'user_id' => $result['user']['id_usuario'] ?? ($result['user']['id'] ?? 'unknown'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $this->logActivity('login', 'Inicio de sesión exitoso');
            $this->redirectWithFlash('dashboard', 'success', $result['message']);
        } else {
            Logger::warning("Intento de login fallido", [
                'username' => $username,
                'reason' => $result['message'],
                'attempts' => $this->getLoginAttempts($username),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            $this->redirectWithFlash('auth/login', 'error', $this->getUserFriendlyErrorMessage($result['message']));
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        if (!$this->auth->isLoggedIn()) {
            $this->redirect('auth/login');
            return;
        }

        $user = $this->auth->getUser();
        Logger::info("Logout realizado", [
            'username' => $user['usuario'] ?? 'unknown',
            'user_id' => $user['id'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        
        $this->logActivity('logout', 'Cierre de sesión');
        $this->auth->logout();
        
        $this->redirectWithFlash('auth/login', 'success', 'Sesión cerrada correctamente. ¡Vuelve pronto!');
    }

    /**
     * Métodos auxiliares para mejor organización
     */
    
    private function isPostRequest(): bool
    {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    private function validateLoginInput(string $username, string $password): array
    {
        $errors = [];

        if (empty(trim($username))) {
            $errors[] = 'El nombre de usuario es requerido';
        }

        if (empty($password)) {
            $errors[] = 'La contraseña es requerida';
        }

        if (strlen($username) > 50) {
            $errors[] = 'El nombre de usuario es demasiado largo';
        }

        return $errors;
    }

    private function getUserFriendlyErrorMessage(string $technicalMessage): string
    {
        $messages = [
            'invalid_credentials' => 'Usuario o contraseña incorrectos',
            'account_locked' => 'Cuenta temporalmente bloqueada',
            'inactive_account' => 'La cuenta está inactiva'
        ];

        return $messages[$technicalMessage] ?? 'Error al iniciar sesión. Por favor intenta nuevamente.';
    }

    private function redirectWithFlash(string $route, string $type, string $message): void
    {
        $this->setFlash($type, $message);
        $this->redirect($route);
    }

    private function getRememberedUsername(): string
    {
        return $this->sanitize($_COOKIE['remembered_username'] ?? '');
    }

    private function getLoginAttempts(string $username): int
    {
        // Implementar lógica de intentos de login si existe
        return 0; // Placeholder
    }

    /**
     * Renderiza la vista de login (sin layout principal)
     */
    private function renderLoginView($data): void
    {
        extract($data);
        include APP_PATH . '/views/auth/login.php';
    }
}
