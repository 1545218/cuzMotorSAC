<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/BackupSistema.php';

class BackupSistemaController extends Controller
{
    private $backupModel;
    protected $db;
    protected $auth;
    protected $logger;

    public function __construct()
    {
        // Incluir archivos de configuración
        require_once ROOT_PATH . '/config/config.php';

        // No llamar al constructor padre para evitar que cargue vistas automáticamente
        // parent::__construct();

        // Inicializar base de datos
        try {
            $this->db = Database::getInstance();
        } catch (Exception $e) {
            throw $e;
        }

        // Inicializar autenticación
        $this->auth = new Auth();

        // Inicializar logger
        $this->logger = new Logger();

        // Inicializar modelo
        $this->backupModel = new BackupSistema();
    }

    /**
     * Vista principal de backups
     */
    public function index()
    {
        // Verificar autenticación y permisos de administrador
        if (!$this->auth || !$this->auth->isLoggedIn()) {
            header('Location: /?page=login');
            exit;
        }

        $userRole = $this->auth->getUserRole();
        if ($userRole !== 'administrador') {
            $this->view('errors/403', ['error' => 'No tienes permisos para acceder a esta sección']);
            return;
        }

        try {
            // Obtener lista de backups
            $backups = $this->backupModel->getListaBackups(50, 0);

            // Obtener estadísticas
            $estadisticas = $this->backupModel->getEstadisticas();

            // Verificar integridad
            $integridad = $this->backupModel->verificarIntegridad();

            $this->view('backups/index', [
                'backups' => $backups,
                'estadisticas' => $estadisticas,
                'integridad' => $integridad,
                'title' => 'Gestión de Backups del Sistema'
            ]);
        } catch (Exception $e) {
            $this->view('errors/500', ['error' => 'Error al cargar backups: ' . $e->getMessage()]);
        }
    }

    /**
     * Crear un nuevo backup
     */
    public function crear()
    {
        // Configurar para respuesta JSON - suprimir warnings que interfieren
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);

        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Iniciar captura de output para evitar warnings accidentales
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        try {
            $user = $this->auth->getUser();
            if (!$user) {
                throw new Exception('Usuario no autenticado');
            }

            // Verificar que el usuario tenga ID
            $userId = isset($user['id']) ? $user['id'] : (isset($user['id_usuario']) ? $user['id_usuario'] : null);
            if (!$userId) {
                throw new Exception('ID de usuario no encontrado');
            }

            $resultado = $this->backupModel->generarBackup($userId);

            // Limpiar cualquier output previo y enviar JSON
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $resultado['success'],
                'mensaje' => $resultado['success'] ?
                    'Backup creado exitosamente: ' . $resultado['archivo'] :
                    $resultado['error'],
                'datos' => $resultado['success'] ? [
                    'archivo' => $resultado['archivo'],
                    'tamaño' => $this->formatBytes($resultado['tamaño'])
                ] : null
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error al crear backup: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Eliminar un backup
     */
    public function eliminar()
    {
        // Configurar para respuesta JSON
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);

        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'ID de backup inválido']);
            exit;
        }

        try {
            $resultado = $this->backupModel->eliminarBackup($id);

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Restaurar un backup
     */
    public function restaurar()
    {
        // Configurar para respuesta JSON
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);

        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

        if ($id <= 0) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'ID de backup inválido']);
            exit;
        }

        try {
            $user = $this->auth->getUser();
            if (!$user) {
                throw new Exception('Usuario no autenticado');
            }

            // Verificar que el usuario tenga ID
            $userId = isset($user['id']) ? $user['id'] : (isset($user['id_usuario']) ? $user['id_usuario'] : null);
            if (!$userId) {
                throw new Exception('ID de usuario no encontrado');
            }

            $resultado = $this->backupModel->restaurarBackup($id, $userId);

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode($resultado);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Descargar un backup
     */
    public function descargar()
    {
        $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

        if ($id <= 0) {
            $this->view('errors/404', ['error' => 'Backup no encontrado']);
            return;
        }

        try {
            $backup = $this->backupModel->getBackupPorId($id);
            if (!$backup) {
                $this->view('errors/404', ['error' => 'Backup no encontrado']);
                return;
            }

            $rutaArchivo = ROOT_PATH . '/storage/backups/' . $backup['nombre_archivo'];

            if (!file_exists($rutaArchivo)) {
                $this->view('errors/404', ['error' => 'Archivo de backup no existe']);
                return;
            }

            // Configurar headers para descarga
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $backup['nombre_archivo'] . '"');
            header('Content-Length: ' . filesize($rutaArchivo));
            header('Cache-Control: must-revalidate');
            header('Pragma: public');

            // Enviar archivo
            readfile($rutaArchivo);
            exit;
        } catch (Exception $e) {
            $this->view('errors/500', ['error' => 'Error al descargar backup: ' . $e->getMessage()]);
        }
    }

    /**
     * Limpiar backups antiguos
     */
    public function limpiar()
    {
        // Configurar para respuesta JSON
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);

        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            ob_end_clean();
            header('HTTP/1.1 405 Method Not Allowed');
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Método no permitido']);
            exit;
        }

        $dias = isset($_POST['dias']) ? (int)$_POST['dias'] : 30;

        if ($dias < 1) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'mensaje' => 'Número de días inválido']);
            exit;
        }

        try {
            $resultado = $this->backupModel->limpiarBackupsAntiguos($dias);

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => $resultado['success'],
                'mensaje' => $resultado['success'] ?
                    "Se eliminaron {$resultado['eliminados']} backups antiguos" :
                    $resultado['error']
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Verificar integridad de backups
     */
    public function verificarIntegridad()
    {
        // Configurar para respuesta JSON
        error_reporting(E_ERROR | E_PARSE);
        ini_set('display_errors', 0);

        // Limpiar cualquier output previo
        while (ob_get_level()) {
            ob_end_clean();
        }
        ob_start();

        try {
            $resultado = $this->backupModel->verificarIntegridad();

            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'mensaje' => 'Verificación completada exitosamente',
                'archivos_ok' => $resultado['archivos_ok'] ?? 0,
                'archivos_faltantes' => $resultado['archivos_faltantes'] ?? 0,
                'total_verificados' => $resultado['total_verificados'] ?? 0
            ]);
        } catch (Exception $e) {
            ob_end_clean();
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'mensaje' => 'Error: ' . $e->getMessage()
            ]);
        }
        exit;
    }

    /**
     * Formatear bytes a tamaño legible
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}
