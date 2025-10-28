<?php
// Script de prueba: obtiene el formulario, extrae CSRF y hace POST simulando el formulario de cliente
$base = 'http://localhost/CruzMotorSAC';
$getUrl = $base . '/?page=clientes&action=create';
$postUrl = $base . '/?page=clientes&action=store';
$cookieFile = sys_get_temp_dir() . '/csfr_test_cookies.txt';

echo "GET: $getUrl\n";
$ch = curl_init($getUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
$html = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);
if (!$html) {
    echo "No se pudo obtener la página de formulario. HTTP info:\n";
    var_dump($info);
    exit(1);
}

// Extraer token CSRF
if (preg_match('/name="csrf_token" value="([a-f0-9]+)"/i', $html, $m)) {
    $token = $m[1];
    echo "Token CSRF obtenido: $token\n";
} else {
    echo "No se encontró token CSRF en la página.\n";
    // volcar fragmento para debug
    echo substr($html, 0, 2000) . "\n...\n";
    exit(1);
}

// Preparar POST con campos mínimos
$postFields = [
    'csrf_token' => $token,
    'nombre' => 'Test Usuario',
    'tipo_documento' => 'DNI',
    'numero_documento' => '12345678'
];

echo "POST: $postUrl\n";
$ch = curl_init($postUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookieFile);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postFields);
$response = curl_exec($ch);
$info = curl_getinfo($ch);
curl_close($ch);

echo "HTTP STATUS: " . ($info['http_code'] ?? 'unknown') . "\n";
// Mostrar parte del response
echo substr($response, 0, 2000) . "\n...\n";

// Mostrar últimas líneas del log
$logFile = __DIR__ . '/../storage/logs/app_' . date('Y-m-d') . '.log';
if (file_exists($logFile)) {
    echo "\n-- Últimas líneas del log ({$logFile}):\n";
    $tail = shell_exec('powershell -Command "Get-Content -Path \"' . $logFile . '\" -Tail 50"');
    echo $tail . "\n";
} else {
    echo "\nNo existe el archivo de log esperado: {$logFile}\n";
}
