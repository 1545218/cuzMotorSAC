<?php
// scripts/notify_low_stock.php
// Script CLI para notificar por correo productos con stock bajo

// Cargar configuración y dependencias mínimas
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../app/core/Logger.php';
require_once __DIR__ . '/../app/core/Database.php';
require_once __DIR__ . '/../app/core/Model.php';
require_once __DIR__ . '/../app/models/Inventario.php';
require_once __DIR__ . '/../app/core/EmailAlertHelper.php';

// Establecer entorno CLI
if (php_sapi_name() !== 'cli') {
    echo "Este script debe ejecutarse desde la línea de comandos.\n";
    exit(1);
}

try {
    $inventario = new Inventario();
    $productos = $inventario->getProductosBajoStock(null, 500);

    if (empty($productos)) {
        Logger::info('notify_low_stock: No hay productos con stock bajo en este momento.');
        echo "No hay productos con stock bajo.\n";
        exit(0);
    }

    // Construir mensaje HTML con la lista (limitada a 100 por seguridad)
    $count = count($productos);
    $maxShow = 100;
    $show = array_slice($productos, 0, $maxShow);

    $body = "<h3>Productos con stock bajo ({$count})</h3>";
    $body .= "<table style=\"width:100%;border-collapse:collapse;\">";
    $body .= "<thead><tr><th style=\"border:1px solid #ccc;padding:6px;text-align:left\">Código</th><th style=\"border:1px solid #ccc;padding:6px;text-align:left\">Producto</th><th style=\"border:1px solid #ccc;padding:6px;text-align:right\">Stock Actual</th><th style=\"border:1px solid #ccc;padding:6px;text-align:right\">Stock Mínimo</th><th style=\"border:1px solid #ccc;padding:6px;text-align:right\">Diferencia</th></tr></thead>";
    $body .= "<tbody>";
    foreach ($show as $p) {
        $code = htmlspecialchars($p['codigo'] ?? '');
        $name = htmlspecialchars($p['producto_nombre'] ?? $p['nombre'] ?? '');
        $stockActual = (int)($p['stock_actual'] ?? $p['stock'] ?? 0);
        $stockMin = (int)($p['stock_minimo'] ?? 0);
        $dif = $stockMin - $stockActual;
        $body .= "<tr><td style=\"border:1px solid #eee;padding:6px;\">{$code}</td><td style=\"border:1px solid #eee;padding:6px;\">{$name}</td><td style=\"border:1px solid #eee;padding:6px;text-align:right\">{$stockActual}</td><td style=\"border:1px solid #eee;padding:6px;text-align:right\">{$stockMin}</td><td style=\"border:1px solid #eee;padding:6px;text-align:right\">{$dif}</td></tr>";
    }
    $body .= "</tbody></table>";

    if ($count > $maxShow) {
        $body .= "<p>Se muestran los primeros {$maxShow} productos. Revise el módulo de inventario para ver la lista completa.</p>";
    }

    // Construir alerta para EmailAlertHelper
    $alerts = [
        [
            'title' => 'Productos con stock bajo',
            'message' => "Hay {$count} productos con stock por debajo del mínimo.",
            'url' => BASE_URL . '/productos/stock-bajo'
        ]
    ];

    // Enviar correo: si NOTIFY_TEST_MODE está activado, enviar sólo a NOTIFY_TEST_EMAIL
    $recipients = [];
    if (defined('NOTIFY_TEST_MODE') && NOTIFY_TEST_MODE === true) {
        if (defined('NOTIFY_TEST_EMAIL') && !empty(NOTIFY_TEST_EMAIL)) {
            $recipients[] = NOTIFY_TEST_EMAIL;
        } else {
            Logger::warning('notify_low_stock: NOTIFY_TEST_MODE activo pero NOTIFY_TEST_EMAIL no configurado. No se enviará correo de prueba.');
            echo "NOTIFY_TEST_MODE activo pero NOTIFY_TEST_EMAIL no está configurado.\n";
            exit(1);
        }
    } else {
        // Leer correos desde la tabla notificacion_correos
        require_once __DIR__ . '/../app/models/NotificacionCorreo.php';
        $correoModel = new NotificacionCorreo();
        $correos = $correoModel->getAll();
        foreach ($correos as $c) {
            if (!empty($c['email']) && filter_var($c['email'], FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $c['email'];
            }
        }
    }

    // Añadir correos adicionales desde configuración (ej.: NOTIFY_ADDITIONAL_EMAILS)
    if (defined('NOTIFY_ADDITIONAL_EMAILS') && !empty(NOTIFY_ADDITIONAL_EMAILS)) {
        $extra = array_map('trim', explode(',', NOTIFY_ADDITIONAL_EMAILS));
        foreach ($extra as $e) {
            if (filter_var($e, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $e;
            } else {
                Logger::warning("notify_low_stock: correo adicional inválido ignorado: {$e}");
            }
        }
    }

    // Normalizar y deduplicar lista de destinatarios
    $recipients = array_unique(array_filter(array_map('trim', $recipients)));

    // Enviar a la lista final de destinatarios
    if (!empty($recipients)) {
        Logger::info('notify_low_stock: Enviando notificaciones a: ' . implode(', ', $recipients));
        foreach ($recipients as $to) {
            Mailer::send($to, 'Alertas: Productos con stock bajo', $body);
        }
    } else {
        Logger::warning('notify_low_stock: No hay destinatarios válidos para enviar notificaciones.');
        echo "No hay destinatarios válidos.\n";
        exit(1);
    }

    Logger::info('notify_low_stock: Correos enviados. Productos encontrados: ' . $count);
    echo "Correos enviados. Productos con stock bajo: {$count}\n";
    exit(0);
} catch (Exception $e) {
    Logger::error('notify_low_stock: Error ejecutando notificador - ' . $e->getMessage());
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
