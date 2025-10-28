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
}
