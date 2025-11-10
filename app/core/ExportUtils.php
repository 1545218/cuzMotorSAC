<?php

/**
 * Utilidad de Exportación Avanzada
 * Sistema de Inventario Cruz Motor S.A.C.
 * 
 * Funcionalidad adicional para exportación mejorada sin afectar sistema actual
 */

class ExportUtils
{
    /**
     * Genera reporte de inventario en formato Excel con estadísticas
     */
    public static function generarInventarioExcel($productos, $opciones = [])
    {
        try {
            $titulo = $opciones['titulo'] ?? 'Reporte de Inventario';
            $incluirEstadisticas = $opciones['estadisticas'] ?? true;

            // Calcular estadísticas si se solicitan
            $stats = [];
            if ($incluirEstadisticas && !empty($productos)) {
                $stats = self::calcularEstadisticasInventario($productos);
            }

            // Generar HTML optimizado para Excel
            $html = self::generarHTMLInventario($productos, $stats, $titulo);

            // Configurar headers para descarga Excel
            $fecha = date('Y-m-d_H-i-s');
            $filename = "inventario_cruz_motor_{$fecha}.xls";

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            // Agregar BOM para UTF-8
            echo "\xEF\xBB\xBF";
            echo $html;
        } catch (Exception $e) {
            Logger::error('ExportUtils::generarInventarioExcel - ' . $e->getMessage());
            throw new Exception('Error al generar reporte Excel: ' . $e->getMessage());
        }
    }

    /**
     * Genera reporte de ventas en formato Excel
     */
    public static function generarVentasExcel($ventas, $opciones = [])
    {
        try {
            $fechaInicio = $opciones['fecha_inicio'] ?? '';
            $fechaFin = $opciones['fecha_fin'] ?? '';
            $titulo = "Reporte de Ventas";
            if ($fechaInicio && $fechaFin) {
                $titulo .= " ({$fechaInicio} al {$fechaFin})";
            }

            // Calcular estadísticas de ventas
            $stats = self::calcularEstadisticasVentas($ventas);

            // Generar HTML
            $html = self::generarHTMLVentas($ventas, $stats, $titulo);

            // Headers para descarga
            $fecha = date('Y-m-d_H-i-s');
            $filename = "ventas_cruz_motor_{$fecha}.xls";

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            echo "\xEF\xBB\xBF";
            echo $html;
        } catch (Exception $e) {
            Logger::error('ExportUtils::generarVentasExcel - ' . $e->getMessage());
            throw new Exception('Error al generar reporte Excel: ' . $e->getMessage());
        }
    }

    /**
     * Genera reporte de productos con stock bajo
     */
    public static function generarStockBajoExcel($productos, $opciones = [])
    {
        try {
            $titulo = 'Reporte de Stock Bajo - Requiere Atención';

            // Estadísticas específicas para stock bajo
            $stats = self::calcularEstadisticasStockBajo($productos);

            // HTML con recomendaciones
            $html = self::generarHTMLStockBajo($productos, $stats, $titulo);

            // Headers
            $fecha = date('Y-m-d_H-i-s');
            $filename = "stock_bajo_cruz_motor_{$fecha}.xls";

            header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Cache-Control: max-age=0');

            echo "\xEF\xBB\xBF";
            echo $html;
        } catch (Exception $e) {
            Logger::error('ExportUtils::generarStockBajoExcel - ' . $e->getMessage());
            throw new Exception('Error al generar reporte Excel: ' . $e->getMessage());
        }
    }

    /**
     * Calcula estadísticas del inventario
     */
    private static function calcularEstadisticasInventario($productos)
    {
        $stats = [
            'total_productos' => count($productos),
            'valor_total' => 0,
            'stock_total' => 0,
            'productos_sin_stock' => 0,
            'productos_stock_bajo' => 0,
            'producto_mas_valioso' => null,
            'valor_mas_alto' => 0
        ];

        foreach ($productos as $producto) {
            $stock = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
            $precio = (float)($producto['precio_unitario'] ?? 0);
            $stockMin = (int)($producto['stock_minimo'] ?? 0);
            $valor = $stock * $precio;

            $stats['stock_total'] += $stock;
            $stats['valor_total'] += $valor;

            if ($stock <= 0) {
                $stats['productos_sin_stock']++;
            }

            if ($stock <= $stockMin && $stockMin > 0) {
                $stats['productos_stock_bajo']++;
            }

            if ($valor > $stats['valor_mas_alto']) {
                $stats['valor_mas_alto'] = $valor;
                $stats['producto_mas_valioso'] = $producto['nombre'] ?? '';
            }
        }

        return $stats;
    }

    /**
     * Calcula estadísticas de ventas
     */
    private static function calcularEstadisticasVentas($ventas)
    {
        $stats = [
            'total_ventas' => count($ventas),
            'monto_total' => 0,
            'promedio_venta' => 0,
            'venta_mas_alta' => 0,
            'total_productos_vendidos' => 0
        ];

        foreach ($ventas as $venta) {
            $total = (float)($venta['total'] ?? 0);
            $productos = (int)($venta['total_productos'] ?? 0);

            $stats['monto_total'] += $total;
            $stats['total_productos_vendidos'] += $productos;

            if ($total > $stats['venta_mas_alta']) {
                $stats['venta_mas_alta'] = $total;
            }
        }

        if ($stats['total_ventas'] > 0) {
            $stats['promedio_venta'] = $stats['monto_total'] / $stats['total_ventas'];
        }

        return $stats;
    }

    /**
     * Calcula estadísticas específicas para stock bajo
     */
    private static function calcularEstadisticasStockBajo($productos)
    {
        $stats = [
            'total_productos' => count($productos),
            'valor_en_riesgo' => 0,
            'criticos' => 0,  // Stock = 0
            'urgentes' => 0,  // Stock < 50% del mínimo
            'advertencia' => 0  // Stock = mínimo
        ];

        foreach ($productos as $producto) {
            $stock = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
            $stockMin = (int)($producto['stock_minimo'] ?? 0);
            $precio = (float)($producto['precio_unitario'] ?? 0);

            $stats['valor_en_riesgo'] += ($stockMin * $precio);

            if ($stock <= 0) {
                $stats['criticos']++;
            } elseif ($stock < ($stockMin * 0.5)) {
                $stats['urgentes']++;
            } else {
                $stats['advertencia']++;
            }
        }

        return $stats;
    }

    /**
     * Genera HTML para inventario con estilos Excel
     */
    private static function generarHTMLInventario($productos, $stats, $titulo)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($titulo) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .header { background-color: #2E7D32; color: white; padding: 10px; text-align: center; font-weight: bold; font-size: 14pt; }
        .stats { background-color: #E8F5E8; padding: 8px; margin: 10px 0; }
        .stats-title { font-weight: bold; color: #2E7D32; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #4CAF50; color: white; padding: 8px; text-align: center; font-weight: bold; border: 1px solid #ccc; }
        td { padding: 6px; border: 1px solid #ccc; text-align: left; }
        .numero { text-align: right; }
        .stock-bajo { background-color: #FFEBEE; }
        .sin-stock { background-color: #FFCDD2; font-weight: bold; }
        .fecha { text-align: center; margin: 10px 0; font-size: 10pt; color: #666; }
    </style>
</head>
<body>';

        $html .= '<div class="header">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="fecha">Generado el: ' . date('d/m/Y H:i:s') . '</div>';

        // Estadísticas
        if (!empty($stats)) {
            $html .= '<div class="stats">
                <div class="stats-title">📊 ESTADÍSTICAS DEL INVENTARIO</div>
                <div>✅ Total de productos: <strong>' . number_format($stats['total_productos']) . '</strong></div>
                <div>💰 Valor total del inventario: <strong>S/ ' . number_format($stats['valor_total'], 2) . '</strong></div>
                <div>📦 Stock total: <strong>' . number_format($stats['stock_total']) . '</strong> unidades</div>
                <div>⚠️ Productos con stock bajo: <strong>' . $stats['productos_stock_bajo'] . '</strong></div>
                <div>🔴 Productos sin stock: <strong>' . $stats['productos_sin_stock'] . '</strong></div>';

            if ($stats['producto_mas_valioso']) {
                $html .= '<div>⭐ Producto más valioso: <strong>' . htmlspecialchars($stats['producto_mas_valioso']) . '</strong> (S/ ' . number_format($stats['valor_mas_alto'], 2) . ')</div>';
            }

            $html .= '</div>';
        }

        // Tabla de productos
        $html .= '<table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Precio Unitario</th>
                    <th>Valor Total</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($productos as $producto) {
            $codigo = htmlspecialchars($producto['codigo'] ?? $producto['codigo_barras'] ?? '');
            $nombre = htmlspecialchars($producto['nombre'] ?? '');
            $stock = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
            $stockMin = (int)($producto['stock_minimo'] ?? 0);
            $precio = (float)($producto['precio_unitario'] ?? 0);
            $valorTotal = $stock * $precio;

            // Determinar estado y clase CSS
            $estado = '✅ Normal';
            $clase = '';
            if ($stock <= 0) {
                $estado = '🔴 Sin Stock';
                $clase = 'sin-stock';
            } elseif ($stock <= $stockMin && $stockMin > 0) {
                $estado = '⚠️ Stock Bajo';
                $clase = 'stock-bajo';
            }

            $html .= "<tr class=\"{$clase}\">
                <td>{$codigo}</td>
                <td>{$nombre}</td>
                <td class=\"numero\">" . number_format($stock) . "</td>
                <td class=\"numero\">" . number_format($stockMin) . "</td>
                <td class=\"numero\">S/ " . number_format($precio, 2) . "</td>
                <td class=\"numero\">S/ " . number_format($valorTotal, 2) . "</td>
                <td>{$estado}</td>
            </tr>";
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Genera HTML para ventas
     */
    private static function generarHTMLVentas($ventas, $stats, $titulo)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($titulo) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .header { background-color: #1976D2; color: white; padding: 10px; text-align: center; font-weight: bold; font-size: 14pt; }
        .stats { background-color: #E3F2FD; padding: 8px; margin: 10px 0; }
        .stats-title { font-weight: bold; color: #1976D2; margin-bottom: 5px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #2196F3; color: white; padding: 8px; text-align: center; font-weight: bold; border: 1px solid #ccc; }
        td { padding: 6px; border: 1px solid #ccc; text-align: left; }
        .numero { text-align: right; }
        .fecha { text-align: center; margin: 10px 0; font-size: 10pt; color: #666; }
    </style>
</head>
<body>';

        $html .= '<div class="header">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="fecha">Generado el: ' . date('d/m/Y H:i:s') . '</div>';

        // Estadísticas de ventas
        if (!empty($stats)) {
            $html .= '<div class="stats">
                <div class="stats-title">📊 ESTADÍSTICAS DE VENTAS</div>
                <div>📈 Total de ventas: <strong>' . number_format($stats['total_ventas']) . '</strong></div>
                <div>💰 Monto total: <strong>S/ ' . number_format($stats['monto_total'], 2) . '</strong></div>
                <div>📊 Promedio por venta: <strong>S/ ' . number_format($stats['promedio_venta'], 2) . '</strong></div>
                <div>⭐ Venta más alta: <strong>S/ ' . number_format($stats['venta_mas_alta'], 2) . '</strong></div>
                <div>📦 Total productos vendidos: <strong>' . number_format($stats['total_productos_vendidos']) . '</strong></div>
            </div>';
        }

        // Tabla de ventas
        $html .= '<table>
            <thead>
                <tr>
                    <th>Fecha</th>
                    <th>N° Venta</th>
                    <th>Cliente</th>
                    <th>Productos</th>
                    <th>Total</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($ventas as $venta) {
            $fecha = date('d/m/Y', strtotime($venta['fecha'] ?? $venta['fecha_venta'] ?? ''));
            $numero = htmlspecialchars($venta['numero'] ?? $venta['id'] ?? '');
            $cliente = htmlspecialchars($venta['cliente_nombre'] ?? 'N/A');
            $productos = (int)($venta['total_productos'] ?? 0);
            $total = (float)($venta['total'] ?? 0);

            $html .= "<tr>
                <td>{$fecha}</td>
                <td>{$numero}</td>
                <td>{$cliente}</td>
                <td class=\"numero\">" . number_format($productos) . "</td>
                <td class=\"numero\">S/ " . number_format($total, 2) . "</td>
            </tr>";
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Genera HTML para stock bajo con recomendaciones
     */
    private static function generarHTMLStockBajo($productos, $stats, $titulo)
    {
        $html = '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>' . htmlspecialchars($titulo) . '</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11pt; }
        .header { background-color: #D32F2F; color: white; padding: 10px; text-align: center; font-weight: bold; font-size: 14pt; }
        .stats { background-color: #FFEBEE; padding: 8px; margin: 10px 0; border-left: 4px solid #D32F2F; }
        .stats-title { font-weight: bold; color: #D32F2F; margin-bottom: 5px; }
        .recomendaciones { background-color: #FFF3E0; padding: 8px; margin: 10px 0; border-left: 4px solid #FF9800; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th { background-color: #F44336; color: white; padding: 8px; text-align: center; font-weight: bold; border: 1px solid #ccc; }
        td { padding: 6px; border: 1px solid #ccc; text-align: left; }
        .numero { text-align: right; }
        .critico { background-color: #FFCDD2; }
        .urgente { background-color: #FFECB3; }
        .fecha { text-align: center; margin: 10px 0; font-size: 10pt; color: #666; }
    </style>
</head>
<body>';

        $html .= '<div class="header">' . htmlspecialchars($titulo) . '</div>';
        $html .= '<div class="fecha">Generado el: ' . date('d/m/Y H:i:s') . '</div>';

        // Estadísticas críticas
        if (!empty($stats)) {
            $html .= '<div class="stats">
                <div class="stats-title">🚨 SITUACIÓN CRÍTICA DEL INVENTARIO</div>
                <div>⚠️ Total productos afectados: <strong>' . number_format($stats['total_productos']) . '</strong></div>
                <div>🔴 Productos SIN STOCK (críticos): <strong>' . $stats['criticos'] . '</strong></div>
                <div>🟡 Productos URGENTES (< 50% mínimo): <strong>' . $stats['urgentes'] . '</strong></div>
                <div>🟠 Productos en ADVERTENCIA (= mínimo): <strong>' . $stats['advertencia'] . '</strong></div>
                <div>💰 Valor estimado para reposición: <strong>S/ ' . number_format($stats['valor_en_riesgo'], 2) . '</strong></div>
            </div>';

            // Recomendaciones
            $html .= '<div class="recomendaciones">
                <div style="font-weight: bold; color: #FF9800; margin-bottom: 5px;">💡 RECOMENDACIONES URGENTES:</div>
                <div>1. 🔴 Prioridad MÁXIMA: Productos sin stock (' . $stats['criticos'] . ' productos)</div>
                <div>2. 🟡 Prioridad ALTA: Productos urgentes (' . $stats['urgentes'] . ' productos)</div>
                <div>3. 📞 Contactar proveedores inmediatamente</div>
                <div>4. 📋 Revisar políticas de stock mínimo</div>
                <div>5. 💼 Considerar compras de emergencia</div>
            </div>';
        }

        // Tabla de productos
        $html .= '<table>
            <thead>
                <tr>
                    <th>Prioridad</th>
                    <th>Código</th>
                    <th>Producto</th>
                    <th>Stock Actual</th>
                    <th>Stock Mínimo</th>
                    <th>Diferencia</th>
                    <th>Sugerido Comprar</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($productos as $producto) {
            $codigo = htmlspecialchars($producto['codigo'] ?? $producto['codigo_barras'] ?? '');
            $nombre = htmlspecialchars($producto['nombre'] ?? $producto['producto_nombre'] ?? '');
            $stock = (int)($producto['stock_actual'] ?? $producto['stock'] ?? 0);
            $stockMin = (int)($producto['stock_minimo'] ?? 0);
            $diferencia = $stockMin - $stock;
            $sugeridoComprar = max($diferencia, $stockMin); // Al menos reponer al mínimo

            // Determinar prioridad
            $prioridad = '🟠 Advertencia';
            $clase = '';
            if ($stock <= 0) {
                $prioridad = '🔴 CRÍTICO';
                $clase = 'critico';
            } elseif ($stock < ($stockMin * 0.5)) {
                $prioridad = '🟡 URGENTE';
                $clase = 'urgente';
            }

            $html .= "<tr class=\"{$clase}\">
                <td>{$prioridad}</td>
                <td>{$codigo}</td>
                <td>{$nombre}</td>
                <td class=\"numero\">" . number_format($stock) . "</td>
                <td class=\"numero\">" . number_format($stockMin) . "</td>
                <td class=\"numero\">" . number_format($diferencia) . "</td>
                <td class=\"numero\">" . number_format($sugeridoComprar) . "</td>
            </tr>";
        }

        $html .= '</tbody></table>';
        $html .= '</body></html>';

        return $html;
    }
}
