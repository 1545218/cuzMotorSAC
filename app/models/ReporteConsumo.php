<?php

/**
 * Modelo ReporteConsumo - Reportes de consumo de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla reporteconsumo existente
 */

class ReporteConsumo extends Model
{
    protected $table = 'reporteconsumo';
    protected $primaryKey = 'id_reporte_consumo';
    protected $fillable = [
        'id_reporte',
        'id_producto',
        'cantidad_consumida',
        'costo_total'
    ];

    /**
     * Generar reporte de consumo por período
     */
    public function generarReporteConsumo($fechaInicio, $fechaFin)
    {
        return $this->db->select("
            SELECT p.nombre as producto,
                   SUM(sp.cantidad) as total_consumido,
                   AVG(p.precio_unitario) as precio_promedio,
                   SUM(sp.cantidad * p.precio_unitario) as costo_total
            FROM salidasproductos sp
            JOIN productos p ON sp.id_producto = p.id_producto
            WHERE DATE(sp.fecha) BETWEEN ? AND ?
            GROUP BY sp.id_producto, p.nombre
            ORDER BY total_consumido DESC
        ", [$fechaInicio, $fechaFin]);
    }
}
