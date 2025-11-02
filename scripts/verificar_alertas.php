<?php

/**
 * Script automático para verificar y generar alertas del sistema
 * Se debe ejecutar periódicamente (cada hora o cada 30 minutos)
 * 
 * Uso: php scripts/verificar_alertas.php
 */

// Configurar rutas
define('ROOT_PATH', __DIR__ . '/..');

// Inicializar sesión si no existe
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once ROOT_PATH . '/config/config.php';
require_once APP_PATH . '/core/Logger.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/models/Alerta.php';

try {
    echo "=== SCRIPT DE VERIFICACIÓN DE ALERTAS ===\n";
    echo "Fecha y hora: " . date('Y-m-d H:i:s') . "\n\n";

    // Crear instancia del modelo de alertas
    $alertaModel = new Alerta();

    echo "1. Verificando stock bajo...\n";
    $stockBajo = $alertaModel->verificarStockBajo();
    echo "   → Se generaron $stockBajo alertas de stock bajo\n\n";

    echo "2. Verificando productos sin stock...\n";
    $sinStock = $alertaModel->verificarProductosSinStock();
    echo "   → Se generaron $sinStock alertas de productos sin stock\n\n";

    echo "3. Verificando productos próximos a vencer...\n";
    $proximosVencer = $alertaModel->verificarProductosProximosVencer();
    echo "   → Se generaron $proximosVencer alertas de productos próximos a vencer\n\n";

    echo "4. Verificando ventas del día...\n";
    $pocasVentas = $alertaModel->verificarVentasDelDia();
    echo "   → Se generaron $pocasVentas alertas de pocas ventas\n\n";

    $totalAlertas = $stockBajo + $sinStock + $proximosVencer + $pocasVentas;

    echo "=== RESUMEN ===\n";
    echo "Total de nuevas alertas generadas: $totalAlertas\n";

    // Mostrar cantidad de alertas pendientes
    $pendientes = $alertaModel->contarPendientes();
    echo "Total de alertas pendientes en el sistema: $pendientes\n";

    // Log del resultado
    Logger::info("Script de verificación de alertas ejecutado", [
        'alertas_generadas' => $totalAlertas,
        'alertas_pendientes' => $pendientes,
        'stock_bajo' => $stockBajo,
        'sin_stock' => $sinStock,
        'proximos_vencer' => $proximosVencer,
        'pocas_ventas' => $pocasVentas
    ]);

    echo "\n=== SCRIPT COMPLETADO EXITOSAMENTE ===\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    Logger::error("Error en script de verificación de alertas: " . $e->getMessage());
    exit(1);
}
