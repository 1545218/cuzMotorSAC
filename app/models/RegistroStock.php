<?php

/**
 * Modelo RegistroStock - Registro histórico de movimientos de stock
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla registrosstock existente
 */

class RegistroStock extends Model
{
    protected $table = 'registrosstock';
    protected $primaryKey = 'id_registro';
    protected $fillable = [
        'id_producto',
        'tipo',
        'cantidad',
        'fecha',
        'origen',
        'referencia_id',
        'id_usuario'
    ];

    // Tipos de movimiento
    const TIPO_ENTRADA = 'entrada';
    const TIPO_SALIDA = 'salida';
    const TIPO_AJUSTE = 'ajuste';

    /**
     * Registrar movimiento de stock automáticamente
     */
    public static function registrarMovimiento($idProducto, $tipo, $cantidad, $origen = null, $referenciaId = null)
    {
        try {
            $registro = new self();

            return $registro->create([
                'id_producto' => $idProducto,
                'tipo' => $tipo,
                'cantidad' => abs($cantidad), // Siempre positivo, el tipo indica la dirección
                'fecha' => date('Y-m-d H:i:s'),
                'origen' => $origen,
                'referencia_id' => $referenciaId,
                'id_usuario' => $_SESSION['user_id'] ?? null
            ]);
        } catch (Exception $e) {
            Logger::error('Error al registrar movimiento de stock', [
                'error' => $e->getMessage(),
                'producto' => $idProducto,
                'tipo' => $tipo,
                'cantidad' => $cantidad
            ]);

            // No interrumpir el proceso principal si falla el registro
            return false;
        }
    }

    /**
     * Obtener historial de un producto
     */
    public function getHistorialProducto($idProducto, $limite = 50)
    {
        return $this->db->select("
            SELECT r.*, 
                   p.nombre as producto_nombre,
                   u.nombre as usuario_nombre
            FROM registrosstock r
            LEFT JOIN productos p ON r.id_producto = p.id_producto
            LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE r.id_producto = ?
            ORDER BY r.fecha DESC
            LIMIT ?
        ", [$idProducto, $limite]);
    }

    /**
     * Obtener movimientos por rango de fechas
     */
    public function getMovimientosPorFecha($fechaInicio, $fechaFin, $limite = 100)
    {
        return $this->db->select("
            SELECT r.*, 
                   p.nombre as producto_nombre,
                   p.codigo_barras,
                   u.nombre as usuario_nombre
            FROM registrosstock r
            LEFT JOIN productos p ON r.id_producto = p.id_producto
            LEFT JOIN usuarios u ON r.id_usuario = u.id_usuario
            WHERE DATE(r.fecha) BETWEEN ? AND ?
            ORDER BY r.fecha DESC
            LIMIT ?
        ", [$fechaInicio, $fechaFin, $limite]);
    }

    /**
     * Obtener estadísticas de movimientos
     */
    public function getEstadisticasMovimientos()
    {
        return $this->db->select("
            SELECT 
                tipo,
                COUNT(*) as total_movimientos,
                SUM(cantidad) as cantidad_total,
                AVG(cantidad) as cantidad_promedio
            FROM registrosstock
            GROUP BY tipo
        ");
    }
}
