<?php

/**
 * Modelo DetalleInventario - Detalles del conteo de inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla detalleinventario existente
 */

class DetalleInventario extends Model
{
    protected $table = 'detalleinventario';
    protected $primaryKey = 'id_detalle_inventario';
    protected $fillable = [
        'id_inventario',
        'id_producto',
        'cantidad_contada',
        'diferencia'
    ];

    /**
     * Obtener detalles de un conteo de inventario
     */
    public function getByInventario($idInventario)
    {
        return $this->db->select("
            SELECT di.*, p.nombre as producto_nombre, p.stock_actual
            FROM detalleinventario di
            JOIN productos p ON di.id_producto = p.id_producto
            WHERE di.id_inventario = ?
        ", [$idInventario]);
    }

    /**
     * Registrar conteo de producto
     */
    public function registrarConteo($data)
    {
        $diferencia = $data['cantidad_contada'] - $data['stock_sistema'];

        return $this->create([
            'id_inventario' => $data['id_inventario'],
            'id_producto' => $data['id_producto'],
            'cantidad_contada' => $data['cantidad_contada'],
            'diferencia' => $diferencia
        ]);
    }
}
