<?php
require_once __DIR__ . '/../core/Model.php';

class EntradaProducto extends Model
{
    protected $table = 'entradasproductos';
    protected $primaryKey = 'id_entrada';

    /**
     * Registra una entrada manual de producto
     */
    public function registrarEntrada($idProducto, $cantidad, $idUsuario, $observaciones = null)
    {
        try {
            // Registrar en entradasproductos
            $sql = "INSERT INTO entradasproductos (id_producto, cantidad, fecha, id_usuario, observaciones) 
                    VALUES (?, ?, NOW(), ?, ?)";
            $resultado = $this->db->execute($sql, [$idProducto, $cantidad, $idUsuario, $observaciones]);

            if ($resultado) {
                $idEntrada = $this->db->getConnection()->lastInsertId();

                // Actualizar stock en productos
                $sqlUpdate = "UPDATE productos SET stock_actual = stock_actual + ? WHERE id_producto = ?";
                $this->db->execute($sqlUpdate, [$cantidad, $idProducto]);

                // Registrar en registrosstock
                $sqlStock = "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) 
                           VALUES (?, 'entrada', ?, NOW(), 'entrada_manual', ?, ?)";
                $this->db->execute($sqlStock, [$idProducto, $cantidad, $idEntrada, $idUsuario]);

                return $idEntrada;
            }

            return false;
        } catch (Exception $e) {
            error_log("Error en registrarEntrada: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Obtiene lista de entradas con información de producto
     */
    public function getListaEntradas($filtros = [], $limite = 50, $offset = 0)
    {
        $where = [];
        $params = [];

        if (!empty($filtros['producto'])) {
            $where[] = "p.nombre LIKE ?";
            $params[] = "%{$filtros['producto']}%";
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
            $params[] = "%{$filtros['usuario']}%";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(e.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(e.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT e.*, p.nombre as producto_nombre, p.codigo_barras,
                       u.nombre as usuario_nombre, u.apellido as usuario_apellido,
                       c.nombre as categoria_nombre, m.nombre as marca_nombre
                FROM entradasproductos e
                INNER JOIN productos p ON e.id_producto = p.id_producto
                LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
                LEFT JOIN marcas m ON p.id_marca = m.id_marca
                {$whereClause}
                ORDER BY e.fecha DESC
                LIMIT ? OFFSET ?";

        $params[] = $limite;
        $params[] = $offset;

        return $this->db->select($sql, $params);
    }

    /**
     * Obtiene estadísticas de entradas
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_entradas,
                    SUM(cantidad) as cantidad_total,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as entradas_hoy,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN cantidad ELSE 0 END) as cantidad_hoy,
                    SUM(CASE WHEN DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as cantidad_semana,
                    COUNT(DISTINCT id_producto) as productos_diferentes
                FROM entradasproductos";

        $result = $this->db->selectOne($sql);
        return $result ?? [];
    }
}

class SalidaProducto extends Model
{
    protected $table = 'salidasproductos';
    protected $primaryKey = 'id_salida';

    /**
     * Registra una salida manual de producto
     */
    public function registrarSalida($idProducto, $cantidad, $destino, $idUsuario, $observaciones = null)
    {
        try {
            // Verificar stock disponible
            $sqlStock = "SELECT stock_actual FROM productos WHERE id_producto = ?";
            $resultStock = $this->db->selectOne($sqlStock, [$idProducto]);
            $stockActual = $resultStock['stock_actual'] ?? 0;

            if ($stockActual < $cantidad) {
                return [
                    'success' => false,
                    'error' => 'Stock insuficiente. Stock actual: ' . $stockActual
                ];
            }

            // Registrar en salidasproductos
            $sql = "INSERT INTO salidasproductos (id_producto, cantidad, destino, fecha, id_usuario, observaciones) 
                    VALUES (?, ?, ?, NOW(), ?, ?)";
            $resultado = $this->db->execute($sql, [$idProducto, $cantidad, $destino, $idUsuario, $observaciones]);

            if ($resultado) {
                $idSalida = $this->db->getConnection()->lastInsertId();

                // Actualizar stock en productos
                $sqlUpdate = "UPDATE productos SET stock_actual = stock_actual - ? WHERE id_producto = ?";
                $this->db->execute($sqlUpdate, [$cantidad, $idProducto]);

                // Registrar en registrosstock
                $sqlStock = "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) 
                           VALUES (?, 'salida', ?, NOW(), 'salida_manual', ?, ?)";
                $this->db->execute($sqlStock, [$idProducto, $cantidad, $idSalida, $idUsuario]);

                return [
                    'success' => true,
                    'id_salida' => $idSalida
                ];
            }

            return [
                'success' => false,
                'error' => 'Error al registrar la salida'
            ];
        } catch (Exception $e) {
            error_log("Error en registrarSalida: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Error interno: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene lista de salidas con información de producto
     */
    public function getListaSalidas($filtros = [], $limite = 50, $offset = 0)
    {
        $where = [];
        $params = [];

        if (!empty($filtros['producto'])) {
            $where[] = "p.nombre LIKE ?";
            $params[] = "%{$filtros['producto']}%";
        }

        if (!empty($filtros['destino'])) {
            $where[] = "s.destino LIKE ?";
            $params[] = "%{$filtros['destino']}%";
        }

        if (!empty($filtros['usuario'])) {
            $where[] = "(u.nombre LIKE ? OR u.apellido LIKE ?)";
            $params[] = "%{$filtros['usuario']}%";
            $params[] = "%{$filtros['usuario']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(s.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(s.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT s.*, p.nombre as producto_nombre, p.codigo_barras,
                       u.nombre as usuario_nombre, u.apellido as usuario_apellido,
                       c.nombre as categoria_nombre, m.nombre as marca_nombre
                FROM salidasproductos s
                INNER JOIN productos p ON s.id_producto = p.id_producto
                LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario
                LEFT JOIN subcategorias s_cat ON p.id_subcategoria = s_cat.id_subcategoria
                LEFT JOIN categorias c ON s_cat.id_categoria = c.id_categoria
                LEFT JOIN marcas m ON p.id_marca = m.id_marca
                {$whereClause}
                ORDER BY s.fecha DESC
                LIMIT ? OFFSET ?";

        $params[] = $limite;
        $params[] = $offset;

        return $this->db->select($sql, $params);
    }

    /**
     * Obtiene estadísticas de salidas
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_salidas,
                    SUM(cantidad) as cantidad_total,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as salidas_hoy,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN cantidad ELSE 0 END) as cantidad_hoy,
                    SUM(CASE WHEN DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) THEN cantidad ELSE 0 END) as cantidad_semana,
                    COUNT(DISTINCT id_producto) as productos_diferentes,
                    COUNT(DISTINCT destino) as destinos_diferentes
                FROM salidasproductos";

        $result = $this->db->selectOne($sql);
        return $result ?? [];
    }
}
