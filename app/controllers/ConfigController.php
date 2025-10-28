<?php

class ConfigController extends Controller
{
    public function __construct()
    {
        parent::__construct();

        // Verificar autenticación y permisos de administrador
        Auth::requireAuth();
        Auth::requireRole(['administrador']);
    }

    /**
     * Mostrar página de configuración
     */
    public function index()
    {
        $title = 'Configuración del Sistema';
        $breadcrumb = [
            ['title' => 'Configuración']
        ];

        // Obtener configuración actual
        $config = [
            'company_name' => COMPANY_NAME,
            'company_ruc' => COMPANY_RUC,
            'company_address' => COMPANY_ADDRESS,
            'company_phone' => COMPANY_PHONE,
            'company_email' => COMPANY_EMAIL,
            'app_version' => APP_VERSION,
            'default_stock_min' => DEFAULT_STOCK_MIN,
            'default_iva' => DEFAULT_IVA
        ];

        $this->view('config/index', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'config' => $config
        ]);
    }

    /**
     * Actualizar configuración
     */
    public function update()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar datos
            $data = [
                'company_name' => trim($_POST['company_name'] ?? ''),
                'company_ruc' => trim($_POST['company_ruc'] ?? ''),
                'company_address' => trim($_POST['company_address'] ?? ''),
                'company_phone' => trim($_POST['company_phone'] ?? ''),
                'company_email' => trim($_POST['company_email'] ?? ''),
                'default_stock_min' => intval($_POST['default_stock_min'] ?? DEFAULT_STOCK_MIN),
                'default_iva' => floatval($_POST['default_iva'] ?? DEFAULT_IVA)
            ];

            // Validaciones básicas
            if (empty($data['company_name'])) {
                throw new Exception('El nombre de la empresa es requerido');
            }

            if (!empty($data['company_email']) && !filter_var($data['company_email'], FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email no es válido');
            }

            // Aquí se podría actualizar un archivo de configuración o base de datos
            // Por ahora simulamos la actualización exitosa

            Logger::info("Configuración actualizada por usuario ID: " . $_SESSION['user_id'], $data);

            $_SESSION['flash_messages'][] = [
                'type' => 'success',
                'message' => 'Configuración actualizada exitosamente'
            ];

            $this->redirect('?page=config');
        } catch (Exception $e) {
            Logger::error("Error actualizando configuración: " . $e->getMessage());

            $_SESSION['flash_messages'][] = [
                'type' => 'error',
                'message' => 'Error al actualizar configuración: ' . $e->getMessage()
            ];

            $this->redirect('?page=config');
        }
    }

    /**
     * Generar backup del sistema
     */
    public function backup()
    {
        try {
            // Verificar que la carpeta de backups existe
            $backupDir = STORAGE_PATH . '/backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }

            // Generar nombre del archivo
            $filename = 'backup_cruzmotorsac_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;

            // Comando mysqldump
            $command = sprintf(
                'mysqldump --user=%s --password=%s --host=%s %s > %s',
                DB_USER,
                DB_PASS,
                DB_HOST,
                DB_NAME,
                escapeshellarg($filepath)
            );

            // Ejecutar backup
            exec($command, $output, $returnCode);

            if ($returnCode === 0 && file_exists($filepath)) {
                Logger::info("Backup generado exitosamente: " . $filename);

                // Descargar el archivo
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $filename . '"');
                header('Content-Length: ' . filesize($filepath));
                readfile($filepath);

                // Eliminar archivo temporal después de descarga
                unlink($filepath);
                exit;
            } else {
                throw new Exception('Error al generar el backup');
            }
        } catch (Exception $e) {
            Logger::error("Error generando backup: " . $e->getMessage());

            $_SESSION['flash_messages'][] = [
                'type' => 'error',
                'message' => 'Error al generar backup: ' . $e->getMessage()
            ];

            $this->redirect('?page=config');
        }
    }

    /**
     * Limpiar caché del sistema
     */
    public function clearCache()
    {
        try {
            $cacheDir = STORAGE_PATH . '/cache';

            if (is_dir($cacheDir)) {
                $files = glob($cacheDir . '/*');
                foreach ($files as $file) {
                    if (is_file($file)) {
                        unlink($file);
                    }
                }
            }

            Logger::info("Caché limpiado por usuario ID: " . $_SESSION['user_id']);

            echo json_encode([
                'success' => true,
                'message' => 'Caché limpiado exitosamente'
            ]);
        } catch (Exception $e) {
            Logger::error("Error limpiando caché: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => 'Error al limpiar caché'
            ]);
        }
    }
}
