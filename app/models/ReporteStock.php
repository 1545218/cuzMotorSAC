<?php

/**
 * Modelo ReporteStock - Reportes de estado de stock
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla reportestock existente
 */

class ReporteStock extends Model
{
    protected $table = 'reportestock';
    protected $primaryKey = 'id_reporte_stock';
    protected $fillable = [
        'id_reporte',
        'id_producto',
        'stock_actual',
        'stock_minimo',
        'estado_stock'
    ];

    /**
     * Generar reporte actual de stock
     */
    public function generarReporteStockActual()
    {
        return $this->db->select("
            SELECT p.nombre as producto,
                   p.codigo_barras,
                   p.stock_actual,
                   p.stock_minimo,
                   CASE 
                       WHEN p.stock_actual <= 0 THEN 'Sin Stock'
                       WHEN p.stock_actual <= p.stock_minimo THEN 'Stock Bajo'
                       WHEN p.stock_actual <= (p.stock_minimo * 1.5) THEN 'Stock Normal'
                       ELSE 'Stock Alto'
                   END as estado_stock
            FROM productos p
            WHERE p.estado = 'activo'
            ORDER BY p.stock_actual ASC
        ");
    }

    /**
     * Obtener productos con stock crítico
     */
    public function getStockCritico()
    {
        return $this->db->select("
            SELECT p.nombre as producto,
                   p.codigo_barras,
                   p.stock_actual,
                   p.stock_minimo
            FROM productos p
            WHERE p.stock_actual <= p.stock_minimo 
            AND p.estado = 'activo'
            ORDER BY (p.stock_actual - p.stock_minimo) ASC
        ");
    }
}
