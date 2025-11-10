<?php

class MantenimientoSistema extends Model
{
    protected $table = 'mantenimientosistema';
    protected $primaryKey = 'id_parametro';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Obtiene todos los parámetros del sistema
     */
    public function getTodosParametros()
    {
        $sql = "SELECT * FROM mantenimientosistema ORDER BY clave";
        return $this->db->select($sql);
    }

    /**
     * Obtiene un parámetro por clave
     */
    public function getParametroPorClave($clave)
    {
        $sql = "SELECT * FROM mantenimientosistema WHERE clave = ?";
        $result = $this->db->select($sql, [$clave]);
        return !empty($result) ? $result[0] : null;
    }

    /**
     * Obtiene el valor de un parámetro por clave
     */
    public function getValorParametro($clave, $valorDefecto = null)
    {
        $parametro = $this->getParametroPorClave($clave);
        return $parametro ? $parametro['valor'] : $valorDefecto;
    }

    /**
     * Crea un nuevo parámetro
     */
    public function crearParametro($clave, $valor, $descripcion = null)
    {
        // Validar datos de entrada
        if (empty($clave)) {
            throw new Exception("La clave no puede estar vacía");
        }

        if (strlen($clave) > 100) {
            throw new Exception("La clave no puede exceder 100 caracteres");
        }

        if (strlen($valor) > 255) {
            throw new Exception("El valor no puede exceder 255 caracteres");
        }

        // Verificar que no exista la clave
        if ($this->getParametroPorClave($clave)) {
            throw new Exception("La clave '{$clave}' ya existe");
        }

        try {
            $sql = "INSERT INTO mantenimientosistema (clave, valor, descripcion) VALUES (?, ?, ?)";
            $result = $this->db->execute($sql, [$clave, $valor, $descripcion]);

            if ($result) {
                return $this->db->getConnection()->lastInsertId();
            } else {
                throw new Exception("Error al ejecutar la consulta de inserción");
            }
        } catch (Exception $e) {
            throw new Exception("Error al crear parámetro: " . $e->getMessage());
        }
    }

    /**
     * Actualiza un parámetro por ID
     */
    public function actualizarParametro($id, $datos)
    {
        if (empty($id) || !is_numeric($id)) {
            throw new Exception("ID de parámetro inválido");
        }

        $fields = [];
        $params = [];

        if (isset($datos['clave'])) {
            if (empty($datos['clave'])) {
                throw new Exception("La clave no puede estar vacía");
            }
            if (strlen($datos['clave']) > 100) {
                throw new Exception("La clave no puede exceder 100 caracteres");
            }
            $fields[] = "clave = ?";
            $params[] = $datos['clave'];
        }

        if (isset($datos['valor'])) {
            if (strlen($datos['valor']) > 255) {
                throw new Exception("El valor no puede exceder 255 caracteres");
            }
            $fields[] = "valor = ?";
            $params[] = $datos['valor'];
        }

        if (isset($datos['descripcion'])) {
            $fields[] = "descripcion = ?";
            $params[] = $datos['descripcion'];
        }

        if (empty($fields)) {
            return true; // No hay nada que actualizar
        }

        try {
            $params[] = $id;
            $sql = "UPDATE mantenimientosistema SET " . implode(", ", $fields) . " WHERE id_parametro = ?";

            return $this->db->execute($sql, $params);
        } catch (Exception $e) {
            throw new Exception("Error al actualizar parámetro: " . $e->getMessage());
        }
    }

    /**
     * Actualiza el valor de un parámetro por clave
     */
    public function actualizarValorPorClave($clave, $valor)
    {
        $sql = "UPDATE mantenimientosistema SET valor = ? WHERE clave = ?";
        return $this->db->execute($sql, [$valor, $clave]);
    }

    /**
     * Elimina un parámetro
     */
    public function eliminarParametro($id)
    {
        $sql = "DELETE FROM mantenimientosistema WHERE id_parametro = ?";
        return $this->db->execute($sql, [$id]);
    }

    /**
     * Busca parámetros por clave o descripción
     */
    public function buscarParametros($busqueda)
    {
        $sql = "SELECT * FROM mantenimientosistema 
                WHERE clave LIKE ? OR descripcion LIKE ? 
                ORDER BY clave";
        $termino = '%' . $busqueda . '%';
        return $this->db->select($sql, [$termino, $termino]);
    }

    /**
     * Inicializa parámetros predeterminados del sistema
     */
    public function inicializarParametrosPredeterminados()
    {
        $parametrosPredeterminados = [
            [
                'clave' => 'STOCK_MINIMO_GLOBAL',
                'valor' => '5',
                'descripcion' => 'Cantidad mínima de stock por defecto para todos los productos'
            ],
            [
                'clave' => 'DIAS_BACKUP_AUTOMATICO',
                'valor' => '7',
                'descripcion' => 'Cada cuántos días realizar backup automático del sistema'
            ],
            [
                'clave' => 'HORAS_SESION_MAXIMA',
                'valor' => '8',
                'descripcion' => 'Máximo de horas que puede durar una sesión de usuario'
            ],
            [
                'clave' => 'NOTIFICAR_STOCK_BAJO',
                'valor' => '1',
                'descripcion' => 'Enviar notificaciones cuando productos tengan stock bajo (1=Sí, 0=No)'
            ],
            [
                'clave' => 'PRECIO_IVA_INCLUIDO',
                'valor' => '1',
                'descripcion' => 'Los precios incluyen IVA por defecto (1=Sí, 0=No)'
            ],
            [
                'clave' => 'PORCENTAJE_IVA',
                'valor' => '18',
                'descripcion' => 'Porcentaje de IVA aplicable'
            ]
        ];

        $insertados = 0;
        foreach ($parametrosPredeterminados as $parametro) {
            try {
                if (!$this->getParametroPorClave($parametro['clave'])) {
                    $this->crearParametro($parametro['clave'], $parametro['valor'], $parametro['descripcion']);
                    $insertados++;
                }
            } catch (Exception $e) {
                // Continuar con el siguiente parámetro
            }
        }

        return $insertados;
    }
}
