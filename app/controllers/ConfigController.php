<?php

require_once APP_PATH . '/models/MantenimientoSistema.php';

class ConfigController extends Controller
{
    private $configuracionAlmacenModel;
    private $mantenimientoSistemaModel;

    public function __construct()
    {
        parent::__construct();

        // Verificar autenticación y permisos de administrador
        Auth::requireAuth();
        Auth::requireRole(['administrador']);

        // Inicializar modelos
        $this->configuracionAlmacenModel = new ConfiguracionAlmacen();
        $this->mantenimientoSistemaModel = new MantenimientoSistema();
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
     * Gestionar configuraciones de almacén
     */
    public function almacen()
    {
        try {
            $configuraciones = $this->configuracionAlmacenModel->getTodasConfiguraciones();

            $this->view('config/almacen', [
                'title' => 'Configuración de Almacén',
                'breadcrumb' => [
                    ['title' => 'Configuración', 'url' => '?page=config'],
                    ['title' => 'Almacén']
                ],
                'configuraciones' => $configuraciones
            ]);
        } catch (Exception $e) {
            Logger::error("Error cargando configuraciones de almacén: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Crear nueva configuración de almacén
     */
    public function createAlmacen()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $nombreAlmacen = trim($_POST['nombre_almacen'] ?? '');
                $capacidadMaxima = !empty($_POST['capacidad_maxima']) ? intval($_POST['capacidad_maxima']) : null;
                $horarioApertura = !empty($_POST['horario_apertura']) ? $_POST['horario_apertura'] : null;
                $horarioCierre = !empty($_POST['horario_cierre']) ? $_POST['horario_cierre'] : null;
                $responsable = !empty($_POST['responsable']) ? intval($_POST['responsable']) : null;

                if (empty($nombreAlmacen)) {
                    throw new Exception('El nombre del almacén es requerido');
                }

                $id = $this->configuracionAlmacenModel->crear($nombreAlmacen, $capacidadMaxima, $horarioApertura, $horarioCierre, $responsable);

                if ($id) {
                    $this->setFlash('success', 'Configuración de almacén creada exitosamente');
                    Logger::info("Nueva configuración de almacén creada", ['id' => $id, 'nombre' => $nombreAlmacen]);
                } else {
                    throw new Exception('Error al crear la configuración');
                }

                $this->redirect('?page=config&action=almacen');
            } catch (Exception $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('?page=config&action=almacen');
            }
        } else {
            // Mostrar formulario de creación
            require_once APP_PATH . '/models/Usuario.php';
            $usuarioModel = new Usuario();
            $usuarios = $usuarioModel->getAll();

            $this->view('config/almacen_create', [
                'title' => 'Nueva Configuración de Almacén',
                'breadcrumb' => [
                    ['title' => 'Configuración', 'url' => '?page=config'],
                    ['title' => 'Almacén', 'url' => '?page=config&action=almacen'],
                    ['title' => 'Nuevo']
                ],
                'usuarios' => $usuarios
            ]);
        }
    }

    /**
     * Gestionar parámetros del sistema
     */
    public function sistema()
    {
        try {
            // Inicializar parámetros predeterminados si no existen
            $this->mantenimientoSistemaModel->inicializarParametrosPredeterminados();

            $parametros = $this->mantenimientoSistemaModel->getTodosParametros();

            $this->view('config/sistema', [
                'title' => 'Parámetros del Sistema',
                'breadcrumb' => [
                    ['title' => 'Configuración', 'url' => '?page=config'],
                    ['title' => 'Sistema']
                ],
                'parametros' => $parametros
            ]);
        } catch (Exception $e) {
            Logger::error("Error cargando parámetros del sistema: " . $e->getMessage());
            $this->view('errors/500', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Actualizar parámetros del sistema
     */
    public function updateSistema()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $actualizados = 0;

                foreach ($_POST as $key => $value) {
                    if (strpos($key, 'param_') === 0) {
                        $idParametro = str_replace('param_', '', $key);
                        $nuevoValor = trim($value);

                        if ($this->mantenimientoSistemaModel->actualizarParametro($idParametro, ['valor' => $nuevoValor])) {
                            $actualizados++;
                        }
                    }
                }

                $this->setFlash('success', "Se actualizaron {$actualizados} parámetros del sistema");
                Logger::info("Parámetros del sistema actualizados", ['cantidad' => $actualizados, 'usuario' => $_SESSION['user_id']]);
            } catch (Exception $e) {
                $this->setFlash('error', 'Error al actualizar parámetros: ' . $e->getMessage());
                Logger::error("Error actualizando parámetros del sistema: " . $e->getMessage());
            }
        }

        $this->redirect('?page=config&action=sistema');
    }

    /**
     * Crear nuevo parámetro del sistema
     */
    public function createParametro()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $clave = strtoupper(trim($_POST['clave'] ?? ''));
                $valor = trim($_POST['valor'] ?? '');
                $descripcion = trim($_POST['descripcion'] ?? '');

                if (empty($clave) || empty($valor)) {
                    throw new Exception('La clave y el valor son requeridos');
                }

                $id = $this->mantenimientoSistemaModel->crearParametro($clave, $valor, $descripcion);

                if ($id) {
                    $this->setFlash('success', 'Parámetro creado exitosamente');
                    Logger::info("Nuevo parámetro del sistema creado", ['id' => $id, 'clave' => $clave]);
                } else {
                    throw new Exception('Error al crear el parámetro');
                }
            } catch (Exception $e) {
                $this->setFlash('error', $e->getMessage());
                Logger::error("Error creando parámetro del sistema: " . $e->getMessage());
            }
        }

        $this->redirect('?page=config&action=sistema');
    }

    /**
     * Editar configuración de almacén
     */
    public function editAlmacen()
    {
        $id = $_GET['id'] ?? 0;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();

            try {
                $datos = [
                    'nombre_almacen' => trim($_POST['nombre_almacen'] ?? ''),
                    'capacidad_maxima' => !empty($_POST['capacidad_maxima']) ? intval($_POST['capacidad_maxima']) : null,
                    'horario_apertura' => !empty($_POST['horario_apertura']) ? $_POST['horario_apertura'] : null,
                    'horario_cierre' => !empty($_POST['horario_cierre']) ? $_POST['horario_cierre'] : null,
                    'responsable' => !empty($_POST['responsable']) ? intval($_POST['responsable']) : null,
                ];

                if (empty($datos['nombre_almacen'])) {
                    throw new Exception('El nombre del almacén es requerido');
                }

                if ($this->configuracionAlmacenModel->actualizar($id, $datos)) {
                    $this->setFlash('success', 'Configuración de almacén actualizada exitosamente');
                    Logger::info("Configuración de almacén actualizada", ['id' => $id]);
                } else {
                    throw new Exception('Error al actualizar la configuración');
                }

                $this->redirect('?page=config&action=almacen');
            } catch (Exception $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('?page=config&action=editAlmacen&id=' . $id);
            }
        } else {
            // Mostrar formulario de edición
            try {
                $configuracion = $this->configuracionAlmacenModel->find($id);
                if (!$configuracion) {
                    throw new Exception('Configuración no encontrada');
                }

                require_once APP_PATH . '/models/Usuario.php';
                $usuarioModel = new Usuario();
                $usuarios = $usuarioModel->getAll();

                $this->view('config/almacen_edit', [
                    'title' => 'Editar Configuración de Almacén',
                    'breadcrumb' => [
                        ['title' => 'Configuración', 'url' => '?page=config'],
                        ['title' => 'Almacén', 'url' => '?page=config&action=almacen'],
                        ['title' => 'Editar']
                    ],
                    'configuracion' => $configuracion,
                    'usuarios' => $usuarios
                ]);
            } catch (Exception $e) {
                $this->setFlash('error', $e->getMessage());
                $this->redirect('?page=config&action=almacen');
            }
        }
    }

    /**
     * Eliminar configuración de almacén
     */
    public function deleteAlmacen()
    {
        $this->validateCSRF();
        $id = $_GET['id'] ?? 0;

        try {
            $configuracion = $this->configuracionAlmacenModel->find($id);
            if (!$configuracion) {
                throw new Exception('Configuración no encontrada');
            }

            if ($this->configuracionAlmacenModel->delete($id)) {
                $this->setFlash('success', 'Configuración de almacén eliminada exitosamente');
                Logger::info("Configuración de almacén eliminada", ['id' => $id, 'nombre' => $configuracion['nombre_almacen']]);
            } else {
                throw new Exception('Error al eliminar la configuración');
            }
        } catch (Exception $e) {
            $this->setFlash('error', $e->getMessage());
            Logger::error("Error eliminando configuración de almacén: " . $e->getMessage());
        }

        $this->redirect('?page=config&action=almacen');
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
     * Mostrar configuración de almacenes
     */
    public function almacenes()
    {
        $title = 'Configuración de Almacenes';

        // Cargar modelo
        require_once ROOT_PATH . '/app/models/ConfiguracionAlmacen.php';
        $configAlmacenModel = new ConfiguracionAlmacen();

        // Obtener configuraciones de almacén
        $almacenes = $configAlmacenModel->getTodasConfiguraciones();

        // Obtener usuarios para el select de responsables
        require_once ROOT_PATH . '/app/models/Usuario.php';
        $usuarioModel = new Usuario();
        $usuarios = $usuarioModel->getActiveUsers();

        $this->view('config/almacenes', [
            'title' => $title,
            'almacenes' => $almacenes,
            'usuarios' => $usuarios
        ]);
    }

    /**
     * Crear nueva configuración de almacén
     */
    public function crearAlmacen()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $nombre_almacen = trim($_POST['nombre_almacen'] ?? '');
            $capacidad_maxima = (int)($_POST['capacidad_maxima'] ?? 0);
            $horario_apertura = $_POST['horario_apertura'] ?? null;
            $horario_cierre = $_POST['horario_cierre'] ?? null;
            $responsable = (int)($_POST['responsable'] ?? 0);

            // Validaciones
            if (empty($nombre_almacen)) {
                throw new Exception('El nombre del almacén es requerido');
            }

            if ($capacidad_maxima <= 0) {
                throw new Exception('La capacidad máxima debe ser mayor a 0');
            }

            if ($responsable <= 0) {
                throw new Exception('Debe seleccionar un responsable');
            }

            // Cargar modelo y crear
            require_once ROOT_PATH . '/app/models/ConfiguracionAlmacen.php';
            $configAlmacenModel = new ConfiguracionAlmacen();

            $id = $configAlmacenModel->crear(
                $nombre_almacen,
                $capacidad_maxima,
                $horario_apertura,
                $horario_cierre,
                $responsable
            );

            if ($id) {
                Logger::info("Almacén creado: $nombre_almacen", ['user_id' => $_SESSION['user_id']]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Almacén creado exitosamente',
                    'id' => $id
                ]);
            } else {
                throw new Exception('Error al crear el almacén');
            }
        } catch (Exception $e) {
            Logger::error("Error creando almacén: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Actualizar configuración de almacén
     */
    public function actualizarAlmacen()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            $id_config = (int)($_POST['id_config'] ?? 0);
            $datos = [
                'nombre_almacen' => trim($_POST['nombre_almacen'] ?? ''),
                'capacidad_maxima' => (int)($_POST['capacidad_maxima'] ?? 0),
                'horario_apertura' => $_POST['horario_apertura'] ?? null,
                'horario_cierre' => $_POST['horario_cierre'] ?? null,
                'responsable' => (int)($_POST['responsable'] ?? 0)
            ];

            // Validaciones
            if ($id_config <= 0) {
                throw new Exception('ID de configuración inválido');
            }

            if (empty($datos['nombre_almacen'])) {
                throw new Exception('El nombre del almacén es requerido');
            }

            if ($datos['capacidad_maxima'] <= 0) {
                throw new Exception('La capacidad máxima debe ser mayor a 0');
            }

            // Cargar modelo y actualizar
            require_once ROOT_PATH . '/app/models/ConfiguracionAlmacen.php';
            $configAlmacenModel = new ConfiguracionAlmacen();

            $resultado = $configAlmacenModel->actualizar($id_config, $datos);

            if ($resultado) {
                Logger::info("Almacén actualizado: {$datos['nombre_almacen']}", ['user_id' => $_SESSION['user_id']]);

                echo json_encode([
                    'success' => true,
                    'message' => 'Almacén actualizado exitosamente'
                ]);
            } else {
                throw new Exception('Error al actualizar el almacén');
            }
        } catch (Exception $e) {
            Logger::error("Error actualizando almacén: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
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

    /**
     * Mostrar página de configuración de empresa
     */
    public function empresa()
    {
        $title = 'Configuración de Empresa';
        $breadcrumb = [
            ['title' => 'Configuración', 'url' => '?page=config'],
            ['title' => 'Datos de Empresa']
        ];

        // Obtener configuración actual de empresa
        $empresa = [
            'nombre' => COMPANY_NAME,
            'ruc' => COMPANY_RUC,
            'direccion' => COMPANY_ADDRESS,
            'telefono' => COMPANY_PHONE,
            'email' => COMPANY_EMAIL
        ];

        $this->view('config/empresa', [
            'title' => $title,
            'breadcrumb' => $breadcrumb,
            'empresa' => $empresa
        ]);
    }

    /**
     * Actualizar configuración de empresa
     */
    public function actualizarEmpresa()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                throw new Exception('Método no permitido');
            }

            // Validar datos
            $nombre = trim($_POST['nombre'] ?? '');
            $ruc = trim($_POST['ruc'] ?? '');
            $direccion = trim($_POST['direccion'] ?? '');
            $telefono = trim($_POST['telefono'] ?? '');
            $email = trim($_POST['email'] ?? '');

            if (empty($nombre)) {
                throw new Exception('El nombre de la empresa es requerido');
            }

            if (empty($ruc)) {
                throw new Exception('El RUC es requerido');
            }

            // Validar RUC (11 dígitos)
            if (!preg_match('/^\d{11}$/', $ruc)) {
                throw new Exception('El RUC debe tener 11 dígitos');
            }

            // Validar email si se proporciona
            if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                throw new Exception('El email no tiene un formato válido');
            }

            // Actualizar archivo de configuración
            $configPath = dirname(__DIR__, 2) . '/config/config.php';
            $configContent = file_get_contents($configPath);

            // Reemplazar valores
            $configContent = preg_replace(
                "/define\('COMPANY_NAME',\s*'[^']*'\);/",
                "define('COMPANY_NAME', '" . addslashes($nombre) . "');",
                $configContent
            );

            $configContent = preg_replace(
                "/define\('COMPANY_RUC',\s*'[^']*'\);/",
                "define('COMPANY_RUC', '" . addslashes($ruc) . "');",
                $configContent
            );

            $configContent = preg_replace(
                "/define\('COMPANY_ADDRESS',\s*'[^']*'\);/",
                "define('COMPANY_ADDRESS', '" . addslashes($direccion) . "');",
                $configContent
            );

            $configContent = preg_replace(
                "/define\('COMPANY_PHONE',\s*'[^']*'\);/",
                "define('COMPANY_PHONE', '" . addslashes($telefono) . "');",
                $configContent
            );

            $configContent = preg_replace(
                "/define\('COMPANY_EMAIL',\s*'[^']*'\);/",
                "define('COMPANY_EMAIL', '" . addslashes($email) . "');",
                $configContent
            );

            // Guardar archivo
            if (file_put_contents($configPath, $configContent) === false) {
                throw new Exception('No se pudo guardar la configuración');
            }

            Logger::info("Configuración de empresa actualizada", [
                'usuario' => $this->auth->getUser()['usuario'] ?? 'unknown'
            ]);

            echo json_encode([
                'success' => true,
                'message' => 'Configuración de empresa actualizada correctamente'
            ]);
        } catch (Exception $e) {
            Logger::error("Error actualizando configuración de empresa: " . $e->getMessage());

            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        }
    }
}
