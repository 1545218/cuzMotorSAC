<?php

/**
 * Controlador de Reportes - Simplificado
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class ReporteController extends Controller
{
    private $cotizacionModel;
    private $productoModel;
    private $clienteModel;

    public function __construct()
    {
        parent::__construct();
        $this->cotizacionModel = new Cotizacion();
        $this->productoModel = new Producto();
        $this->clienteModel = new Cliente();

        // Cargar ExportUtils una sola vez
        require_once APP_PATH . '/core/ExportUtils.php';

        // Verificar autenticación
        Auth::requireAuth();

        // 🔥 CONFIGURAR TCPDF SOLO UNA VEZ
        $this->configurarTCPDF();
    }

    /**
     * 🔥 CONFIGURACIÓN SEGURA DE TCPDF
     * Previene constantes duplicadas y configuración conflictiva
     */
    private function configurarTCPDF()
    {
        // Solo definir constantes si no existen
        if (!defined('PDF_CREATOR')) {
            define('PDF_CREATOR', 'Cruz Motor S.A.C.');
        }
        if (!defined('PDF_AUTHOR')) {
            define('PDF_AUTHOR', 'Sistema de Inventario');
        }
        if (!defined('PDF_MARGIN_TOP')) {
            define('PDF_MARGIN_TOP', 15);
        }
        if (!defined('PDF_MARGIN_BOTTOM')) {
            define('PDF_MARGIN_BOTTOM', 15);
        }
        if (!defined('PDF_MARGIN_LEFT')) {
            define('PDF_MARGIN_LEFT', 15);
        }
        if (!defined('PDF_MARGIN_RIGHT')) {
            define('PDF_MARGIN_RIGHT', 15);
        }
        if (!defined('PDF_UNIT')) {
            define('PDF_UNIT', 'mm');
        }
        if (!defined('PDF_PAGE_ORIENTATION')) {
            define('PDF_PAGE_ORIENTATION', 'P');
        }
        if (!defined('PDF_PAGE_FORMAT')) {
            define('PDF_PAGE_FORMAT', 'A4');
        }
    }

    /**
     * Página principal de reportes
     */
    public function index()
    {
        $this->view('reportes/index', [
            'title' => 'Reportes del Sistema'
        ]);
    }

    /**
     * Reporte de ventas (basado en cotizaciones aprobadas)
     */
    public function ventas()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $formato = $_GET['formato'] ?? 'html';

            // Obtener cotizaciones aprobadas en el rango de fechas
            $ventas = $this->cotizacionModel->getAll();
            $ventas = array_filter($ventas, function ($cotizacion) use ($fechaInicio, $fechaFin) {
                $fecha = $cotizacion['fecha'] ?? '';
                return $cotizacion['estado'] === 'aprobada' &&
                    $fecha >= $fechaInicio &&
                    $fecha <= $fechaFin;
            });

            if ($formato === 'html') {
                $this->view('reportes/ventas', [
                    'title' => 'Reporte de Ventas',
                    'ventas' => $ventas,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);
            } elseif ($formato === 'excel') {
                // Nueva funcionalidad Excel mejorada - NO afecta el PDF existente
                ExportUtils::generarVentasExcel($ventas, [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);
                exit; // Terminar después de la descarga
            } else {
                $this->generarReportePDF('ventas', $ventas, [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::ventas - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de ventas']);
        }
    }

    /**
     * Reporte de inventario
     */
    public function inventario()
    {
        try {
            // Usar el método que existe en el modelo
            $productos = $this->productoModel->getActive();
            $formato = $_GET['formato'] ?? 'html';

            if ($formato === 'html') {
                $this->view('reportes/inventario', [
                    'title' => 'Reporte de Inventario',
                    'productos' => $productos
                ]);
            } elseif ($formato === 'excel') {
                // Nueva funcionalidad Excel mejorada - NO afecta el PDF existente
                ExportUtils::generarInventarioExcel($productos, [
                    'titulo' => 'Reporte de Inventario - Cruz Motor S.A.C.',
                    'estadisticas' => true
                ]);
                exit; // Terminar después de la descarga
            } else {
                $this->generarReportePDF('inventario', $productos);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::inventario - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de inventario']);
        }
    }

    /**
     * Reporte de clientes
     */
    public function clientes()
    {
        try {
            // Usar el método que existe en el modelo
            $clientes = $this->clienteModel->getActive();
            $formato = $_GET['formato'] ?? 'html';

            if ($formato === 'html') {
                $this->view('reportes/clientes', [
                    'title' => 'Reporte de Clientes',
                    'clientes' => $clientes
                ]);
            } elseif ($formato === 'excel') {
                // Generar Excel básico para clientes
                $this->generarClientesExcel($clientes);
                exit;
            } else {
                $this->generarReportePDF('clientes', $clientes);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::clientes - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de clientes']);
        }
    }

    /**
     * Reporte de cotizaciones
     */
    public function cotizaciones()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $formato = $_GET['formato'] ?? 'html';

            $cotizaciones = $this->cotizacionModel->getAll();
            $cotizaciones = array_filter($cotizaciones, function ($cotizacion) use ($fechaInicio, $fechaFin) {
                $fecha = $cotizacion['fecha'] ?? '';
                return $fecha >= $fechaInicio && $fecha <= $fechaFin;
            });

            if ($formato === 'html') {
                $this->view('reportes/cotizaciones', [
                    'title' => 'Reporte de Cotizaciones',
                    'cotizaciones' => $cotizaciones,
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);
            } elseif ($formato === 'excel') {
                // Generar Excel básico para cotizaciones
                $this->generarCotizacionesExcel($cotizaciones, $fechaInicio, $fechaFin);
                exit;
            } else {
                $this->generarReportePDF('cotizaciones', $cotizaciones);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::cotizaciones - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de cotizaciones']);
        }
    }

    /**
     * Reporte de productos bajo stock
     */
    public function bajo_stock()
    {
        try {
            // Usar el método que existe en el modelo
            $productos = $this->productoModel->getActive();
            $bajo_stock = array_filter($productos, function ($producto) {
                return ($producto['stock'] ?? 0) <= ($producto['stock_minimo'] ?? 5);
            });

            $formato = $_GET['formato'] ?? 'html';

            if ($formato === 'html') {
                $this->view('reportes/bajo_stock', [
                    'title' => 'Productos Bajo Stock',
                    'productos' => $bajo_stock
                ]);
            } elseif ($formato === 'excel') {
                // Nueva funcionalidad Excel mejorada con alertas críticas
                ExportUtils::generarStockBajoExcel($bajo_stock);
                exit; // Terminar después de la descarga
            } else {
                $this->generarReportePDF('bajo_stock', $bajo_stock);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::bajo_stock - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de bajo stock']);
        }
    }

    /**
     * Genera Excel básico para clientes
     */
    private function generarClientesExcel($clientes)
    {
        $fecha = date('Y-m-d_H-i-s');
        $filename = "clientes_cruz_motor_{$fecha}.xls";

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM para UTF-8

        echo '<table border="1">';
        echo '<tr style="background-color: #17a2b8; color: white;">';
        echo '<th>Nombre</th><th>Email</th><th>Teléfono</th><th>Estado</th>';
        echo '</tr>';

        foreach ($clientes as $cliente) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($cliente['nombre'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($cliente['email'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($cliente['telefono'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($cliente['estado'] ?? '') . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Genera Excel básico para cotizaciones
     */
    private function generarCotizacionesExcel($cotizaciones, $fechaInicio, $fechaFin)
    {
        $fecha = date('Y-m-d_H-i-s');
        $filename = "cotizaciones_cruz_motor_{$fecha}.xls";

        header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: max-age=0');

        echo "\xEF\xBB\xBF"; // BOM para UTF-8

        echo '<table border="1">';
        echo '<tr style="background-color: #ffc107; color: black;">';
        echo '<th>Fecha</th><th>Cliente</th><th>Estado</th><th>Total</th>';
        echo '</tr>';

        foreach ($cotizaciones as $cotizacion) {
            echo '<tr>';
            echo '<td>' . htmlspecialchars($cotizacion['fecha'] ?? '') . '</td>';
            echo '<td>' . htmlspecialchars($cotizacion['cliente_nombre'] ?? 'N/A') . '</td>';
            echo '<td>' . htmlspecialchars($cotizacion['estado'] ?? '') . '</td>';
            echo '<td>S/ ' . number_format((float)($cotizacion['total'] ?? 0), 2) . '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

    /**
     * Reporte de Stock de Inventario
     */
    public function stock()
    {
        try {
            $fechaReporte = $_GET['fecha'] ?? date('Y-m-d');
            $formato = $_GET['formato'] ?? 'html';
            $soloStockBajo = isset($_GET['solo_stock_bajo']) ? $_GET['solo_stock_bajo'] : false;

            // Obtener productos con stock actual
            $productos = $this->productoModel->getProductsWithDetails();

            // Filtrar solo productos con stock bajo si se solicita
            if ($soloStockBajo) {
                $productos = array_filter($productos, function ($producto) {
                    return ($producto['stock_actual'] ?? 0) <= ($producto['stock_minimo'] ?? 0);
                });
            }

            // Calcular estadísticas
            $totalProductos = count($productos);
            $stockBajo = count(array_filter($productos, function ($producto) {
                return ($producto['stock_actual'] ?? 0) <= ($producto['stock_minimo'] ?? 0);
            }));
            $valorTotal = array_sum(array_map(function ($producto) {
                return ($producto['stock_actual'] ?? 0) * ($producto['precio_unitario'] ?? 0);
            }, $productos));

            $estadisticas = [
                'total_productos' => $totalProductos,
                'productos_stock_bajo' => $stockBajo,
                'valor_inventario' => $valorTotal,
                'fecha_reporte' => $fechaReporte
            ];

            if ($formato === 'pdf') {
                $this->generarReportePDF('stock', $productos, $estadisticas);
                return;
            }

            $this->view('reportes/stock', [
                'title' => 'Reporte de Stock de Inventario',
                'productos' => $productos,
                'estadisticas' => $estadisticas,
                'soloStockBajo' => $soloStockBajo
            ]);
        } catch (Exception $e) {
            Logger::error('Error en reporte de stock: ' . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de stock']);
        }
    }

    /**
     * Reporte de Movimientos de Inventario
     */
    public function movimientos()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $tipoMovimiento = $_GET['tipo'] ?? 'todos'; // entrada, salida, ajuste, todos
            $formato = $_GET['formato'] ?? 'html';

            // Obtener movimientos del período
            $inventario = new Inventario();
            $movimientos = $inventario->getMovimientos($fechaInicio, $fechaFin);

            // Filtrar por tipo si se especifica
            if ($tipoMovimiento !== 'todos') {
                $movimientos = array_filter($movimientos, function ($mov) use ($tipoMovimiento) {
                    return ($mov['tipo'] ?? '') === $tipoMovimiento;
                });
            }

            // Calcular estadísticas
            $entradas = array_filter($movimientos, function ($mov) {
                return ($mov['tipo'] ?? '') === 'entrada';
            });
            $salidas = array_filter($movimientos, function ($mov) {
                return ($mov['tipo'] ?? '') === 'salida';
            });
            $ajustes = array_filter($movimientos, function ($mov) {
                return ($mov['tipo'] ?? '') === 'ajuste';
            });

            $estadisticas = [
                'total_movimientos' => count($movimientos),
                'total_entradas' => count($entradas),
                'total_salidas' => count($salidas),
                'total_ajustes' => count($ajustes),
                'cantidad_entradas' => array_sum(array_column($entradas, 'cantidad')),
                'cantidad_salidas' => array_sum(array_column($salidas, 'cantidad')),
                'periodo' => "$fechaInicio a $fechaFin"
            ];

            if ($formato === 'pdf') {
                $this->generarReportePDF('movimientos', $movimientos, $estadisticas);
                return;
            }

            $this->view('reportes/movimientos', [
                'title' => 'Reporte de Movimientos de Inventario',
                'movimientos' => $movimientos,
                'estadisticas' => $estadisticas,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'tipoMovimiento' => $tipoMovimiento
            ]);
        } catch (Exception $e) {
            Logger::error('Error en reporte de movimientos: ' . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de movimientos']);
        }
    }

    /**
     * Reporte de Consumo de Productos
     */
    public function consumo()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');
            $limite = (int)($_GET['limite'] ?? 20);
            $formato = $_GET['formato'] ?? 'html';

            // Obtener productos más consumidos (salidas)
            $inventario = new Inventario();
            $movimientos = $inventario->getMovimientos($fechaInicio, $fechaFin);

            // Filtrar solo salidas
            $salidas = array_filter($movimientos, function ($mov) {
                return ($mov['tipo'] ?? '') === 'salida';
            });

            // Agrupar por producto y sumar cantidades
            $consumoPorProducto = [];
            foreach ($salidas as $salida) {
                $idProducto = $salida['id_producto'] ?? 0;
                $cantidad = $salida['cantidad'] ?? 0;

                if (!isset($consumoPorProducto[$idProducto])) {
                    $consumoPorProducto[$idProducto] = [
                        'id_producto' => $idProducto,
                        'nombre' => $salida['producto_nombre'] ?? 'Producto sin nombre',
                        'codigo' => $salida['codigo_barras'] ?? '',
                        'cantidad_total' => 0,
                        'veces_consumido' => 0,
                        'valor_total' => 0
                    ];
                }

                $consumoPorProducto[$idProducto]['cantidad_total'] += $cantidad;
                $consumoPorProducto[$idProducto]['veces_consumido']++;
                $consumoPorProducto[$idProducto]['valor_total'] += $cantidad * ($salida['precio_unitario'] ?? 0);
            }

            // Ordenar por cantidad total descendente y limitar
            usort($consumoPorProducto, function ($a, $b) {
                return $b['cantidad_total'] - $a['cantidad_total'];
            });

            $consumoPorProducto = array_slice($consumoPorProducto, 0, $limite);

            // Calcular estadísticas generales
            $estadisticas = [
                'total_productos_consumidos' => count($consumoPorProducto),
                'cantidad_total_salidas' => array_sum(array_column($consumoPorProducto, 'cantidad_total')),
                'valor_total_consumido' => array_sum(array_column($consumoPorProducto, 'valor_total')),
                'periodo' => "$fechaInicio a $fechaFin",
                'limite' => $limite
            ];

            if ($formato === 'pdf') {
                $this->generarReportePDF('consumo', $consumoPorProducto, $estadisticas);
                return;
            }

            $this->view('reportes/consumo', [
                'title' => 'Reporte de Consumo de Productos',
                'productos' => $consumoPorProducto,
                'estadisticas' => $estadisticas,
                'fechaInicio' => $fechaInicio,
                'fechaFin' => $fechaFin,
                'limite' => $limite
            ]);
        } catch (Exception $e) {
            Logger::error('Error en reporte de consumo: ' . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de consumo']);
        }
    }

    /**
     * Generar PDF genérico
     */
    private function generarReportePDF($tipo, $datos, $params = [])
    {
        try {
            // 🔥 LIMPIAR BUFFER DE SALIDA ANTES DE PDF
            if (ob_get_length()) {
                ob_end_clean();
            }

            // 🔥 PREVENIR SALIDA ADICIONAL
            ob_start();

            // Incluir TCPDF solo una vez
            if (!class_exists('TCPDF')) {
                $tcpdfPath = dirname(dirname(__DIR__)) . '/libs/tcpdf/tcpdf.php';
                if (!file_exists($tcpdfPath)) {
                    $tcpdfPath = (defined('ROOT_PATH') ? ROOT_PATH : dirname(dirname(__DIR__))) . '/libs/tcpdf/tcpdf.php';
                }
                require_once $tcpdfPath;
            }

            // Crear instancia de TCPDF con configuración segura
            $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Configurar información del documento usando constantes seguras
            $pdf->SetCreator(PDF_CREATOR);
            $pdf->SetAuthor(PDF_AUTHOR);
            $pdf->SetTitle('Reporte de ' . ucfirst($tipo));
            $pdf->SetSubject('Reporte generado el ' . date('Y-m-d H:i:s'));

            // Configurar header y footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Configurar márgenes usando constantes seguras
            $pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
            $pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

            // Agregar página
            $pdf->AddPage();

            // Generar contenido según el tipo
            $html = $this->generarHTMLParaPDF($tipo, $datos, $params);

            // Escribir HTML al PDF
            $pdf->writeHTML($html, true, false, true, false, '');

            // Nombre del archivo
            $nombreArchivo = 'Reporte_' . ucfirst($tipo) . '_' . date('Y-m-d_H-i-s') . '.pdf';

            // 🔥 LIMPIAR BUFFER ANTES DE ENVIAR PDF
            if (ob_get_length()) {
                ob_end_clean();
            }

            // Descargar el PDF
            $pdf->Output($nombreArchivo, 'D');

            // 🔥 TERMINAR EJECUCIÓN PARA EVITAR OUTPUT ADICIONAL
            exit();
        } catch (Exception $e) {
            // Limpiar buffer en caso de error también
            if (ob_get_length()) {
                ob_end_clean();
            }
            Logger::error('Error generando PDF: ' . $e->getMessage());
            header('Content-Type: text/html; charset=utf-8');
            echo "<h1>Error al generar PDF</h1>";
            echo "<p>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
            echo "<a href='javascript:history.back()'>Volver</a>";
            exit();
        }
    }

    /**
     * Generar HTML específico para cada tipo de reporte
     */
    private function generarHTMLParaPDF($tipo, $datos, $params = [])
    {
        $html = '<style>
            body { font-family: Arial, sans-serif; font-size: 10px; }
            .header { text-align: center; margin-bottom: 20px; }
            .title { font-size: 18px; font-weight: bold; color: #333; }
            .subtitle { font-size: 12px; color: #666; margin-top: 5px; }
            .info { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
            .stats { display: flex; justify-content: space-around; margin: 15px 0; }
            .stat-item { text-align: center; padding: 10px; }
            .stat-value { font-size: 16px; font-weight: bold; color: #2196F3; }
            .stat-label { font-size: 10px; color: #666; }
            table { width: 100%; border-collapse: collapse; margin-top: 15px; }
            th { background: #2196F3; color: white; padding: 8px; font-size: 9px; }
            td { padding: 6px; border-bottom: 1px solid #ddd; font-size: 8px; }
            .text-center { text-align: center; }
            .text-right { text-align: right; }
            .text-danger { color: #dc3545; }
            .text-success { color: #28a745; }
            .text-warning { color: #ffc107; }
        </style>';

        $html .= '<div class="header">
                    <div class="title">CRUZ MOTOR S.A.C.</div>
                    <div class="subtitle">Reporte de ' . ucfirst($tipo) . '</div>
                    <div class="subtitle">Generado el ' . date('d/m/Y H:i:s') . '</div>
                  </div>';

        switch ($tipo) {
            case 'stock':
                $html .= $this->generarHTMLStock($datos, $params);
                break;
            case 'movimientos':
                $html .= $this->generarHTMLMovimientos($datos, $params);
                break;
            case 'consumo':
                $html .= $this->generarHTMLConsumo($datos, $params);
                break;
            case 'ventas':
                $html .= $this->generarHTMLVentas($datos, $params);
                break;
            default:
                $html .= '<p>Tipo de reporte no reconocido.</p>';
        }

        return $html;
    }

    /**
     * HTML para reporte de stock
     */
    private function generarHTMLStock($productos, $estadisticas)
    {
        $html = '<div class="info">
                    <strong>Período:</strong> ' . ($estadisticas['fecha_reporte'] ?? date('Y-m-d')) . '<br>
                    <strong>Total productos:</strong> ' . ($estadisticas['total_productos'] ?? 0) . '<br>
                    <strong>Productos con stock bajo:</strong> ' . ($estadisticas['productos_stock_bajo'] ?? 0) . '<br>
                    <strong>Valor total del inventario:</strong> S/. ' . number_format($estadisticas['valor_inventario'] ?? 0, 2) . '
                 </div>';

        if (empty($productos)) {
            $html .= '<p>No hay productos para mostrar.</p>';
            return $html;
        }

        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Código</th>
                            <th>Producto</th>
                            <th>Categoría</th>
                            <th>Stock Actual</th>
                            <th>Stock Mínimo</th>
                            <th>Estado</th>
                            <th>Precio Unit.</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($productos as $producto) {
            $stockActual = $producto['stock_actual'] ?? 0;
            $stockMinimo = $producto['stock_minimo'] ?? 0;
            $precioUnit = $producto['precio_unitario'] ?? 0;
            $valorTotal = $stockActual * $precioUnit;
            $esStockBajo = $stockActual <= $stockMinimo;

            $html .= '<tr>
                        <td>' . htmlspecialchars($producto['codigo_barras'] ?? '') . '</td>
                        <td>' . htmlspecialchars($producto['nombre'] ?? '') . '</td>
                        <td>' . htmlspecialchars($producto['categoria'] ?? '') . '</td>
                        <td class="text-center ' . ($esStockBajo ? 'text-danger' : '') . '">' . number_format($stockActual) . '</td>
                        <td class="text-center">' . number_format($stockMinimo) . '</td>
                        <td class="text-center ' . ($esStockBajo ? 'text-danger' : 'text-success') . '">' .
                ($esStockBajo ? 'Stock Bajo' : 'Normal') . '</td>
                        <td class="text-right">S/. ' . number_format($precioUnit, 2) . '</td>
                        <td class="text-right">S/. ' . number_format($valorTotal, 2) . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * HTML para reporte de movimientos
     */
    private function generarHTMLMovimientos($movimientos, $estadisticas)
    {
        $html = '<div class="info">
                    <strong>Período:</strong> ' . ($estadisticas['periodo'] ?? '') . '<br>
                    <strong>Total movimientos:</strong> ' . ($estadisticas['total_movimientos'] ?? 0) . '<br>
                    <strong>Entradas:</strong> ' . ($estadisticas['total_entradas'] ?? 0) . ' 
                    (Cantidad: ' . number_format($estadisticas['cantidad_entradas'] ?? 0) . ')<br>
                    <strong>Salidas:</strong> ' . ($estadisticas['total_salidas'] ?? 0) . ' 
                    (Cantidad: ' . number_format($estadisticas['cantidad_salidas'] ?? 0) . ')
                 </div>';

        if (empty($movimientos)) {
            $html .= '<p>No hay movimientos para mostrar en este período.</p>';
            return $html;
        }

        $html .= '<table>
                    <thead>
                        <tr>
                            <th>Fecha</th>
                            <th>Producto</th>
                            <th>Tipo</th>
                            <th>Cantidad</th>
                            <th>Stock Anterior</th>
                            <th>Stock Nuevo</th>
                            <th>Motivo</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($movimientos as $movimiento) {
            $tipo = $movimiento['tipo'] ?? '';
            $cantidad = $movimiento['cantidad'] ?? 0;
            $colorClass = $tipo === 'entrada' ? 'text-success' : ($tipo === 'salida' ? 'text-danger' : 'text-warning');

            $html .= '<tr>
                        <td>' . date('d/m/Y H:i', strtotime($movimiento['fecha_movimiento'] ?? '')) . '</td>
                        <td>' . htmlspecialchars($movimiento['producto_nombre'] ?? '') . '</td>
                        <td class="text-center ' . $colorClass . '">' . ucfirst($tipo) . '</td>
                        <td class="text-center ' . $colorClass . '">' .
                ($tipo === 'entrada' ? '+' : ($tipo === 'salida' ? '-' : '±')) . number_format(abs($cantidad)) . '</td>
                        <td class="text-center">' . number_format($movimiento['stock_anterior'] ?? 0) . '</td>
                        <td class="text-center">' . number_format($movimiento['stock_nuevo'] ?? 0) . '</td>
                        <td>' . htmlspecialchars($movimiento['motivo'] ?? '') . '</td>
                      </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * HTML para reporte de consumo
     */
    private function generarHTMLConsumo($productos, $estadisticas)
    {
        $html = '<div class="info">
                    <strong>Período:</strong> ' . ($estadisticas['periodo'] ?? '') . '<br>
                    <strong>Productos analizados:</strong> ' . ($estadisticas['total_productos_consumidos'] ?? 0) . '<br>
                    <strong>Total unidades consumidas:</strong> ' . number_format($estadisticas['cantidad_total_salidas'] ?? 0) . '<br>
                    <strong>Valor total consumido:</strong> S/. ' . number_format($estadisticas['valor_total_consumido'] ?? 0, 2) . '
                 </div>';

        if (empty($productos)) {
            $html .= '<p>No hay datos de consumo para mostrar en este período.</p>';
            return $html;
        }

        $html .= '<table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Código</th>
                            <th>Cantidad Total</th>
                            <th>Veces Usado</th>
                            <th>Promedio por Uso</th>
                            <th>Valor Total</th>
                        </tr>
                    </thead>
                    <tbody>';

        foreach ($productos as $index => $producto) {
            $ranking = $index + 1;
            $cantidadTotal = $producto['cantidad_total'] ?? 0;
            $vecesUsado = $producto['veces_consumido'] ?? 1;
            $promedioPorUso = $vecesUsado > 0 ? $cantidadTotal / $vecesUsado : 0;
            $valorTotal = $producto['valor_total'] ?? 0;

            $html .= '<tr>
                        <td class="text-center"><strong>' . $ranking . '</strong></td>
                        <td>' . htmlspecialchars($producto['nombre'] ?? '') . '</td>
                        <td>' . htmlspecialchars($producto['codigo'] ?? '') . '</td>
                        <td class="text-center text-danger"><strong>' . number_format($cantidadTotal) . '</strong></td>
                        <td class="text-center">' . number_format($vecesUsado) . '</td>
                        <td class="text-center">' . number_format($promedioPorUso, 1) . '</td>
                        <td class="text-right"><strong>S/. ' . number_format($valorTotal, 2) . '</strong></td>
                      </tr>';
        }

        $html .= '</tbody></table>';
        return $html;
    }

    /**
     * HTML para reporte de ventas (compatibilidad)
     */
    private function generarHTMLVentas($datos, $params)
    {
        return '<div class="info">
                    <p>Reporte de ventas - Funcionalidad en desarrollo.</p>
                </div>';
    }

    // =============================
    // REPORTES AUTOMÁTICOS AVANZADOS - DÍA 8
    // =============================

    /**
     * Dashboard avanzado de reportes
     */
    public function dashboard()
    {
        try {
            // Estadísticas generales
            $stats = $this->getEstadisticasGenerales();

            // Datos para gráficos
            $datosGraficos = $this->getDatosGraficos();

            // Alertas de stock
            $alertasStock = $this->getAlertasStock();

            // Productos más vendidos (simulado)
            $productosMasVendidos = $this->getProductosMasVendidos();

            renderView('reportes/dashboard', [
                'title' => 'Dashboard de Reportes',
                'estadisticas' => $stats,
                'graficos' => $datosGraficos,
                'alertas_stock' => $alertasStock,
                'productos_mas_vendidos' => $productosMasVendidos
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ReporteController::dashboard', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al cargar dashboard de reportes';
            header('Location: ?page=reportes');
            exit;
        }
    }

    /**
     * Reporte automático de movimientos de inventario
     */
    public function movimientosInventario()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01'); // Primer día del mes
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d'); // Hoy

            // Obtener movimientos usando los nuevos modelos
            $registroStockModel = new RegistroStock();
            $movimientos = $registroStockModel->getMovimientosPorFecha($fechaInicio, $fechaFin, 100);

            // Estadísticas de movimientos
            $estadisticas = $registroStockModel->getEstadisticasMovimientos();

            renderView('reportes/movimientos_inventario', [
                'title' => 'Reporte de Movimientos de Inventario',
                'movimientos' => $movimientos,
                'estadisticas' => $estadisticas,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ReporteController::movimientosInventario', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al generar reporte de movimientos';
            header('Location: ?page=reportes');
            exit;
        }
    }

    /**
     * Reporte automático de consumo por período
     */
    public function consumoPorPeriodo()
    {
        try {
            $fechaInicio = $_GET['fecha_inicio'] ?? date('Y-m-01');
            $fechaFin = $_GET['fecha_fin'] ?? date('Y-m-d');

            $reporteConsumoModel = new ReporteConsumo();
            $consumos = $reporteConsumoModel->generarReporteConsumo($fechaInicio, $fechaFin);

            renderView('reportes/consumo_periodo', [
                'title' => 'Reporte de Consumo por Período',
                'consumos' => $consumos,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ReporteController::consumoPorPeriodo', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al generar reporte de consumo';
            header('Location: ?page=reportes');
            exit;
        }
    }

    /**
     * Reporte automático de estado de stock
     */
    public function estadoStock()
    {
        try {
            $reporteStockModel = new ReporteStock();

            // Stock actual de todos los productos
            $stockCompleto = $reporteStockModel->generarReporteStockActual();

            // Productos con stock crítico
            $stockCritico = $reporteStockModel->getStockCritico();

            renderView('reportes/estado_stock', [
                'title' => 'Reporte de Estado de Stock',
                'stock_completo' => $stockCompleto,
                'stock_critico' => $stockCritico
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ReporteController::estadoStock', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al generar reporte de stock';
            header('Location: ?page=reportes');
            exit;
        }
    }

    /**
     * Programar reportes automáticos
     */
    public function programarReportes()
    {
        try {
            // Solo administradores pueden programar reportes
            Auth::requireRole(['administrador']);

            if ($_SERVER['REQUEST_METHOD'] === 'POST') {
                $this->procesarProgramacionReporte();
                return;
            }

            renderView('reportes/programar', [
                'title' => 'Programar Reportes Automáticos'
            ]);
        } catch (Exception $e) {
            Logger::error('Error en ReporteController::programarReportes', [
                'error' => $e->getMessage()
            ]);

            $_SESSION['error'] = 'Error al acceder a programación de reportes';
            header('Location: ?page=reportes');
            exit;
        }
    }

    /**
     * Obtener estadísticas generales para dashboard
     */
    private function getEstadisticasGenerales()
    {
        try {
            $stats = [];

            // Total de productos
            $stats['total_productos'] = $this->productoModel->count('estado = ?', ['activo']);

            // Stock total valorizado (aproximado)
            $db = Database::getInstance();
            $valorStock = $db->select("
                SELECT SUM(stock_actual * precio_unitario) as valor_total 
                FROM productos 
                WHERE estado = 'activo'
            ");
            $stats['valor_stock'] = $valorStock[0]['valor_total'] ?? 0;

            // Productos con stock bajo
            $stockBajo = $db->select("
                SELECT COUNT(*) as count 
                FROM productos 
                WHERE stock_actual <= stock_minimo AND estado = 'activo'
            ");
            $stats['productos_stock_bajo'] = $stockBajo[0]['count'] ?? 0;

            // Movimientos hoy
            $movimientosHoy = $db->select("
                SELECT COUNT(*) as count 
                FROM ajustesinventario 
                WHERE DATE(fecha_creacion) = CURDATE()
            ");
            $stats['movimientos_hoy'] = $movimientosHoy[0]['count'] ?? 0;

            return $stats;
        } catch (Exception $e) {
            Logger::error('Error al obtener estadísticas generales', [
                'error' => $e->getMessage()
            ]);
            return [
                'total_productos' => 0,
                'valor_stock' => 0,
                'productos_stock_bajo' => 0,
                'movimientos_hoy' => 0
            ];
        }
    }

    /**
     * Obtener datos para gráficos
     */
    private function getDatosGraficos()
    {
        try {
            // Movimientos por tipo en los últimos 7 días
            $db = Database::getInstance();
            $movimientosSemana = $db->select("
                SELECT 
                    tipo,
                    COUNT(*) as cantidad,
                    DATE(fecha_creacion) as fecha
                FROM ajustesinventario 
                WHERE fecha_creacion >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                GROUP BY tipo, DATE(fecha_creacion)
                ORDER BY fecha ASC
            ");

            return [
                'movimientos_semana' => $movimientosSemana
            ];
        } catch (Exception $e) {
            Logger::error('Error al obtener datos para gráficos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener alertas de stock
     */
    private function getAlertasStock()
    {
        try {
            $db = Database::getInstance();
            return $db->select("
                SELECT 
                    nombre,
                    codigo_barras,
                    stock_actual,
                    stock_minimo,
                    CASE 
                        WHEN stock_actual <= 0 THEN 'Sin Stock'
                        WHEN stock_actual <= stock_minimo THEN 'Stock Crítico'
                        ELSE 'Normal'
                    END as estado
                FROM productos 
                WHERE stock_actual <= stock_minimo 
                AND estado = 'activo'
                ORDER BY stock_actual ASC
                LIMIT 10
            ");
        } catch (Exception $e) {
            Logger::error('Error al obtener alertas de stock', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Obtener productos más vendidos (simulado por ahora)
     */
    private function getProductosMasVendidos()
    {
        try {
            // Por ahora usamos ajustes de salida como proxy de ventas
            $db = Database::getInstance();
            return $db->select("
                SELECT 
                    p.nombre,
                    p.codigo_barras,
                    COUNT(a.id_ajuste) as total_movimientos,
                    SUM(a.cantidad) as cantidad_total
                FROM productos p
                LEFT JOIN ajustesinventario a ON p.id_producto = a.id_producto 
                    AND a.tipo = 'disminucion'
                    AND a.fecha >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                WHERE p.estado = 'activo'
                GROUP BY p.id_producto, p.nombre, p.codigo_barras
                HAVING total_movimientos > 0
                ORDER BY cantidad_total DESC
                LIMIT 10
            ");
        } catch (Exception $e) {
            Logger::error('Error al obtener productos más vendidos', [
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Procesar programación de reportes automáticos
     */
    private function procesarProgramacionReporte()
    {
        try {
            $tipo = $_POST['tipo_reporte'] ?? '';
            $frecuencia = $_POST['frecuencia'] ?? '';
            $email = $_POST['email_destino'] ?? '';

            // Validar datos
            if (empty($tipo) || empty($frecuencia) || empty($email)) {
                throw new Exception('Todos los campos son obligatorios');
            }

            // Aquí se implementaría la lógica para guardar la programación
            // Por ahora solo simulamos el proceso

            $_SESSION['success'] = 'Reporte programado exitosamente';
            header('Location: ?page=reportes&action=programar');
            exit;
        } catch (Exception $e) {
            Logger::error('Error al programar reporte', [
                'error' => $e->getMessage(),
                'data' => $_POST
            ]);

            $_SESSION['error'] = $e->getMessage();
            header('Location: ?page=reportes&action=programar');
            exit;
        }
    }
}
