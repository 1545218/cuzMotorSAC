<?php
// Endpoint para crear subcategorías desde AJAX
require_once __DIR__ . '/../../../../config/config.php';
require_once APP_PATH . '/core/Logger.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/models/Subcategoria.php';

header('Content-Type: application/json; charset=utf-8');

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'No autorizado']);
    exit;
}

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?? $_POST;

// CSRF token check (flexible)
$token = $data[CSRF_TOKEN_NAME] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST[CSRF_TOKEN_NAME] ?? $_POST['csrf_token'] ?? null;
if (!$auth->validateCSRFToken($token)) {
    http_response_code(403);
    echo json_encode(['error' => 'Token CSRF inválido']);
    exit;
}

$nombre = trim($data['nombre'] ?? '');
$id_categoria = isset($data['id_categoria']) ? (int)$data['id_categoria'] : 0;

if ($nombre === '' || $id_categoria <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'Parámetros inválidos']);
    exit;
}

try {
    $sub = new Subcategoria();
    $created = $sub->createSubcategory(['id_categoria' => $id_categoria, 'nombre' => $nombre]);
    // createSubcategory devuelve el id (o array con id)
    if (is_array($created) && isset($created['id_subcategoria'])) {
        $id = (int)$created['id_subcategoria'];
    } elseif (is_numeric($created)) {
        $id = (int)$created;
    } else {
        // intentar obtener el último insertado
        $db = Database::getInstance();
        $last = $db->selectOne('SELECT MAX(id_subcategoria) as id FROM subcategorias');
        $id = $last['id'] ?? null;
    }

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    Logger::error('Error creando subcategoría desde API: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
