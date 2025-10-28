<?php

require_once __DIR__ . '/../../libs/tcpdf/tcpdf.php';
require_once __DIR__ . '/../core/Logger.php';

/**
 * Generador de PDF para cotizaciones
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class CotizacionPDF
{
    private $tcpdf;
    private $cotizacion;
    private $cliente;
    private $productos;
    private $empresa;

    public function __construct()
    {
        // Configuración de la empresa (usar constantes de config si están disponibles)
        $this->empresa = [
            'nombre' => defined('COMPANY_NAME') ? COMPANY_NAME : 'Cruz Motor S.A.C.',
            'ruc' => defined('COMPANY_RUC') ? COMPANY_RUC : '20123456789',
            'direccion' => defined('COMPANY_ADDRESS') ? COMPANY_ADDRESS : 'AV. Panamericana N° 197 - puno',
            'telefono' => defined('COMPANY_PHONE') ? COMPANY_PHONE : '991715768',
            'email' => defined('COMPANY_EMAIL') ? COMPANY_EMAIL : '#',
            'web' => defined('BASE_URL') ? BASE_URL : 'www.cruzmotor.com'
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
        $this->tcpdf->SetTitle('Cotización');
        $this->tcpdf->SetSubject('Cotización de productos');
        $this->tcpdf->SetKeywords('cotización, productos, cruzmotor');

        // Configurar márgenes
        $this->tcpdf->SetMargins(15, 30, 15);
        $this->tcpdf->SetHeaderMargin(5);
        $this->tcpdf->SetFooterMargin(10);

        // Auto page breaks
        $this->tcpdf->SetAutoPageBreak(true, 25);

        // Configurar fuente
        $this->tcpdf->SetFont('helvetica', '', 10);

        // Eliminar header y footer por defecto
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);
    }

    public function generar($cotizacion, $cliente, $productos)
    {
        try {
            $this->cotizacion = $cotizacion;
            $this->cliente = $cliente;
            $this->productos = $productos;

            // Añadir página
            $this->tcpdf->AddPage();

            // Generar contenido
            $this->generarEncabezado();
            $this->generarDatosCliente();
            $this->generarTablaProductos();
            $this->generarTotales();
            $this->generarPie();

            return $this->tcpdf->Output('cotizacion_' . $this->cotizacion['id_cotizacion'] . '.pdf', 'S');
        } catch (Exception $e) {
            Logger::error("Error al generar PDF: " . $e->getMessage());
            throw new Exception("Error al generar el PDF de la cotización");
        }
    }

    private function generarEncabezado()
    {
        // Logo y datos de la empresa
        $this->tcpdf->SetFont('helvetica', 'B', 16);
        $this->tcpdf->Cell(0, 10, $this->empresa['nombre'], 0, 1, 'C');

        $this->tcpdf->SetFont('helvetica', '', 10);
        $this->tcpdf->Cell(0, 5, 'RUC: ' . $this->empresa['ruc'], 0, 1, 'C');
        $this->tcpdf->Cell(0, 5, $this->empresa['direccion'], 0, 1, 'C');
        $this->tcpdf->Cell(0, 5, 'Tel: ' . $this->empresa['telefono'] . ' | Email: ' . $this->empresa['email'], 0, 1, 'C');

        $this->tcpdf->Ln(10);

        // Título de la cotización
        $this->tcpdf->SetFont('helvetica', 'B', 14);
        $this->tcpdf->Cell(0, 8, 'COTIZACIÓN', 0, 1, 'C');

        $this->tcpdf->Ln(5);

        // Información de la cotización
        $this->tcpdf->SetFont('helvetica', '', 10);

        // Datos en dos columnas
        $fecha = date('d/m/Y', strtotime($this->cotizacion['fecha']));
        // Calcular fecha de vencimiento (15 días después de la cotización)
        $fecha_vencimiento = date('d/m/Y', strtotime($this->cotizacion['fecha'] . ' +15 days'));

        $this->tcpdf->Cell(95, 6, 'N° Cotización: ' . str_pad($this->cotizacion['id_cotizacion'], 6, '0', STR_PAD_LEFT), 1, 0, 'L');
        $this->tcpdf->Cell(95, 6, 'Fecha: ' . $fecha, 1, 1, 'L');

        $this->tcpdf->Cell(95, 6, 'Estado: ' . ucfirst($this->cotizacion['estado'] ?? 'pendiente'), 1, 0, 'L');
        $this->tcpdf->Cell(95, 6, 'Válida hasta: ' . $fecha_vencimiento, 1, 1, 'L');

        $this->tcpdf->Ln(5);
    }

    private function generarDatosCliente()
    {
        $this->tcpdf->SetFont('helvetica', 'B', 11);
        $this->tcpdf->Cell(0, 7, 'DATOS DEL CLIENTE', 0, 1, 'L');

        $this->tcpdf->SetFont('helvetica', '', 10);

        $nombreCompleto = trim($this->cliente['nombre'] . ' ' . $this->cliente['apellido']);
        $documento = $this->cliente['tipo_documento'] . ': ' . $this->cliente['numero_documento'];

        $this->tcpdf->Cell(95, 6, 'Cliente: ' . $nombreCompleto, 1, 0, 'L');
        $this->tcpdf->Cell(95, 6, 'Documento: ' . $documento, 1, 1, 'L');

        if (!empty($this->cliente['telefono'])) {
            $this->tcpdf->Cell(95, 6, 'Teléfono: ' . $this->cliente['telefono'], 1, 0, 'L');
        } else {
            $this->tcpdf->Cell(95, 6, 'Teléfono: ---', 1, 0, 'L');
        }

        if (!empty($this->cliente['email'])) {
            $this->tcpdf->Cell(95, 6, 'Email: ' . $this->cliente['email'], 1, 1, 'L');
        } else {
            $this->tcpdf->Cell(95, 6, 'Email: ---', 1, 1, 'L');
        }

        if (!empty($this->cliente['direccion'])) {
            $this->tcpdf->Cell(0, 6, 'Dirección: ' . $this->cliente['direccion'], 1, 1, 'L');
        }

        $this->tcpdf->Ln(5);
    }

    private function generarTablaProductos()
    {
        $this->tcpdf->SetFont('helvetica', 'B', 11);
        $this->tcpdf->Cell(0, 7, 'DETALLE DE PRODUCTOS', 0, 1, 'L');

        // Encabezados de tabla
        $this->tcpdf->SetFont('helvetica', 'B', 9);
        $this->tcpdf->SetFillColor(230, 230, 230);

        $this->tcpdf->Cell(15, 8, 'Item', 1, 0, 'C', true);
        $this->tcpdf->Cell(25, 8, 'Código', 1, 0, 'C', true);
        $this->tcpdf->Cell(70, 8, 'Producto', 1, 0, 'C', true);
        $this->tcpdf->Cell(20, 8, 'Cant.', 1, 0, 'C', true);
        $this->tcpdf->Cell(25, 8, 'P. Unit.', 1, 0, 'C', true);
        $this->tcpdf->Cell(25, 8, 'Subtotal', 1, 1, 'C', true);

        // Contenido de la tabla
        $this->tcpdf->SetFont('helvetica', '', 9);
        $subtotal = 0;
        $item = 1;

        foreach ($this->productos as $producto) {
            $precio_unitario = floatval($producto['precio_unitario']);
            $cantidad = intval($producto['cantidad']);
            $subtotal_producto = $precio_unitario * $cantidad;
            $subtotal += $subtotal_producto;

            $this->tcpdf->Cell(15, 6, $item, 1, 0, 'C');
            $this->tcpdf->Cell(25, 6, $producto['codigo'], 1, 0, 'C');
            $this->tcpdf->Cell(70, 6, substr($producto['nombre'], 0, 40), 1, 0, 'L');
            $this->tcpdf->Cell(20, 6, $cantidad, 1, 0, 'C');
            $this->tcpdf->Cell(25, 6, 'S/ ' . number_format($precio_unitario, 2), 1, 0, 'R');
            $this->tcpdf->Cell(25, 6, 'S/ ' . number_format($subtotal_producto, 2), 1, 1, 'R');

            $item++;
        }

        $this->tcpdf->Ln(3);
    }

    private function generarTotales()
    {
        // Calcular totales basándose en los productos
        $subtotal = 0;
        foreach ($this->productos as $producto) {
            $precio_unitario = floatval($producto['precio_unitario']);
            $cantidad = intval($producto['cantidad']);
            $subtotal += $precio_unitario * $cantidad;
        }

        $igv = $subtotal * 0.18; // 18% de IGV
        $total = $subtotal + $igv;

        // Tabla de totales (alineada a la derecha)
        $x = $this->tcpdf->GetPageWidth() - 70;
        $this->tcpdf->SetX($x);

        $this->tcpdf->SetFont('helvetica', '', 10);

        $this->tcpdf->Cell(30, 6, 'Subtotal:', 1, 0, 'L');
        $this->tcpdf->Cell(25, 6, 'S/ ' . number_format($subtotal, 2), 1, 1, 'R');
        $this->tcpdf->SetX($x);

        $this->tcpdf->Cell(30, 6, 'IGV (18%):', 1, 0, 'L');
        $this->tcpdf->Cell(25, 6, 'S/ ' . number_format($igv, 2), 1, 1, 'R');
        $this->tcpdf->SetX($x);

        $this->tcpdf->SetFont('helvetica', 'B', 11);
        $this->tcpdf->Cell(30, 8, 'TOTAL:', 1, 0, 'L');
        $this->tcpdf->Cell(25, 8, 'S/ ' . number_format($total, 2), 1, 1, 'R');

        $this->tcpdf->Ln(10);
    }

    private function generarPie()
    {
        // Condiciones y observaciones
        $this->tcpdf->SetFont('helvetica', 'B', 10);
        $this->tcpdf->Cell(0, 6, 'CONDICIONES COMERCIALES:', 0, 1, 'L');

        $this->tcpdf->SetFont('helvetica', '', 9);
        $condiciones = [
            '• Precios válidos por 15 días',
            '• Precios incluyen IGV',
            '• Forma de pago: Al contado o según acuerdo',
            '• Garantía según especificaciones del fabricante',
            '• Entrega según disponibilidad de stock'
        ];

        foreach ($condiciones as $condicion) {
            $this->tcpdf->Cell(0, 5, $condicion, 0, 1, 'L');
        }

        if (!empty($this->cotizacion['observaciones'])) {
            $this->tcpdf->Ln(5);
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->Cell(0, 6, 'OBSERVACIONES:', 0, 1, 'L');

            $this->tcpdf->SetFont('helvetica', '', 9);
            $this->tcpdf->MultiCell(0, 5, $this->cotizacion['observaciones'], 0, 'L');
        }

        // Pie de página
        $this->tcpdf->SetY(-25);
        $this->tcpdf->SetFont('helvetica', 'I', 8);
        $this->tcpdf->Cell(0, 5, 'Gracias por su preferencia - ' . $this->empresa['web'], 0, 1, 'C');
        $this->tcpdf->Cell(0, 5, 'Documento generado automáticamente el ' . date('d/m/Y H:i'), 0, 1, 'C');
    }

    public function descargar($cotizacion, $cliente, $productos, $nombre_archivo = null)
    {
        if (!$nombre_archivo) {
            $nombre_archivo = 'cotizacion_' . str_pad($cotizacion['id_cotizacion'], 6, '0', STR_PAD_LEFT) . '.pdf';
        }

        $contenido_pdf = $this->generar($cotizacion, $cliente, $productos);

        // Limpiar buffers de salida para evitar corrupción del PDF
        if (ob_get_length()) {
            @ob_end_clean();
        }

        header('Content-Type: application/pdf');
        header('Content-Transfer-Encoding: binary');
        header('Content-Disposition: attachment; filename="' . $nombre_archivo . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . strlen($contenido_pdf));

        echo $contenido_pdf;
        exit;
    }

    public function guardar($cotizacion, $cliente, $productos, $ruta_archivo)
    {
        $contenido_pdf = $this->generar($cotizacion, $cliente, $productos);
        return file_put_contents($ruta_archivo, $contenido_pdf);
    }
}
