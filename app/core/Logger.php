<?php

/**
 * Clase Logger - Sistema de logs para Cruz Motor S.A.C.
 */
class Logger
{
    const DEBUG = 'DEBUG';
    const INFO = 'INFO';
    const WARNING = 'WARNING';
    const ERROR = 'ERROR';

    private static $logFile;
    private static $initialized = false;

    /**
     * Inicializar el logger
     */
    private static function init()
    {
        if (self::$initialized) {
            return;
        }

        // Verificar que las constantes estén definidas
        if (!defined('LOGS_PATH')) {
            define('LOGS_PATH', dirname(__DIR__, 2) . '/storage/logs');
        }

        // Crear directorio de logs si no existe
        if (!is_dir(LOGS_PATH)) {
            mkdir(LOGS_PATH, 0755, true);
        }

        self::$logFile = LOGS_PATH . '/app_' . date('Y-m-d') . '.log';
        self::$initialized = true;
    }

    /**
     * Log nivel DEBUG
     */
    public static function debug($message, $context = [])
    {
        self::log(self::DEBUG, $message, $context);
    }

    /**
     * Log nivel INFO
     */
    public static function info($message, $context = [])
    {
        self::log(self::INFO, $message, $context);
    }

    /**
     * Log nivel WARNING
     */
    public static function warning($message, $context = [])
    {
        self::log(self::WARNING, $message, $context);
    }

    /**
     * Log nivel ERROR
     */
    public static function error($message, $context = [])
    {
        self::log(self::ERROR, $message, $context);
    }

    /**
     * Escribir log
     */
    private static function log($level, $message, $context = [])
    {
        self::init();

        // Verificar si el nivel está habilitado
        if (!self::isLevelEnabled($level)) {
            return;
        }

        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' | Context: ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';

        $logEntry = "[{$timestamp}] [{$level}] {$message}{$contextStr}" . PHP_EOL;

        // Verificar tamaño del archivo usando constante o valor por defecto
        $maxSize = defined('LOG_MAX_SIZE') ? LOG_MAX_SIZE : (10 * 1024 * 1024); // 10MB por defecto
        if (file_exists(self::$logFile) && filesize(self::$logFile) > $maxSize) {
            self::rotateLog();
        }

        file_put_contents(self::$logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }

    /**
     * Verificar si el nivel está habilitado
     */
    private static function isLevelEnabled($level)
    {
        $levels = [
            self::DEBUG => 0,
            self::INFO => 1,
            self::WARNING => 2,
            self::ERROR => 3
        ];

        // Usar LOG_LEVEL si está definido, sino usar INFO por defecto
        $logLevel = defined('LOG_LEVEL') ? LOG_LEVEL : 'INFO';
        $currentLevel = $levels[$logLevel] ?? 1;
        $messageLevel = $levels[$level] ?? 0;

        return $messageLevel >= $currentLevel;
    }

    /**
     * Rotar archivo de log
     */
    private static function rotateLog()
    {
        $rotatedFile = self::$logFile . '.' . time();
        rename(self::$logFile, $rotatedFile);
    }

    /**
     * Limpia logs antiguos según configuración de retención
     */
    public static function cleanOldLogs()
    {
        if (!defined('LOGS_PATH') || !is_dir(LOGS_PATH)) {
            return;
        }

        $files = glob(LOGS_PATH . '/app_*.log*');
        $retentionDays = defined('BACKUP_RETENTION_DAYS') ? BACKUP_RETENTION_DAYS : 30;
        $cutoffTime = time() - ($retentionDays * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }

    /**
     * Prueba de escritura en el sistema de logs
     */
    public static function testLog()
    {
        self::info('Prueba de escritura en el sistema de logs');
    }

    /**
     * MÉTODOS AVANZADOS PARA AUDITORÍA DE SEGURIDAD
     */

    /**
     * Log de actividad de usuario con contexto completo
     */
    public static function logUserActivity($userId, $action, $resource, $details = [])
    {
        $context = [
            'user_id' => $userId,
            'action' => $action,
            'resource' => $resource,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ];

        self::log(self::INFO, "User Activity: {$action} on {$resource}", $context);

        // También guardar en base de datos si está disponible
        self::logToDatabase('user_activity', $action, $context);
    }

    /**
     * Log de eventos de seguridad críticos
     */
    public static function logSecurityEvent($type, $severity, $message, $details = [])
    {
        $context = [
            'security_event_type' => $type,
            'severity' => $severity,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null,
            'details' => $details
        ];

        $level = ($severity === 'critical' || $severity === 'high') ? self::ERROR : self::WARNING;
        self::log($level, "Security Event [{$type}]: {$message}", $context);

        // Guardar también en log de seguridad especial
        self::logToSecurityFile($type, $severity, $message, $context);

        // Guardar en base de datos
        self::logToDatabase('security_event', $message, $context);
    }

    /**
     * Log de intentos de acceso fallidos
     */
    public static function logFailedLogin($username, $reason = 'Invalid credentials')
    {
        $context = [
            'attempted_username' => $username,
            'failure_reason' => $reason,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::log(self::WARNING, "Failed login attempt for user: {$username}", $context);
        self::logToSecurityFile('failed_login', 'medium', "Failed login: {$username}", $context);
    }

    /**
     * Log de cambios en datos sensibles
     */
    public static function logDataChange($table, $recordId, $field, $oldValue, $newValue, $userId = null)
    {
        $context = [
            'table' => $table,
            'record_id' => $recordId,
            'field' => $field,
            'old_value' => self::sanitizeValue($oldValue),
            'new_value' => self::sanitizeValue($newValue),
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::log(self::INFO, "Data change in {$table}.{$field} for record {$recordId}", $context);
        self::logToDatabase('data_change', "Change in {$table}", $context);
    }

    /**
     * Log de acciones administrativas
     */
    public static function logAdminAction($action, $target, $description, $userId = null)
    {
        $context = [
            'admin_action' => $action,
            'target' => $target,
            'description' => $description,
            'user_id' => $userId ?? ($_SESSION['user_id'] ?? null),
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'timestamp' => date('Y-m-d H:i:s')
        ];

        self::log(self::WARNING, "Admin Action: {$action} on {$target}", $context);
        self::logToSecurityFile('admin_action', 'medium', $description, $context);
        self::logToDatabase('admin_action', $description, $context);
    }

    /**
     * Guardar log en archivo de seguridad especial
     */
    private static function logToSecurityFile($type, $severity, $message, $context)
    {
        try {
            $securityLogFile = LOGS_PATH . '/security_' . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            $formattedMessage = "[{$timestamp}] {$severity}.{$type}: {$message}";

            if (!empty($context)) {
                $formattedMessage .= ' ' . json_encode($context);
            }

            $formattedMessage .= PHP_EOL;

            file_put_contents($securityLogFile, $formattedMessage, FILE_APPEND | LOCK_EX);
        } catch (Exception $e) {
            // Fallback a log normal
            self::error("Error escribiendo log de seguridad: " . $e->getMessage());
        }
    }

    /**
     * Guardar log en base de datos
     */
    private static function logToDatabase($type, $message, $context)
    {
        try {
            $db = Database::getInstance();

            $data = [
                'tipo' => $type,
                'descripcion' => $message,
                'nivel' => self::mapLevelToDb($context['severity'] ?? 'info'),
                'id_usuario' => $context['user_id'] ?? null,
                'ip_address' => $context['ip_address'] ?? null,
                'user_agent' => $context['user_agent'] ?? null,
                'datos_adicionales' => json_encode($context),
                'fecha' => date('Y-m-d H:i:s')
            ];

            $db->insert('logsistema', $data);
        } catch (Exception $e) {
            // Falló BD, al menos guardar en archivo
            self::error("Error guardando log en BD: " . $e->getMessage());
        }
    }

    /**
     * Mapear niveles de log para la base de datos
     */
    private static function mapLevelToDb($level)
    {
        $mapping = [
            'critical' => 'critical',
            'high' => 'error',
            'medium' => 'warning',
            'low' => 'info',
            'info' => 'info'
        ];

        return $mapping[strtolower($level)] ?? 'info';
    }

    /**
     * Sanitizar valores sensibles para logs
     */
    private static function sanitizeValue($value)
    {
        // No registrar contraseñas completas
        if (is_string($value) && (
            stripos($value, 'password') !== false ||
            stripos($value, 'passwd') !== false ||
            strlen($value) > 50
        )) {
            return '[REDACTED]';
        }

        // Truncar valores muy largos
        if (is_string($value) && strlen($value) > 200) {
            return substr($value, 0, 200) . '...';
        }

        return $value;
    }

    /**
     * Obtener logs de seguridad recientes
     */
    public static function getRecentSecurityLogs($limit = 50)
    {
        try {
            $securityLogFile = LOGS_PATH . '/security_' . date('Y-m-d') . '.log';

            if (!file_exists($securityLogFile)) {
                return [];
            }

            $lines = file($securityLogFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $recentLines = array_slice($lines, -$limit);

            return array_reverse($recentLines);
        } catch (Exception $e) {
            self::error("Error obteniendo logs de seguridad: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Analizar patrones en logs de seguridad
     */
    public static function analyzeSecurityPatterns()
    {
        try {
            $logs = self::getRecentSecurityLogs(1000);
            $patterns = [
                'failed_logins' => 0,
                'suspicious_ips' => [],
                'admin_actions' => 0,
                'security_events' => 0
            ];

            foreach ($logs as $log) {
                if (strpos($log, 'failed_login') !== false) {
                    $patterns['failed_logins']++;

                    // Extraer IP si es posible
                    if (preg_match('/"ip_address":"([^"]+)"/', $log, $matches)) {
                        $ip = $matches[1];
                        $patterns['suspicious_ips'][$ip] = ($patterns['suspicious_ips'][$ip] ?? 0) + 1;
                    }
                }

                if (strpos($log, 'admin_action') !== false) {
                    $patterns['admin_actions']++;
                }

                if (strpos($log, 'security_event') !== false) {
                    $patterns['security_events']++;
                }
            }

            return $patterns;
        } catch (Exception $e) {
            self::error("Error analizando patrones de seguridad: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Limpiar logs antiguos (extendido)
     */
    public static function cleanupExtended($daysToKeep = 30)
    {
        // Limpiar logs normales
        if (!defined('LOGS_PATH') || !is_dir(LOGS_PATH)) {
            return;
        }

        $files = glob(LOGS_PATH . '/app_*.log*');
        $cutoffTime = time() - ($daysToKeep * 24 * 60 * 60);

        foreach ($files as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }

        // Limpiar también logs de seguridad
        $securityFiles = glob(LOGS_PATH . '/security_*.log');

        foreach ($securityFiles as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }

        foreach ($securityFiles as $file) {
            if (filemtime($file) < $cutoffTime) {
                unlink($file);
            }
        }
    }
}
