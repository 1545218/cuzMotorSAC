<?php
// Endpoint para recibir logs del navegador y guardarlos con Logger
// Migrado a public/api para ser servido desde el document root.
require_once __DIR__ . '/../../config/config.php';
require_once APP_PATH . '/core/Logger.php';

// Aceptar JSON o form-data
$raw = file_get_contents('php://input');
$data = [];
if (!empty($raw)) {
    $json = json_decode($raw, true);
    if (json_last_error() === JSON_ERROR_NONE && is_array($json)) {
        $data = $json;
    }
}

// Merge with POST si hay
if ($_POST) {
    $data = array_merge($data, $_POST);
}

// Iniciar sesión si existe cookie de sesión para obtener usuario
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

$context = [
    'url' => $data['url'] ?? ($_SERVER['HTTP_REFERER'] ?? null),
    'message' => $data['message'] ?? ($data['msg'] ?? null),
    'stack' => $data['stack'] ?? null,
    'file' => $data['file'] ?? null,
    'line' => $data['line'] ?? null,
    'col' => $data['col'] ?? null,
    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
    'ip' => $_SERVER['REMOTE_ADDR'] ?? null,
    'session' => isset($_SESSION) ? array_intersect_key($_SESSION, array_flip(['user_id', 'username', 'rol'])) : []
];

// Nivel según tipo
$level = 'ERROR';
if (!empty($data['level'])) {
    $level = strtoupper(substr($data['level'], 0, 5));
}

// Escribir log
try {
    if ($level === 'WARN' || $level === 'WARNING') {
        Logger::warning('JS: ' . ($context['message'] ?? 'Browser log'), $context);
    } else {
        Logger::error('JS: ' . ($context['message'] ?? 'Browser log'), $context);
    }
} catch (Exception $e) {
    // No romper la respuesta por fallos en logger
}

// Responder vacío
http_response_code(204);
exit;
