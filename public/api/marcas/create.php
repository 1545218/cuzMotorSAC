<?php
// API para crear nueva marca
require_once __DIR__ . '/../../../config/config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once APP_PATH . '/core/Logger.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Auth.php';
require_once APP_PATH . '/core/Security.php';
require_once APP_PATH . '/core/Controller.php';
require_once APP_PATH . '/core/Model.php';

require_once APP_PATH . '/models/Marca.php';
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
    $marcaModel = new Marca();
    $created = $marcaModel->create(['nombre' => $nombre]);
    if (is_array($created) && isset($created['id_marca'])) {
        $id = (int)$created['id_marca'];
    } elseif (is_numeric($created)) {
        $id = (int)$created;
    } else {
        $last = $marcaModel->getLastInserted();
        $id = $last['id_marca'] ?? null;
    }

    echo json_encode(['success' => true, 'id' => $id]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
