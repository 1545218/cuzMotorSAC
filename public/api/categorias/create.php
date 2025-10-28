<?php
// API para crear nueva categoría
require_once __DIR__ . '/../../../config/config.php';

// Asegurar sesión y cargar núcleo del framework (mismo bootstrap que public/index.php)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_PATH . '/core/Logger.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Security.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Model.php';

require_once APP_PATH . '/models/Categoria.php';
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Método no permitido']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$nombre = trim($data['nombre'] ?? '');
if (!$nombre) {
    echo json_encode(['success' => false, 'error' => 'Nombre requerido']);
    exit;
}

try {
    $categoriaModel = new Categoria();
    $created = $categoriaModel->create(['nombre' => $nombre]);
    // $created puede ser el registro creado (array) o false
    if (is_array($created) && isset($created['id_categoria'])) {
        $id = (int)$created['id_categoria'];
    } elseif (is_numeric($created)) {
        $id = (int)$created;
    } else {
        // intentar obtener último insert
        $last = $categoriaModel->getLastInserted();
        $id = $last['id_categoria'] ?? null;
    }

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
