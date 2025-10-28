<?php

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';

/**
 * Clase VentaPDF - Generación de PDFs para ventas
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class VentaPDF
{
    private $pdf;

    public function __construct()
    {
        $this->pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
        $this->configurarPDF();
    }

    private function configurarPDF()
    {
        // Configuración básica
        $this->pdf->SetCreator('Cruz Motor S.A.C.');
        $this->pdf->SetAuthor('Sistema de Inventario');
        $this->pdf->SetTitle('Boleta de Venta');
        $this->pdf->SetSubject('Comprobante de Venta');

        // Configuración de página
        $this->pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $this->pdf->SetMargins(15, 15, 15);
        $this->pdf->SetHeaderMargin(5);
        $this->pdf->SetFooterMargin(10);
        $this->pdf->SetAutoPageBreak(TRUE, 25);
        $this->pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);
    }

    public function generar($venta)
    {
        $this->pdf->AddPage();

        // Encabezado de la empresa
        $this->generarEncabezado();

        // Información de la venta
        $this->generarInfoVenta($venta);

        // Información del cliente
        $this->generarInfoCliente($venta);

        // Tabla de productos
        $this->generarTablaProductos($venta);

        // Totales
        $this->generarTotales($venta);

        // Pie de página con términos
        $this->generarPie();

        // Salida del PDF
        $filename = 'Venta_' . $venta['numero_venta'] . '_' . date('Y-m-d') . '.pdf';
        $this->pdf->Output($filename, 'D');
    }

    private function generarEncabezado()
    {
        // Logo (si existe)
        $logoPath = __DIR__ . '/../../public/images/logo.png';
        if (file_exists($logoPath)) {
            $this->pdf->Image($logoPath, 15, 15, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 1, false, false, false);
        }

        // Información de la empresa
        $this->pdf->SetFont('helvetica', 'B', 16);
        $this->pdf->SetXY(50, 15);
        $this->pdf->Cell(0, 10, COMPANY_NAME, 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 10);
        $this->pdf->SetXY(50, 25);
        $this->pdf->Cell(0, 5, 'Venta de Repuestos y Accesorios Automotrices', 0, 1, 'L');
        $this->pdf->Cell(0, 5, 'RUC: ' . COMPANY_RUC, 0, 1, 'L');
        $this->pdf->Cell(0, 5, 'Dirección: ' . COMPANY_ADDRESS, 0, 1, 'L');
        $this->pdf->Cell(0, 5, 'Teléfono: ' . COMPANY_PHONE . ' | Email: ' . COMPANY_EMAIL, 0, 1, 'L');

        // Título del comprobante
        $this->pdf->SetFont('helvetica', 'B', 14);
        $this->pdf->SetXY(130, 15);
        $this->pdf->SetFillColor(0, 102, 204);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(65, 10, 'BOLETA DE VENTA', 1, 1, 'C', true);
        $this->pdf->SetTextColor(0, 0, 0);
    }

    private function generarInfoVenta($venta)
    {
        $this->pdf->SetY(55);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'INFORMACIÓN DE LA VENTA', 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 10);
        $data = [
            ['Número de Venta:', $venta['numero_venta']],
            ['Fecha de Emisión:', date('d/m/Y H:i', strtotime($venta['fecha_venta']))],
            ['Vendedor:', $venta['vendedor_nombre']],
            ['Tipo de Pago:', ucfirst($venta['tipo_pago'])],
            ['Estado:', ucfirst($venta['estado'])]
        ];

        foreach ($data as $row) {
            $this->pdf->Cell(40, 6, $row[0], 0, 0, 'L');
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(0, 6, $row[1], 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
        }
    }

    private function generarInfoCliente($venta)
    {
        $this->pdf->SetY($this->pdf->GetY() + 5);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'INFORMACIÓN DEL CLIENTE', 0, 1, 'L');

        $this->pdf->SetFont('helvetica', '', 10);
        $data = [
            ['Cliente:', $venta['cliente_nombre']],
            ['Documento:', $venta['numero_documento']],
        ];

        if (!empty($venta['cliente_telefono'])) {
            $data[] = ['Teléfono:', $venta['cliente_telefono']];
        }

        if (!empty($venta['cliente_email'])) {
            $data[] = ['Email:', $venta['cliente_email']];
        }

        if (!empty($venta['cliente_direccion'])) {
            $data[] = ['Dirección:', $venta['cliente_direccion']];
        }

        foreach ($data as $row) {
            $this->pdf->Cell(40, 6, $row[0], 0, 0, 'L');
            $this->pdf->SetFont('helvetica', 'B', 10);
            $this->pdf->Cell(0, 6, $row[1], 0, 1, 'L');
            $this->pdf->SetFont('helvetica', '', 10);
        }
    }

    private function generarTablaProductos($venta)
    {
        $this->pdf->SetY($this->pdf->GetY() + 10);
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->Cell(0, 8, 'DETALLE DE PRODUCTOS', 0, 1, 'L');

        // Encabezados de tabla
        $this->pdf->SetFont('helvetica', 'B', 9);
        $this->pdf->SetFillColor(240, 240, 240);

        $widths = [25, 70, 20, 25, 25, 25];
        $headers = ['Código', 'Producto', 'Unidad', 'Cantidad', 'Precio Unit.', 'Subtotal'];

        foreach ($headers as $i => $header) {
            $this->pdf->Cell($widths[$i], 8, $header, 1, 0, 'C', true);
        }
        $this->pdf->Ln();

        // Datos de productos
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->SetFillColor(255, 255, 255);

        foreach ($venta['detalles'] as $detalle) {
            $this->pdf->Cell($widths[0], 6, $detalle['producto_codigo'], 1, 0, 'C');
            $this->pdf->Cell($widths[1], 6, substr($detalle['producto_nombre'], 0, 35), 1, 0, 'L');
            $this->pdf->Cell($widths[2], 6, $detalle['unidad'] ?? 'UND', 1, 0, 'C');
            $this->pdf->Cell($widths[3], 6, number_format($detalle['cantidad'], 0), 1, 0, 'C');
            $this->pdf->Cell($widths[4], 6, 'S/ ' . number_format($detalle['precio_unitario'], 2), 1, 0, 'R');
            $this->pdf->Cell($widths[5], 6, 'S/ ' . number_format($detalle['subtotal'], 2), 1, 0, 'R');
            $this->pdf->Ln();
        }
    }

    private function generarTotales($venta)
    {
        $this->pdf->SetY($this->pdf->GetY() + 5);
        $this->pdf->SetFont('helvetica', 'B', 10);

        // Subtotal
        $this->pdf->Cell(140, 6, '', 0, 0);
        $this->pdf->Cell(30, 6, 'Subtotal:', 1, 0, 'R');
        $this->pdf->Cell(20, 6, 'S/ ' . number_format($venta['subtotal'], 2), 1, 1, 'R');

        // IGV
        $this->pdf->Cell(140, 6, '', 0, 0);
        $this->pdf->Cell(30, 6, 'IGV (18%):', 1, 0, 'R');
        $this->pdf->Cell(20, 6, 'S/ ' . number_format($venta['igv'], 2), 1, 1, 'R');

        // Total
        $this->pdf->SetFont('helvetica', 'B', 12);
        $this->pdf->SetFillColor(0, 102, 204);
        $this->pdf->SetTextColor(255, 255, 255);
        $this->pdf->Cell(140, 8, '', 0, 0);
        $this->pdf->Cell(30, 8, 'TOTAL:', 1, 0, 'R', true);
        $this->pdf->Cell(20, 8, 'S/ ' . number_format($venta['total'], 2), 1, 1, 'R', true);
        $this->pdf->SetTextColor(0, 0, 0);

        // Total en letras
        $this->pdf->SetFont('helvetica', 'I', 9);
        $this->pdf->Cell(0, 6, 'Son: ' . $this->numeroALetras($venta['total']) . ' SOLES', 0, 1, 'L');
    }

    private function generarPie()
    {
        $this->pdf->SetY(-40);
        $this->pdf->SetFont('helvetica', '', 8);
        $this->pdf->Cell(0, 5, '• Gracias por su preferencia', 0, 1, 'L');
        $this->pdf->Cell(0, 5, '• Para consultas o reclamos contactar al ' . COMPANY_PHONE, 0, 1, 'L');
        $this->pdf->Cell(0, 5, '• Este documento es generado electrónicamente', 0, 1, 'L');

        $this->pdf->SetY(-20);
        $this->pdf->SetFont('helvetica', 'I', 8);
        $this->pdf->Cell(0, 5, 'Documento generado el ' . date('d/m/Y H:i') . ' - Cruz Motor S.A.C.', 0, 1, 'C');
    }

    private function numeroALetras($numero)
    {
        $entero = intval($numero);
        $decimales = intval(($numero - $entero) * 100);

        // Esta es una función simplificada, en producción se usaría una más completa
        $unidades = ['', 'UNO', 'DOS', 'TRES', 'CUATRO', 'CINCO', 'SEIS', 'SIETE', 'OCHO', 'NUEVE'];
        $decenas = ['', '', 'VEINTE', 'TREINTA', 'CUARENTA', 'CINCUENTA', 'SESENTA', 'SETENTA', 'OCHENTA', 'NOVENTA'];
        $centenas = ['', 'CIENTO', 'DOSCIENTOS', 'TRESCIENTOS', 'CUATROCIENTOS', 'QUINIENTOS', 'SEISCIENTOS', 'SETECIENTOS', 'OCHOCIENTOS', 'NOVECIENTOS'];

        if ($entero == 0) {
            return 'CERO CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
        }

        // Implementación simplificada para números hasta 999
        $resultado = '';

        if ($entero >= 100) {
            $resultado .= $centenas[intval($entero / 100)] . ' ';
            $entero %= 100;
        }

        if ($entero >= 20) {
            $resultado .= $decenas[intval($entero / 10)] . ' ';
            $entero %= 10;
        }

        if ($entero > 0) {
            $resultado .= $unidades[$entero] . ' ';
        }

        return trim($resultado) . ' CON ' . str_pad($decimales, 2, '0', STR_PAD_LEFT) . '/100';
    }
}
