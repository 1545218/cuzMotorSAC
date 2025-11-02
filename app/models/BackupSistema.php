<?php
require_once __DIR__ . '/../core/Model.php';

class BackupSistema extends Model
{
    protected $table = 'backupsistema';
    protected $primaryKey = 'id_backup';

    /**
     * Registra un backup en el sistema
     */
    public function registrarBackup($nombreArchivo, $realizadoPor)
    {
        $sql = "INSERT INTO backupsistema (nombre_archivo, fecha, realizado_por) 
                VALUES (?, NOW(), ?)";
        $result = $this->db->execute($sql, [$nombreArchivo, $realizadoPor]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Obtiene lista de backups
     */
    public function getListaBackups($limite = 50, $offset = 0)
    {
        $sql = "SELECT b.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM backupsistema b
                LEFT JOIN usuarios u ON b.realizado_por = u.id_usuario
                ORDER BY b.fecha DESC
                LIMIT ? OFFSET ?";

        return $this->db->select($sql, [$limite, $offset]);
    }

    /**
     * Genera backup de la base de datos
     */
    public function generarBackup($idUsuario, $rutaDestino = null)
    {
        try {
            if (!$rutaDestino) {
                $rutaDestino = ROOT_PATH . '/storage/backups/';
            }

            // Crear directorio si no existe
            if (!is_dir($rutaDestino)) {
                mkdir($rutaDestino, 0755, true);
            }

            $nombreArchivo = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $rutaCompleta = $rutaDestino . $nombreArchivo;

            // Intentar primero con mysqldump (Linux/Mac)
            if ($this->tieneMySQL()) {
                $resultado = $this->generarBackupMySQL($rutaCompleta);
                if ($resultado['success']) {
                    $this->registrarBackup($nombreArchivo, $idUsuario);
                    return [
                        'success' => true,
                        'archivo' => $nombreArchivo,
                        'ruta' => $rutaCompleta,
                        'tamaño' => filesize($rutaCompleta),
                        'metodo' => 'mysqldump'
                    ];
                }
            }

            // Alternativa: backup con PHP
            $resultado = $this->generarBackupPHP($rutaCompleta);
            if ($resultado['success']) {
                $this->registrarBackup($nombreArchivo, $idUsuario);
                return [
                    'success' => true,
                    'archivo' => $nombreArchivo,
                    'ruta' => $rutaCompleta,
                    'tamaño' => filesize($rutaCompleta),
                    'metodo' => 'php'
                ];
            }

            return [
                'success' => false,
                'error' => $resultado['error']
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verifica si mysqldump está disponible
     */
    private function tieneMySQL()
    {
        $output = [];
        $returnCode = 0;
        exec('mysqldump --version 2>&1', $output, $returnCode);
        return $returnCode === 0;
    }

    /**
     * Genera backup usando mysqldump
     */
    private function generarBackupMySQL($rutaCompleta)
    {
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $database = DB_NAME;

        $comando = "mysqldump --host={$host} --user={$user} --password={$pass} {$database} > {$rutaCompleta}";

        $output = [];
        $returnCode = 0;
        exec($comando . ' 2>&1', $output, $returnCode);

        if ($returnCode === 0 && file_exists($rutaCompleta)) {
            return ['success' => true];
        } else {
            return [
                'success' => false,
                'error' => 'Error mysqldump: ' . implode('\n', $output)
            ];
        }
    }

    /**
     * Genera backup usando PHP
     */
    private function generarBackupPHP($rutaCompleta)
    {
        try {
            $sql = "-- Backup generado con PHP el " . date('Y-m-d H:i:s') . "\n";
            $sql .= "-- Base de datos: " . DB_NAME . "\n\n";
            $sql .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

            // Obtener todas las tablas
            $tablas = $this->db->select("SHOW TABLES");
            $nombreBaseDatos = 'Tables_in_' . DB_NAME;

            foreach ($tablas as $tabla) {
                $nombreTabla = $tabla[$nombreBaseDatos];

                // Estructura de la tabla
                $sql .= "-- \n";
                $sql .= "-- Estructura de tabla para `{$nombreTabla}`\n";
                $sql .= "-- \n\n";

                $sql .= "DROP TABLE IF EXISTS `{$nombreTabla}`;\n";

                $createTable = $this->db->select("SHOW CREATE TABLE `{$nombreTabla}`");
                $sql .= $createTable[0]['Create Table'] . ";\n\n";

                // Datos de la tabla
                $sql .= "-- \n";
                $sql .= "-- Volcado de datos para la tabla `{$nombreTabla}`\n";
                $sql .= "-- \n\n";

                $filas = $this->db->select("SELECT * FROM `{$nombreTabla}`");

                if (!empty($filas)) {
                    $columnas = array_keys($filas[0]);
                    $sql .= "INSERT INTO `{$nombreTabla}` (`" . implode('`, `', $columnas) . "`) VALUES\n";

                    $valores = [];
                    foreach ($filas as $fila) {
                        $valoresFila = [];
                        foreach ($fila as $valor) {
                            if ($valor === null) {
                                $valoresFila[] = 'NULL';
                            } else {
                                $valoresFila[] = "'" . addslashes($valor) . "'";
                            }
                        }
                        $valores[] = '(' . implode(', ', $valoresFila) . ')';
                    }

                    $sql .= implode(",\n", $valores) . ";\n\n";
                }
            }

            $sql .= "SET FOREIGN_KEY_CHECKS=1;\n";

            // Escribir archivo
            if (file_put_contents($rutaCompleta, $sql) !== false) {
                return ['success' => true];
            } else {
                return ['success' => false, 'error' => 'No se pudo escribir el archivo'];
            }
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Error PHP: ' . $e->getMessage()];
        }
    }

    /**
     * Elimina backups antiguos
     */
    public function limpiarBackupsAntiguos($diasAntiguedad = 30, $rutaBackups = null)
    {
        try {
            if (!$rutaBackups) {
                $rutaBackups = ROOT_PATH . '/storage/backups/';
            }

            // Obtener backups antiguos de la BD
            $sql = "SELECT * FROM backupsistema 
                    WHERE fecha < DATE_SUB(NOW(), INTERVAL ? DAY)";
            $backupsAntiguos = $this->db->select($sql, [$diasAntiguedad]);

            $eliminados = 0;
            foreach ($backupsAntiguos as $backup) {
                $rutaArchivo = $rutaBackups . $backup['nombre_archivo'];

                // Eliminar archivo físico si existe
                if (file_exists($rutaArchivo)) {
                    unlink($rutaArchivo);
                }

                // Eliminar registro de BD
                $this->delete($backup['id_backup']);
                $eliminados++;
            }

            return [
                'success' => true,
                'eliminados' => $eliminados
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Verifica integridad de backups
     */
    public function verificarIntegridad($rutaBackups = null)
    {
        try {
            if (!$rutaBackups) {
                $rutaBackups = ROOT_PATH . '/storage/backups/';
            }

            $backups = $this->getListaBackups(1000, 0); // Obtener todos
            $resultado = [
                'total' => count($backups),
                'existentes' => 0,
                'faltantes' => 0,
                'archivos_huérfanos' => 0
            ];

            $archivosEnBD = [];

            // Verificar archivos registrados en BD
            foreach ($backups as $backup) {
                $rutaArchivo = $rutaBackups . $backup['nombre_archivo'];
                $archivosEnBD[] = $backup['nombre_archivo'];

                if (file_exists($rutaArchivo)) {
                    $resultado['existentes']++;
                } else {
                    $resultado['faltantes']++;
                }
            }

            // Verificar archivos huérfanos (existen físicamente pero no en BD)
            if (is_dir($rutaBackups)) {
                $archivosEnDisco = scandir($rutaBackups);
                foreach ($archivosEnDisco as $archivo) {
                    if ($archivo !== '.' && $archivo !== '..' && !in_array($archivo, $archivosEnBD)) {
                        $resultado['archivos_huérfanos']++;
                    }
                }
            }

            return $resultado;
        } catch (Exception $e) {
            return [
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene estadísticas de backups
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_backups,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as backups_hoy,
                    SUM(CASE WHEN DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) as backups_semana,
                    MAX(fecha) as ultimo_backup,
                    COUNT(DISTINCT realizado_por) as usuarios_diferentes
                FROM backupsistema";

        $result = $this->db->select($sql);
        return $result[0] ?? [];
    }

    /**
     * Obtiene un backup específico por ID
     */
    public function getBackupPorId($id)
    {
        $sql = "SELECT b.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM backupsistema b
                LEFT JOIN usuarios u ON b.realizado_por = u.id_usuario
                WHERE b.id_backup = ?";

        $result = $this->db->select($sql, [$id]);
        return $result[0] ?? null;
    }

    /**
     * Elimina un backup específico
     */
    public function eliminarBackup($id, $rutaBackups = null)
    {
        try {
            if (!$rutaBackups) {
                $rutaBackups = ROOT_PATH . '/storage/backups/';
            }

            // Obtener información del backup
            $backup = $this->getBackupPorId($id);
            if (!$backup) {
                return ['success' => false, 'error' => 'Backup no encontrado'];
            }

            // Eliminar archivo físico
            $rutaArchivo = $rutaBackups . $backup['nombre_archivo'];
            if (file_exists($rutaArchivo)) {
                unlink($rutaArchivo);
            }

            // Eliminar registro de BD
            $sql = "DELETE FROM backupsistema WHERE id_backup = ?";
            $result = $this->db->execute($sql, [$id]);

            return [
                'success' => $result,
                'mensaje' => $result ? 'Backup eliminado correctamente' : 'Error al eliminar backup'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Restaura una base de datos desde un backup
     */
    public function restaurarBackup($id, $idUsuario)
    {
        try {
            $backup = $this->getBackupPorId($id);
            if (!$backup) {
                return ['success' => false, 'error' => 'Backup no encontrado'];
            }

            $rutaBackups = ROOT_PATH . '/storage/backups/';
            $rutaArchivo = $rutaBackups . $backup['nombre_archivo'];

            if (!file_exists($rutaArchivo)) {
                return ['success' => false, 'error' => 'Archivo de backup no existe'];
            }

            // Comando mysql para restaurar
            $host = DB_HOST;
            $user = DB_USER;
            $pass = DB_PASS;
            $database = DB_NAME;

            $comando = "mysql --host={$host} --user={$user} --password={$pass} {$database} < {$rutaArchivo}";

            // Ejecutar comando
            $output = [];
            $returnCode = 0;
            exec($comando . ' 2>&1', $output, $returnCode);

            if ($returnCode === 0) {
                // Registrar la restauración en logs (crear una entrada simple)
                $mensaje = "Restauración realizada desde: " . $backup['nombre_archivo'];

                return [
                    'success' => true,
                    'mensaje' => 'Base de datos restaurada exitosamente'
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'Error al restaurar: ' . implode('\n', $output)
                ];
            }
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => 'Excepción: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Valida si un archivo es un backup válido
     */
    public function validarBackup($rutaArchivo)
    {
        if (!file_exists($rutaArchivo)) {
            return ['valido' => false, 'error' => 'Archivo no existe'];
        }

        // Verificar extensión
        if (pathinfo($rutaArchivo, PATHINFO_EXTENSION) !== 'sql') {
            return ['valido' => false, 'error' => 'Extensión de archivo inválida'];
        }

        // Verificar contenido básico (primeras líneas)
        $handle = fopen($rutaArchivo, 'r');
        if (!$handle) {
            return ['valido' => false, 'error' => 'No se puede leer el archivo'];
        }

        $primerasLineas = [];
        for ($i = 0; $i < 10 && !feof($handle); $i++) {
            $primerasLineas[] = fgets($handle);
        }
        fclose($handle);

        $contenido = implode('', $primerasLineas);

        // Verificar si contiene comandos SQL típicos de mysqldump
        if (
            strpos($contenido, 'mysqldump') !== false ||
            strpos($contenido, 'CREATE TABLE') !== false ||
            strpos($contenido, 'INSERT INTO') !== false
        ) {
            return [
                'valido' => true,
                'tamaño' => filesize($rutaArchivo),
                'fecha_archivo' => date('Y-m-d H:i:s', filemtime($rutaArchivo))
            ];
        }

        return ['valido' => false, 'error' => 'El archivo no parece ser un backup válido'];
    }
}
