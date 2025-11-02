<?php
require_once __DIR__ . '/../core/Model.php';

class MantenimientoSistema extends Model
{
    protected $table = 'mantenimientosistema';
    protected $primaryKey = 'id_parametro';

    /**
     * Obtiene el valor de un parámetro del sistema
     */
    public function getParametro($clave, $valorDefecto = null)
    {
        $sql = "SELECT valor FROM mantenimientosistema WHERE clave = ?";
        $result = $this->db->fetch($sql, [$clave]);

        if (!empty($result)) {
            return $result[0]['valor'];
        }

        return $valorDefecto;
    }

    /**
     * Establece el valor de un parámetro del sistema
     */
    public function setParametro($clave, $valor, $descripcion = null)
    {
        // Verificar si el parámetro ya existe
        $existe = $this->getParametro($clave);

        if ($existe !== null) {
            // Actualizar parámetro existente
            $sql = "UPDATE mantenimientosistema SET valor = ?, descripcion = ? WHERE clave = ?";
            $params = [$valor, $descripcion, $clave];
        } else {
            // Crear nuevo parámetro
            $sql = "INSERT INTO mantenimientosistema (clave, valor, descripcion) VALUES (?, ?, ?)";
            $params = [$clave, $valor, $descripcion];
        }

        return $this->db->execute($sql, $params);
    }

    /**
     * Obtiene todos los parámetros del sistema
     */
    public function getTodosParametros()
    {
        $sql = "SELECT * FROM mantenimientosistema ORDER BY clave ASC";
        return $this->db->fetch($sql);
    }

    /**
     * Elimina un parámetro del sistema
     */
    public function eliminarParametro($clave)
    {
        $sql = "DELETE FROM mantenimientosistema WHERE clave = ?";
        return $this->db->execute($sql, [$clave]);
    }

    /**
     * Obtiene parámetros relacionados con inventario
     */
    public function getParametrosInventario()
    {
        $parametros = [
            'stock_minimo_alerta' => $this->getParametro('stock_minimo_alerta', '5'),
            'dias_reporte_automatico' => $this->getParametro('dias_reporte_automatico', '30'),
            'email_notificaciones' => $this->getParametro('email_notificaciones', ''),
            'moneda_sistema' => $this->getParametro('moneda_sistema', 'PEN'),
            'iva_porcentaje' => $this->getParametro('iva_porcentaje', '18')
        ];

        return $parametros;
    }

    /**
     * Inicializa parámetros por defecto del sistema
     */
    public function inicializarParametrosDefecto()
    {
        $parametrosDefecto = [
            'stock_minimo_alerta' => ['valor' => '5', 'descripcion' => 'Cantidad mínima de stock para generar alertas'],
            'dias_reporte_automatico' => ['valor' => '30', 'descripcion' => 'Días para generar reportes automáticos'],
            'email_notificaciones' => ['valor' => '', 'descripcion' => 'Email principal para notificaciones del sistema'],
            'moneda_sistema' => ['valor' => 'PEN', 'descripcion' => 'Moneda principal del sistema'],
            'iva_porcentaje' => ['valor' => '18', 'descripcion' => 'Porcentaje de IVA/IGV aplicado'],
            'nombre_empresa' => ['valor' => 'Cruz Motor S.A.C.', 'descripcion' => 'Nombre de la empresa'],
            'direccion_empresa' => ['valor' => '', 'descripcion' => 'Dirección de la empresa'],
            'telefono_empresa' => ['valor' => '', 'descripcion' => 'Teléfono principal de la empresa'],
            'backup_automatico' => ['valor' => '1', 'descripcion' => '1 para activar backup automático, 0 para desactivar'],
            'dias_conservar_logs' => ['valor' => '90', 'descripcion' => 'Días para conservar logs del sistema']
        ];

        foreach ($parametrosDefecto as $clave => $config) {
            $existe = $this->getParametro($clave);
            if ($existe === null) {
                $this->setParametro($clave, $config['valor'], $config['descripcion']);
            }
        }

        return true;
    }
}
