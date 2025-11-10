<?php

/**
 * Modelo OrdenTrabajo - Gestión de órdenes de trabajo
 * Sistema de Inventario Cruz Motor S.A.C.
 * Conecta clientes, vehículos y servicios
 */

class OrdenTrabajo extends Model
{
    protected $table = 'ordenestrabajo';
    protected $primaryKey = 'id_orden';
    protected $fillable = [
        'id_cliente',
        'id_vehiculo',
        'fecha',
        'estado',
        'observaciones'
    ];

    /**
     * Estados válidos para órdenes
     */
    const ESTADO_ABIERTA = 'abierta';
    const ESTADO_EN_PROCESO = 'en_proceso';
    const ESTADO_CERRADA = 'cerrada';

    /**
     * Obtiene todas las órdenes con información de cliente y vehículo
     */
    public function getAllWithDetails()
    {
        $sql = "SELECT 
                    o.id_orden,
                    o.fecha,
                    o.estado,
                    o.observaciones,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    c.telefono as cliente_telefono,
                    v.placa as vehiculo_placa,
                    v.marca as vehiculo_marca,
                    v.modelo as vehiculo_modelo
                FROM {$this->table} o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                ORDER BY o.fecha DESC";

        return $this->db->select($sql);
    }

    /**
     * Obtiene una orden específica con todos los detalles
     */
    public function getByIdWithDetails($id)
    {
        $sql = "SELECT 
                    o.*,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    c.email as cliente_email,
                    c.telefono as cliente_telefono,
                    c.numero_documento as cliente_documento,
                    v.placa as vehiculo_placa,
                    v.marca as vehiculo_marca,
                    v.modelo as vehiculo_modelo
                FROM {$this->table} o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                WHERE o.id_orden = ?";

        $result = $this->db->select($sql, [$id]);
        return $result ? $result[0] : null;
    }

    /**
     * Obtiene órdenes por cliente
     */
    public function getByCliente($id_cliente)
    {
        return $this->where("id_cliente = ?", [$id_cliente], 'fecha DESC');
    }

    /**
     * Obtiene órdenes por vehículo
     */
    public function getByVehiculo($id_vehiculo)
    {
        return $this->where("id_vehiculo = ?", [$id_vehiculo], 'fecha DESC');
    }

    /**
     * Obtiene órdenes por estado
     */
    public function getByEstado($estado)
    {
        return $this->where("estado = ?", [$estado], 'fecha DESC');
    }

    /**
     * Crea una nueva orden de trabajo
     */
    public function create($data)
    {
        try {
            error_log("=== OrdenTrabajo::create() INICIO ===");
            error_log("Datos recibidos: " . print_r($data, true));

            // Validar datos requeridos
            if (empty($data['id_cliente'])) {
                error_log("Error: id_cliente está vacío");
                throw new Exception('Cliente es requerido');
            }

            // Establecer valores por defecto
            $data['fecha'] = $data['fecha'] ?? date('Y-m-d H:i:s');
            $data['estado'] = $data['estado'] ?? self::ESTADO_ABIERTA;

            error_log("Datos después de defaults: " . print_r($data, true));

            $sql = "INSERT INTO {$this->table} (id_cliente, id_vehiculo, fecha, estado, observaciones) 
                    VALUES (?, ?, ?, ?, ?)";

            $params = [
                $data['id_cliente'],
                $data['id_vehiculo'] ?? null,
                $data['fecha'],
                $data['estado'],
                $data['observaciones'] ?? ''
            ];

            error_log("SQL: " . $sql);
            error_log("Parámetros: " . print_r($params, true));

            $result = $this->db->execute($sql, $params);

            error_log("Resultado execute(): " . ($result ? 'true' : 'false'));

            if ($result) {
                $lastId = $this->db->lastInsertId();
                error_log("Last insert ID: " . $lastId);

                Logger::info('Orden de trabajo creada', [
                    'id_cliente' => $data['id_cliente'],
                    'id_vehiculo' => $data['id_vehiculo'] ?? 'N/A'
                ]);

                error_log("=== OrdenTrabajo::create() FIN EXITOSO ===");
                return $lastId;
            }

            error_log("Execute falló, retornando false");
            error_log("=== OrdenTrabajo::create() FIN FALLIDO ===");
            return false;
        } catch (Exception $e) {
            error_log("EXCEPCIÓN en OrdenTrabajo::create(): " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            Logger::error('Error al crear orden de trabajo: ' . $e->getMessage(), $data);
            throw $e;
        }
    }

    /**
     * Actualiza una orden de trabajo
     */
    public function update($id, $data)
    {
        try {
            $sql = "UPDATE {$this->table} 
                    SET id_cliente = ?, id_vehiculo = ?, estado = ?, observaciones = ?
                    WHERE id_orden = ?";

            $params = [
                $data['id_cliente'],
                $data['id_vehiculo'] ?? null,
                $data['estado'] ?? self::ESTADO_ABIERTA,
                $data['observaciones'] ?? '',
                $id
            ];

            $result = $this->db->execute($sql, $params);

            if ($result) {
                Logger::info('Orden de trabajo actualizada', ['id' => $id]);
            }

            return $result;
        } catch (Exception $e) {
            Logger::error('Error al actualizar orden de trabajo: ' . $e->getMessage(), ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Cambia el estado de una orden
     */
    public function cambiarEstado($id, $estado)
    {
        $estados_validos = [self::ESTADO_ABIERTA, self::ESTADO_EN_PROCESO, self::ESTADO_CERRADA];

        if (!in_array($estado, $estados_validos)) {
            throw new Exception('Estado no válido');
        }

        $sql = "UPDATE {$this->table} SET estado = ? WHERE id_orden = ?";
        $result = $this->db->execute($sql, [$estado, $id]);

        if ($result) {
            Logger::info('Estado de orden cambiado', ['id' => $id, 'estado' => $estado]);
        }

        return $result;
    }

    /**
     * Elimina una orden de trabajo
     */
    public function delete($id)
    {
        try {
            $orden = $this->find($id);
            if (!$orden) {
                return false;
            }

            $sql = "DELETE FROM {$this->table} WHERE id_orden = ?";
            $result = $this->db->execute($sql, [$id]);

            if ($result) {
                Logger::info('Orden de trabajo eliminada', ['id' => $id]);
            }

            return $result;
        } catch (Exception $e) {
            Logger::error('Error al eliminar orden de trabajo: ' . $e->getMessage(), ['id' => $id]);
            throw $e;
        }
    }

    /**
     * Obtiene estadísticas básicas
     */
    public function getEstadisticas()
    {
        $sql = "SELECT 
                    estado,
                    COUNT(*) as cantidad
                FROM {$this->table}
                GROUP BY estado";

        return $this->db->select($sql);
    }

    /**
     * Obtiene órdenes recientes (últimos 30 días)
     */
    public function getRecientes($limite = 10)
    {
        $sql = "SELECT 
                    o.id_orden,
                    o.fecha,
                    o.estado,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    v.placa as vehiculo_placa
                FROM {$this->table} o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                WHERE o.fecha >= DATE_SUB(NOW(), INTERVAL 30 DAY)
                ORDER BY o.fecha DESC
                LIMIT ?";

        return $this->db->select($sql, [$limite]);
    }

    /**
     * Busca órdenes por término
     */
    public function buscar($termino)
    {
        $sql = "SELECT 
                    o.id_orden,
                    o.fecha,
                    o.estado,
                    c.nombre as cliente_nombre,
                    c.apellido as cliente_apellido,
                    v.placa as vehiculo_placa
                FROM {$this->table} o
                INNER JOIN clientes c ON o.id_cliente = c.id_cliente
                LEFT JOIN vehiculoscliente v ON o.id_vehiculo = v.id_vehiculo
                WHERE c.nombre LIKE ? OR c.apellido LIKE ? OR v.placa LIKE ?
                ORDER BY o.fecha DESC";

        $termino = "%{$termino}%";
        return $this->db->select($sql, [$termino, $termino, $termino]);
    }
}
