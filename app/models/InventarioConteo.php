<?php
require_once __DIR__ . '/../core/Model.php';

class InventarioConteo extends Model
{
    protected $table = 'inventarioconteo';
    protected $primaryKey = 'id_inventario';

    /**
     * Obtiene todos los inventarios
     */
    public function getAll()
    {
        $sql = "SELECT * FROM inventarioconteo ORDER BY fecha DESC";
        return $this->db->select($sql);
    }

    /**
     * Inicia un nuevo conteo de inventario
     */
    public function iniciarConteo($idUsuario, $observaciones = null)
    {
        $sql = "INSERT INTO inventarioconteo (fecha, id_usuario, observaciones) 
                VALUES (NOW(), ?, ?)";
        $result = $this->db->execute($sql, [$idUsuario, $observaciones]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Agrega detalle de producto al conteo
     */
    public function agregarDetalle($idInventario, $idProducto, $stockFisico)
    {
        // Obtener stock actual del sistema
        $sqlStock = "SELECT stock_actual FROM productos WHERE id_producto = ?";
        $resultStock = $this->db->selectOne($sqlStock, [$idProducto]);
        $stockSistema = $resultStock['stock_actual'] ?? 0;

        $diferencia = $stockFisico - $stockSistema;

        $sql = "INSERT INTO detalleinventario (id_inventario, id_producto, stock_fisico, diferencia) 
                VALUES (?, ?, ?, ?)";

        return $this->db->execute($sql, [$idInventario, $idProducto, $stockFisico, $diferencia]);
    }

    /**
     * Obtiene detalles de un conteo
     */
    public function getDetalles($idInventario)
    {
        $sql = "SELECT di.*, p.nombre as producto_nombre, p.stock_actual as stock_sistema,
                       p.codigo_barras, c.nombre as categoria_nombre, m.nombre as marca_nombre
                FROM detalleinventario di
                INNER JOIN productos p ON di.id_producto = p.id_producto
                LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
                LEFT JOIN marcas m ON p.id_marca = m.id_marca
                WHERE di.id_inventario = ?
                ORDER BY p.nombre";

        return $this->db->select($sql, [$idInventario]);
    }

    /**
     * Obtiene lista de conteos
     */
    public function getListaConteos($limite = 50, $offset = 0)
    {
        $sql = "SELECT ic.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido,
                       COUNT(di.id_detalle) as total_productos,
                       SUM(CASE WHEN di.diferencia != 0 THEN 1 ELSE 0 END) as productos_con_diferencia
                FROM inventarioconteo ic
                LEFT JOIN usuarios u ON ic.id_usuario = u.id_usuario
                LEFT JOIN detalleinventario di ON ic.id_inventario = di.id_inventario
                GROUP BY ic.id_inventario
                ORDER BY ic.fecha DESC
                LIMIT ? OFFSET ?";

        return $this->db->select($sql, [$limite, $offset]);
    }

    /**
     * Aplica ajustes del conteo al stock
     */
    public function aplicarAjustes($idInventario, $idUsuario)
    {
        try {
            $detalles = $this->getDetalles($idInventario);
            $ajustesAplicados = 0;

            foreach ($detalles as $detalle) {
                if ($detalle['diferencia'] != 0) {
                    // Actualizar stock en productos
                    $sqlUpdate = "UPDATE productos SET stock_actual = ? WHERE id_producto = ?";
                    $this->db->execute($sqlUpdate, [$detalle['stock_fisico'], $detalle['id_producto']]);

                    // Registrar movimiento en registrosstock
                    $tipo = $detalle['diferencia'] > 0 ? 'entrada' : 'salida';
                    $cantidad = abs($detalle['diferencia']);

                    $sqlMovimiento = "INSERT INTO registrosstock (id_producto, tipo, cantidad, fecha, origen, referencia_id, id_usuario) 
                                     VALUES (?, ?, ?, NOW(), 'inventario_fisico', ?, ?)";
                    $this->db->execute($sqlMovimiento, [$detalle['id_producto'], $tipo, $cantidad, $idInventario, $idUsuario]);

                    // Registrar en ajustesinventario
                    $tipoAjuste = $detalle['diferencia'] > 0 ? 'aumento' : 'disminucion';
                    $motivo = "Ajuste por inventario físico - Diferencia: {$detalle['diferencia']}";

                    $sqlAjuste = "INSERT INTO ajustesinventario (id_producto, tipo, cantidad, motivo, fecha, id_usuario) 
                                 VALUES (?, ?, ?, ?, NOW(), ?)";
                    $this->db->execute($sqlAjuste, [$detalle['id_producto'], $tipoAjuste, $cantidad, $motivo, $idUsuario]);

                    $ajustesAplicados++;
                }
            }

            return [
                'success' => true,
                'ajustes_aplicados' => $ajustesAplicados,
                'total_productos' => count($detalles)
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Obtiene resumen de un conteo
     */
    public function getResumen($idInventario)
    {
        $sql = "SELECT 
                    COUNT(*) as total_productos,
                    SUM(CASE WHEN diferencia > 0 THEN 1 ELSE 0 END) as productos_sobrantes,
                    SUM(CASE WHEN diferencia < 0 THEN 1 ELSE 0 END) as productos_faltantes,
                    SUM(CASE WHEN diferencia = 0 THEN 1 ELSE 0 END) as productos_coinciden,
                    SUM(diferencia) as diferencia_total,
                    SUM(ABS(diferencia)) as diferencia_absoluta
                FROM detalleinventario 
                WHERE id_inventario = ?";

        $result = $this->db->selectOne($sql, [$idInventario]);
        return $result ?? [];
    }

    /**
     * Elimina un conteo y sus detalles
     */
    public function eliminarConteo($idInventario)
    {
        try {
            // Eliminar detalles primero
            $this->db->execute("DELETE FROM detalleinventario WHERE id_inventario = ?", [$idInventario]);

            // Eliminar conteo principal
            return $this->delete($idInventario);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Obtiene estadísticas de conteos
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total_conteos,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as conteos_hoy,
                    SUM(CASE WHEN DATE(fecha) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as conteos_mes,
                    MAX(fecha) as ultimo_conteo,
                    COUNT(DISTINCT id_usuario) as usuarios_diferentes
                FROM inventarioconteo";

        $result = $this->db->selectOne($sql);
        return $result ?? [];
    }

    /**
     * Obtiene un conteo con información del usuario
     */
    public function getConteoConUsuario($idInventario)
    {
        $sql = "SELECT ic.*, u.nombre as usuario_nombre, u.apellido as usuario_apellido
                FROM inventarioconteo ic
                LEFT JOIN usuarios u ON ic.id_usuario = u.id_usuario
                WHERE ic.id_inventario = ?";

        return $this->db->selectOne($sql, [$idInventario]);
    }
}
