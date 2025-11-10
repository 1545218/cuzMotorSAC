<?php

class AuditoriaController extends Controller
{
    private $historialCambiosModel;

    public function __construct()
    {
        parent::__construct();

        // Verificar autenticación y permisos de administrador
        Auth::requireAuth();
        Auth::requireRole(['administrador']);

        $this->historialCambiosModel = new HistorialCambios();
    }

    /**
     * Vista principal de auditoría
     */
    public function index()
    {
        try {
            // Obtener filtros de la URL
            $filtros = [
                'tabla' => $_GET['tabla'] ?? '',
                'usuario' => $_GET['usuario'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
            ];

            // Obtener historial con filtros
            $historial = $this->historialCambiosModel->getHistorialConFiltros($filtros);

            // Obtener estadísticas
            $estadisticas = $this->historialCambiosModel->getEstadisticasPorTabla(30);

            // Obtener usuarios para el filtro
            require_once APP_PATH . '/models/Usuario.php';
            $usuarioModel = new Usuario();
            $usuarios = $usuarioModel->getAll();

            $this->view('auditoria/index', [
                'title' => 'Auditoría del Sistema',
                'breadcrumb' => [
                    ['title' => 'Auditoría']
                ],
                'historial' => $historial,
                'estadisticas' => $estadisticas,
                'usuarios' => $usuarios,
                'filtros' => $filtros
            ]);
        } catch (Exception $e) {
            Logger::error("Error cargando auditoría: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Ver estadísticas detalladas
     */
    public function estadisticas()
    {
        try {
            $estadisticasTablas = $this->historialCambiosModel->getTablasActividad();
            $cambiosRecientes = $this->historialCambiosModel->getCambiosRecientes(20);

            $this->view('auditoria/estadisticas', [
                'title' => 'Estadísticas de Auditoría',
                'breadcrumb' => [
                    ['title' => 'Auditoría', 'url' => '?page=auditoria'],
                    ['title' => 'Estadísticas']
                ],
                'estadisticasTablas' => $estadisticasTablas,
                'cambiosRecientes' => $cambiosRecientes
            ]);
        } catch (Exception $e) {
            Logger::error("Error cargando estadísticas de auditoría: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Buscar en el historial de cambios
     */
    public function buscar()
    {
        try {
            $busqueda = $_GET['q'] ?? '';
            $resultados = [];

            if (!empty($busqueda)) {
                $resultados = $this->historialCambiosModel->buscarCambios($busqueda);
            }

            $this->view('auditoria/buscar', [
                'title' => 'Buscar en Auditoría',
                'breadcrumb' => [
                    ['title' => 'Auditoría', 'url' => '?page=auditoria'],
                    ['title' => 'Buscar']
                ],
                'busqueda' => $busqueda,
                'resultados' => $resultados
            ]);
        } catch (Exception $e) {
            Logger::error("Error en búsqueda de auditoría: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Ver historial de un registro específico
     */
    public function registro()
    {
        try {
            $tabla = $_GET['tabla'] ?? '';
            $id = $_GET['id'] ?? 0;

            if (empty($tabla) || empty($id)) {
                throw new Exception('Parámetros inválidos');
            }

            $historial = $this->historialCambiosModel->getHistorialRegistro($tabla, $id);

            $this->view('auditoria/registro', [
                'title' => "Historial de {$tabla} #{$id}",
                'breadcrumb' => [
                    ['title' => 'Auditoría', 'url' => '?page=auditoria'],
                    ['title' => "Registro {$tabla}"]
                ],
                'historial' => $historial,
                'tabla' => $tabla,
                'registro_id' => $id
            ]);
        } catch (Exception $e) {
            Logger::error("Error cargando historial de registro: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Limpieza de registros antiguos
     */
    public function limpiar()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $diasMantenimiento = intval($_POST['dias_mantenimiento'] ?? 365);

                if ($diasMantenimiento < 30) {
                    throw new Exception('Debe mantener al menos 30 días de historial');
                }

                $eliminados = $this->historialCambiosModel->limpiarRegistrosAntiguos($diasMantenimiento);

                if ($eliminados !== false) {
                    $this->setFlash('success', "Se eliminaron {$eliminados} registros antiguos exitosamente");
                    Logger::info("Limpieza de auditoría realizada", ['eliminados' => $eliminados, 'dias' => $diasMantenimiento]);
                } else {
                    throw new Exception('Error al realizar la limpieza');
                }
            } catch (Exception $e) {
                $this->setFlash('error', 'Error en limpieza: ' . $e->getMessage());
                Logger::error("Error en limpieza de auditoría: " . $e->getMessage());
            }
        }

        $this->redirect('?page=auditoria');
    }

    /**
     * Exportar historial de auditoría
     */
    public function exportar()
    {
        try {
            $formato = $_GET['formato'] ?? 'csv';
            $filtros = [
                'tabla' => $_GET['tabla'] ?? '',
                'fecha_desde' => $_GET['fecha_desde'] ?? '',
                'fecha_hasta' => $_GET['fecha_hasta'] ?? ''
            ];

            $datos = $this->historialCambiosModel->getHistorialConFiltros($filtros);

            if ($formato === 'csv') {
                $this->exportarCSV($datos);
            } else {
                throw new Exception('Formato no soportado');
            }
        } catch (Exception $e) {
            Logger::error("Error exportando auditoría: " . $e->getMessage());
            $this->setFlash('error', 'Error al exportar: ' . $e->getMessage());
            $this->redirect('?page=auditoria');
        }
    }

    /**
     * Exportar datos en formato CSV
     */
    private function exportarCSV($datos)
    {
        $filename = 'auditoria_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        // Encabezados
        fputcsv($output, ['ID', 'Usuario', 'Tabla', 'Registro ID', 'Campo', 'Valor Anterior', 'Valor Nuevo', 'Fecha']);

        // Datos
        foreach ($datos as $fila) {
            fputcsv($output, [
                $fila['id_cambio'],
                $fila['nombre'] . ' ' . $fila['apellido'],
                $fila['tabla_afectada'],
                $fila['registro_id'],
                $fila['campo_modificado'],
                $fila['valor_anterior'],
                $fila['valor_nuevo'],
                $fila['fecha']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * API para obtener cambios recientes (AJAX)
     */
    public function api_recientes()
    {
        try {
            $limite = intval($_GET['limite'] ?? 10);
            $cambios = $this->historialCambiosModel->getCambiosRecientes($limite);

            $this->json(['success' => true, 'data' => $cambios]);
        } catch (Exception $e) {
            $this->json(['success' => false, 'error' => $e->getMessage()]);
        }
    }

    /**
     * NUEVAS FUNCIONES DE SEGURIDAD AVANZADA
     */

    /**
     * Dashboard de seguridad en tiempo real
     */
    public function dashboard_seguridad()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            switch ($input['type'] ?? '') {
                case 'metricas':
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'metrics' => $this->getMetricasSeguridad()
                    ]);
                    exit;

                case 'sesiones_activas':
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'sesiones' => $this->getSesionesActivas()
                    ]);
                    exit;

                case 'logs_criticos':
                    header('Content-Type: application/json');
                    echo json_encode([
                        'success' => true,
                        'logs' => $this->getLogsCriticos()
                    ]);
                    exit;
            }
        }

        $this->view('auditoria/dashboard_seguridad', [
            'title' => 'Dashboard de Seguridad'
        ]);
    }

    /**
     * Monitoreo de sesiones activas
     */
    public function monitoreo_sesiones()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $input = json_decode(file_get_contents('php://input'), true);

            switch ($input['action'] ?? '') {
                case 'cerrar_sesion':
                    $resultado = $this->cerrarSesionRemota($input['sesion_id']);
                    header('Content-Type: application/json');
                    echo json_encode($resultado);
                    exit;

                case 'bloquear_usuario':
                    $resultado = $this->bloquearUsuario($input['usuario_id']);
                    header('Content-Type: application/json');
                    echo json_encode($resultado);
                    exit;
            }
        }

        $this->view('auditoria/monitoreo_sesiones', [
            'title' => 'Monitoreo de Sesiones'
        ]);
    }

    /**
     * Detectar actividad sospechosa
     */
    public function detectar_anomalias()
    {
        try {
            $db = Database::getInstance();

            // Múltiples intentos fallidos
            $loginsFallidos = $db->select("
                SELECT 
                    usuario_intento,
                    COUNT(*) as intentos,
                    MAX(fecha) as ultimo_intento
                FROM historial_cambios 
                WHERE campo_modificado = 'login_fallido' 
                AND fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY usuario_intento
                HAVING intentos >= 5
            ");

            // Actividad fuera de horario
            $actividadNocturna = $db->select("
                SELECT 
                    id_usuario,
                    COUNT(*) as actividades
                FROM historial_cambios 
                WHERE HOUR(fecha) BETWEEN 0 AND 6
                AND DATE(fecha) = CURDATE()
                GROUP BY id_usuario
                HAVING actividades >= 10
            ");

            // Cambios masivos
            $cambiosMasivos = $db->select("
                SELECT 
                    id_usuario,
                    tabla_afectada,
                    COUNT(*) as cambios
                FROM historial_cambios 
                WHERE fecha >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
                GROUP BY id_usuario, tabla_afectada
                HAVING cambios >= 50
            ");

            return [
                'logins_fallidos' => $loginsFallidos,
                'actividad_nocturna' => $actividadNocturna,
                'cambios_masivos' => $cambiosMasivos
            ];
        } catch (Exception $e) {
            error_log("Error detectando anomalías: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener métricas de seguridad
     */
    private function getMetricasSeguridad()
    {
        try {
            $db = Database::getInstance();

            // Actividad por hora
            $actividadHora = $db->select("
                SELECT 
                    HOUR(fecha) as hora,
                    COUNT(*) as actividades
                FROM historial_cambios 
                WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY HOUR(fecha)
                ORDER BY hora
            ");

            // Usuarios más activos
            $usuariosActivos = $db->select("
                SELECT 
                    u.nombre,
                    COUNT(h.id_cambio) as cambios
                FROM usuarios u
                LEFT JOIN historial_cambios h ON u.id_usuario = h.id_usuario
                WHERE h.fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY u.id_usuario
                ORDER BY cambios DESC
                LIMIT 10
            ");

            // Tablas más modificadas
            $tablasCambiadas = $db->select("
                SELECT 
                    tabla_afectada,
                    COUNT(*) as modificaciones
                FROM historial_cambios 
                WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                GROUP BY tabla_afectada
                ORDER BY modificaciones DESC
                LIMIT 10
            ");

            return [
                'actividad_hora' => $actividadHora,
                'usuarios_activos' => $usuariosActivos,
                'tablas_cambiadas' => $tablasCambiadas,
                'total_cambios_hoy' => array_sum(array_column($actividadHora, 'actividades'))
            ];
        } catch (Exception $e) {
            error_log("Error obteniendo métricas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener sesiones activas
     */
    private function getSesionesActivas()
    {
        try {
            // Implementación básica - en producción usar tabla de sesiones
            $db = Database::getInstance();

            $sesiones = $db->select("
                SELECT DISTINCT
                    u.id_usuario,
                    u.nombre,
                    u.email,
                    MAX(h.fecha) as ultima_actividad,
                    COUNT(h.id_cambio) as actividades_sesion
                FROM usuarios u
                LEFT JOIN historial_cambios h ON u.id_usuario = h.id_usuario
                WHERE h.fecha >= DATE_SUB(NOW(), INTERVAL 2 HOUR)
                GROUP BY u.id_usuario
                ORDER BY ultima_actividad DESC
            ");

            return $sesiones;
        } catch (Exception $e) {
            error_log("Error obteniendo sesiones: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtener logs críticos
     */
    private function getLogsCriticos()
    {
        try {
            $db = Database::getInstance();

            return $db->select("
                SELECT *
                FROM historial_cambios 
                WHERE (
                    campo_modificado LIKE '%password%' OR
                    campo_modificado LIKE '%email%' OR
                    tabla_afectada = 'usuarios' OR
                    tabla_afectada = 'configuracion'
                )
                AND fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY fecha DESC
                LIMIT 50
            ");
        } catch (Exception $e) {
            error_log("Error obteniendo logs críticos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Cerrar sesión remota (simulado)
     */
    private function cerrarSesionRemota($sesionId)
    {
        try {
            // Registrar la acción
            $this->registrarCambio(
                'sesiones',
                $sesionId,
                'estado',
                'activa',
                'cerrada',
                'Sesión cerrada remotamente por administrador'
            );

            return ['success' => true, 'mensaje' => 'Sesión cerrada exitosamente'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Bloquear usuario
     */
    private function bloquearUsuario($usuarioId)
    {
        try {
            $db = Database::getInstance();

            $db->update(
                'usuarios',
                ['estado' => 'bloqueado'],
                ['id_usuario' => $usuarioId]
            );

            // Registrar la acción
            $this->registrarCambio(
                'usuarios',
                $usuarioId,
                'estado',
                'activo',
                'bloqueado',
                'Usuario bloqueado por administrador'
            );

            return ['success' => true, 'mensaje' => 'Usuario bloqueado exitosamente'];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Registrar cambio en auditoría
     */
    private function registrarCambio($tabla, $registroId, $campo, $valorAnterior, $valorNuevo, $observaciones = '')
    {
        try {
            $this->historialCambiosModel->registrarCambio(
                $_SESSION['user_id'] ?? 1, // ID del usuario actual
                $tabla,
                $registroId,
                $campo,
                $valorAnterior,
                $valorNuevo
            );
        } catch (Exception $e) {
            error_log("Error registrando cambio: " . $e->getMessage());
        }
    }

    /**
     * Generar reporte de seguridad automático
     */
    public function generar_reporte_seguridad()
    {
        try {
            $anomalias = $this->detectar_anomalias();
            $metricas = $this->getMetricasSeguridad();

            $reporte = [
                'fecha_generacion' => date('Y-m-d H:i:s'),
                'anomalias_detectadas' => $anomalias,
                'metricas_actividad' => $metricas,
                'recomendaciones' => $this->generarRecomendaciones($anomalias)
            ];

            // Guardar reporte
            $nombreArchivo = 'reporte_seguridad_' . date('Y-m-d_H-i-s') . '.json';
            $rutaArchivo = STORAGE_PATH . '/reports/' . $nombreArchivo;

            if (!is_dir(dirname($rutaArchivo))) {
                mkdir(dirname($rutaArchivo), 0755, true);
            }

            file_put_contents($rutaArchivo, json_encode($reporte, JSON_PRETTY_PRINT));

            return ['success' => true, 'archivo' => $nombreArchivo, 'reporte' => $reporte];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Generar recomendaciones de seguridad
     */
    private function generarRecomendaciones($anomalias)
    {
        $recomendaciones = [];

        if (!empty($anomalias['logins_fallidos'])) {
            $recomendaciones[] = [
                'prioridad' => 'alta',
                'tipo' => 'seguridad',
                'mensaje' => 'Se detectaron múltiples intentos de login fallidos. Considere implementar bloqueo temporal de IPs.'
            ];
        }

        if (!empty($anomalias['actividad_nocturna'])) {
            $recomendaciones[] = [
                'prioridad' => 'media',
                'tipo' => 'monitoreo',
                'mensaje' => 'Actividad inusual fuera de horario detectada. Revisar legitimidad de los accesos.'
            ];
        }

        if (!empty($anomalias['cambios_masivos'])) {
            $recomendaciones[] = [
                'prioridad' => 'alta',
                'tipo' => 'operacion',
                'mensaje' => 'Cambios masivos detectados. Verificar que sean parte de operaciones autorizadas.'
            ];
        }

        return $recomendaciones;
    }
}
