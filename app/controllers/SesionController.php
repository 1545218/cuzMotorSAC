<?php

/**
 * Controlador de Sesiones - Gestión avanzada de sesiones de usuarios
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class SesionController extends Controller
{
    private $sesionUsuario;
    protected $auth;

    public function __construct()
    {
        parent::__construct();

        // Verificar autenticación
        Auth::requireAuth();

        // Inicializar modelos
        $this->sesionUsuario = new SesionUsuario();
        $this->auth = new Auth();
    }

    /**
     * Mostrar sesiones activas (administradores: todas, usuarios: propias)
     */
    public function index()
    {
        try {
            $isAdmin = Auth::hasRole('administrador');
            $filtros = [];
            $sesiones = [];
            $estadisticas = [];

            // Procesar filtros de búsqueda
            if ($_GET) {
                $filtros = [
                    'usuario_id' => $_GET['usuario_id'] ?? '',
                    'activo' => $_GET['activo'] ?? '',
                    'fecha_desde' => $_GET['fecha_desde'] ?? '',
                    'fecha_hasta' => $_GET['fecha_hasta'] ?? '',
                    'ip' => $_GET['ip'] ?? ''
                ];
            }

            if ($isAdmin) {
                // Los administradores ven todas las sesiones
                $sesiones = $this->sesionUsuario->obtenerSesionesConFiltros($filtros, 100, 0);
                $estadisticas = $this->sesionUsuario->obtenerEstadisticas('today');

                // Obtener lista de usuarios para filtro
                $usuarios = $this->db->select("SELECT id_usuario, usuario, nombre FROM usuarios WHERE estado = 'activo' ORDER BY usuario");
            } else {
                // Los usuarios normales solo ven sus propias sesiones
                $filtros['usuario_id'] = $_SESSION['user_id'];
                $sesiones = $this->sesionUsuario->obtenerSesionesConFiltros($filtros, 50, 0);
                $estadisticas = $this->auth->obtenerEstadisticasMisSesiones();
                $usuarios = [];
            }

            renderView('sesiones/index', [
                'sesiones' => $sesiones,
                'estadisticas' => $estadisticas,
                'filtros' => $filtros,
                'usuarios' => $usuarios,
                'isAdmin' => $isAdmin,
                'title' => 'Gestión de Sesiones'
            ]);
        } catch (Exception $e) {
            Logger::error("Error en sesiones/index: " . $e->getMessage());
            $this->redirectToSesiones('Error al cargar las sesiones');
        }
    }

    /**
     * Cerrar una sesión específica
     */
    public function cerrar()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToSesiones('Método no permitido');
                return;
            }

            // Verificar CSRF token
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectToSesiones('Token de seguridad inválido');
                return;
            }

            $sessionId = (int)($_POST['session_id'] ?? 0);
            $isAdmin = Auth::hasRole('administrador');

            if (!$sessionId) {
                $this->redirectToSesiones('ID de sesión no válido');
                return;
            }

            $success = false;

            if ($isAdmin) {
                // Los administradores pueden cerrar cualquier sesión
                $success = $this->sesionUsuario->finalizarSesion($sessionId);
                $mensaje = $success ? 'Sesión cerrada exitosamente' : 'Error al cerrar la sesión';
            } else {
                // Los usuarios solo pueden cerrar sus propias sesiones
                $success = $this->auth->cerrarMiSesion($sessionId);
                $mensaje = $success ? 'Tu sesión fue cerrada exitosamente' : 'Error al cerrar tu sesión';
            }

            $this->redirectToSesiones($mensaje);
        } catch (Exception $e) {
            Logger::error("Error al cerrar sesión: " . $e->getMessage());
            $this->redirectToSesiones('Error interno al cerrar la sesión');
        }
    }

    /**
     * Cerrar todas las demás sesiones del usuario actual
     */
    public function cerrarOtras()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToSesiones('Método no permitido');
                return;
            }

            // Verificar CSRF token
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectToSesiones('Token de seguridad inválido');
                return;
            }

            $cerradas = $this->auth->cerrarOtrasSesiones();

            if ($cerradas !== false) {
                $mensaje = $cerradas > 0 ?
                    "Se cerraron {$cerradas} sesión(es) adicional(es)" :
                    "No hay otras sesiones activas";
            } else {
                $mensaje = "Error al cerrar las otras sesiones";
            }

            $this->redirectToSesiones($mensaje);
        } catch (Exception $e) {
            Logger::error("Error al cerrar otras sesiones: " . $e->getMessage());
            $this->redirectToSesiones('Error interno');
        }
    }

    /**
     * Limpiar sesiones expiradas (solo administradores)
     */
    public function limpiar()
    {
        try {
            Auth::requireRole('administrador');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToSesiones('Método no permitido');
                return;
            }

            // Verificar CSRF token
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectToSesiones('Token de seguridad inválido');
                return;
            }

            $horas = (int)($_POST['horas_vencimiento'] ?? 24);
            $limpiadas = $this->auth->limpiarSesionesExpiradas($horas);

            $mensaje = $limpiadas > 0 ?
                "Se limpiaron {$limpiadas} sesión(es) expirada(s)" :
                "No hay sesiones expiradas para limpiar";

            $this->redirectToSesiones($mensaje);
        } catch (Exception $e) {
            Logger::error("Error al limpiar sesiones: " . $e->getMessage());
            $this->redirectToSesiones('Error al limpiar sesiones expiradas');
        }
    }

    /**
     * Cerrar todas las sesiones de un usuario específico (solo administradores)
     */
    public function cerrarUsuario()
    {
        try {
            Auth::requireRole('administrador');

            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->redirectToSesiones('Método no permitido');
                return;
            }

            // Verificar CSRF token
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->redirectToSesiones('Token de seguridad inválido');
                return;
            }

            $userId = (int)($_POST['user_id'] ?? 0);

            if (!$userId) {
                $this->redirectToSesiones('ID de usuario no válido');
                return;
            }

            $success = $this->sesionUsuario->finalizarTodasLasSesiones($userId);

            $mensaje = $success ?
                'Todas las sesiones del usuario fueron cerradas' :
                'Error al cerrar las sesiones del usuario';

            $this->redirectToSesiones($mensaje);
        } catch (Exception $e) {
            Logger::error("Error al cerrar sesiones de usuario: " . $e->getMessage());
            $this->redirectToSesiones('Error interno');
        }
    }

    /**
     * Mostrar estadísticas detalladas de sesiones (solo administradores)
     */
    public function estadisticas()
    {
        try {
            Auth::requireRole('administrador');

            $periodos = ['today', 'week', 'month'];
            $estadisticas = [];

            foreach ($periodos as $periodo) {
                $estadisticas[$periodo] = $this->sesionUsuario->obtenerEstadisticas($periodo);
            }

            // Sesiones por usuario (top 10)
            $topUsuarios = $this->db->select("
                SELECT u.usuario, u.nombre, COUNT(s.id_sesion) as total_sesiones,
                       SUM(CASE WHEN s.activo = 1 THEN 1 ELSE 0 END) as sesiones_activas
                FROM usuarios u 
                LEFT JOIN sesionusuarios s ON u.id_usuario = s.id_usuario 
                WHERE s.inicio_sesion >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                GROUP BY u.id_usuario, u.usuario, u.nombre 
                ORDER BY total_sesiones DESC 
                LIMIT 10
            ");

            // Sesiones por hora del día (últimos 7 días)
            $sesionesPorHora = $this->db->select("
                SELECT HOUR(inicio_sesion) as hora, COUNT(*) as total
                FROM sesionusuarios 
                WHERE inicio_sesion >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY HOUR(inicio_sesion)
                ORDER BY hora
            ");

            renderView('sesiones/estadisticas', [
                'estadisticas' => $estadisticas,
                'topUsuarios' => $topUsuarios,
                'sesionesPorHora' => $sesionesPorHora,
                'title' => 'Estadísticas de Sesiones'
            ]);
        } catch (Exception $e) {
            Logger::error("Error en estadísticas de sesiones: " . $e->getMessage());
            $this->redirectToSesiones('Error al cargar estadísticas');
        }
    }

    /**
     * API para obtener sesiones activas en tiempo real (AJAX)
     */
    public function activas()
    {
        try {
            header('Content-Type: application/json');

            $isAdmin = Auth::hasRole('administrador');

            if ($isAdmin) {
                $sesiones = $this->sesionUsuario->obtenerSesionesActivas();
            } else {
                $sesiones = $this->auth->obtenerMisSesionesActivas();
            }

            echo json_encode([
                'success' => true,
                'sesiones' => $sesiones,
                'total' => count($sesiones)
            ]);
        } catch (Exception $e) {
            header('HTTP/1.1 500 Internal Server Error');
            echo json_encode([
                'success' => false,
                'message' => 'Error al obtener sesiones activas'
            ]);
        }
    }

    /**
     * Redirigir con mensaje
     */
    protected function redirectToSesiones($message = '')
    {
        if ($message) {
            $_SESSION['message'] = $message;
        }
        header("Location: ?page=sesiones");
        exit;
    }
}
