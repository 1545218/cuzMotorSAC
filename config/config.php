
<?php
// Configuración principal del sistema Cruz Motor SAC

// Cargar variables de entorno desde .env
$envPath = dirname(__DIR__) . '/.env';
if (file_exists($envPath)) {
    $lines = file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (!str_contains($line, '=')) continue;
        list($name, $value) = array_map('trim', explode('=', $line, 2));
        $_ENV[$name] = $value;
    }
}


// Información de la base de datos desde .env
define('DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('DB_NAME', $_ENV['DB_NAME'] ?? 'cruzmotorstockbd');
define('DB_USER', $_ENV['DB_USER'] ?? 'root');
define('DB_PASS', $_ENV['DB_PASS'] ?? '');
define('DB_CHARSET', 'utf8mb4');

// Configuración de la aplicación
define('APP_NAME', 'Cruz Motor S.A.C.');
define('APP_VERSION', '1.0.0');
define('APP_URL', 'http://localhost/CruzMotorSAC');
define('BASE_PATH', '/CruzMotorSAC');
define('BASE_URL', 'http://localhost/CruzMotorSAC');

// Configuración de directorios
if (!defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__));
}
define('APP_PATH', ROOT_PATH . '/app');
define('PUBLIC_PATH', ROOT_PATH . '/public');
define('STORAGE_PATH', ROOT_PATH . '/storage');
define('LOGS_PATH', ROOT_PATH . '/logs');

// Configuración de sesiones
define('SESSION_LIFETIME', 3600); // 1 hora
define('SESSION_NAME', 'CRUZMOTORSAC_SESSION');

// Configuración de seguridad
define('CSRF_TOKEN_NAME', '_token');
define('PASSWORD_MIN_LENGTH', 6);

// Configuración del sistema
define('DEFAULT_TIMEZONE', 'America/Lima');
define('DATE_FORMAT', 'd/m/Y');
define('DATETIME_FORMAT', 'd/m/Y H:i:s');

// Configuración de empresa
define('COMPANY_NAME', 'Cruz Motor S.A.C.');
define('COMPANY_RUC', '20448670288');
define('COMPANY_ADDRESS', 'AV. Panamericana N° 197 - puno');
define('COMPANY_PHONE', '+51 999 123 456');
define('COMPANY_EMAIL', '#');

// Configuración de correo / SMTP (puede leerse desde .env si está presente)
// Valores por defecto pensados para usar con Gmail (requiere app password)
if (isset($_ENV['SMTP_USE'])) {
    $smtpUse = strtolower($_ENV['SMTP_USE']) === 'true';
} else {
    $smtpUse = false; // por defecto usar mail()
}
define('SMTP_USE', $smtpUse);
define('SMTP_HOST', $_ENV['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', isset($_ENV['SMTP_PORT']) ? (int)$_ENV['SMTP_PORT'] : 587);
define('SMTP_USERNAME', $_ENV['SMTP_USERNAME'] ?? '');
define('SMTP_PASSWORD', $_ENV['SMTP_PASSWORD'] ?? '');
define('SMTP_SECURE', $_ENV['SMTP_SECURE'] ?? 'tls'); // tls o ssl
define('SMTP_DEBUG', isset($_ENV['SMTP_DEBUG']) ? (int)$_ENV['SMTP_DEBUG'] : 0); // 0 = off, 1 = client, 2 = client+server
define('MAIL_FROM', $_ENV['MAIL_FROM'] ?? ($_ENV['SMTP_USERNAME'] ?? 'no-reply@cruzmotorsac.com'));
define('MAIL_FROM_NAME', $_ENV['MAIL_FROM_NAME'] ?? COMPANY_NAME);

// Notificaciones: modo de prueba para enviar sólo a un correo de verificación
// NOTIFY_TEST_MODE = true -> enviará únicamente a NOTIFY_TEST_EMAIL y NO a los administradores.
define('NOTIFY_TEST_MODE', true);
define('NOTIFY_TEST_EMAIL', 'lulzsec2409@gmail.com'); // Pon tu correo aquí para recibir la notificación de prueba
define('NOTIFY_BATCH_LIMIT', 100); // Máximo de filas incluidas en el correo
// Correos adicionales que siempre deben recibir notificaciones (coma-separados)
// Ejemplo: 'a@ejemplo.com,b@ejemplo.com'
define('NOTIFY_ADDITIONAL_EMAILS', '');

// Configuración de inventario
define('DEFAULT_STOCK_MIN', 10);
define('DEFAULT_STOCK_MAX', 1000);
define('DEFAULT_IVA', 18); // 18% IGV en Perú

// Configuración de paginación
define('RECORDS_PER_PAGE', 20);
define('MAX_PAGINATION_LINKS', 5);

// Configuración de archivos
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', 'jpg,jpeg,png,gif');
define('ALLOWED_DOCUMENT_TYPES', 'pdf,doc,docx,xls,xlsx');

// Configuración de reportes PDF
define('PDF_AUTHOR', 'Cruz Motor S.A.C.');
define('PDF_CREATOR', 'Sistema de Inventario Cruz Motor');
define('PDF_MARGIN_TOP', 15);
define('PDF_MARGIN_BOTTOM', 15);
define('PDF_MARGIN_LEFT', 15);
define('PDF_MARGIN_RIGHT', 15);

// Configuración de logs
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR
define('LOG_MAX_SIZE', 10 * 1024 * 1024); // 10MB

// Configuración de respaldos
define('BACKUP_PATH', STORAGE_PATH . '/backups');
define('BACKUP_RETENTION_DAYS', 30);

// Roles del sistema
define('ROLE_ADMIN', 'administrador');
define('ROLE_VENDEDOR', 'vendedor');
define('ROLE_MECANICO', 'mecanico');

// Configuración de zona horaria
date_default_timezone_set(DEFAULT_TIMEZONE);

// Configuración de errores (solo para desarrollo)
if (isset($_SERVER['HTTP_HOST']) && ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false)) {
    define('DEBUG_MODE', true);
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);
    ini_set('error_log', LOGS_PATH . '/error.log');
} else {
    define('DEBUG_MODE', false);
    error_reporting(0);
    ini_set('display_errors', 0);
}

// Función para obtener configuración personalizada
function getConfig($key, $default = null)
{
    $configs = [
        // Aplicación
        'app.name' => APP_NAME,
        'app.version' => APP_VERSION,
        'app.url' => APP_URL,
        'app.debug' => DEBUG_MODE,

        // Empresa
        'company.name' => COMPANY_NAME,
        'company.ruc' => COMPANY_RUC,
        'company.address' => COMPANY_ADDRESS,
        'company.phone' => COMPANY_PHONE,
        'company.email' => COMPANY_EMAIL,

        // Inventario
        'inventory.stock_min' => DEFAULT_STOCK_MIN,
        'inventory.stock_max' => DEFAULT_STOCK_MAX,
        'inventory.iva' => DEFAULT_IVA,

        // Paginación
        'pagination.records_per_page' => RECORDS_PER_PAGE,
        'pagination.max_links' => MAX_PAGINATION_LINKS,

        // Archivos
        'files.max_size' => MAX_FILE_SIZE,
        'files.image_types' => ALLOWED_IMAGE_TYPES,
        'files.document_types' => ALLOWED_DOCUMENT_TYPES,

        // Seguridad
        'security.password_min_length' => PASSWORD_MIN_LENGTH,
        'security.session_lifetime' => SESSION_LIFETIME,
        'security.csrf_token_name' => CSRF_TOKEN_NAME,

        // Roles
        'roles.admin' => ROLE_ADMIN,
        'roles.vendedor' => ROLE_VENDEDOR,
        'roles.mecanico' => ROLE_MECANICO,

        // PDF
        'pdf.author' => PDF_AUTHOR,
        'pdf.creator' => PDF_CREATOR,
        'pdf.margin_top' => PDF_MARGIN_TOP,
        'pdf.margin_bottom' => PDF_MARGIN_BOTTOM,
        'pdf.margin_left' => PDF_MARGIN_LEFT,
        'pdf.margin_right' => PDF_MARGIN_RIGHT,
    ];

    return isset($configs[$key]) ? $configs[$key] : $default;
}

// Función para formatear fechas
function formatDate($date, $format = DATE_FORMAT)
{
    if (empty($date)) {
        return '';
    }

    if (is_string($date)) {
        $date = new DateTime($date);
    }

    return $date instanceof DateTime ? $date->format($format) : '';
}

// Función para formatear moneda
function formatCurrency($amount, $symbol = 'S/')
{
    return $symbol . ' ' . number_format((float)$amount, 2, '.', ',');
}

// Función para formatear porcentajes
function formatPercentage($value, $decimals = 2)
{
    return number_format((float)$value, $decimals) . '%';
}

// Función para obtener el IGV calculado
function calculateIva($amount)
{
    return $amount * (DEFAULT_IVA / 100);
}

// Función para obtener el monto sin IGV
function calculateAmountWithoutIva($totalAmount)
{
    return $totalAmount / (1 + (DEFAULT_IVA / 100));
}

// Función para validar roles
function isValidRole($role)
{
    $validRoles = [ROLE_ADMIN, ROLE_VENDEDOR, ROLE_MECANICO];
    return in_array($role, $validRoles);
}

// Función para obtener lista de roles
function getAvailableRoles()
{
    return [
        ROLE_ADMIN => 'Administrador',
        ROLE_VENDEDOR => 'Vendedor',
        ROLE_MECANICO => 'Mecánico'
    ];
}
