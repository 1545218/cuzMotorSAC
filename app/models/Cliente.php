<?php

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $fillable = [
        'nombres',
        'apellidos',
        'nombre',
        'apellido',
        'dni_ruc',
        'numero_documento',
        'telefono',
        'correo',
        'email',
        'direccion',
        'estado',
        'tipo_documento',
        'distrito',
        'provincia',
        'departamento',
        'fecha_nacimiento'
    ];

    public function getActive()
    {
        return $this->db->select("SELECT * FROM {$this->table} WHERE estado = 'activo' ORDER BY nombres ASC, nombre ASC");
    }

    public function obtenerPorId($id)
    {
        return $this->find($id);
    }

    public function getNombreCompleto($cliente)
    {
        if (is_array($cliente)) {
            // Priorizar campos más específicos
            $nombre = '';
            if (!empty($cliente['nombres']) && !empty($cliente['apellidos'])) {
                $nombre = trim($cliente['nombres'] . ' ' . $cliente['apellidos']);
            } elseif (!empty($cliente['nombre']) && !empty($cliente['apellido'])) {
                $nombre = trim($cliente['nombre'] . ' ' . $cliente['apellido']);
            } elseif (!empty($cliente['nombres'])) {
                $nombre = trim($cliente['nombres']);
            } elseif (!empty($cliente['nombre'])) {
                $nombre = trim($cliente['nombre']);
            }
            return $nombre;
        }
        return '';
    }

    public function getParaSelect()
    {
        $clientes = $this->getActive();
        $result = [];

        foreach ($clientes as $cliente) {
            $documento = $cliente['dni_ruc'] ?? $cliente['numero_documento'] ?? '';
            $result[] = [
                'id' => $cliente['id_cliente'],
                'text' => $this->getNombreCompleto($cliente),
                'documento' => $documento
            ];
        }

        return $result;
    }

    /**
     * Obtiene los vehículos de un cliente
     */
    public function getVehiculos($idCliente)
    {
        $sql = "SELECT * FROM vehiculoscliente WHERE id_cliente = ? ORDER BY placa ASC";
        return $this->db->select($sql, [$idCliente]);
    }

    /**
     * Agrega un vehículo a un cliente
     */
    public function agregarVehiculo($idCliente, $placa, $marca, $modelo)
    {
        $sql = "INSERT INTO vehiculoscliente (id_cliente, placa, marca, modelo) VALUES (?, ?, ?, ?)";
        return $this->db->execute($sql, [$idCliente, $placa, $marca, $modelo]);
    }

    /**
     * Elimina un vehículo de un cliente
     */
    public function eliminarVehiculo($idVehiculo)
    {
        $sql = "DELETE FROM vehiculoscliente WHERE id_vehiculo = ?";
        return $this->db->execute($sql, [$idVehiculo]);
    }

    /**
     * Actualiza un vehículo
     */
    public function actualizarVehiculo($idVehiculo, $placa, $marca, $modelo)
    {
        $sql = "UPDATE vehiculoscliente SET placa = ?, marca = ?, modelo = ? WHERE id_vehiculo = ?";
        return $this->db->execute($sql, [$placa, $marca, $modelo, $idVehiculo]);
    }

    /**
     * Obtiene un cliente con sus vehículos
     */
    public function getConVehiculos($idCliente)
    {
        $cliente = $this->find($idCliente);
        if ($cliente) {
            $cliente['vehiculos'] = $this->getVehiculos($idCliente);
        }
        return $cliente;
    }
}
