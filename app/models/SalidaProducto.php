<?php

/**
 * Modelo SalidaProducto - Gestión de salidas de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla salidasproductos existente
 */

class SalidaProducto extends Model
{
    protected $table = 'salidasproductos';
    protected $primaryKey = 'id_salida';
    protected $fillable = [
        'id_producto',
        'cantidad',
        'destino',
        'fecha',
        'id_usuario',
        'observaciones'
    ];

    /**
     * Obtener todas las salidas con información del producto
     */
    public function getAllWithProductos()
    {
        return $this->db->select("
            SELECT s.*, 
                   p.nombre as producto_nombre,
                   p.codigo_barras,
                   u.nombre as usuario_nombre
            FROM salidasproductos s 
            LEFT JOIN productos p ON s.id_producto = p.id_producto
            LEFT JOIN usuarios u ON s.id_usuario = u.id_usuario
            ORDER BY s.fecha DESC
        ");
    }

    /**
     * Registrar salida de producto con actualización automática de stock
     */
    public function registrarSalida($data)
    {
        try {
            $this->db->beginTransaction();

            // Validar datos
            if (empty($data['id_producto']) || empty($data['cantidad']) || $data['cantidad'] <= 0) {
                throw new Exception('Datos de salida inválidos');
            }

            // Obtener producto actual
            $producto = $this->db->select(
                "SELECT stock_actual, nombre FROM productos WHERE id_producto = ?",
                [$data['id_producto']]
            );

            if (empty($producto)) {
                throw new Exception('Producto no encontrado');
            }

            $stockActual = (int)$producto[0]['stock_actual'];
            $cantidad = (int)$data['cantidad'];

            if ($stockActual < $cantidad) {
                throw new Exception('Stock insuficiente para la salida');
            }

            $nuevoStock = $stockActual - $cantidad;

            // Registrar salida
            $salidaData = [
                'id_producto' => $data['id_producto'],
                'cantidad' => $cantidad,
                'destino' => trim($data['destino'] ?? ''),
                'fecha' => date('Y-m-d H:i:s'),
                'id_usuario' => $_SESSION['user_id'] ?? null,
                'observaciones' => trim($data['observaciones'] ?? '')
            ];

            $idSalida = $this->create($salidaData);

            // Actualizar stock
            $this->db->update(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$nuevoStock, $data['id_producto']]
            );

            $this->db->commit();

            Logger::info('Salida de producto registrada', [
                'id_salida' => $idSalida,
                'producto' => $producto[0]['nombre'],
                'cantidad' => $cantidad,
                'stock_nuevo' => $nuevoStock
            ]);

            return $idSalida;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
