<?php
// api/consulta_documento.php
// Endpoint para consultar DNI y RUC usando api.decolecta.com (requiere token gratuito)
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Método no permitido']);
    exit;
}

$tipo = $_POST['tipo'] ?? '';
$numero = $_POST['numero'] ?? '';


if (!$tipo || !$numero) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

// Logging temporal para depuración
$log_path = __DIR__ . '/../../storage/logs/consulta_documento.log';
$log_msg = date('Y-m-d H:i:s') . " | Tipo: $tipo | Numero: $numero\n";
file_put_contents($log_path, $log_msg, FILE_APPEND);

$token = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJlbWFpbCI6Imx1bHpzZWMyNDA5QGdtYWlsLmNvbSJ9.N7N72ZbD8ZHcWg8tYfWAoziC9TQ9JsI9PHHiOMuk-Sk'; // Pega aquí tu token real de apisperu.com. Si lo dejas vacío, la API no responderá datos.
if ($tipo === 'DNI') {
    $url = 'https://dniruc.apisperu.com/api/v1/dni/' . urlencode($numero) . '?token=' . $token;
} elseif ($tipo === 'RUC') {
    $url = 'https://dniruc.apisperu.com/api/v1/ruc/' . urlencode($numero) . '?token=' . $token;
} else {
    file_put_contents($log_path, date('Y-m-d H:i:s') . " | Tipo no soportado\n", FILE_APPEND);
    echo json_encode(['error' => 'Tipo de documento no soportado']);
    exit;
}

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 10);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Accept: application/json'
]);
$response = curl_exec($ch);
$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

file_put_contents($log_path, date('Y-m-d H:i:s') . " | HTTP $httpcode | Respuesta: $response\n", FILE_APPEND);

if ($httpcode !== 200 || !$response) {
    echo json_encode(['error' => 'No se pudo consultar la API']);
    exit;
}

echo $response;
