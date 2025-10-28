<?php

/**
 * Clase StorageManager - Gestión segura del directorio storage
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class StorageManager
{
    // Rutas de los directorios
    private static $basePath = null;
    private static $paths = [
        'logs' => '/logs',
        'cache' => '/cache',
        'backups' => '/backups',
        'uploads' => '/uploads',
        'temp' => '/temp',
        'uploads_productos' => '/uploads/productos',
        'uploads_cotizaciones' => '/uploads/cotizaciones',
        'uploads_documentos' => '/uploads/documentos'
    ];

    // Tipos de archivo permitidos por directorio
    private static $allowedTypes = [
        'uploads_productos' => ['jpg', 'jpeg', 'png', 'webp'],
        'uploads_cotizaciones' => ['pdf'],
        'uploads_documentos' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']
    ];

    // Límites de tamaño por directorio (en bytes)
    private static $sizeLimits = [
        'uploads_productos' => 5242880,    // 5MB
        'uploads_cotizaciones' => 10485760, // 10MB  
        'uploads_documentos' => 20971520    // 20MB
    ];

    /**
     * Inicializa las rutas base
     */
    private static function init()
    {
        if (self::$basePath === null) {
            self::$basePath = defined('STORAGE_PATH') ? STORAGE_PATH : ROOT_PATH . '/storage';
        }
    }

    /**
     * Obtiene la ruta completa de un directorio
     */
    public static function getPath($directory)
    {
        self::init();

        if (!isset(self::$paths[$directory])) {
            throw new Exception("Directorio de storage no válido: {$directory}");
        }

        $path = self::$basePath . self::$paths[$directory];

        // Crear directorio si no existe
        if (!is_dir($path)) {
            mkdir($path, 0755, true);
            Logger::info("Directorio de storage creado", ['path' => $path]);
        }

        return $path;
    }

    /**
     * Guarda un archivo de forma segura
     */
    public static function saveFile($directory, $filename, $content, $validateType = true)
    {
        try {
            $path = self::getPath($directory);
            $fullPath = $path . '/' . $filename;

            // Validar tipo de archivo si es necesario
            if ($validateType && isset(self::$allowedTypes[$directory])) {
                $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                if (!in_array($extension, self::$allowedTypes[$directory])) {
                    throw new Exception("Tipo de archivo no permitido en {$directory}: {$extension}");
                }
            }

            // Validar tamaño si es necesario
            if (isset(self::$sizeLimits[$directory])) {
                $size = is_string($content) ? strlen($content) : filesize($content);
                if ($size > self::$sizeLimits[$directory]) {
                    $maxSize = self::formatBytes(self::$sizeLimits[$directory]);
                    throw new Exception("Archivo excede el tamaño máximo permitido para {$directory}: {$maxSize}");
                }
            }

            // Asegurar nombre de archivo seguro
            $safeName = self::sanitizeFilename($filename);
            $safeFullPath = $path . '/' . $safeName;

            // Guardar archivo
            $result = file_put_contents($safeFullPath, $content);

            if ($result === false) {
                throw new Exception("Error al guardar archivo en {$directory}");
            }

            Logger::info("Archivo guardado en storage", [
                'directory' => $directory,
                'filename' => $safeName,
                'size' => $result
            ]);

            return $safeName;
        } catch (Exception $e) {
            Logger::error("Error al guardar archivo en storage: " . $e->getMessage(), [
                'directory' => $directory,
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    /**
     * Lee un archivo de forma segura
     */
    public static function readFile($directory, $filename)
    {
        try {
            $path = self::getPath($directory);
            $fullPath = $path . '/' . $filename;

            // Verificar que el archivo existe y está dentro del directorio permitido
            if (!file_exists($fullPath) || !self::isPathSafe($fullPath, $path)) {
                throw new Exception("Archivo no encontrado o acceso no permitido: {$filename}");
            }

            $content = file_get_contents($fullPath);

            if ($content === false) {
                throw new Exception("Error al leer archivo: {$filename}");
            }

            Logger::debug("Archivo leído desde storage", [
                'directory' => $directory,
                'filename' => $filename,
                'size' => strlen($content)
            ]);

            return $content;
        } catch (Exception $e) {
            Logger::error("Error al leer archivo desde storage: " . $e->getMessage(), [
                'directory' => $directory,
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    /**
     * Elimina un archivo de forma segura
     */
    public static function deleteFile($directory, $filename)
    {
        try {
            $path = self::getPath($directory);
            $fullPath = $path . '/' . $filename;

            // Verificar que el archivo existe y está dentro del directorio permitido
            if (!file_exists($fullPath) || !self::isPathSafe($fullPath, $path)) {
                throw new Exception("Archivo no encontrado o acceso no permitido: {$filename}");
            }

            $result = unlink($fullPath);

            if (!$result) {
                throw new Exception("Error al eliminar archivo: {$filename}");
            }

            Logger::info("Archivo eliminado desde storage", [
                'directory' => $directory,
                'filename' => $filename
            ]);

            return true;
        } catch (Exception $e) {
            Logger::error("Error al eliminar archivo desde storage: " . $e->getMessage(), [
                'directory' => $directory,
                'filename' => $filename
            ]);
            throw $e;
        }
    }

    /**
     * Lista archivos en un directorio de forma segura
     */
    public static function listFiles($directory, $pattern = '*')
    {
        try {
            $path = self::getPath($directory);
            $files = glob($path . '/' . $pattern);

            // Solo retornar nombres de archivo, no rutas completas
            $filenames = [];
            foreach ($files as $file) {
                if (is_file($file)) {
                    $filenames[] = basename($file);
                }
            }

            Logger::debug("Archivos listados desde storage", [
                'directory' => $directory,
                'count' => count($filenames)
            ]);

            return $filenames;
        } catch (Exception $e) {
            Logger::error("Error al listar archivos desde storage: " . $e->getMessage(), [
                'directory' => $directory
            ]);
            throw $e;
        }
    }

    /**
     * Limpia archivos temporales antiguos
     */
    public static function cleanTempFiles($maxAge = 3600) // 1 hora por defecto
    {
        try {
            $tempPath = self::getPath('temp');
            $files = glob($tempPath . '/*');
            $cleaned = 0;
            $currentTime = time();

            foreach ($files as $file) {
                if (is_file($file) && (filemtime($file) + $maxAge) < $currentTime) {
                    if (unlink($file)) {
                        $cleaned++;
                    }
                }
            }

            Logger::info("Limpieza de archivos temporales completada", [
                'files_cleaned' => $cleaned,
                'max_age_seconds' => $maxAge
            ]);

            return $cleaned;
        } catch (Exception $e) {
            Logger::error("Error al limpiar archivos temporales: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Obtiene estadísticas de uso del storage
     */
    public static function getStorageStats()
    {
        try {
            self::init();
            $stats = [];

            foreach (self::$paths as $name => $path) {
                $fullPath = self::$basePath . $path;

                if (is_dir($fullPath)) {
                    $size = self::getDirectorySize($fullPath);
                    $files = count(glob($fullPath . '/*', GLOB_NOSORT));

                    $stats[$name] = [
                        'size_bytes' => $size,
                        'size_formatted' => self::formatBytes($size),
                        'files_count' => $files,
                        'path' => $fullPath
                    ];
                }
            }

            return $stats;
        } catch (Exception $e) {
            Logger::error("Error al obtener estadísticas de storage: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Sanitiza un nombre de archivo
     */
    private static function sanitizeFilename($filename)
    {
        // Eliminar caracteres peligrosos
        $filename = preg_replace('/[^a-zA-Z0-9\._-]/', '', $filename);

        // Prevenir nombres de archivo peligrosos
        $filename = str_replace(['..', './', '\\'], '', $filename);

        // Asegurar que no esté vacío
        if (empty($filename)) {
            $filename = 'file_' . time();
        }

        return $filename;
    }

    /**
     * Verifica que una ruta esté dentro del directorio permitido
     */
    private static function isPathSafe($filePath, $allowedPath)
    {
        $realFilePath = realpath($filePath);
        $realAllowedPath = realpath($allowedPath);

        // Verificar que la ruta real comience con la ruta permitida
        return $realFilePath !== false &&
            $realAllowedPath !== false &&
            strpos($realFilePath, $realAllowedPath) === 0;
    }

    /**
     * Calcula el tamaño de un directorio
     */
    private static function getDirectorySize($path)
    {
        $size = 0;
        $files = glob($path . '/*', GLOB_NOSORT);

        foreach ($files as $file) {
            if (is_file($file)) {
                $size += filesize($file);
            } elseif (is_dir($file)) {
                $size += self::getDirectorySize($file);
            }
        }

        return $size;
    }

    /**
     * Formatea bytes en formato legible
     */
    public static function formatBytes($size)
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, 2) . ' ' . $units[$i];
    }
}
