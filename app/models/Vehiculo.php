<?php

/**
 * Modelo Vehiculo - Gestión de vehículos de clientes
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla vehiculoscliente existente
 */

class Vehiculo extends Model
{
    protected $table = 'vehiculoscliente';
    protected $primaryKey = 'id_vehiculo';
    protected $fillable = [
        'id_cliente',
        'placa',
        'marca',
        'modelo'
    ];

    /**
     * Obtener todos los vehículos con información del cliente
     */
    public function getAllWithClientes()
    {
        return $this->db->select(
            "SELECT v.*, 
                    COALESCE(c.nombres, c.nombre, '') as cliente_nombre,
                    COALESCE(c.apellidos, c.apellido, '') as cliente_apellido,
                    COALESCE(c.dni_ruc, c.numero_documento, '') as cliente_documento, 
                    c.telefono
             FROM vehiculoscliente v
             INNER JOIN clientes c ON v.id_cliente = c.id_cliente
             ORDER BY v.placa ASC"
        );
    }

    /**
     * Obtener vehículo por ID
     */
    public function getById($id)
    {
        return $this->db->selectOne(
            "SELECT v.*, 
                    COALESCE(c.nombres, c.nombre, '') as cliente_nombre,
                    COALESCE(c.apellidos, c.apellido, '') as cliente_apellido,
                    COALESCE(c.dni_ruc, c.numero_documento, '') as cliente_documento
             FROM vehiculoscliente v
             INNER JOIN clientes c ON v.id_cliente = c.id_cliente
             WHERE v.id_vehiculo = ?",
            [$id]
        );
    }

    /**
     * Crear nuevo vehículo
     */
    public function create($data)
    {
        try {
            return $this->db->execute(
                "INSERT INTO vehiculoscliente (id_cliente, placa, marca, modelo) 
                 VALUES (?, ?, ?, ?)",
                [
                    $data['id_cliente'],
                    $data['placa'],
                    $data['marca'],
                    $data['modelo']
                ]
            );
        } catch (Exception $e) {
            Logger::error('Error creando vehículo', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Actualizar vehículo
     */
    public function update($id, $data)
    {
        try {
            return $this->db->execute(
                "UPDATE vehiculoscliente 
                 SET id_cliente = ?, placa = ?, marca = ?, modelo = ?
                 WHERE id_vehiculo = ?",
                [
                    $data['id_cliente'],
                    $data['placa'],
                    $data['marca'],
                    $data['modelo'],
                    $id
                ]
            );
        } catch (Exception $e) {
            Logger::error('Error actualizando vehículo', [
                'error' => $e->getMessage(),
                'id' => $id,
                'data' => $data
            ]);
            return false;
        }
    }

    /**
     * Eliminar vehículo
     */
    public function delete($id)
    {
        try {
            // Verificar si el vehículo tiene órdenes de trabajo
            $ordenes = $this->db->selectOne(
                "SELECT COUNT(*) as total FROM ordenestrabajo WHERE id_vehiculo = ?",
                [$id]
            );

            if ($ordenes && $ordenes['total'] > 0) {
                Logger::warning('Intento de eliminar vehículo con órdenes', ['id' => $id]);
                return false;
            }

            return $this->db->execute(
                "DELETE FROM vehiculoscliente WHERE id_vehiculo = ?",
                [$id]
            );
        } catch (Exception $e) {
            Logger::error('Error eliminando vehículo', [
                'error' => $e->getMessage(),
                'id' => $id
            ]);
            return false;
        }
    }

    /**
     * Verificar si existe una placa
     */
    public function existePlaca($placa, $exceptoId = null)
    {
        $sql = "SELECT COUNT(*) as total FROM vehiculoscliente WHERE placa = ?";
        $params = [$placa];

        if ($exceptoId) {
            $sql .= " AND id_vehiculo != ?";
            $params[] = $exceptoId;
        }

        $result = $this->db->selectOne($sql, $params);
        return $result && $result['total'] > 0;
    }

    /**
     * Obtener vehículos de un cliente específico
     */
    public function getByCliente($id_cliente)
    {
        return $this->db->select(
            "SELECT id_vehiculo, placa, marca, modelo
             FROM vehiculoscliente 
             WHERE id_cliente = ?
             ORDER BY placa ASC",
            [$id_cliente]
        );
    }

    /**
     * Obtener todos los vehículos (método básico para compatibilidad)
     */
    public function getAll()
    {
        return $this->getAllWithClientes();
    }

    /**
     * Buscar vehículos por placa o marca
     */
    public function buscar($termino)
    {
        return $this->db->select(
            "SELECT v.*, 
                    COALESCE(c.nombres, c.nombre, '') as cliente_nombre,
                    COALESCE(c.apellidos, c.apellido, '') as cliente_apellido,
                    COALESCE(c.dni_ruc, c.numero_documento, '') as cliente_documento
             FROM vehiculoscliente v
             INNER JOIN clientes c ON v.id_cliente = c.id_cliente
             WHERE v.placa LIKE ? OR v.marca LIKE ? OR v.modelo LIKE ?
             ORDER BY v.placa ASC",
            ["%$termino%", "%$termino%", "%$termino%"]
        );
    }

    /**
     * Obtener estadísticas de vehículos
     */
    public function getEstadisticas()
    {
        $stats = [];

        // Total de vehículos
        $total = $this->db->selectOne("SELECT COUNT(*) as total FROM vehiculoscliente");
        $stats['total'] = $total ? $total['total'] : 0;

        // Vehículos por marca
        $stats['por_marca'] = $this->db->select(
            "SELECT marca, COUNT(*) as cantidad 
             FROM vehiculoscliente 
             WHERE marca IS NOT NULL AND marca != ''
             GROUP BY marca 
             ORDER BY cantidad DESC 
             LIMIT 10"
        );

        // Clientes con más vehículos
        $stats['clientes_con_mas_vehiculos'] = $this->db->select(
            "SELECT COALESCE(c.nombres, c.nombre, '') as nombre,
                    COALESCE(c.apellidos, c.apellido, '') as apellido,
                    COALESCE(c.dni_ruc, c.numero_documento, '') as documento,
                    COUNT(v.id_vehiculo) as cantidad_vehiculos
             FROM clientes c
             INNER JOIN vehiculoscliente v ON c.id_cliente = v.id_cliente
             GROUP BY c.id_cliente, c.nombres, c.nombre, c.apellidos, c.apellido
             ORDER BY cantidad_vehiculos DESC
             LIMIT 5"
        );

        return $stats;
    }
}
