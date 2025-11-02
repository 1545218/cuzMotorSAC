<?php
require_once __DIR__ . '/../core/Model.php';

class OrdenTrabajo extends Model
{
    protected $table = 'ordenestrabajo';
    protected $primaryKey = 'id_orden';

    /**
     * Obtiene todas las órdenes de trabajo con información de cliente y vehículo
     */
    public function getAll($filtros = [])
    {
        $where = [];
        $params = [];

        if (!empty($filtros['estado'])) {
            $where[] = "o.estado = ?";
            $params[] = $filtros['estado'];
        }

        if (!empty($filtros['cliente'])) {
            $where[] = "(c.nombre LIKE ? OR c.apellido LIKE ?)";
            $params[] = "%{$filtros['cliente']}%";
            $params[] = "%{$filtros['cliente']}%";
        }

        if (!empty($filtros['fecha_desde'])) {
            $where[] = "DATE(o.fecha) >= ?";
            $params[] = $filtros['fecha_desde'];
        }

        if (!empty($filtros['fecha_hasta'])) {
            $where[] = "DATE(o.fecha) <= ?";
            $params[] = $filtros['fecha_hasta'];
        }

        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

        $sql = "SELECT o.*, 
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                       c.telefono as cliente_telefono,
                       v.placa, v.marca, v.modelo
                FROM ordenestrabajo o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                {$whereClause}
                ORDER BY o.fecha DESC";

        return $this->db->fetch($sql, $params);
    }

    /**
     * Crea una nueva orden de trabajo
     */
    public function crear($idCliente, $idVehiculo = null, $observaciones = null)
    {
        $sql = "INSERT INTO ordenestrabajo (id_cliente, id_vehiculo, fecha, estado, observaciones) 
                VALUES (?, ?, NOW(), 'abierta', ?)";
        $result = $this->db->execute($sql, [$idCliente, $idVehiculo, $observaciones]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Actualiza el estado de una orden
     */
    public function cambiarEstado($idOrden, $nuevoEstado, $observaciones = null)
    {
        $sql = "UPDATE ordenestrabajo SET estado = ?, observaciones = ? WHERE id_orden = ?";
        return $this->db->execute($sql, [$nuevoEstado, $observaciones, $idOrden]);
    }

    /**
     * Obtiene órdenes por estado
     */
    public function getPorEstado($estado)
    {
        return $this->getAll(['estado' => $estado]);
    }

    /**
     * Obtiene estadísticas de órdenes
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN estado = 'abierta' THEN 1 ELSE 0 END) as abiertas,
                    SUM(CASE WHEN estado = 'en_proceso' THEN 1 ELSE 0 END) as en_proceso,
                    SUM(CASE WHEN estado = 'cerrada' THEN 1 ELSE 0 END) as cerradas,
                    SUM(CASE WHEN DATE(fecha) = CURDATE() THEN 1 ELSE 0 END) as hoy
                FROM ordenestrabajo";

        $result = $this->db->fetch($sql);
        return $result[0] ?? [];
    }

    /**
     * Obtiene orden completa por ID
     */
    public function getCompleta($idOrden)
    {
        $sql = "SELECT o.*, 
                       c.nombre as cliente_nombre, c.apellido as cliente_apellido,
                       c.telefono as cliente_telefono, c.email as cliente_email,
                       c.direccion as cliente_direccion,
                       v.placa, v.marca, v.modelo
                FROM ordenestrabajo o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                WHERE o.id_orden = ?";

        $result = $this->db->fetch($sql, [$idOrden]);
        return $result[0] ?? null;
    }

    /**
     * Obtiene órdenes del mes actual
     */
    public function getDelMes()
    {
        $sql = "SELECT o.*, c.nombre as cliente_nombre, c.apellido as cliente_apellido
                FROM ordenestrabajo o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                WHERE MONTH(o.fecha) = MONTH(CURDATE()) 
                AND YEAR(o.fecha) = YEAR(CURDATE())
                ORDER BY o.fecha DESC";

        return $this->db->fetch($sql);
    }
}
