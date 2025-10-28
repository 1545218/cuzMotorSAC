<?php

/**
 * Modelo Venta - Gestión de ventas del sistema
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Venta extends Model
{
    protected $table = 'ventas';
    protected $primaryKey = 'id_venta';
    protected $fillable = [
        'numero_venta',
        'id_cotizacion',
        'id_cliente',
        'id_usuario',
        'fecha_venta',
        'subtotal',
        'igv',
        'total',
        'estado',
        'tipo_pago',
        'observaciones'
    ];

    /**
     * Genera número de venta
     */
    public function generateNumeroVenta()
    {
        $year = date('Y');
        $month = date('m');

        $lastVenta = $this->db->selectOne(
            "SELECT numero_venta FROM ventas 
             WHERE numero_venta LIKE ? 
             ORDER BY id_venta DESC LIMIT 1",
            ["VTA-{$year}-{$month}-%"]
        );

        $number = 1;
        if ($lastVenta && preg_match('/(\d+)$/', $lastVenta['numero_venta'], $matches)) {
            $number = (int)$matches[1] + 1;
        }

        return "VTA-{$year}-{$month}-" . str_pad($number, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Crear venta desde cotización
     */
    public function crearDesdeCotizacion($id_cotizacion, $datos_adicionales = [])
    {
        try {
            // Obtener datos de la cotización
            $cotizacion = $this->db->selectOne(
                "SELECT * FROM cotizaciones WHERE id_cotizacion = ?",
                [$id_cotizacion]
            );

            if (!$cotizacion) {
                throw new Exception('Cotización no encontrada');
            }

            // Crear la venta
            $numero_venta = $this->generateNumeroVenta();

            $venta_data = [
                'numero_venta' => $numero_venta,
                'id_cotizacion' => $id_cotizacion,
                'id_cliente' => $cotizacion['id_cliente'],
                'id_usuario' => $datos_adicionales['id_usuario'] ?? $_SESSION['user_id'],
                'fecha_venta' => $datos_adicionales['fecha_venta'] ?? date('Y-m-d'),
                'subtotal' => $cotizacion['subtotal'],
                'igv' => $cotizacion['igv'],
                'total' => $cotizacion['total'],
                'estado' => 'completada',
                'tipo_pago' => $datos_adicionales['tipo_pago'] ?? 'contado',
                'observaciones' => $datos_adicionales['observaciones'] ?? null
            ];

            $id_venta = $this->create($venta_data);

            if ($id_venta) {
                // Copiar productos de la cotización
                $this->copiarProductosCotizacion($id_venta, $id_cotizacion);

                // Actualizar estado de cotización
                $this->db->execute(
                    "UPDATE cotizaciones SET estado = 'aprobada' WHERE id_cotizacion = ?",
                    [$id_cotizacion]
                );

                Logger::info("Venta creada desde cotización: Venta ID {$id_venta}, Cotización ID {$id_cotizacion}");
                return $id_venta;
            }

            return false;
        } catch (Exception $e) {
            Logger::error("Error al crear venta desde cotización: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Copiar productos de cotización a venta
     */
    private function copiarProductosCotizacion($id_venta, $id_cotizacion)
    {
        $productos = $this->db->select(
            "SELECT * FROM detalle_cotizacion WHERE id_cotizacion = ?",
            [$id_cotizacion]
        );

        foreach ($productos as $producto) {
            $this->db->execute(
                "INSERT INTO detalle_ventas (id_venta, id_producto, cantidad, precio_unitario, subtotal) 
                 VALUES (?, ?, ?, ?, ?)",
                [
                    $id_venta,
                    $producto['id_producto'],
                    $producto['cantidad'],
                    $producto['precio_unitario'],
                    $producto['subtotal']
                ]
            );

            // Reducir stock del producto
            $this->reducirStock($producto['id_producto'], $producto['cantidad'], $id_venta);
        }
    }

    /**
     * Reducir stock del producto por venta
     */
    private function reducirStock($id_producto, $cantidad, $id_venta)
    {
        // Obtener stock actual
        $producto = $this->db->selectOne(
            "SELECT stock_actual FROM productos WHERE id_producto = ?",
            [$id_producto]
        );

        if ($producto) {
            $stock_anterior = (int)$producto['stock_actual'];
            $stock_nuevo = $stock_anterior - $cantidad;

            // Actualizar stock
            $this->db->execute(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$stock_nuevo, $id_producto]
            );

            // Registrar movimiento en registrosstock (salida)
            try {
                $this->db->execute(
                    "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) VALUES (?, 'salida', ?, NOW(), 'venta', ?, ?)",
                    [$id_producto, $cantidad, $id_venta, $_SESSION['user_id'] ?? 1]
                );
            } catch (Exception $e) {
                Logger::error("No se pudo registrar salida en registrosstock: " . $e->getMessage());
            }
        }
    }

    /**
     * Obtener ventas con detalles
     */
    public function getVentasWithDetails($fecha_inicio = null, $fecha_fin = null, $estado = null)
    {
        $conditions = ['1=1'];
        $params = [];

        if ($fecha_inicio) {
            $conditions[] = "v.fecha_venta >= ?";
            $params[] = $fecha_inicio;
        }

        if ($fecha_fin) {
            $conditions[] = "v.fecha_venta <= ?";
            $params[] = $fecha_fin;
        }

        if ($estado) {
            $conditions[] = "v.estado = ?";
            $params[] = $estado;
        }

        $where_clause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT v.*, 
                    c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                    u.nombre as usuario_nombre,
                    cot.numero_cotizacion
             FROM ventas v
             LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
             LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
             LEFT JOIN cotizaciones cot ON v.id_cotizacion = cot.id_cotizacion
             WHERE {$where_clause}
             ORDER BY v.fecha_venta DESC, v.id_venta DESC",
            $params
        );
    }

    /**
     * Obtener estadísticas de ventas
     */
    public function getEstadisticasVentas($fecha_inicio = null, $fecha_fin = null)
    {
        $conditions = ["v.estado = 'completada'"];
        $params = [];

        if ($fecha_inicio) {
            $conditions[] = "v.fecha_venta >= ?";
            $params[] = $fecha_inicio;
        }

        if ($fecha_fin) {
            $conditions[] = "v.fecha_venta <= ?";
            $params[] = $fecha_fin;
        }

        $where_clause = implode(' AND ', $conditions);

        $stats = $this->db->selectOne(
            "SELECT 
                COUNT(*) as total_ventas,
                SUM(v.total) as total_ingresos,
                AVG(v.total) as promedio_venta,
                SUM(v.subtotal) as total_subtotal,
                SUM(v.igv) as total_igv
             FROM ventas v
             WHERE {$where_clause}",
            $params
        );

        return [
            'total_ventas' => (int)($stats['total_ventas'] ?? 0),
            'total_ingresos' => (float)($stats['total_ingresos'] ?? 0),
            'promedio_venta' => (float)($stats['promedio_venta'] ?? 0),
            'total_subtotal' => (float)($stats['total_subtotal'] ?? 0),
            'total_igv' => (float)($stats['total_igv'] ?? 0)
        ];
    }

    /**
     * Obtener productos más vendidos
     */
    public function getProductosMasVendidos($fecha_inicio = null, $fecha_fin = null, $limit = 10)
    {
        $conditions = ["v.estado = 'completada'"];
        $params = [];

        if ($fecha_inicio) {
            $conditions[] = "v.fecha_venta >= ?";
            $params[] = $fecha_inicio;
        }

        if ($fecha_fin) {
            $conditions[] = "v.fecha_venta <= ?";
            $params[] = $fecha_fin;
        }

        $where_clause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.nombre, p.codigo,
                    SUM(dv.cantidad) as total_vendido,
                    SUM(dv.subtotal) as total_ingresos,
                    COUNT(DISTINCT v.id_venta) as num_ventas
             FROM detalle_ventas dv
             JOIN ventas v ON dv.id_venta = v.id_venta
             JOIN productos p ON dv.id_producto = p.id_producto
             WHERE {$where_clause}
             GROUP BY dv.id_producto, p.nombre, p.codigo
             ORDER BY total_vendido DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Obtener venta con detalles completos
     */
    public function obtenerConDetalles($id)
    {
        // Obtener datos principales de la venta
        $venta = $this->db->selectOne(
            "SELECT v.*, 
                    CONCAT(c.nombre, ' ', c.apellido) as cliente_nombre,
                    c.numero_documento,
                    c.telefono as cliente_telefono,
                    c.email as cliente_email,
                    c.direccion as cliente_direccion,
                    u.nombre as vendedor_nombre,
                    cot.numero_cotizacion
             FROM ventas v
             LEFT JOIN clientes c ON v.id_cliente = c.id_cliente
             LEFT JOIN usuarios u ON v.id_usuario = u.id_usuario
             LEFT JOIN cotizaciones cot ON v.id_cotizacion = cot.id_cotizacion
             WHERE v.id_venta = ?",
            [$id]
        );

        if ($venta) {
            // Obtener detalles de productos
            $venta['detalles'] = $this->db->select(
                "SELECT dv.*, 
                        p.nombre as producto_nombre,
                        p.codigo as producto_codigo,
                        p.unidad
                 FROM detalle_ventas dv
                 LEFT JOIN productos p ON dv.id_producto = p.id_producto
                 WHERE dv.id_venta = ?
                 ORDER BY dv.id_detalle",
                [$id]
            );
        }

        return $venta;
    }

    /**
     * Anular venta y restaurar stock
     */
    public function anular($id)
    {
        try {
            $this->db->beginTransaction();

            // Obtener detalles de la venta
            $detalles = $this->db->select(
                "SELECT * FROM detalle_ventas WHERE id_venta = ?",
                [$id]
            );

            // Restaurar stock de cada producto
            foreach ($detalles as $detalle) {
                $this->restaurarStock($detalle['id_producto'], $detalle['cantidad'], $id);
            }

            // Actualizar estado de la venta
            $result = $this->update($id, ['estado' => 'anulada']);

            // Si había cotización asociada, volver a estado pendiente
            $venta = $this->find($id);
            if ($venta && $venta['id_cotizacion']) {
                $this->db->execute(
                    "UPDATE cotizaciones SET estado = 'pendiente' WHERE id_cotizacion = ?",
                    [$venta['id_cotizacion']]
                );
            }

            $this->db->commit();
            return $result;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Restaurar stock del producto por anulación
     */
    private function restaurarStock($id_producto, $cantidad, $id_venta)
    {
        // Obtener stock actual
        $producto = $this->db->selectOne(
            "SELECT stock_actual FROM productos WHERE id_producto = ?",
            [$id_producto]
        );

        if ($producto) {
            $stock_anterior = (int)$producto['stock_actual'];
            $stock_nuevo = $stock_anterior + $cantidad;

            // Actualizar stock
            $this->db->execute(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$stock_nuevo, $id_producto]
            );

            // Registrar entrada en registrosstock
            try {
                $this->db->execute(
                    "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) VALUES (?, 'entrada', ?, NOW(), 'venta_anulada', ?, ?)",
                    [$id_producto, $cantidad, $id_venta, $_SESSION['user_id'] ?? 1]
                );
            } catch (Exception $e) {
                Logger::error("No se pudo registrar entrada en registrosstock: " . $e->getMessage());
            }
        }
    }

    /**
     * Buscar venta por ID
     */
    public function findById($id)
    {
        return $this->db->selectOne(
            "SELECT v.*, 
                    CONCAT(c.nombre, ' ', COALESCE(c.apellido, '')) as cliente_nombre,
                    c.numero_documento,
                    c.telefono as cliente_telefono
             FROM ventas v
             JOIN clientes c ON v.id_cliente = c.id_cliente
             WHERE v.id_venta = ?",
            [$id]
        );
    }

    /**
     * Obtener detalle de una venta
     */
    public function getDetalleVenta($idVenta)
    {
        return $this->db->select(
            "SELECT dv.*, 
                    p.nombre as producto_nombre,
                    p.codigo as producto_codigo
             FROM detalle_ventas dv
             JOIN productos p ON dv.id_producto = p.id_producto
             WHERE dv.id_venta = ?
             ORDER BY dv.id_detalle",
            [$idVenta]
        );
    }
}
