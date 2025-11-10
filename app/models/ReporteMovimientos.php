<?php

/**
 * Modelo ReporteMovimientos - Reportes de movimientos de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla reportemovimientos existente
 */

class ReporteMovimientos extends Model
{
    protected $table = 'reportemovimientos';
    protected $primaryKey = 'id_reporte_movimientos';
    protected $fillable = [
        'id_reporte',
        'id_producto',
        'tipo_movimiento',
        'cantidad',
        'fecha_movimiento'
    ];

    /**
     * Generar reporte de movimientos por período
     */
    public function generarReporteMovimientos($fechaInicio, $fechaFin)
    {
        return $this->db->select("
            SELECT p.nombre as producto,
                   rs.tipo,
                   SUM(rs.cantidad) as total_cantidad,
                   COUNT(*) as total_movimientos
            FROM registrosstock rs
            JOIN productos p ON rs.id_producto = p.id_producto  
            WHERE DATE(rs.fecha) BETWEEN ? AND ?
            GROUP BY rs.id_producto, p.nombre, rs.tipo
            ORDER BY p.nombre, rs.tipo
        ", [$fechaInicio, $fechaFin]);
    }
}
