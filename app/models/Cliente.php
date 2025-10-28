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
}
