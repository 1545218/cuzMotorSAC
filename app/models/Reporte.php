<?php
require_once __DIR__ . '/../core/Model.php';

class Reporte extends Model
{
    protected $table = 'reportes';
    protected $primaryKey = 'id_reporte';

    /**
     * Crea un nuevo reporte
     */
    public function crear($tipo, $periodo, $idUsuario)
    {
        $sql = "INSERT INTO reportes (tipo, periodo, fecha_generado, id_usuario) 
                VALUES (?, ?, NOW(), ?)";
        $result = $this->db->execute($sql, [$tipo, $periodo, $idUsuario]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Genera reporte de stock
     */
    public function generarReporteStock($idUsuario, $periodo = null)
    {
        $idReporte = $this->crear('stock', $periodo ?? date('Y-m'), $idUsuario);

        if ($idReporte) {
            // Insertar datos de stock actual
            $sql = "INSERT INTO reportestock (id_reporte, id_producto, stock_actual)
                    SELECT ?, id_producto, stock_actual
                    FROM productos
                    WHERE estado = 'activo'";

            $this->db->execute($sql, [$idReporte]);
        }

        return $idReporte;
    }

    /**
     * Genera reporte de movimientos
     */
    public function generarReporteMovimientos($idUsuario, $fechaInicio, $fechaFin)
    {
        $periodo = date('Y-m', strtotime($fechaInicio)) . '_' . date('Y-m', strtotime($fechaFin));
        $idReporte = $this->crear('movimientos', $periodo, $idUsuario);

        if ($idReporte) {
            $sql = "INSERT INTO reportemovimientos (id_reporte, tipo, id_producto, cantidad, fecha)
                    SELECT ?, tipo, id_producto, cantidad, fecha
                    FROM registrosstock
                    WHERE fecha BETWEEN ? AND ?";

            $this->db->execute($sql, [$idReporte, $fechaInicio, $fechaFin]);
        }

        return $idReporte;
    }

    /**
     * Genera reporte de consumo
     */
    public function generarReporteConsumo($idUsuario, $fechaInicio, $fechaFin)
    {
        $periodo = date('Y-m', strtotime($fechaInicio)) . '_' . date('Y-m', strtotime($fechaFin));
        $idReporte = $this->crear('consumo', $periodo, $idUsuario);

        if ($idReporte) {
            $sql = "INSERT INTO reporteconsumo (id_reporte, id_producto, cantidad_total)
                    SELECT ?, id_producto, SUM(cantidad) as cantidad_total
                    FROM registrosstock
                    WHERE tipo = 'salida' AND fecha BETWEEN ? AND ?
                    GROUP BY id_producto";

            $this->db->execute($sql, [$idReporte, $fechaInicio, $fechaFin]);
        }

        return $idReporte;
    }

    /**
     * Obtiene datos de reporte de stock
     */
    public function getDatosReporteStock($idReporte)
    {
        $sql = "SELECT rs.*, p.nombre as producto_nombre, p.precio_unitario,
                       c.nombre as categoria_nombre, m.nombre as marca_nombre
                FROM reportestock rs
                INNER JOIN productos p ON rs.id_producto = p.id_producto
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
                LEFT JOIN marcas m ON p.id_marca = m.id_marca
                WHERE rs.id_reporte = ?
                ORDER BY p.nombre";

        return $this->db->select($sql, [$idReporte]);
    }

    /**
     * Obtiene datos de reporte de movimientos
     */
    public function getDatosReporteMovimientos($idReporte)
    {
        $sql = "SELECT rm.*, p.nombre as producto_nombre
                FROM reportemovimientos rm
                INNER JOIN productos p ON rm.id_producto = p.id_producto
                WHERE rm.id_reporte = ?
                ORDER BY rm.fecha DESC";

        return $this->db->select($sql, [$idReporte]);
    }

    /**
     * Obtiene datos de reporte de consumo
     */
    public function getDatosReporteConsumo($idReporte)
    {
        $sql = "SELECT rc.*, p.nombre as producto_nombre, p.precio_unitario,
                       (rc.cantidad_total * p.precio_unitario) as valor_total
                FROM reporteconsumo rc
                INNER JOIN productos p ON rc.id_producto = p.id_producto
                WHERE rc.id_reporte = ?
                ORDER BY rc.cantidad_total DESC";

        return $this->db->select($sql, [$idReporte]);
    }

    /**
     * Obtiene lista de reportes
     */
    public function getListaReportes($limite = 50, $offset = 0, $filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['tipo'])) {
            $where[] = "r.tipo = ?";
            $params[] = $filtros['tipo'];
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
            $params[] = "%{$filtros['usuario']}%";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(r.fecha_generado) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(r.fecha_generado) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT r.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM reportes r
                LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
                {$whereClause}
                ORDER BY r.fecha_generado DESC
                LIMIT ? OFFSET ?";

        $params[] = $limite;
        $params[] = $offset;

        return $this->db->select($sql, $params);
    }

    /**
     * Elimina un reporte y sus datos relacionados
     */
    public function eliminar($idReporte)
    {
        try {
            // Obtener tipo de reporte
            $reporte = $this->find($idReporte);
            if (!$reporte) return false;

            // Eliminar datos según tipo
            switch ($reporte['tipo']) {
                case 'stock':
                    $this->db->execute("DELETE FROM reportestock WHERE id_reporte = ?", [$idReporte]);
                    break;
                case 'movimientos':
                    $this->db->execute("DELETE FROM reportemovimientos WHERE id_reporte = ?", [$idReporte]);
                    break;
                case 'consumo':
                    $this->db->execute("DELETE FROM reporteconsumo WHERE id_reporte = ?", [$idReporte]);
                    break;
            }

            // Eliminar reporte principal
            return $this->delete($idReporte);
        } catch (Exception $e) {
            return false;
        }
    }
}
