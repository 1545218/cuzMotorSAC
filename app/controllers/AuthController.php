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
            $this->redirect('dashboard');
            return;
        }

        // Comentamos la verificación de remember token por ahora
        // $this->auth->checkRememberToken();
        if ($this->auth->isLoggedIn()) {
            $this->redirect('dashboard');
            return;
        }

        $data = [
            'title' => 'Iniciar Sesión',
            'csrf_token' => $this->auth->getCSRFToken(),
            'flash_messages' => $this->getFlashMessages()
        ];

        $this->renderLoginView($data);
    }

    /**
     * Procesa el login del usuario
     */
    public function login()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('auth/login');
            return;
        }

        // Validar CSRF
        $this->validateCSRF();

        $username = $this->sanitize($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validar entrada
        $errors = $this->validate([
            'username' => $username,
            'password' => $password
        ], [
            'username' => 'required',
            'password' => 'required'
        ]);

        if (!empty($errors)) {
            Logger::warning("Intento de login con datos incompletos", [
                'username' => $username ?? 'no proporcionado',
                'errors' => $errors,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            $this->setFlash('error', 'Por favor complete todos los campos');
            $this->redirect('auth/login');
            return;
        }

        // Intentar autenticación
        $result = $this->auth->login($username, $password, $remember);

        if ($result['success']) {
            Logger::info("Login exitoso", [
                'username' => $username,
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);

            // Registrar en logs del sistema
            try {
                require_once APP_PATH . '/models/LogSistema.php';
                $logSistema = new LogSistema();
                $user = $this->auth->getUser();
                if ($user) {
                    $logSistema->registrarAccion($user['id_usuario'], 'login', 'Usuario logueado exitosamente');
                }
            } catch (Exception $e) {
                // No interrumpir el flujo si falla el log
                error_log("Error al registrar log de login: " . $e->getMessage());
            }

            $this->setFlash('success', $result['message']);
            $this->logActivity('login', 'Usuario logueado exitosamente');
            $this->redirect('dashboard');
        } else {
            Logger::warning("Intento de login fallido", [
                'username' => $username,
                'reason' => $result['message'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
            ]);
            $this->setFlash('error', $result['message']);
            $this->redirect('auth/login');
        }
    }

    /**
     * Cierra la sesión del usuario
     */
    public function logout()
    {
        $user = $this->auth->getUser();
        Logger::info("Logout realizado", [
            'username' => $user['usuario'] ?? 'unknown',
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ]);
        $this->logActivity('logout', 'Usuario cerró sesión');
        $this->auth->logout();
        $this->setFlash('success', 'Sesión cerrada correctamente');
        $this->redirect('auth/login');
    }

    /**
     * Renderiza la vista de login (sin layout principal)
     */
    private function renderLoginView($data)
    {
        extract($data);
        include APP_PATH . '/views/auth/login.php';
    }
}
