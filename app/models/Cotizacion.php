<?php

class Cotizacion extends Model
{
    protected $table = 'cotizaciones';
    protected $primaryKey = 'id_cotizacion';
    protected $fillable = [
        'id_cliente',
        'fecha',
        'total',
        'estado',
        'observaciones'
    ];

    public function getAll()
    {
        return $this->db->select(
            "SELECT c.*, cl.nombre as cliente_nombre
             FROM cotizaciones c
             LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
             ORDER BY c.fecha DESC"
        );
    }

    public function getWithDetails($id)
    {
        return $this->db->selectOne(
            "SELECT c.*, cl.nombre as cliente_nombre
             FROM cotizaciones c
             LEFT JOIN clientes cl ON c.id_cliente = cl.id_cliente
             WHERE c.id_cotizacion = ?",
            [$id]
        );
    }

    public function getStats()
    {
        return [
            'total' => $this->count(),
            'pendientes' => $this->count("estado = 'pendiente'"),
            'aprobadas' => $this->count("estado = 'aprobada'"),
            'rechazadas' => $this->count("estado = 'rechazada'")
        ];
    }

    /**
     * Genera número de cotización
     */
    public function generateNumber()
    {
        $year = date('Y');
        $count = $this->count() + 1;
        return sprintf("COT-%s-%04d", $year, $count);
    }

    /**
     * Crea una cotización y sus detalles dentro de una transacción
     * @param array $data  Datos de la cotización (id_cliente, fecha, total, estado, observaciones)
     * @param array $productos  Array de productos con keys: id_producto, cantidad, precio_unitario, descripcion_servicio (opcional)
     * @return mixed id de la cotización creada o false
     */
    public function create($data, $productos = [])
    {
        try {
            $this->db->beginTransaction();

            // Insertar cotización
            $sql = "INSERT INTO {$this->table} (id_cliente, fecha, total, estado, observaciones) VALUES (?, ?, ?, ?, ?)";
            $params = [
                $data['id_cliente'],
                $data['fecha'],
                $data['total'],
                $data['estado'],
                $data['observaciones'] ?? ''
            ];

            $cotizacionId = $this->db->insert($sql, $params);
            if (!$cotizacionId) {
                $this->db->rollback();
                return false;
            }

            // Insertar detalles
            foreach ($productos as $prod) {
                $id_producto = $prod['id_producto'] ?? null;
                $cantidad = $prod['cantidad'] ?? 0;
                $precio = $prod['precio_unitario'] ?? ($prod['precio'] ?? 0);
                $descripcion = $prod['descripcion_servicio'] ?? ($prod['descripcion'] ?? null);

                $sqlDet = "INSERT INTO detallecotizacion (id_cotizacion, id_producto, descripcion_servicio, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)";
                $detParams = [$cotizacionId, $id_producto, $descripcion, $cantidad, $precio];
                $this->db->insert($sqlDet, $detParams);
            }

            $this->db->commit();
            return $cotizacionId;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            Logger::error("Error creando cotización en modelo: " . $e->getMessage(), ['data' => $data, 'productos' => $productos]);
            return false;
        }
    }

    /**
     * Cambia el estado de una cotización
     */
    public function changeStatus($id, $estado, $userId = null)
    {
        return $this->update($id, ['estado' => $estado]);
    }

    /**
     * Duplica una cotización (simplificado)
     */
    public function duplicate($id, $userId = null)
    {
        $cotizacion = $this->find($id);
        if (!$cotizacion) {
            return false;
        }

        $nuevaCotizacion = [
            'id_cliente' => $cotizacion['id_cliente'],
            'fecha' => date('Y-m-d'),
            'total' => $cotizacion['total'],
            'estado' => 'pendiente',
            'observaciones' => 'Copia de cotización #' . $id
        ];

        return $this->create($nuevaCotizacion);
    }

    /**
     * Actualiza una cotización y reemplaza sus detalles (transaccional)
     */
    public function updateWithDetails($id, $data, $productos = [])
    {
        try {
            $this->db->beginTransaction();

            // Update cotizacion
            $updateParams = [];
            $sets = [];
            foreach ($data as $k => $v) {
                $sets[] = "{$k} = ?";
                $updateParams[] = $v;
            }
            $updateParams[] = $id;

            $sql = "UPDATE {$this->table} SET " . implode(', ', $sets) . " WHERE {$this->primaryKey} = ?";
            $this->db->update($sql, $updateParams);

            // Borrar detalles antiguos
            $this->db->delete("DELETE FROM detallecotizacion WHERE id_cotizacion = ?", [$id]);

            // Insertar nuevos detalles
            foreach ($productos as $prod) {
                $id_producto = $prod['id_producto'] ?? null;
                $cantidad = $prod['cantidad'] ?? 0;
                $precio = $prod['precio_unitario'] ?? ($prod['precio'] ?? 0);
                $descripcion = $prod['descripcion_servicio'] ?? ($prod['descripcion'] ?? null);

                $sqlDet = "INSERT INTO detallecotizacion (id_cotizacion, id_producto, descripcion_servicio, cantidad, precio_unitario) VALUES (?, ?, ?, ?, ?)";
                $detParams = [$id, $id_producto, $descripcion, $cantidad, $precio];
                $this->db->insert($sqlDet, $detParams);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollback();
            }
            Logger::error("Error actualizando cotización en modelo: " . $e->getMessage(), ['id' => $id, 'data' => $data, 'productos' => $productos]);
            return false;
        }
    }

    /**
     * Obtiene cotizaciones vencidas (simplificado)
     */
    public function getVencidas()
    {
        // Como no tenemos fecha_vencimiento, retornamos array vacío
        return [];
    }

    /**
     * Método para generar PDF
     */
    public function generatePDF($id)
    {
        $cotizacion = $this->getWithDetails($id);
        if (!$cotizacion) {
            return false;
        }

        // Por ahora retorna la información básica
        // Se puede implementar la generación real de PDF después
        return [
            'success' => true,
            'data' => $cotizacion,
            'filename' => 'cotizacion_' . $id . '.pdf'
        ];
    }

    /**
     * Obtener productos de una cotización
     */
    public function getProductos($id_cotizacion)
    {
        return $this->db->select(
            "SELECT dc.*, p.codigo_barras AS codigo, p.nombre
             FROM detallecotizacion dc
             LEFT JOIN productos p ON dc.id_producto = p.id_producto
             WHERE dc.id_cotizacion = ?
             ORDER BY dc.id_detalle",
            [$id_cotizacion]
        );
    }
}
