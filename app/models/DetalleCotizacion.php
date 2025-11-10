<?php

/**
 * Modelo DetalleCotizacion - Detalles de las cotizaciones
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla detallecotizacion existente
 */

class DetalleCotizacion extends Model
{
    protected $table = 'detallecotizacion';
    protected $primaryKey = 'id_detalle';
    protected $fillable = [
        'id_cotizacion',
        'id_producto',
        'cantidad',
        'precio_unitario',
        'subtotal'
    ];

    /**
     * Obtener detalles de una cotización
     */
    public function getByCotizacion($idCotizacion)
    {
        return $this->db->select("
            SELECT dc.*, p.nombre as producto_nombre, p.codigo_barras
            FROM detallecotizacion dc
            JOIN productos p ON dc.id_producto = p.id_producto
            WHERE dc.id_cotizacion = ?
            ORDER BY dc.id_detalle
        ", [$idCotizacion]);
    }

    /**
     * Agregar producto a cotización
     */
    public function agregarProducto($data)
    {
        $subtotal = $data['cantidad'] * $data['precio_unitario'];

        return $this->create([
            'id_cotizacion' => $data['id_cotizacion'],
            'id_producto' => $data['id_producto'],
            'cantidad' => $data['cantidad'],
            'precio_unitario' => $data['precio_unitario'],
            'subtotal' => $subtotal
        ]);
    }
}
