<?php

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../core/Logger.php';

/**
 * Generador de PDF para reportes
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class ReportePDF
{
    private $tcpdf;
    private $empresa;

    public function __construct()
    {
        // Configuración de la empresa
        $this->empresa = [
            'nombre' => 'CRUZ MOTOR S.A.C.',
            'ruc' => '20123456789',
            'direccion' => 'Av. Principal 123, Lima, Perú',
            'telefono' => '(01) 123-4567',
            'email' => 'reportes@cruzmotor.com',
            'web' => 'www.cruzmotor.com'
        ];

        // Configurar TCPDF
        $this->tcpdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
        $this->configurarPDF();
    }

    private function configurarPDF()
    {
        // Configuración del documento
        $this->tcpdf->SetCreator('CruzMotor SAC');
        $this->tcpdf->SetAuthor('Sistema de Inventario');
        $this->tcpdf->SetTitle('Reporte');
        $this->tcpdf->SetSubject('Reporte del sistema');
        $this->tcpdf->SetKeywords('reporte, ventas, inventario, cruzmotor');

        // Configurar márgenes
        $this->tcpdf->SetMargins(15, 30, 15);
        $this->tcpdf->SetHeaderMargin(5);
        $this->tcpdf->SetFooterMargin(10);

        // Auto page breaks
        $this->tcpdf->SetAutoPageBreak(TRUE, 25);

        // Configurar fuentes
        $this->tcpdf->setFontSubsetting(true);
        $this->tcpdf->SetFont('helvetica', '', 10);
    }

    /**
     * Genera reporte de ventas en PDF
     */
    public function generarReporteVentas($datos)
    {
        try {
            $this->tcpdf->AddPage();

            // Encabezado del reporte
            $this->agregarEncabezadoEmpresa();
            $this->agregarTituloReporte('REPORTE DE VENTAS');

            // Información del período
            if (isset($datos['fecha_inicio']) && isset($datos['fecha_fin'])) {
                $this->tcpdf->SetY($this->tcpdf->GetY() + 5);
                $this->tcpdf->SetFont('helvetica', 'B', 12);
                $this->tcpdf->Cell(0, 8, 'Período: ' . date('d/m/Y', strtotime($datos['fecha_inicio'])) . ' - ' . date('d/m/Y', strtotime($datos['fecha_fin'])), 0, 1, 'C');
                $this->tcpdf->Ln(5);
            }

            // Tabla de ventas
            if (isset($datos['ventas']) && !empty($datos['ventas'])) {
                $this->crearTablaVentas($datos['ventas']);

                // Total
                $total = isset($datos['total']) ? $datos['total'] : 0;
                $this->tcpdf->SetFont('helvetica', 'B', 12);
                $this->tcpdf->Cell(130, 8, 'TOTAL GENERAL:', 1, 0, 'R', true);
                $this->tcpdf->Cell(40, 8, 'S/ ' . number_format($total, 2), 1, 1, 'R', true);
            } else {
                $this->tcpdf->SetFont('helvetica', 'I', 10);
                $this->tcpdf->Cell(0, 10, 'No se encontraron ventas en el período seleccionado', 0, 1, 'C');
            }

            // Generar y mostrar PDF
            $this->tcpdf->Output('reporte_ventas_' . date('Y-m-d') . '.pdf', 'I');
        } catch (Exception $e) {
            Logger::error("Error generando PDF de ventas: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Genera reporte de inventario en PDF
     */
    public function generarReporteInventario($datos)
    {
        try {
            $this->tcpdf->AddPage();

            // Encabezado del reporte
            $this->agregarEncabezadoEmpresa();
            $this->agregarTituloReporte('VALORIZACIÓN DE INVENTARIO');

            // Fecha del reporte
            $this->tcpdf->SetY($this->tcpdf->GetY() + 5);
            $this->tcpdf->SetFont('helvetica', 'B', 12);
            $this->tcpdf->Cell(0, 8, 'Fecha: ' . date('d/m/Y'), 0, 1, 'C');
            $this->tcpdf->Ln(5);

            // Tabla de inventario
            if (isset($datos['inventario']) && !empty($datos['inventario'])) {
                $this->crearTablaInventario($datos['inventario']);

                // Total
                $total = isset($datos['total']) ? $datos['total'] : 0;
                $this->tcpdf->SetFont('helvetica', 'B', 12);
                $this->tcpdf->Cell(130, 8, 'VALOR TOTAL INVENTARIO:', 1, 0, 'R', true);
                $this->tcpdf->Cell(40, 8, 'S/ ' . number_format($total, 2), 1, 1, 'R', true);
            } else {
                $this->tcpdf->SetFont('helvetica', 'I', 10);
                $this->tcpdf->Cell(0, 10, 'No se encontraron productos en el inventario', 0, 1, 'C');
            }

            // Generar y mostrar PDF
            $this->tcpdf->Output('reporte_inventario_' . date('Y-m-d') . '.pdf', 'I');
        } catch (Exception $e) {
            Logger::error("Error generando PDF de inventario: " . $e->getMessage());
            throw $e;
        }
    }

    private function agregarEncabezadoEmpresa()
    {
        // Logo (simulado con texto)
        $this->tcpdf->SetFont('helvetica', 'B', 16);
        $this->tcpdf->Cell(40, 15, '🔧', 0, 0, 'C');

        // Información de la empresa
        $this->tcpdf->SetFont('helvetica', 'B', 14);
        $this->tcpdf->Cell(130, 6, $this->empresa['nombre'], 0, 1, 'L');

        $this->tcpdf->SetX(55);
        $this->tcpdf->SetFont('helvetica', '', 10);
        $this->tcpdf->Cell(130, 4, 'RUC: ' . $this->empresa['ruc'], 0, 1, 'L');

        $this->tcpdf->SetX(55);
        $this->tcpdf->Cell(130, 4, $this->empresa['direccion'], 0, 1, 'L');

        $this->tcpdf->SetX(55);
        $this->tcpdf->Cell(65, 4, 'Tel: ' . $this->empresa['telefono'], 0, 0, 'L');
        $this->tcpdf->Cell(65, 4, 'Email: ' . $this->empresa['email'], 0, 1, 'L');

        $this->tcpdf->Ln(5);
    }

    private function agregarTituloReporte($titulo)
    {
        $this->tcpdf->SetFont('helvetica', 'B', 16);
        $this->tcpdf->Cell(0, 10, $titulo, 0, 1, 'C');
        $this->tcpdf->Ln(5);
    }

    private function crearTablaVentas($ventas)
    {
        // Encabezados
        $this->tcpdf->SetFont('helvetica', 'B', 9);
        $this->tcpdf->SetFillColor(230, 230, 230);

        $this->tcpdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
        $this->tcpdf->Cell(30, 8, 'N° Venta', 1, 0, 'C', true);
        $this->tcpdf->Cell(60, 8, 'Cliente', 1, 0, 'C', true);
        $this->tcpdf->Cell(25, 8, 'Productos', 1, 0, 'C', true);
        $this->tcpdf->Cell(30, 8, 'Total', 1, 1, 'C', true);

        // Datos
        $this->tcpdf->SetFont('helvetica', '', 8);
        foreach ($ventas as $venta) {
            $this->tcpdf->Cell(25, 6, date('d/m/Y', strtotime($venta['fecha_venta'])), 1, 0, 'C');
            $this->tcpdf->Cell(30, 6, $venta['numero_venta'], 1, 0, 'C');
            $this->tcpdf->Cell(60, 6, substr($venta['cliente_nombre'], 0, 25), 1, 0, 'L');
            $this->tcpdf->Cell(25, 6, $venta['total_productos'], 1, 0, 'C');
            $this->tcpdf->Cell(30, 6, 'S/ ' . number_format($venta['total'], 2), 1, 1, 'R');
        }
    }

    private function crearTablaInventario($inventario)
    {
        // Encabezados
        $this->tcpdf->SetFont('helvetica', 'B', 9);
        $this->tcpdf->SetFillColor(230, 230, 230);

        $this->tcpdf->Cell(25, 8, 'Código', 1, 0, 'C', true);
        $this->tcpdf->Cell(60, 8, 'Producto', 1, 0, 'C', true);
        $this->tcpdf->Cell(20, 8, 'Stock', 1, 0, 'C', true);
        $this->tcpdf->Cell(30, 8, 'P. Unitario', 1, 0, 'C', true);
        $this->tcpdf->Cell(35, 8, 'Valor Total', 1, 1, 'C', true);

        // Datos
        $this->tcpdf->SetFont('helvetica', '', 8);
        foreach ($inventario as $item) {
            $this->tcpdf->Cell(25, 6, $item['codigo'], 1, 0, 'C');
            $this->tcpdf->Cell(60, 6, substr($item['nombre'], 0, 25), 1, 0, 'L');
            $this->tcpdf->Cell(20, 6, $item['stock_actual'], 1, 0, 'C');
            $this->tcpdf->Cell(30, 6, 'S/ ' . number_format($item['precio_venta'], 2), 1, 0, 'R');
            $this->tcpdf->Cell(35, 6, 'S/ ' . number_format($item['valor_total'], 2), 1, 1, 'R');
        }
    }
}
