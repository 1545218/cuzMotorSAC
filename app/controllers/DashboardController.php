<?php

/**
 * DashboardController - Controlador del panel principal
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class DashboardController extends Controller
{

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Muestra el dashboard principal
     */
    public function index()
    {
        try {
            // Obtener estadísticas generales
            $stats = $this->getStats();

            // Obtener productos con stock bajo
            $lowStockProducts = $this->getLowStockProducts();

            // Sistema de ENVÍO AUTOMÁTICO a Gmail - SÍ funciona
            if (!empty($lowStockProducts)) {
                // Filtrar productos críticos (menos del 25% del stock mínimo)
                $productosTriticos = array_filter($lowStockProducts, function ($producto) {
                    return isset($producto['porcentaje_stock']) && $producto['porcentaje_stock'] <= 25;
                });

                if (!empty($productosTriticos)) {
                    try {
                        // Intentar enviar email automático si está configurado
                        $this->enviarAlertaAutomatica($productosTriticos);
                    } catch (Exception $e) {
                        error_log("Error enviando alerta automática: " . $e->getMessage());
                    }
                }
            }

            // Sistema SIMPLE - Solo guardar en archivos (sin emails complicados)
            $pendingAlerts = [];
            if (!empty($lowStockProducts)) {
                foreach ($lowStockProducts as $producto) {
                    // Validar que el producto tenga los datos necesarios
                    if (
                        !isset($producto['id']) || !isset($producto['nombre']) ||
                        !isset($producto['stock_actual']) || !isset($producto['stock_minimo']) ||
                        !isset($producto['porcentaje_stock'])
                    ) {
                        continue; // Saltar productos con datos incompletos
                    }

                    if ($producto['porcentaje_stock'] <= 50) {
                        $pendingAlerts[] = [
                            'id' => $producto['id'],
                            'producto_id' => $producto['id'],
                            'producto_nombre' => $producto['nombre'],
                            'mensaje' => "Stock bajo: {$producto['nombre']} - {$producto['stock_actual']}/{$producto['stock_minimo']} unidades",
                            'nivel_urgencia' => $producto['porcentaje_stock'] <= 25 ? 'critico' : 'alto',
                            'fecha_creacion' => date('Y-m-d H:i:s'),
                            'datos' => [
                                'stock_actual' => $producto['stock_actual'],
                                'stock_minimo' => $producto['stock_minimo'],
                                'porcentaje' => $producto['porcentaje_stock'],
                                'icono' => $producto['porcentaje_stock'] <= 25 ? '🔴' : '🟡',
                                'color' => $producto['porcentaje_stock'] <= 25 ? '#dc3545' : '#ffc107'
                            ]
                        ];
                    }
                }

                // Solo guardar en archivo como backup (sin emails)
                if (!empty($pendingAlerts)) {
                    try {
                        $timestamp = date('Y-m-d_H-i-s');
                        $logDir = __DIR__ . '/../../storage/logs/';
                        if (!is_dir($logDir)) {
                            @mkdir($logDir, 0755, true);
                        }

                        $logFile = $logDir . "stock_alert_$timestamp.txt";
                        $content = "🚨 ALERTA DE STOCK BAJO - " . date('d/m/Y H:i:s') . "\n";
                        $content .= str_repeat("=", 50) . "\n\n";

                        foreach ($pendingAlerts as $alerta) {
                            $nombre = $alerta['producto_nombre'] ?? 'Producto desconocido';
                            $stockActual = $alerta['datos']['stock_actual'] ?? 0;
                            $stockMinimo = $alerta['datos']['stock_minimo'] ?? 0;
                            $porcentaje = $alerta['datos']['porcentaje'] ?? 0;

                            $content .= "Producto: $nombre\n";
                            $content .= "Stock: $stockActual/$stockMinimo\n";
                            $content .= "Porcentaje: $porcentaje%\n";
                            $content .= str_repeat("-", 30) . "\n";
                        }

                        file_put_contents($logFile, $content);
                        error_log("📄 Alerta guardada en: $logFile");
                    } catch (Exception $e) {
                        error_log("Error guardando log: " . $e->getMessage());
                    }
                }
            }

            // Obtener últimos movimientos
            $recentMovements = $this->getRecentMovements();

            // Obtener alertas del sistema (siempre array)
            $alertas = [];
            try {
                $alertas = $this->getAlerts();
                if (!is_array($alertas)) {
                    $alertas = [];
                }
            } catch (Exception $e) {
                error_log("Error seguro obteniendo alertas: " . $e->getMessage());
                $alertas = [];
            }

            // Enviar alertas por correo solo si existen
            if (!empty($alertas)) {
                try {
                    require_once __DIR__ . '/../core/EmailAlertHelper.php';
                    EmailAlertHelper::sendSystemAlerts($alertas);
                } catch (Exception $e) {
                    error_log("Error enviando alertas por correo: " . $e->getMessage());
                }
            }

            // Datos para gráficos
            $chartData = $this->getChartData();

            $data = [
                'title' => 'Dashboard',
                'stats' => $stats,
                'lowStockProducts' => $lowStockProducts,
                'recentMovements' => $recentMovements,
                'alertas' => $pendingAlerts, // Alertas simples sin base de datos
                'chartData' => $chartData,
                'breadcrumb' => [
                    ['title' => 'Dashboard']
                ]
            ];

            $this->view('dashboard/index', $data);
        } catch (Exception $e) {
            Logger::error("Error en DashboardController::index - " . $e->getMessage());
            // Mostrar dashboard vacío pero sin error 500 destructivo
            $this->view('dashboard/index', [
                'title' => 'Dashboard',
                'stats' => [],
                'lowStockProducts' => [],
                'recentMovements' => [],
                'alertas' => [],
                'chartData' => [],
                'breadcrumb' => [
                    ['title' => 'Dashboard']
                ]
            ]);
        }
    }

    /**
     * Endpoint para refresh AJAX del dashboard
     */
    public function refresh()
    {
        try {
            if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || $_SERVER['HTTP_X_REQUESTED_WITH'] !== 'XMLHttpRequest') {
                throw new Exception('Acceso no permitido');
            }

            $stats = $this->getStats();

            echo json_encode([
                'success' => true,
                'stats' => $stats,
                'timestamp' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Error en dashboard: " . $e->getMessage());
            $this->setFlash('error', 'Error al cargar el dashboard');
            $this->view('dashboard/index', [
                'title' => 'Dashboard',
                'stats' => [],
                'lowStockProducts' => [],
                'recentMovements' => [],
                'alerts' => [],
                'chartData' => []
            ]);
        }
    }

    /**
     * Obtiene estadísticas generales del sistema
     */
    private function getStats()
    {
        try {
            $stats = [];

            // Total de productos
            $stats['total_productos'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos WHERE estado = 'activo'"
            )['total'] ?? 0;

            // Productos con stock bajo
            $stats['productos_stock_bajo'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos 
                 WHERE stock_actual <= stock_minimo AND estado = 'activo'"
            )['total'] ?? 0;

            // Valor total del inventario
            $inventoryValue = $this->db->selectOne(
                "SELECT SUM(stock_actual * precio_unitario) as total FROM productos WHERE estado = 'activo'"
            );
            $stats['valor_inventario'] = $inventoryValue['total'] ?? 0;

            // Movimientos del mes actual (leer desde registrosstock si existe)
            try {
                $res = $this->db->selectOne(
                    "SELECT COUNT(*) as total FROM registrosstock 
                     WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                     AND YEAR(fecha) = YEAR(CURRENT_DATE())"
                );
                $stats['movimientos_mes'] = $res['total'] ?? 0;
            } catch (Exception $e) {
                // En caso de fallo, devolver 0 sin tirar excepción
                $stats['movimientos_mes'] = 0;
            }

            // Movimientos de hoy (para mecánicos)
            try {
                $res = $this->db->selectOne(
                    "SELECT COUNT(*) as total FROM registrosstock 
                     WHERE DATE(fecha) = CURRENT_DATE()"
                );
                $stats['movimientos_hoy'] = $res['total'] ?? 0;
            } catch (Exception $e) {
                $stats['movimientos_hoy'] = 0;
            }

            // Stock bajo (para mecánicos y administradores)
            $stats['stock_bajo'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos 
                 WHERE stock_actual <= stock_minimo AND estado = 'activo'"
            )['total'] ?? 0;

            // Ventas del mes (calculado desde cotizaciones aprobadas)
            try {
                $res = $this->db->selectOne(
                    "SELECT SUM(total) as total FROM cotizaciones 
                     WHERE estado = 'aprobada' 
                     AND MONTH(fecha) = MONTH(CURRENT_DATE()) 
                     AND YEAR(fecha) = YEAR(CURRENT_DATE())"
                );
                $stats['ventas_mes'] = $res['total'] ?? 0;
            } catch (Exception $e) {
                $stats['ventas_mes'] = 0;
            }

            // Cotizaciones pendientes
            $stats['cotizaciones_pendientes'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM cotizaciones 
                 WHERE estado = 'pendiente'"
            )['total'] ?? 0;

            // Cotizaciones del mes
            $stats['cotizaciones_mes'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM cotizaciones 
                 WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                 AND YEAR(fecha) = YEAR(CURRENT_DATE())"
            )['total'] ?? 0;

            // Estadísticas de vehículos
            $stats['total_vehiculos'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM vehiculoscliente"
            )['total'] ?? 0;

            // Órdenes de trabajo activas
            $stats['ordenes_abiertas'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM ordenestrabajo WHERE estado = 'abierta'"
            )['total'] ?? 0;

            // Órdenes en proceso
            $stats['ordenes_proceso'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM ordenestrabajo WHERE estado = 'en_proceso'"
            )['total'] ?? 0;

            // Total de órdenes del mes
            $stats['ordenes_mes'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM ordenestrabajo 
                 WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                 AND YEAR(fecha) = YEAR(CURRENT_DATE())"
            )['total'] ?? 0;

            // Total de clientes
            $stats['total_clientes'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM clientes WHERE estado = 'activo'"
            )['total'] ?? 0;

            // Productos más movidos (top 5) usando registrosstock
            try {
                $stats['productos_top'] = $this->db->select(
                    "SELECT p.nombre, p.codigo_barras as codigo, SUM(rs.cantidad) as total_movimientos
                     FROM productos p
                     JOIN registrosstock rs ON p.id_producto = rs.id_producto
                     WHERE rs.fecha >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                     GROUP BY p.id_producto, p.nombre, p.codigo_barras
                     ORDER BY total_movimientos DESC
                     LIMIT 5"
                );
            } catch (Exception $e) {
                $stats['productos_top'] = [];
            }

            return $stats;
        } catch (Exception $e) {
            error_log("Error obteniendo estadísticas: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene productos con stock bajo
     */
    private function getLowStockProducts($limit = 10)
    {
        try {
            return $this->db->select(
                "SELECT id_producto as id, codigo_barras as codigo, nombre, 
                        stock_actual, stock_minimo, 
                        ROUND(((stock_actual / GREATEST(stock_minimo, 1)) * 100), 2) as porcentaje_stock
                 FROM productos 
                 WHERE stock_actual <= stock_minimo AND estado = 'activo'
                 ORDER BY porcentaje_stock ASC, stock_actual ASC
                 LIMIT ?",
                [$limit]
            );
        } catch (Exception $e) {
            error_log("Error obteniendo productos con stock bajo: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene los últimos movimientos de inventario
     */
    private function getRecentMovements($limit = 10)
    {
        try {
            // Usar la tabla registrosstock que ya existe
            return $this->db->select(
                "SELECT rs.*, p.nombre as producto_nombre, p.codigo_barras as producto_codigo,
                        u.nombre as usuario_nombre, rs.tipo as tipo_movimiento, rs.fecha as fecha_movimiento
                 FROM registrosstock rs
                 JOIN productos p ON rs.id_producto = p.id_producto
                 LEFT JOIN usuarios u ON rs.id_usuario = u.id_usuario
                 ORDER BY rs.fecha DESC, rs.id_registro DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (Exception $e) {
            error_log("Error obteniendo movimientos recientes: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene alertas del sistema usando el modelo Alerta
     */
    private function getAlerts()
    {
        try {
            require_once APP_PATH . '/models/Alerta.php';
            $alertaModel = new Alerta();

            // Obtener alertas pendientes del sistema
            $alertasPendientes = $alertaModel->getAlertasPendientes();

            // Convertir al formato que espera el dashboard
            $alerts = [];
            foreach ($alertasPendientes as $alerta) {
                $typeMap = [
                    'stock_bajo' => ['type' => 'warning', 'icon' => 'fas fa-exclamation-triangle'],
                    'sin_stock' => ['type' => 'danger', 'icon' => 'fas fa-times-circle'],
                    'proximo_vencer' => ['type' => 'info', 'icon' => 'fas fa-calendar-alt'],
                    'pocas_ventas' => ['type' => 'warning', 'icon' => 'fas fa-chart-line'],
                    'sistema' => ['type' => 'info', 'icon' => 'fas fa-cog'],
                    'critico' => ['type' => 'danger', 'icon' => 'fas fa-exclamation']
                ];

                $config = $typeMap[$alerta['tipo']] ?? ['type' => 'secondary', 'icon' => 'fas fa-bell'];

                $alerts[] = [
                    'type' => $config['type'],
                    'icon' => $config['icon'],
                    'title' => ucfirst(str_replace('_', ' ', $alerta['tipo'])),
                    'message' => $alerta['mensaje'],
                    'date' => $alerta['fecha'],
                    'id' => $alerta['id_alerta'],
                    'url' => '?page=alertas'
                ];
            }

            return $alerts;
        } catch (Exception $e) {
            Logger::error("Error al obtener alertas del dashboard: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Obtiene datos para gráficos
     */
    private function getChartData()
    {
        try {
            $chartData = [];

            // Movimientos por día usando registrosstock
            try {
                $movimientosDiarios = $this->db->select(
                    "SELECT DATE_FORMAT(fecha, '%H:%i') as fecha,
                            COUNT(*) as total_movimientos,
                            SUM(CASE WHEN tipo = 'entrada' THEN cantidad ELSE 0 END) as entradas,
                            SUM(CASE WHEN tipo = 'salida' THEN cantidad ELSE 0 END) as salidas
                     FROM registrosstock
                     WHERE fecha >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                     GROUP BY HOUR(fecha)
                     ORDER BY fecha"
                );
                $chartData['movimientos_diarios'] = $movimientosDiarios;
            } catch (Exception $e) {
                error_log("Error obteniendo movimientos diarios: " . $e->getMessage());
                $chartData['movimientos_diarios'] = [];
            }

            // Productos por categoría
            $productosPorCategoria = $this->db->select(
                "SELECT c.nombre as categoria, COUNT(p.id_producto) as total
                 FROM categorias c
                 LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
                 LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
                 GROUP BY c.id_categoria, c.nombre
                 ORDER BY total DESC"
            );

            $chartData['productos_por_categoria'] = $productosPorCategoria;

            // Stock por categoría
            $stockPorCategoria = $this->db->select(
                "SELECT c.nombre as categoria, 
                        SUM(p.stock_actual) as total_stock,
                        SUM(p.stock_actual * p.precio_unitario) as valor_stock
                 FROM categorias c
                 LEFT JOIN subcategorias s ON c.id_categoria = s.id_categoria
                 LEFT JOIN productos p ON s.id_subcategoria = p.id_subcategoria AND p.estado = 'activo'
                 GROUP BY c.id_categoria, c.nombre
                 HAVING total_stock > 0
                 ORDER BY valor_stock DESC"
            );

            $chartData['stock_por_categoria'] = $stockPorCategoria;

            return $chartData;
        } catch (Exception $e) {
            error_log("Error obteniendo datos de gráficos: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Enviar alerta automática por email cuando hay stock crítico
     */
    private function enviarAlertaAutomatica($productos)
    {
        try {
            // Configuración de email (puedes cambiar estos valores)
            $emailDestino = 'lulzsec2409@gmail.com'; // Tu email donde recibes las alertas
            $emailOrigen = 'lulzsec2409@gmail.com';  // Tu email de Gmail para enviar
            $passwordApp = '';  // Tu App Password de Gmail (déjalo vacío por seguridad)

            // Solo intentar envío si hay configuración
            if (empty($passwordApp)) {
                error_log("⚠️ Alerta automática detectada pero falta configurar App Password de Gmail");
                return;
            }

            // Usar la clase Mailer existente para evitar duplicación de código
            require_once __DIR__ . '/../core/Mailer.php';

            $html = $this->crearHTMLAlertaAutomatica($productos);
            $subject = '🚨 ALERTA AUTOMÁTICA: Stock Crítico Detectado - CruzMotorSAC';

            try {
                $result = Mailer::send($emailDestino, $subject, $html);
                if ($result) {
                    error_log("✅ Alerta automática enviada exitosamente a: $emailDestino");
                } else {
                    error_log("❌ Error enviando alerta automática");
                }
            } catch (Exception $e) {
                error_log("❌ Error en envío automático: " . $e->getMessage());
            }
        } catch (\Exception $e) {
            error_log("❌ Error general en envío automático: " . $e->getMessage());
        }
    }

    /**
     * Crear HTML para alerta automática
     */
    private function crearHTMLAlertaAutomatica($productos)
    {
        $fecha = date('d/m/Y H:i:s');
        $total = count($productos);

        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>Alerta Automática Stock Crítico</title>
            <style>
                body { font-family: Arial, sans-serif; margin: 0; padding: 0; background: #f4f4f4; }
                .container { max-width: 600px; margin: 20px auto; background: white; border-radius: 10px; overflow: hidden; box-shadow: 0 4px 15px rgba(0,0,0,0.2); }
                .header { background: linear-gradient(135deg, #dc3545, #c82333); color: white; padding: 30px 20px; text-align: center; }
                .content { padding: 30px 20px; }
                .producto { background: #f8d7da; border-left: 5px solid #dc3545; padding: 15px; margin: 15px 0; border-radius: 5px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; color: #666; font-size: 12px; }
                .urgente { background: #ff4757; color: white; padding: 15px; text-align: center; margin: 20px 0; border-radius: 8px; font-weight: bold; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h1>🚨 ALERTA AUTOMÁTICA CRÍTICA</h1>
                    <p>Sistema de Inventario CruzMotorSAC</p>
                    <p style="font-size: 20px; margin: 15px 0;"><strong>DETECCIÓN AUTOMÁTICA DE STOCK CRÍTICO</strong></p>
                </div>
                
                <div class="content">
                    <div class="urgente">
                        ⚠️ ATENCIÓN: Se detectaron automáticamente ' . $total . ' productos con stock CRÍTICO
                    </div>
                    
                    <p><strong>🕐 Fecha de detección:</strong> ' . $fecha . '</p>
                    <p><strong>🤖 Generado por:</strong> Sistema automático al acceder al dashboard</p>
                    <p><strong>📋 Productos que requieren atención inmediata:</strong></p>';

        foreach ($productos as $producto) {
            $porcentaje = round($producto['porcentaje_stock'], 1);

            $html .= '
                    <div class="producto">
                        <h3 style="margin: 0; color: #721c24;">🔴 ' . htmlspecialchars($producto['nombre']) . '</h3>
                        <p style="margin: 5px 0; color: #721c24;">
                            <strong>Código:</strong> ' . htmlspecialchars($producto['codigo'] ?? 'N/A') . '<br>
                            <strong>Stock Actual:</strong> <span style="font-size: 18px; font-weight: bold;">' . $producto['stock_actual'] . '</span> unidades<br>
                            <strong>Stock Mínimo:</strong> ' . $producto['stock_minimo'] . ' unidades<br>
                            <strong>Nivel Crítico:</strong> <span style="font-size: 16px; font-weight: bold;">' . $porcentaje . '% del mínimo</span>
                        </p>
                    </div>';
        }

        $html .= '
                    <div style="background: #d1ecf1; border: 1px solid #bee5eb; padding: 20px; border-radius: 8px; margin: 30px 0;">
                        <h3 style="color: #0c5460; margin-top: 0;">🔧 ACCIÓN REQUERIDA INMEDIATAMENTE:</h3>
                        <ul style="color: #0c5460; margin: 0;">
                            <li><strong>URGENTE:</strong> Contactar proveedores para pedido inmediato</li>
                            <li><strong>REVISAR:</strong> Pedidos pendientes que puedan estar en proceso</li>
                            <li><strong>VERIFICAR:</strong> Ventas recientes para proyectar demanda</li>
                            <li><strong>ACTUALIZAR:</strong> Stock mínimo si es necesario</li>
                        </ul>
                    </div>
                    
                    <div style="text-align: center; margin: 30px 0;">
                        <a href="http://localhost/CruzMotorSAC/public/index.php?page=inventario" 
                           style="display: inline-block; padding: 15px 30px; background: #dc3545; color: white; text-decoration: none; border-radius: 8px; font-weight: bold; margin: 5px;">
                           ⚡ VER INVENTARIO URGENTE
                        </a>
                    </div>
                </div>
                
                <div class="footer">
                    <p><strong>🤖 Alerta Automática del Sistema CruzMotorSAC</strong></p>
                    <p><strong>📧 Enviado a:</strong> lulzsec2409@gmail.com</p>
                    <p><strong>🕐 Fecha:</strong> ' . $fecha . '</p>
                    <hr style="border: 1px solid #eee; margin: 15px 0;">
                    <p>Este mensaje fue generado automáticamente al detectar productos con stock crítico.</p>
                    <p>Para configurar o desactivar estas alertas, contacta al administrador del sistema.</p>
                </div>
            </div>
        </body>
        </html>';

        return $html;
    }
}
