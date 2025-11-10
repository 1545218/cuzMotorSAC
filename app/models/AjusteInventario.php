<?php

/**
 * Modelo AjusteInventario - Gestión de ajustes de inventario
 * Sistema de Inventario Cruz Motor S.A.C.
 * IMPLEMENTACIÓN SEGURA - Usa tabla ajustesinventario existente
 */

class AjusteInventario extends Model
{
    protected $table = 'ajustesinventario';
    protected $primaryKey = 'id_ajuste';
    protected $fillable = [
        'id_producto',
        'tipo',
        'cantidad',
        'motivo',
        'fecha',
        'id_usuario'
    ];

    // Tipos de ajuste permitidos
    const TIPO_AUMENTO = 'aumento';
    const TIPO_DISMINUCION = 'disminucion';

    /**
     * Obtener todos los ajustes con información del producto
     */
    public function getAllWithProductos()
    {
        return $this->db->select("
            SELECT a.*, 
                   p.nombre as producto_nombre,
                   p.codigo_barras,
                   u.nombre as usuario_nombre
            FROM ajustesinventario a 
            LEFT JOIN productos p ON a.id_producto = p.id_producto
            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
            ORDER BY a.fecha DESC
        ");
    }

    /**
     * Obtener ajustes por producto
     */
    public function getByProducto($idProducto)
    {
        return $this->db->select("
            SELECT a.*, 
                   u.nombre as usuario_nombre
            FROM ajustesinventario a 
            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE a.id_producto = ?
            ORDER BY a.fecha DESC
        ", [$idProducto]);
    }

    /**
     * Obtener ajustes por rango de fechas
     */
    public function getByRangoFechas($fechaInicio, $fechaFin)
    {
        return $this->db->select("
            SELECT a.*, 
                   p.nombre as producto_nombre,
                   p.codigo_barras,
                   u.nombre as usuario_nombre
            FROM ajustesinventario a 
            LEFT JOIN productos p ON a.id_producto = p.id_producto
            LEFT JOIN usuarios u ON a.id_usuario = u.id_usuario
            WHERE DATE(a.fecha) BETWEEN ? AND ?
            ORDER BY a.fecha DESC
        ", [$fechaInicio, $fechaFin]);
    }

    /**
     * Crear ajuste de inventario con actualización automática de stock
     */
    public function crearAjuste($data)
    {
        try {
            $this->db->beginTransaction();

            // Validar datos
            $errores = $this->validarDatos($data);
            if (!empty($errores)) {
                throw new Exception(implode(', ', $errores));
            }

            // Obtener stock actual del producto
            $producto = $this->db->select(
                "SELECT stock_actual, nombre FROM productos WHERE id_producto = ?",
                [$data['id_producto']]
            );

            if (empty($producto)) {
                throw new Exception('Producto no encontrado');
            }

            $stockActual = (int)$producto[0]['stock_actual'];
            $cantidad = (int)$data['cantidad'];

            // Calcular nuevo stock
            if ($data['tipo'] === self::TIPO_AUMENTO) {
                $nuevoStock = $stockActual + $cantidad;
            } else {
                $nuevoStock = $stockActual - $cantidad;

                // Verificar que no quede en negativo
                if ($nuevoStock < 0) {
                    throw new Exception('El ajuste resultaría en stock negativo');
                }
            }

            // Crear el registro del ajuste
            $ajusteData = [
                'id_producto' => $data['id_producto'],
                'tipo' => $data['tipo'],
                'cantidad' => $cantidad,
                'motivo' => trim($data['motivo'] ?? ''),
                'fecha' => date('Y-m-d H:i:s'),
                'id_usuario' => $_SESSION['user_id'] ?? null
            ];

            $idAjuste = $this->create($ajusteData);

            // Actualizar stock del producto
            $this->db->update(
                "UPDATE productos SET stock_actual = ? WHERE id_producto = ?",
                [$nuevoStock, $data['id_producto']]
            );

            // Registrar en historial de cambios
            $this->registrarHistorial($data, $stockActual, $nuevoStock);

            $this->db->commit();

            Logger::info('Ajuste de inventario creado', [
                'id_ajuste' => $idAjuste,
                'producto' => $producto[0]['nombre'],
                'tipo' => $data['tipo'],
                'cantidad' => $cantidad,
                'stock_anterior' => $stockActual,
                'stock_nuevo' => $nuevoStock
            ]);

            return $idAjuste;
        } catch (Exception $e) {
            $this->db->rollback();

            Logger::error('Error al crear ajuste de inventario', [
                'error' => $e->getMessage(),
                'data' => $data
            ]);

            throw $e;
        }
    }

    /**
     * Validar datos del ajuste
     */
    public function validarDatos($data)
    {
        $errores = [];

        // Validar producto
        if (empty($data['id_producto'])) {
            $errores[] = 'Debe seleccionar un producto';
        }

        // Validar tipo
        if (empty($data['tipo']) || !in_array($data['tipo'], [self::TIPO_AUMENTO, self::TIPO_DISMINUCION])) {
            $errores[] = 'Tipo de ajuste inválido';
        }

        // Validar cantidad
        if (empty($data['cantidad']) || !is_numeric($data['cantidad']) || $data['cantidad'] <= 0) {
            $errores[] = 'La cantidad debe ser un número mayor a 0';
        }

        // Validar motivo (opcional pero recomendado)
        if (empty($data['motivo'])) {
            $errores[] = 'Se recomienda especificar el motivo del ajuste';
        } elseif (strlen($data['motivo']) > 500) {
            $errores[] = 'El motivo no puede exceder 500 caracteres';
        }

        return $errores;
    }

    /**
     * Registrar en historial de cambios
     */
    private function registrarHistorial($data, $stockAnterior, $stockNuevo)
    {
        try {
            $historial = new HistorialCambios();
            $historial->create([
                'tabla_afectada' => 'productos',
                'id_registro' => $data['id_producto'],
                'accion' => 'ajuste_inventario',
                'datos_anteriores' => json_encode(['stock_actual' => $stockAnterior]),
                'datos_nuevos' => json_encode(['stock_actual' => $stockNuevo]),
                'id_usuario' => $_SESSION['user_id'] ?? null,
                'fecha' => date('Y-m-d H:i:s'),
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // No interrumpir el proceso si falla el historial
            Logger::warning('Error al registrar historial de ajuste', [
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Obtener resumen de ajustes por período
     */
    public function getResumenPorPeriodo($fechaInicio, $fechaFin)
    {
        return $this->db->select("
            SELECT 
                tipo,
                COUNT(*) as total_ajustes,
                SUM(cantidad) as cantidad_total,
                AVG(cantidad) as cantidad_promedio
            FROM ajustesinventario 
            WHERE DATE(fecha) BETWEEN ? AND ?
            GROUP BY tipo
        ", [$fechaInicio, $fechaFin]);
    }

    /**
     * Obtener productos con más ajustes
     */
    public function getProductosMasAjustados($limite = 10)
    {
        return $this->db->select("
            SELECT 
                p.nombre as producto_nombre,
                p.codigo_barras,
                COUNT(a.id_ajuste) as total_ajustes,
                SUM(CASE WHEN a.tipo = 'aumento' THEN a.cantidad ELSE 0 END) as aumentos,
                SUM(CASE WHEN a.tipo = 'disminucion' THEN a.cantidad ELSE 0 END) as disminuciones
            FROM ajustesinventario a
            JOIN productos p ON a.id_producto = p.id_producto
            GROUP BY a.id_producto, p.nombre, p.codigo_barras
            ORDER BY total_ajustes DESC
            LIMIT ?
        ", [$limite]);
    }

    /**
     * Obtener estadísticas generales
     */
    public function getEstadisticas()
    {
        $stats = $this->db->select("
            SELECT 
                COUNT(*) as total_ajustes,
                SUM(CASE WHEN tipo = 'aumento' THEN 1 ELSE 0 END) as aumentos,
                SUM(CASE WHEN tipo = 'disminucion' THEN 1 ELSE 0 END) as disminuciones,
                SUM(CASE WHEN tipo = 'aumento' THEN cantidad ELSE 0 END) as cantidad_aumentada,
                SUM(CASE WHEN tipo = 'disminucion' THEN cantidad ELSE 0 END) as cantidad_disminuida
            FROM ajustesinventario
        ");

        return $stats[0];
    }
}
