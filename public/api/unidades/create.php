<?php
// API para crear nueva unidad
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

require_once APP_PATH . '/models/Unidad.php';
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
    $unidadModel = new Unidad();
    try {
        // Intentar crear de forma estándar
        $created = $unidadModel->create(['nombre' => $nombre]);
        if (is_array($created) && isset($created['id_unidad'])) {
            $id = (int)$created['id_unidad'];
        } elseif (is_numeric($created)) {
            $id = (int)$created;
        } else {
            $last = $unidadModel->getLastInserted();
            $id = $last['id_unidad'] ?? null;
        }

        echo json_encode(['success' => true, 'id' => $id]);
    } catch (Exception $e) {
        // Si falla por esquema sin AUTO_INCREMENT (error 1364), intentar insert manual con id calculado
        $msg = $e->getMessage();
        if (strpos($msg, "doesn't have a default value") !== false || strpos($msg, '1364') !== false) {
            // Calcular próximo id y hacer INSERT explícito
            try {
                $db = Database::getInstance();
                // Detectar columna primaria de unidades (intentar id_unidad, si no existe usar primera columna)
                $cols = $db->select('SHOW COLUMNS FROM unidades');
                $primaryCol = 'id_unidad';
                $fields = array_column($cols, 'Field');
                if (!in_array('id_unidad', $fields)) {
                    $primaryCol = $fields[0] ?? 'id_unidad';
                }

                $nextSql = "SELECT COALESCE(MAX({$primaryCol}), 0) + 1 as next FROM unidades";
                $next = (int)($db->selectOne($nextSql)['next'] ?? 1);
                $insertSql = "INSERT INTO unidades ({$primaryCol}, nombre) VALUES (?, ?)";
                $db->execute($insertSql, [$next, $nombre]);
                echo json_encode(['success' => true, 'id' => $next]);
            } catch (Exception $e2) {
                echo json_encode(['success' => false, 'error' => 'Error al insertar unidad (fallback): ' . $e2->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
