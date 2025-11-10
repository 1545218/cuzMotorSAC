<?php

/**
 * Modelo EntradaProducto - Gestión de entradas de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla entradasproductos existente
 */

class EntradaProducto extends Model
{
    protected $table = 'entradasproductos';
    protected $primaryKey = 'id_entrada';
    protected $fillable = [
        'id_producto',
        'cantidad',
        'fecha',
        'id_usuario',
        'observaciones'
    ];

    /**
     * Obtener todas las entradas con información del producto
     */
    public function getAllWithProductos()
    {
        return $this->db->select("
            SELECT e.*, 
                   p.nombre as producto_nombre,
                   p.codigo_barras,
                   u.nombre as usuario_nombre
            FROM entradasproductos e 
            LEFT JOIN productos p ON e.id_producto = p.id_producto
            LEFT JOIN usuarios u ON e.id_usuario = u.id_usuario
            ORDER BY e.fecha DESC
        ");
    }

    /**
     * Registrar entrada de producto con actualización automática de stock
     */
    public function registrarEntrada($data)
    {
        try {
            $this->db->beginTransaction();

            // Validar datos
            if (empty($data['id_producto']) || empty($data['cantidad']) || $data['cantidad'] <= 0) {
                throw new Exception('Datos de entrada inválidos');
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
            $nuevoStock = $stockActual + $cantidad;

            // Registrar entrada
            $entradaData = [
                'id_producto' => $data['id_producto'],
                'cantidad' => $cantidad,
                'fecha' => date('Y-m-d H:i:s'),
                'id_usuario' => $_SESSION['user_id'] ?? null,
                'observaciones' => trim($data['observaciones'] ?? '')
            ];

            $idEntrada = $this->create($entradaData);

            // Actualizar stock
            $this->db->update(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$nuevoStock, $data['id_producto']]
            );

            $this->db->commit();

            Logger::info('Entrada de producto registrada', [
                'id_entrada' => $idEntrada,
                'producto' => $producto[0]['nombre'],
                'cantidad' => $cantidad,
                'stock_nuevo' => $nuevoStock
            ]);

            return $idEntrada;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}
