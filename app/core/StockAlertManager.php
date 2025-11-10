<?php

/**
 * StockAlertManager - Gestor automático de alertas de stock bajo
 * Sistema de Inventario Cruz Motor S.A.C.
 */

require_once __DIR__ . '/Mailer.php';
require_once __DIR__ . '/../models/NotificacionCorreo.php';

class StockAlertManager
{
    private $db;
    private $notificacionModel;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->notificacionModel = new NotificacionCorreo();
    }

    /**
     * Envía alertas de stock bajo por correo electrónico
     */
    public function sendLowStockAlerts()
    {
        try {
            // Obtener productos con stock bajo
            $lowStockProducts = $this->getLowStockProducts();

            if (empty($lowStockProducts)) {
                return ['success' => true, 'message' => 'No hay productos con stock bajo'];
            }

            // Obtener correos para notificaciones
            $emails = $this->notificacionModel->getAll();

            if (empty($emails)) {
                return ['success' => false, 'message' => 'No hay correos configurados para notificaciones'];
            }

            // Preparar el correo
            $subject = '🚨 ALERTA: Productos con Stock Bajo - CruzMotorSAC';
            $htmlBody = $this->generateEmailBody($lowStockProducts);

            $sentEmails = 0;
            $failedEmails = 0;

            // Enviar a todos los correos registrados
            foreach ($emails as $emailData) {
                $email = $emailData['email'];

                try {
                    $result = Mailer::send($email, $subject, $htmlBody);
                    if ($result) {
                        $sentEmails++;
                        error_log("Alerta de stock enviada exitosamente a: $email");
                    } else {
                        $failedEmails++;
                        error_log("Error enviando alerta de stock a: $email");
                    }
                } catch (Exception $e) {
                    $failedEmails++;
                    error_log("Excepción enviando alerta de stock a $email: " . $e->getMessage());
                }
            }

            // Registrar en log de sistema
            $this->logStockAlert($lowStockProducts, $sentEmails, $failedEmails);

            return [
                'success' => true,
                'message' => "Alertas enviadas: $sentEmails exitosas, $failedEmails fallidas",
                'products_count' => count($lowStockProducts),
                'sent_emails' => $sentEmails,
                'failed_emails' => $failedEmails
            ];
        } catch (Exception $e) {
            error_log("Error en StockAlertManager::sendLowStockAlerts: " . $e->getMessage());
            return ['success' => false, 'message' => 'Error interno: ' . $e->getMessage()];
        }
    }

    /**
     * Obtiene productos con stock bajo o crítico
     */
    private function getLowStockProducts()
    {
        try {
            return $this->db->getConnection()->prepare("
                SELECT 
                    nombre,
                    codigo_barras,
                    stock_actual,
                    stock_minimo,
                    ROUND(((stock_actual / stock_minimo) * 100), 1) as porcentaje_stock
                FROM productos 
                WHERE stock_actual <= stock_minimo 
                AND estado = 'activo'
                ORDER BY porcentaje_stock ASC, stock_actual ASC
            ")->execute() ? $this->db->getConnection()->prepare("
                SELECT 
                    nombre,
                    codigo_barras,
                    stock_actual,
                    stock_minimo,
                    ROUND(((stock_actual / stock_minimo) * 100), 1) as porcentaje_stock
                FROM productos 
                WHERE stock_actual <= stock_minimo 
                AND estado = 'activo'
                ORDER BY porcentaje_stock ASC, stock_actual ASC
            ")->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Exception $e) {
            error_log("Error obteniendo productos con stock bajo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Genera el cuerpo HTML del correo de alerta
     */
    private function generateEmailBody($products)
    {
        $totalProducts = count($products);
        $criticalProducts = array_filter($products, function ($p) {
            return $p['porcentaje_stock'] <= 25;
        });
        $warningProducts = array_filter($products, function ($p) {
            return $p['porcentaje_stock'] > 25 && $p['porcentaje_stock'] <= 50;
        });

        $html = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
                .header { background: #dc3545; color: white; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
                .alert-critical { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .alert-warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 5px; margin: 10px 0; }
                .product-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
                .product-table th, .product-table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                .product-table th { background-color: #f2f2f2; }
                .status-critical { color: #dc3545; font-weight: bold; }
                .status-warning { color: #ffc107; font-weight: bold; }
                .footer { margin-top: 20px; padding-top: 15px; border-top: 1px solid #ddd; color: #666; font-size: 12px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>🚨 ALERTA: Stock Bajo Detectado</h2>
                    <p>Sistema de Inventario CruzMotorSAC</p>
                </div>
                
                <p><strong>Resumen de la alerta:</strong></p>
                <ul>
                    <li>Total de productos con stock bajo: <strong>$totalProducts</strong></li>
                    <li>Productos en estado crítico (≤25%): <strong>" . count($criticalProducts) . "</strong></li>
                    <li>Productos en advertencia (26-50%): <strong>" . count($warningProducts) . "</strong></li>
                    <li>Fecha y hora: <strong>" . date('d/m/Y H:i:s') . "</strong></li>
                </ul>";

        if (!empty($criticalProducts)) {
            $html .= "
                <div class='alert-critical'>
                    <h3>🔴 PRODUCTOS EN ESTADO CRÍTICO</h3>
                    <table class='product-table'>
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>% Stock</th>
                        </tr>";

            foreach ($criticalProducts as $product) {
                $html .= "
                        <tr>
                            <td>{$product['nombre']}</td>
                            <td>{$product['codigo_barras']}</td>
                            <td>{$product['stock_actual']}</td>
                            <td>{$product['stock_minimo']}</td>
                            <td class='status-critical'>{$product['porcentaje_stock']}%</td>
                        </tr>";
            }
            $html .= "</table></div>";
        }

        if (!empty($warningProducts)) {
            $html .= "
                <div class='alert-warning'>
                    <h3>🟡 PRODUCTOS EN ADVERTENCIA</h3>
                    <table class='product-table'>
                        <tr>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>% Stock</th>
                        </tr>";

            foreach ($warningProducts as $product) {
                $html .= "
                        <tr>
                            <td>{$product['nombre']}</td>
                            <td>{$product['codigo_barras']}</td>
                            <td>{$product['stock_actual']}</td>
                            <td>{$product['stock_minimo']}</td>
                            <td class='status-warning'>{$product['porcentaje_stock']}%</td>
                        </tr>";
            }
            $html .= "</table></div>";
        }

        $html .= "
                <div class='footer'>
                    <p><strong>¿Qué hacer?</strong></p>
                    <ul>
                        <li>Revisar inmediatamente los productos críticos</li>
                        <li>Programar reposición de stock</li>
                        <li>Contactar a proveedores</li>
                        <li>Actualizar stock mínimo si es necesario</li>
                    </ul>
                    
                    <p>Este correo fue enviado automáticamente por el Sistema de Inventario CruzMotorSAC.</p>
                    <p>Para dejar de recibir estas notificaciones, contacte al administrador del sistema.</p>
                </div>
            </div>
        </body>
        </html>";

        return $html;
    }

    /**
     * Registra la alerta en el log del sistema
     */
    private function logStockAlert($products, $sentEmails, $failedEmails)
    {
        try {
            $message = "Alerta de stock bajo enviada. Productos afectados: " . count($products) .
                ". Correos enviados: $sentEmails. Fallos: $failedEmails";

            error_log("STOCK_ALERT: $message");

            // También podríamos insertar en tabla de logs si existe
            // $this->db->insert("INSERT INTO logs_sistema (tipo, mensaje, fecha) VALUES (?, ?, NOW())", 
            //                   ['stock_alert', $message]);

        } catch (Exception $e) {
            error_log("Error registrando log de alerta de stock: " . $e->getMessage());
        }
    }

    /**
     * Verifica si es necesario enviar alertas (para evitar spam)
     * Puede ser llamado cada hora o según configuración
     */
    public function shouldSendAlerts()
    {
        // Lógica para evitar enviar alertas muy frecuentemente
        // Por ejemplo, solo una vez por día para el mismo conjunto de productos
        return true; // Por ahora siempre envía
    }
}
