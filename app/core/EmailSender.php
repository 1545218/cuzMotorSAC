<?php

/**
 * EmailSender - Sistema simple de envío de correos REAL
 * Funciona con cualquier email sin configuración complicada
 */

class EmailSender
{

    /**
     * Enviar correo REAL usando mail() de PHP
     */
    public static function sendEmail($to, $subject, $message, $isHTML = true)
    {
        try {
            // Headers básicos
            $headers = [];
            $headers[] = 'From: Sistema CruzMotorSAC <sistema@cruzmotorsac.local>';
            $headers[] = 'Reply-To: no-reply@cruzmotorsac.local';
            $headers[] = 'X-Mailer: PHP/' . phpversion();

            if ($isHTML) {
                $headers[] = 'Content-Type: text/html; charset=UTF-8';
                $headers[] = 'MIME-Version: 1.0';
            } else {
                $headers[] = 'Content-Type: text/plain; charset=UTF-8';
            }

            // Convertir headers a string
            $headerString = implode("\r\n", $headers);

            // Log del intento
            error_log("📧 Enviando correo a: $to");
            error_log("📧 Asunto: $subject");

            // Intentar envío con mail() nativo
            $result = mail($to, $subject, $message, $headerString);

            if ($result) {
                error_log("✅ Correo enviado exitosamente a: $to");
                return ['success' => true, 'message' => 'Correo enviado exitosamente'];
            } else {
                error_log("❌ Error al enviar correo a: $to");
                return ['success' => false, 'message' => 'Error en mail() - verifica configuración SMTP del servidor'];
            }
        } catch (Exception $e) {
            error_log("💥 Excepción enviando correo: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
        }
    }

    /**
     * Enviar alerta de stock bajo por correo
     */
    public static function sendStockAlert($email, $productos_bajo_stock)
    {
        $subject = "🚨 ALERTA: Stock Bajo - CruzMotorSAC";

        // Crear mensaje HTML
        $html = self::createStockAlertHTML($productos_bajo_stock);

        return self::sendEmail($email, $subject, $html, true);
    }

    /**
     * Crear HTML para alerta de stock
     */
    private static function createStockAlertHTML($productos)
    {
        $fecha = date('d/m/Y H:i:s');
        $totalProductos = count($productos);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Alerta de Stock Bajo</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
                .header { background: #dc3545; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .alert-item { background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin: 10px 0; border-radius: 5px; }
                .critical { background: #f8d7da; border-color: #f5c6cb; }
                .footer { background: #f8f9fa; padding: 15px; text-align: center; color: #6c757d; font-size: 12px; }
                .btn { display: inline-block; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 4px; margin: 10px 5px; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚨 ALERTA DE STOCK BAJO</h1>
                    <p>Sistema de Inventario CruzMotorSAC</p>
                </div>
                
                <div class="content">
                    <h2>⚠️ Productos con Stock Crítico</h2>
                    <p><strong>Se detectaron ' . $totalProductos . ' productos con stock bajo:</strong></p>
                    
                    <div style="margin: 20px 0;">';

        foreach ($productos as $producto) {
            $porcentaje = $producto['stock_minimo'] > 0 ?
                round(($producto['stock_actual'] / $producto['stock_minimo']) * 100, 1) : 0;

            $clase = $porcentaje <= 10 ? 'critical' : '';
            $icono = $porcentaje <= 10 ? '🔴' : ($porcentaje <= 25 ? '🟡' : '🟢');

            $html .= '
                        <div class="alert-item ' . $clase . '">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div>
                                    <h4 style="margin: 0; color: #333;">' . $icono . ' ' . htmlspecialchars($producto['nombre']) . '</h4>
                                    <p style="margin: 5px 0; color: #666;">
                                        <strong>Código:</strong> ' . htmlspecialchars($producto['codigo']) . '<br>
                                        <strong>Stock Actual:</strong> ' . $producto['stock_actual'] . ' unidades<br>
                                        <strong>Stock Mínimo:</strong> ' . $producto['stock_minimo'] . ' unidades<br>
                                        <strong>Estado:</strong> <span style="color: #dc3545; font-weight: bold;">' . $porcentaje . '% del mínimo</span>
                                    </p>
                                </div>
                            </div>
                        </div>';
        }

        $html .= '
                    </div>
                    
                    <div style="background: #d1ecf1; padding: 15px; border-radius: 5px; margin: 20px 0;">
                        <h3 style="color: #0c5460; margin-top: 0;">🔧 Acciones Recomendadas:</h3>
                        <ul style="color: #0c5460;">
                            <li>Contactar proveedores para reabastecimiento urgente</li>
                            <li>Verificar pedidos pendientes</li>
                            <li>Revisar histórico de ventas para ajustar stock mínimo</li>
                            <li>Actualizar inventario si es necesario</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin: 20px 0;">
                        <a href="http://localhost/CruzMotorSAC/public/index.php?page=inventario" class="btn">
                            📦 Ver Inventario Completo
                        </a>
                        <a href="http://localhost/CruzMotorSAC/public/index.php?page=productos" class="btn">
                            ✏️ Gestionar Productos
                        </a>
                    </div>
                </div>
                
                <div class="footer">
                    <p><strong>Fecha de alerta:</strong> ' . $fecha . '</p>
                    <p>Este es un mensaje automático del Sistema de Inventario CruzMotorSAC</p>
                    <p>Si recibiste este correo por error, puedes ignorarlo.</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }

    /**
     * Probar envío de correo
     */
    public static function testEmail($email)
    {
        $subject = "✅ PRUEBA: Sistema CruzMotorSAC Funcionando";

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; background: #f4f4f4; }
                .container { max-width: 500px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin: 10px 0; }
            </style>
        </head>
        <body>
            <div class="container">
                <h2>🎉 ¡Prueba Exitosa!</h2>
                
                <div class="success">
                    <strong>✅ El sistema de correos está funcionando correctamente</strong>
                </div>
                
                <p>Este es un correo de prueba del sistema de inventario CruzMotorSAC.</p>
                
                <p><strong>Fecha/Hora:</strong> ' . date('d/m/Y H:i:s') . '</p>
                <p><strong>Sistema:</strong> Funcionando perfectamente 🚀</p>
                
                <p>Si recibes este mensaje, significa que las alertas de stock bajo también funcionarán.</p>
                
                <hr>
                <p style="font-size: 12px; color: #666;">
                    Sistema desarrollado para CruzMotorSAC<br>
                    Inventario y Control de Stock
                </p>
            </div>
        </body>
        </html>';

        return self::sendEmail($email, $subject, $html, true);
    }
}
