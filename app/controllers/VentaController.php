<?php

/**
 * Controlador Venta - Gestión de ventas del sistema
 * Sistema de Inventario Cruz Motor S.A.C.
 * Versión optimizada
 */

class VentaController extends Controller
{
    private $ventaModel;
    private $clienteModel;
    private $cotizacionModel;

    public function __construct()
    {
        parent::__construct();
        $this->ventaModel = new Venta();
        $this->clienteModel = new Cliente();
        $this->cotizacionModel = new Cotizacion();
    }
    /**
     * Mostrar listado de ventas
     */
    public function index()
    {
        try {
            // Por ahora vamos a mostrar las cotizaciones aprobadas como "ventas"
            $ventas = $this->cotizacionModel->getAll();

            // Filtrar solo las aprobadas
            $ventas = array_filter($ventas, function ($cotizacion) {
                return ($cotizacion['estado'] ?? '') === 'aprobada';
            });

            $this->view('ventas/index', [
                'title' => 'Gestión de Ventas',
                'ventas' => $ventas
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VentaController::index', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            $this->view('errors/500', ['error' => 'Error al cargar ventas']);
        }
    }

    /**
     * Mostrar formulario para crear venta desde una cotización
     */
    public function createFromCotizacion()
    {
        try {
            $id = $_GET['cotizacion'] ?? ($_GET['id'] ?? null);
            if (!$id) {
                $this->setFlash('error', 'ID de cotización requerido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            // Obtener cotización y detalles
            $cotizacion = $this->cotizacionModel->getWithDetails($id);
            if (!$cotizacion) {
                $this->setFlash('error', 'Cotización no encontrada');
                $this->redirect('?page=cotizaciones');
                return;
            }

            $detalles = $this->cotizacionModel->getProductos($id);

            // Mapear estructura esperada por la vista
            $cotizacion['detalles'] = [];
            $subtotal = 0;
            foreach ($detalles as $d) {
                $lineSubtotal = ($d['cantidad'] ?? 0) * ($d['precio_unitario'] ?? ($d['precio'] ?? 0));
                $cotizacion['detalles'][] = [
                    'producto_codigo' => $d['codigo'] ?? '',
                    'producto_nombre' => $d['nombre'] ?? ($d['producto_nombre'] ?? ''),
                    'cantidad' => $d['cantidad'] ?? 0,
                    'precio_unitario' => $d['precio_unitario'] ?? ($d['precio'] ?? 0),
                    'subtotal' => $lineSubtotal
                ];
                $subtotal += $lineSubtotal;
            }

            $cotizacion['subtotal'] = $subtotal;
            $cotizacion['igv'] = round($subtotal * (DEFAULT_IVA / 100), 2);
            $cotizacion['total'] = $subtotal + $cotizacion['igv'];

            $this->view('ventas/create_from_cotizacion', [
                'title' => 'Crear Venta desde Cotización',
                'data' => ['cotizacion' => $cotizacion]
            ]);
        } catch (Exception $e) {
            Logger::error('Error en VentaController::createFromCotizacion - ' . $e->getMessage());
            $this->view('errors/500', ['error' => 'Error al preparar venta desde cotización']);
        }
    }

    /**
     * Procesar creación de venta desde cotización (POST)
     */
    public function storeFromCotizacion()
    {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                $this->setFlash('error', 'Método no permitido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            // CSRF
            if (!Auth::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $this->setFlash('error', 'Token CSRF inválido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            $id_cotizacion = $_POST['id_cotizacion'] ?? null;
            if (!$id_cotizacion) {
                $this->setFlash('error', 'ID de cotización requerido');
                $this->redirect('?page=cotizaciones');
                return;
            }

            $datos = [
                'id_usuario' => $_SESSION['user_id'] ?? 1,
                'fecha_venta' => $_POST['fecha_venta'] ?? date('Y-m-d'),
                'tipo_pago' => $_POST['tipo_pago'] ?? 'contado',
                'observaciones' => $_POST['observaciones'] ?? null
            ];

            try {
                $ventaId = $this->ventaModel->crearDesdeCotizacion($id_cotizacion, $datos);
            } catch (Exception $e) {
                Logger::warning('Venta::crearDesdeCotizacion falló, se intentará marcar la cotización como aprobada: ' . $e->getMessage());
                $ventaId = false;
            }

            if ($ventaId) {
                $this->setFlash('success', 'Venta creada exitosamente');
                $this->redirect('?page=ventas&action=view&id=' . $ventaId);
                return;
            }

            // Fallback: si no se pudo crear la venta, marcar la cotización como aprobada para que aparezca en el listado de ventas
            try {
                $this->cotizacionModel->update($id_cotizacion, ['estado' => 'aprobada']);
                $this->setFlash('warning', 'No se pudo generar la venta automáticamente, pero la cotización fue marcada como APROBADA. Revise los registros.');
                $this->redirect('?page=cotizaciones&action=view&id=' . $id_cotizacion);
            } catch (Exception $e) {
                Logger::error('No se pudo marcar la cotización como aprobada: ' . $e->getMessage());
                $this->setFlash('error', 'Error al generar la venta o marcar la cotización como aprobada');
                $this->redirect('?page=cotizaciones');
            }
        } catch (Exception $e) {
            Logger::error('Error en VentaController::storeFromCotizacion - ' . $e->getMessage());
            $this->setFlash('error', 'Error interno al crear la venta');
            $this->redirect('?page=cotizaciones');
        }
    }
}
