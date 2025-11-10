<?php

/**
 * AutoEmailer - Sistema SIMPLE de correos que SÍ funciona
 * Sin errores, sin complicaciones
 */

class AutoEmailer
{

    /**
     * Enviar correo usando mail() CORREGIDO
     */
    public static function sendAutoEmail($to, $subject, $message)
    {
        try {
            // Headers básicos que funcionan
            $headers = "From: CruzMotorSAC <sistema@cruzmotorsac.local>\r\n";
            $headers .= "Reply-To: no-reply@cruzmotorsac.local\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "X-Mailer: CruzMotorSAC\r\n";

            // Intentar envío
            $result = @mail($to, $subject, $message, $headers);

            if ($result) {
                error_log("✅ Email enviado exitosamente a: $to");
                return ['success' => true, 'method' => 'mail()'];
            } else {
                error_log("❌ Fallo mail() para: $to");
                // Intentar método alternativo
                return self::sendViaFile($to, $subject, $message);
            }
        } catch (Exception $e) {
            error_log("❌ Error en sendAutoEmail: " . $e->getMessage());
            return self::sendViaFile($to, $subject, $message);
        }
    }

    /**
     * Método alternativo - guardar en archivo para revisar
     */
    private static function sendViaFile($to, $subject, $message)
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = __DIR__ . "/../../storage/logs/email_$timestamp.html";

            // Crear directorio si no existe
            $dir = dirname($filename);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            // Contenido del email
            $content = "<!DOCTYPE html><html><head><meta charset='UTF-8'>";
            $content .= "<title>$subject</title></head><body>";
            $content .= "<h2>📧 EMAIL GENERADO</h2>";
            $content .= "<p><strong>Para:</strong> $to</p>";
            $content .= "<p><strong>Asunto:</strong> $subject</p>";
            $content .= "<p><strong>Fecha:</strong> " . date('d/m/Y H:i:s') . "</p>";
            $content .= "<hr>$message</body></html>";

            file_put_contents($filename, $content);

            error_log("📄 Email guardado en: $filename");

            return [
                'success' => true,
                'method' => 'file',
                'file' => $filename,
                'message' => 'Email guardado como archivo HTML'
            ];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Enviar alerta de stock - SIMPLIFICADO
     */
    public static function sendStockAlert($email, $productos)
    {
        $subject = "🚨 ALERTA: Stock Bajo - CruzMotorSAC";
        $html = self::createSimpleAlert($productos);
        return self::sendAutoEmail($email, $subject, $html);
    }

    /**
     * HTML simple para alerta
     */
    private static function createSimpleAlert($productos)
    {
        $fecha = date('d/m/Y H:i:s');
        $total = count($productos);

        $html = "<div style='font-family: Arial; max-width: 600px; margin: 20px auto; padding: 20px; border: 2px solid #dc3545; border-radius: 10px;'>";
        $html .= "<h1 style='color: #dc3545; text-align: center;'>🚨 ALERTA DE STOCK BAJO</h1>";
        $html .= "<div style='background: #f8d7da; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        $html .= "<h2 style='margin: 0; color: #721c24;'>⚠️ ATENCIÓN URGENTE</h2>";
        $html .= "<p><strong>Fecha:</strong> $fecha</p>";
        $html .= "<p><strong>Productos críticos:</strong> $total</p>";
        $html .= "</div>";

        foreach ($productos as $producto) {
            $porcentaje = isset($producto['porcentaje_stock']) ? $producto['porcentaje_stock'] : 0;
            if ($porcentaje == 0 && isset($producto['stock_minimo']) && $producto['stock_minimo'] > 0) {
                $porcentaje = round(($producto['stock_actual'] / $producto['stock_minimo']) * 100, 1);
            }

            $html .= "<div style='background: #fff3cd; border-left: 5px solid #ffc107; padding: 15px; margin: 10px 0;'>";
            $html .= "<h3 style='margin: 0; color: #856404;'>🔴 " . htmlspecialchars($producto['nombre']) . "</h3>";
            $html .= "<p><strong>Código:</strong> " . htmlspecialchars($producto['codigo'] ?? 'N/A') . "</p>";
            $html .= "<p><strong>Stock actual:</strong> <span style='color: #dc3545; font-weight: bold;'>" . $producto['stock_actual'] . "</span> unidades</p>";
            $html .= "<p><strong>Stock mínimo:</strong> " . ($producto['stock_minimo'] ?? 0) . " unidades</p>";
            $html .= "<p><strong>Nivel:</strong> <span style='color: #dc3545; font-weight: bold;'>" . $porcentaje . "% del mínimo</span></p>";
            $html .= "</div>";
        }

        $html .= "<div style='background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;'>";
        $html .= "<h3 style='color: #0c5460;'>� ACCIÓN REQUERIDA:</h3>";
        $html .= "<ul style='color: #0c5460;'>";
        $html .= "<li>Revisar inventario inmediatamente</li>";
        $html .= "<li>Contactar proveedores para reabastecimiento</li>";
        $html .= "<li>Verificar pedidos pendientes</li>";
        $html .= "</ul>";
        $html .= "</div>";

        $html .= "<div style='text-align: center; margin: 20px 0;'>";
        $html .= "<a href='http://localhost/CruzMotorSAC/public/index.php?page=inventario' style='background: #007bff; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>📦 Ver Inventario</a>";
        $html .= "</div>";

        $html .= "<hr><p style='text-align: center; color: #666; font-size: 12px;'>";
        $html .= "Sistema CruzMotorSAC - Alerta automática generada el $fecha";
        $html .= "</p></div>";

        return $html;
    }

    /**
     * Guardar log simple
     */
    public static function saveAlertToFile($productos)
    {
        try {
            $timestamp = date('Y-m-d_H-i-s');
            $filename = __DIR__ . "/../../storage/logs/alert_$timestamp.txt";

            $dir = dirname($filename);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }

            $content = "🚨 ALERTA DE STOCK BAJO - " . date('d/m/Y H:i:s') . "\n";
            $content .= str_repeat("=", 50) . "\n\n";

            foreach ($productos as $producto) {
                $content .= "Producto: " . $producto['nombre'] . "\n";
                $content .= "Código: " . ($producto['codigo'] ?? 'N/A') . "\n";
                $content .= "Stock actual: " . $producto['stock_actual'] . "\n";
                $content .= "Stock mínimo: " . ($producto['stock_minimo'] ?? 0) . "\n";
                $content .= str_repeat("-", 30) . "\n";
            }

            file_put_contents($filename, $content);

            return ['success' => true, 'file' => $filename];
        } catch (Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
}
