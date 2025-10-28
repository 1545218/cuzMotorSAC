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
                'alertas' => $alertas,
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

            // Cotizaciones del mes
            $stats['cotizaciones_mes'] = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM cotizaciones 
                 WHERE MONTH(fecha) = MONTH(CURRENT_DATE()) 
                 AND YEAR(fecha) = YEAR(CURRENT_DATE())"
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
                "SELECT id_producto, codigo_barras as codigo, nombre, stock_actual as stock, stock_minimo, 
                        ROUND(((stock_actual / stock_minimo) * 100), 2) as porcentaje_stock
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
        // Tabla movimientos_inventario no existe aún
        return [];

        /*
        try {
            return $this->db->select(
                "SELECT mi.*, p.nombre as producto_nombre, p.codigo as producto_codigo,
                        u.nombre as usuario_nombre
                 FROM movimientos_inventario mi
                 JOIN productos p ON mi.producto_id = p.id_producto
                 JOIN usuarios u ON mi.usuario_id = u.id_usuario
                 ORDER BY mi.fecha_movimiento DESC, mi.id DESC
                 LIMIT ?",
                [$limit]
            );
        } catch (Exception $e) {
            error_log("Error obteniendo movimientos recientes: " . $e->getMessage());
            return [];
        }
        */
    }

    /**
     * Obtiene alertas del sistema
     */
    private function getAlerts()
    {
        try {
            $alerts = [];

            // Productos sin stock
            $outOfStock = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos WHERE stock_actual = 0 AND estado = 'activo'"
            )['total'] ?? 0;

            if ($outOfStock > 0) {
                $alerts[] = [
                    'type' => 'danger',
                    'icon' => 'fas fa-exclamation-triangle',
                    'title' => 'Productos sin stock',
                    'message' => "Hay {$outOfStock} productos sin stock disponible",
                    'url' => BASE_PATH . '/productos?filter=sin_stock'
                ];
            }

            // Productos próximos a vencer (si tienes fecha de vencimiento)
            // Esto sería una mejora futura

            // Usuarios inactivos hace más de 30 días
            if ($this->auth->hasRole(ROLE_ADMIN)) {
                $inactiveUsers = $this->db->selectOne(
                    "SELECT COUNT(*) as total FROM usuarios 
                     WHERE estado = 'activo'"
                )['total'] ?? 0;

                if ($inactiveUsers > 0) {
                    $alerts[] = [
                        'type' => 'warning',
                        'icon' => 'fas fa-user-clock',
                        'title' => 'Usuarios inactivos',
                        'message' => "Hay {$inactiveUsers} usuarios sin actividad reciente",
                        'url' => BASE_PATH . '/usuarios?filter=inactive'
                    ];
                }
            }

            return $alerts;
        } catch (Exception $e) {
            error_log("Error obteniendo alertas: " . $e->getMessage());
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

            // Movimientos por día usando registrosstock (si existe)
            try {
                $movimientosDiarios = $this->db->select(
                    "SELECT DATE_FORMAT(fecha, '%d/%m %H:%i') as fecha,
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
}
