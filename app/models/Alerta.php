<?php
require_once __DIR__ . '/../core/Model.php';

class Alerta extends Model
{
    protected $table = 'alertas';
    protected $primaryKey = 'id_alerta';

    public function getAlertasPendientes()
    {
        $sql = "SELECT * FROM alertas WHERE estado = 'pendiente' ORDER BY fecha DESC";
        return $this->db->select($sql);
    }

    public function crear($tipo, $mensaje)
    {
        $sql = "INSERT INTO alertas (tipo, mensaje, fecha, estado) VALUES (?, ?, NOW(), 'pendiente')";
        return $this->db->execute($sql, [$tipo, $mensaje]);
    }

    public function marcarResuelta($id)
    {
        $sql = "UPDATE alertas SET estado = 'resuelta' WHERE id_alerta = ?";
        return $this->db->execute($sql, [$id]);
    }

    public function verificarStockBajo()
    {
        // Obtiene productos con stock bajo
        $sql = "SELECT id_producto, nombre, stock_actual, stock_minimo 
                FROM productos 
                WHERE stock_actual <= stock_minimo AND estado = 'activo'";
        $productosStockBajo = $this->db->select($sql);

        foreach ($productosStockBajo as $producto) {
            // Verifica si ya existe una alerta pendiente para este producto
            $sqlVerificar = "SELECT COUNT(*) as count FROM alertas 
                           WHERE tipo = 'stock_bajo' 
                           AND mensaje LIKE ? 
                           AND estado = 'pendiente'";
            $existe = $this->db->select($sqlVerificar, ["%{$producto['nombre']}%"]);

            if ($existe[0]['count'] == 0) {
                $mensaje = "Stock bajo para producto: {$producto['nombre']} (Stock actual: {$producto['stock_actual']}, Mínimo: {$producto['stock_minimo']})";
                $this->crear('stock_bajo', $mensaje);
            }
        }

        return count($productosStockBajo);
    }

    public function contarPendientes()
    {
        $sql = "SELECT COUNT(*) as total FROM alertas WHERE estado = 'pendiente'";
        $result = $this->db->select($sql);
        return $result[0]['total'];
    }

    public function eliminar($id)
    {
        $sql = "DELETE FROM alertas WHERE id_alerta = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * Verifica todas las condiciones que deben generar alertas
     */
    public function verificarTodasLasAlertas()
    {
        $totalGeneradas = 0;

        // 1. Verificar stock bajo
        $totalGeneradas += $this->verificarStockBajo();

        // 2. Verificar productos sin stock
        $totalGeneradas += $this->verificarProductosSinStock();

        // 3. Verificar productos próximos a vencer (si existe campo fecha_vencimiento)
        $totalGeneradas += $this->verificarProductosProximosVencer();

        // 4. Verificar ventas del día
        $totalGeneradas += $this->verificarVentasDelDia();

        return $totalGeneradas;
    }

    /**
     * Verifica productos sin stock (0)
     */
    public function verificarProductosSinStock()
    {
        $sql = "SELECT id_producto, nombre, stock_actual 
                FROM productos 
                WHERE stock_actual = 0 AND estado = 'activo'";
        $productosSinStock = $this->db->select($sql);

        foreach ($productosSinStock as $producto) {
            // Verifica si ya existe una alerta pendiente para este producto
            $sqlVerificar = "SELECT COUNT(*) as count FROM alertas 
                           WHERE tipo = 'sin_stock' 
                           AND mensaje LIKE ? 
                           AND estado = 'pendiente'";
            $existe = $this->db->select($sqlVerificar, ["%{$producto['nombre']}%"]);

            if ($existe[0]['count'] == 0) {
                $mensaje = "Producto sin stock: {$producto['nombre']} - Stock actual: 0";
                $this->crear('sin_stock', $mensaje);
            }
        }

        return count($productosSinStock);
    }

    /**
     * Verifica productos próximos a vencer (30 días)
     */
    public function verificarProductosProximosVencer()
    {
        // Verificar si existe la columna fecha_vencimiento
        $sql = "SHOW COLUMNS FROM productos LIKE 'fecha_vencimiento'";
        $columna = $this->db->select($sql);

        if (empty($columna)) {
            return 0; // No hay columna de fecha de vencimiento
        }

        $sql = "SELECT id_producto, nombre, fecha_vencimiento 
                FROM productos 
                WHERE fecha_vencimiento IS NOT NULL 
                AND fecha_vencimiento <= DATE_ADD(NOW(), INTERVAL 30 DAY)
                AND fecha_vencimiento >= NOW()
                AND estado = 'activo'";
        $productosVencimiento = $this->db->select($sql);

        foreach ($productosVencimiento as $producto) {
            $sqlVerificar = "SELECT COUNT(*) as count FROM alertas 
                           WHERE tipo = 'proximo_vencer' 
                           AND mensaje LIKE ? 
                           AND estado = 'pendiente'";
            $existe = $this->db->select($sqlVerificar, ["%{$producto['nombre']}%"]);

            if ($existe[0]['count'] == 0) {
                $fechaVenc = date('d/m/Y', strtotime($producto['fecha_vencimiento']));
                $mensaje = "Producto próximo a vencer: {$producto['nombre']} - Vence: {$fechaVenc}";
                $this->crear('proximo_vencer', $mensaje);
            }
        }

        return count($productosVencimiento);
    }

    /**
     * Verifica si hay pocas ventas en el día
     */
    public function verificarVentasDelDia()
    {
        try {
            // Verificar si existe la tabla ventas
            $sql = "SHOW TABLES LIKE 'ventas'";
            $tabla = $this->db->select($sql);

            if (empty($tabla)) {
                return 0; // No hay tabla de ventas
            }

            $sql = "SELECT COUNT(*) as total_ventas, COALESCE(SUM(total), 0) as monto_total
                    FROM ventas 
                    WHERE DATE(fecha_venta) = CURDATE()";
            $ventasHoy = $this->db->select($sql);

            $totalVentas = $ventasHoy[0]['total_ventas'];
            $montoTotal = $ventasHoy[0]['monto_total'];

            // Generar alerta si hay menos de 3 ventas en el día después de las 2 PM
            $horaActual = date('H');
            if ($horaActual >= 14 && $totalVentas < 3) {
                $sqlVerificar = "SELECT COUNT(*) as count FROM alertas 
                               WHERE tipo = 'pocas_ventas' 
                               AND DATE(fecha) = CURDATE()
                               AND estado = 'pendiente'";
                $existe = $this->db->select($sqlVerificar);

                if ($existe[0]['count'] == 0) {
                    $mensaje = "Pocas ventas hoy: Solo {$totalVentas} ventas realizadas. Monto total: S/ " . number_format($montoTotal, 2);
                    $this->crear('pocas_ventas', $mensaje);
                    return 1;
                }
            }

            return 0;
        } catch (Exception $e) {
            // Si hay error, simplemente retorna 0 y no genera alertas
            return 0;
        }
    }

    /**
     * Obtiene alertas por tipo
     */
    public function getAlertasPorTipo($tipo)
    {
        $sql = "SELECT * FROM alertas WHERE tipo = ? AND estado = 'pendiente' ORDER BY fecha DESC";
        return $this->db->select($sql, [$tipo]);
    }

    /**
     * Marca múltiples alertas como resueltas
     */
    public function marcarMultiplesResueltas($ids)
    {
        if (empty($ids)) {
            return false;
        }

        $placeholders = str_repeat('?,', count($ids) - 1) . '?';
        $sql = "UPDATE alertas SET estado = 'resuelta' WHERE id_alerta IN ({$placeholders})";
        return $this->db->execute($sql, $ids);
    }
}
