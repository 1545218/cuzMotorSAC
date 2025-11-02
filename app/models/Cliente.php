<?php

class Cliente extends Model
{
    protected $table = 'clientes';
    protected $primaryKey = 'id_cliente';
    protected $fillable = [
        'nombre',
        'apellido',
        'email',
        'telefono',
        'estado',
        'numero_documento',
        'tipo_documento',
        'direccion'
    ];

    public function getActive()
    {
        return $this->where("estado = ?", ['activo'], 'nombre ASC');
    }

    public function obtenerPorId($id)
    {
        return $this->find($id);
    }

    public function getNombreCompleto($cliente)
    {
        if (is_array($cliente)) {
            return trim($cliente['nombre'] . ' ' . $cliente['apellido']);
        }
        return '';
    }

    public function getParaSelect()
    {
        $clientes = $this->getActive();
        $result = [];

        foreach ($clientes as $cliente) {
            $result[] = [
                'id' => $cliente['id_cliente'],
                'text' => $this->getNombreCompleto($cliente),
                'documento' => $cliente['numero_documento'] ?? ''
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
        return $this->db->fetch($sql, [$idCliente]);
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
