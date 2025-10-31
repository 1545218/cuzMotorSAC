<?php

/**
 * Modelo Inventario - Gestión del inventario de productos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Inventario extends Model
{
    // Nota: la base de datos existente no contiene la tabla `inventario` ni `movimientos_inventario`.
    // Para mantener la BD intacta usamos la tabla `productos` como fuente de stock y `registrosstock`
    // como historial de movimientos. No cambiamos el esquema de la BD.
    protected $table = 'productos';
    protected $primaryKey = 'id_producto';
    protected $fillable = [
        'id_producto',
        'id_ubicacion',
        'stock_actual',
        'stock_minimo',
        'precio_costo',
        'precio_venta',
        'fecha_actualizacion'
    ];

    /**
     * Obtiene resumen del inventario
     */
    public function getResumen()
    {
        return $this->getStats();
    }

    /**
     * Obtiene productos con stock
     */
    public function getProductosConStock()
    {
        // Usar directamente los campos de `productos` (stock_actual, stock_minimo)
        return $this->db->select(
            "SELECT p.id_producto as id, p.codigo_barras as codigo, p.nombre, 
                    c.nombre as categoria, m.nombre as marca,
                    COALESCE(p.stock_actual, 0) as stock_actual, 
                    COALESCE(p.stock_minimo, 0) as stock_minimo
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             WHERE p.estado = 'activo'
             ORDER BY p.nombre ASC"
        );
    }

    /**
     * Obtiene alertas de stock bajo
     */
    public function getAlertasStockBajo()
    {
        return $this->getProductosBajoStock();
    }

    /**
     * Obtiene stock actual de un producto
     */
    public function getStockActual($productoId)
    {
        $result = $this->db->selectOne(
            "SELECT COALESCE(stock_actual, 0) as stock FROM productos WHERE id_producto = ?",
            [$productoId]
        );
        return $result ? (int)$result['stock'] : 0;
    }

    /**
     * Registra un movimiento de inventario
     */
    public function registrarMovimiento($datos)
    {
        try {
            // Insertar en registrosstock (tabla existente en el dump) y actualizar stock en productos
            $tipo = $datos['tipo_movimiento'] ?? ($datos['tipo'] ?? 'entrada');
            $cantidad = (int)($datos['cantidad'] ?? 0);

            // Registrar en registrosstock
            $this->db->insert('registrosstock', [
                'id_producto' => $datos['producto_id'],
                'tipo' => $tipo,
                'cantidad' => $cantidad,
                'origen' => $datos['referencia'] ?? ($datos['motivo'] ?? null),
                'referencia_id' => $datos['referencia_id'] ?? null,
                'id_usuario' => $datos['usuario_id'] ?? ($_SESSION['user_id'] ?? null)
            ]);

            // Actualizar stock en productos
            $producto = $this->db->selectOne("SELECT stock_actual FROM productos WHERE id_producto = ?", [$datos['producto_id']]);
            $stockAnterior = $producto ? (int)$producto['stock_actual'] : 0;
            $stockNuevo = $stockAnterior;
            if ($tipo === 'entrada') {
                $stockNuevo = $stockAnterior + $cantidad;
            } elseif ($tipo === 'salida') {
                $stockNuevo = max(0, $stockAnterior - $cantidad);
            } elseif ($tipo === 'ajuste') {
                // en ajuste, se espera que cantidad sea el nuevo stock
                $stockNuevo = (int)$datos['cantidad'];
            }

            $this->db->execute("UPDATE productos SET stock_actual = ? WHERE id_producto = ?", [$stockNuevo, $datos['producto_id']]);

            return true;
        } catch (Exception $e) {
            Logger::error("Error en registrarMovimiento: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra un ajuste de inventario
     */
    public function registrarAjuste($movimientoData, $nuevoStock)
    {
        // Registrar ajuste usando tablas existentes: insertar en ajustesinventario y registrosstock, y actualizar productos
        $this->db->beginTransaction();
        try {
            // Guardar en ajustesinventario para mantener compatibilidad
            $this->db->insert('ajustesinventario', [
                'id_producto' => $movimientoData['producto_id'],
                'tipo' => $movimientoData['cantidad'] > 0 ? 'aumento' : 'disminucion',
                'cantidad' => abs((int)$movimientoData['cantidad']),
                'motivo' => $movimientoData['motivo'] ?? null,
                'id_usuario' => $movimientoData['user_id'] ?? ($_SESSION['user_id'] ?? null)
            ]);

            // Registrar en registrosstock
            $this->db->insert('registrosstock', [
                'id_producto' => $movimientoData['producto_id'],
                'tipo' => 'ajuste',
                'cantidad' => abs((int)$movimientoData['cantidad']),
                'origen' => 'ajuste',
                'referencia_id' => null,
                'id_usuario' => $movimientoData['user_id'] ?? ($_SESSION['user_id'] ?? null)
            ]);

            // Actualizar stock en productos
            $this->db->execute("UPDATE productos SET stock_actual = ? WHERE id_producto = ?", [$nuevoStock, $movimientoData['producto_id']]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Obtiene productos con stock bajo  
     */
    public function getProductosStockBajo()
    {
        return $this->getProductosBajoStock();
    }

    /**
     * Obtiene reporte de movimientos
     */
    public function getReporteMovimientos($fechaInicio, $fechaFin)
    {
        return $this->getMovimientos(null, null, null, $fechaInicio, $fechaFin);
    }

    /**
     * Obtiene inventario valorizado
     */
    public function getInventarioValorizado()
    {
        // Usar precio_unitario como costo si no existe precio_costo
        return $this->db->select(
            "SELECT p.nombre, COALESCE(p.stock_actual,0) as stock_actual,
                    COALESCE(p.precio_unitario,0) as precio_costo,
                    (COALESCE(p.stock_actual,0) * COALESCE(p.precio_unitario,0)) as valor_total
             FROM productos p
             WHERE p.estado = 'activo'
             ORDER BY valor_total DESC"
        );
    }

    /**
     * Obtiene resumen general
     */
    public function getResumenGeneral()
    {
        return $this->getStats();
    }

    /**
     * Obtiene inventario con detalles de productos y ubicaciones
     */
    public function getInventarioCompleto($ubicacionId = null, $categoriaId = null, $bajoStock = false)
    {
        $conditions = ['p.estado = "activo"'];
        $params = [];

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        if ($categoriaId) {
            // productos reference subcategoría; filtrar por la categoría a través de subcategorias
            $conditions[] = "s.id_categoria = ?";
            $params[] = $categoriaId;
        }

        if ($bajoStock) {
            $conditions[] = "p.stock_actual <= p.stock_minimo";
        }

        $whereClause = implode(' AND ', $conditions);

        // Usar datos desde productos y ubicaciones
        return $this->db->select(
            "SELECT p.id_producto as id, p.codigo_barras as codigo, p.nombre as producto_nombre, p.descripcion,
                    c.nombre as categoria_nombre, m.nombre as marca_nombre,
                    u.nombre as ubicacion_nombre, un.nombre as unidad_nombre,
                    (p.stock_actual * COALESCE(p.precio_unitario,0)) as valor_stock,
                    CASE 
                        WHEN p.stock_actual <= 0 THEN 'sin_stock'
                        WHEN p.stock_actual <= p.stock_minimo THEN 'bajo_stock'
                        ELSE 'normal'
                    END as estado_stock,
                    p.stock_actual, p.stock_minimo, p.precio_unitario
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             LEFT JOIN unidades un ON p.id_unidad = un.id_unidad
             WHERE {$whereClause}
             ORDER BY p.nombre ASC",
            $params
        );
    }

    /**
     * Registra movimiento de inventario (método completo)
     */
    public function registrarMovimientoCompleto($idProducto, $idUbicacion, $tipoMovimiento, $cantidad, $motivo, $referencia = null)
    {
        // Versión adaptada a la BD existente: actualiza productos.stock_actual y registra en registrosstock
        $this->db->beginTransaction();
        try {
            $producto = $this->db->selectOne("SELECT stock_actual FROM productos WHERE id_producto = ?", [$idProducto]);
            $stockAnterior = $producto ? (int)$producto['stock_actual'] : 0;
            $stockNuevo = $stockAnterior;

            switch ($tipoMovimiento) {
                case 'entrada':
                    $stockNuevo = $stockAnterior + (int)$cantidad;
                    break;
                case 'salida':
                    if ($stockAnterior < $cantidad) {
                        throw new Exception('Stock insuficiente para la salida solicitada');
                    }
                    $stockNuevo = $stockAnterior - (int)$cantidad;
                    break;
                case 'ajuste':
                    $stockNuevo = (int)$cantidad;
                    $cantidad = $stockNuevo - $stockAnterior;
                    break;
                default:
                    throw new Exception('Tipo de movimiento no válido');
            }

            // Actualizar stock en productos
            $this->db->execute("UPDATE productos SET stock_actual = ? WHERE id_producto = ?", [$stockNuevo, $idProducto]);

            // Registrar en registrosstock
            $this->db->insert('registrosstock', [
                'id_producto' => $idProducto,
                'tipo' => $tipoMovimiento,
                'cantidad' => abs((int)$cantidad),
                'origen' => $motivo,
                'referencia_id' => $referencia,
                'id_usuario' => $_SESSION['user_id'] ?? 1
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Crea o actualiza inventario de producto
     */
    public function createOrUpdateInventario($data)
    {
        // La BD no tiene tabla `inventario`. Como alternativa actualizamos los campos relevantes
        // directamente en la tabla `productos`. Esto preserva la información de stock y precios
        // sin cambiar el esquema de la BD.
        $productoId = $data['id_producto'] ?? null;
        if (!$productoId) throw new Exception('id_producto es requerido');

        $fields = [];
        $params = [];

        if (isset($data['stock_actual'])) {
            $fields[] = 'stock_actual = ?';
            $params[] = (int)$data['stock_actual'];
        }
        if (isset($data['stock_minimo'])) {
            $fields[] = 'stock_minimo = ?';
            $params[] = (int)$data['stock_minimo'];
        }
        if (isset($data['precio_costo'])) {
            $fields[] = 'precio_costo = ?';
            $params[] = (float)$data['precio_costo'];
        }
        if (isset($data['precio_venta'])) {
            $fields[] = 'precio_unitario = ?';
            $params[] = (float)$data['precio_venta'];
        }

        if (!empty($fields)) {
            $fields[] = 'fecha_actualizacion = ?';
            $params[] = date('Y-m-d H:i:s');
            $params[] = $productoId;
            $sql = "UPDATE productos SET " . implode(', ', $fields) . " WHERE id_producto = ?";
            $this->db->execute($sql, $params);
        }

        return $productoId;
    }

    /**
     * Busca productos en inventario
     */
    public function searchInventario($term, $ubicacionId = null, $categoriaId = null)
    {
        // Adaptado: buscar directamente en `productos` porque no existe `inventario`
        $conditions = ['p.estado = "activo"'];
        $params = [];

        if (!empty($term)) {
            $conditions[] = "(p.codigo LIKE ? OR p.nombre LIKE ? OR p.descripcion LIKE ? OR m.nombre LIKE ?)";
            $params = array_merge($params, ["%$term%", "%$term%", "%$term%", "%$term%"]);
        }

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        if ($categoriaId) {
            // filtrar por categoría a través de subcategorias
            $conditions[] = "s.id_categoria = ?";
            $params[] = $categoriaId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.id_producto as id, p.codigo_barras as codigo, p.nombre as producto_nombre, p.descripcion,
                    c.nombre as categoria_nombre, m.nombre as marca_nombre,
                    u.nombre as ubicacion_nombre,
                    COALESCE(p.stock_actual,0) as stock_actual, COALESCE(p.stock_minimo,0) as stock_minimo,
                    (COALESCE(p.stock_actual,0) * COALESCE(p.precio_unitario,0)) as valor_stock
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             WHERE {$whereClause}
             ORDER BY p.nombre ASC",
            $params
        );
    }

    /**
     * Obtiene productos con bajo stock
     */
    public function getProductosBajoStock($ubicacionId = null, $limit = 50)
    {
        $conditions = ['p.stock_actual <= p.stock_minimo', 'p.estado = "activo"'];
        $params = [];

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.id_producto as id, p.codigo_barras as codigo, p.nombre as producto_nombre,
                    c.nombre as categoria_nombre, m.nombre as marca_nombre,
                    u.nombre as ubicacion_nombre,
                    (p.stock_minimo - p.stock_actual) as diferencia_stock,
                    p.stock_actual, p.stock_minimo
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             WHERE {$whereClause}
             ORDER BY diferencia_stock DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Obtiene productos sin stock
     */
    public function getProductosSinStock($ubicacionId = null, $limit = 50)
    {
        $conditions = ['p.stock_actual <= 0', 'p.estado = "activo"'];
        $params = [];

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.id_producto as id, p.codigo_barras as codigo, p.nombre as producto_nombre,
                    c.nombre as categoria_nombre, m.nombre as marca_nombre,
                    u.nombre as ubicacion_nombre, p.stock_actual
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             LEFT JOIN marcas m ON p.id_marca = m.id_marca
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             WHERE {$whereClause}
             ORDER BY p.nombre ASC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Obtiene historial de movimientos
     */
    public function getMovimientos($idProducto = null, $idUbicacion = null, $tipoMovimiento = null, $fechaInicio = null, $fechaFin = null, $limit = 100)
    {
        $conditions = ['1=1'];
        $params = [];

        if ($idProducto) {
            $conditions[] = "rs.id_producto = ?";
            $params[] = $idProducto;
        }

        if ($idUbicacion) {
            // la ubicación está en la tabla productos, no en registrosstock
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $idUbicacion;
        }

        if ($tipoMovimiento) {
            $conditions[] = "rs.tipo = ?";
            $params[] = $tipoMovimiento;
        }

        if ($fechaInicio) {
            $conditions[] = "DATE(rs.fecha) >= ?";
            $params[] = $fechaInicio;
        }

        if ($fechaFin) {
            $conditions[] = "DATE(rs.fecha) <= ?";
            $params[] = $fechaFin;
        }

        $whereClause = implode(' AND ', $conditions);

        // Usar registrosstock como historial de movimientos
        return $this->db->select(
            "SELECT rs.*, p.codigo_barras as codigo, p.nombre as producto_nombre,
                    u.nombre as ubicacion_nombre, us.nombre as usuario_nombre
             FROM registrosstock rs
             LEFT JOIN productos p ON rs.id_producto = p.id_producto
             LEFT JOIN ubicaciones u ON p.id_ubicacion = u.id_ubicacion
             LEFT JOIN usuarios us ON rs.id_usuario = us.id_usuario
             WHERE {$whereClause}
             ORDER BY rs.fecha DESC
             LIMIT ?",
            array_merge($params, [$limit])
        );
    }

    /**
     * Obtiene estadísticas del inventario
     */
    public function getStats($ubicacionId = null)
    {
        // Basar estadísticas en tabla productos (no existe inventario)
        $conditions = ['p.estado = "activo"'];
        $params = [];

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        $whereClause = implode(' AND ', $conditions);

        return [
            'total_productos' => $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos p WHERE " . implode(' AND ', array_map(function ($c) {
                    return $c;
                }, $conditions)),
                $params
            )['total'] ?? 0,
            'valor_total_costo' => $this->db->selectOne(
                "SELECT COALESCE(SUM(p.stock_actual * COALESCE(p.precio_unitario,0)), 0) as valor FROM productos p WHERE " . implode(' AND ', array_map(function ($c) {
                    return $c;
                }, $conditions)),
                $params
            )['valor'] ?? 0,
            'valor_total_venta' => $this->db->selectOne(
                "SELECT COALESCE(SUM(p.stock_actual * COALESCE(p.precio_unitario,0)), 0) as valor FROM productos p WHERE " . implode(' AND ', array_map(function ($c) {
                    return $c;
                }, $conditions)),
                $params
            )['valor'] ?? 0,
            'productos_bajo_stock' => $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos p WHERE p.stock_actual <= p.stock_minimo AND " . implode(' AND ', array_map(function ($c) {
                    return $c;
                }, $conditions)),
                $params
            )['total'] ?? 0,
            'productos_sin_stock' => $this->db->selectOne(
                "SELECT COUNT(*) as total FROM productos p WHERE p.stock_actual <= 0 AND " . implode(' AND ', array_map(function ($c) {
                    return $c;
                }, $conditions)),
                $params
            )['total'] ?? 0
        ];
    }

    /**
     * Obtiene resumen por categorías
     */
    public function getResumenCategorias($ubicacionId = null)
    {
        $conditions = ['p.estado = "activo"'];
        $params = [];

        if ($ubicacionId) {
            // usar la ubicación desde productos
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT c.nombre as categoria_nombre,
                    COUNT(p.id_producto) as total_productos,
                    COALESCE(SUM(p.stock_actual), 0) as stock_total,
                    COALESCE(SUM(p.stock_actual * COALESCE(p.precio_unitario,0)), 0) as valor_costo,
                    COALESCE(SUM(p.stock_actual * COALESCE(p.precio_unitario,0)), 0) as valor_venta
             FROM productos p
             LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
             LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
             WHERE {$whereClause}
             GROUP BY s.id_categoria, c.nombre
             ORDER BY valor_costo DESC",
            $params
        );
    }

    /**
     * Actualiza precios masivamente
     */
    public function updatePreciosMasivo($productos, $tipoPrecio, $tipoActualizacion, $valor)
    {
        $this->db->beginTransaction();

        try {
            foreach ($productos as $idInventario) {
                $inventario = $this->find($idInventario);
                if (!$inventario) continue;

                $nuevoPrecio = 0;
                $precioActual = $inventario[$tipoPrecio];

                switch ($tipoActualizacion) {
                    case 'incremento_porcentual':
                        $nuevoPrecio = $precioActual * (1 + $valor / 100);
                        break;
                    case 'decremento_porcentual':
                        $nuevoPrecio = $precioActual * (1 - $valor / 100);
                        break;
                    case 'incremento_fijo':
                        $nuevoPrecio = $precioActual + $valor;
                        break;
                    case 'decremento_fijo':
                        $nuevoPrecio = $precioActual - $valor;
                        break;
                    case 'precio_fijo':
                        $nuevoPrecio = $valor;
                        break;
                }

                $this->update($idInventario, [
                    $tipoPrecio => $nuevoPrecio,
                    'fecha_actualizacion' => date('Y-m-d H:i:s')
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Genera reporte de rotación de inventario
     */
    public function getRotacionInventario($fechaInicio, $fechaFin, $ubicacionId = null)
    {
        // Reescribir para usar productos + registrosstock
        $conditions = ['p.estado = "activo"'];
        $params = [$fechaInicio, $fechaFin];

        if ($ubicacionId) {
            $conditions[] = "p.id_ubicacion = ?";
            $params[] = $ubicacionId;
        }

        $whereClause = implode(' AND ', $conditions);

        return $this->db->select(
            "SELECT p.codigo_barras as codigo, p.nombre as producto_nombre,
                    p.stock_actual,
                    COALESCE(SUM(CASE WHEN rs.tipo = 'salida' THEN rs.cantidad ELSE 0 END), 0) as total_salidas,
                    COALESCE(AVG(p.stock_actual), 0) as promedio_stock,
                    CASE 
                        WHEN AVG(p.stock_actual) > 0 
                        THEN COALESCE(SUM(CASE WHEN rs.tipo = 'salida' THEN rs.cantidad ELSE 0 END),0) / AVG(p.stock_actual)
                        ELSE 0 
                    END as rotacion
             FROM productos p
             LEFT JOIN registrosstock rs ON p.id_producto = rs.id_producto AND DATE(rs.fecha) BETWEEN ? AND ?
             WHERE {$whereClause}
             GROUP BY p.id_producto, p.codigo_barras, p.nombre, p.stock_actual
             ORDER BY rotacion DESC",
            $params
        );
    }
}
