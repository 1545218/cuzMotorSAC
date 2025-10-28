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

        // Verificar autenticación
        Auth::requireAuth();
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
            } else {
                $this->generarReportePDF('cotizaciones', $cotizaciones, [
                    'fecha_inicio' => $fechaInicio,
                    'fecha_fin' => $fechaFin
                ]);
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
            } else {
                $this->generarReportePDF('bajo_stock', $bajo_stock);
            }
        } catch (Exception $e) {
            Logger::error("Error en ReporteController::bajo_stock - " . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al generar reporte de bajo stock']);
        }
    }

    /**
     * Generar PDF genérico
     */
    private function generarReportePDF($tipo, $datos, $params = [])
    {
        // Por ahora muestra un mensaje simple
        echo "<h1>Reporte PDF - " . ucfirst($tipo) . "</h1>";
        echo "<p>Funcionalidad PDF en desarrollo...</p>";
        echo "<a href='javascript:history.back()'>Volver</a>";
    }
}
