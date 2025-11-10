<?php
require_once __DIR__ . '/../core/Model.php';

class ConfiguracionAlmacen extends Model
{
    protected $table = 'configuracionalmacen';
    protected $primaryKey = 'id_config';

    /**
     * Crea una nueva configuración de almacén
     */
    public function crear($nombreAlmacen, $capacidadMaxima = null, $horarioApertura = null, $horarioCierre = null, $responsable = null)
    {
        $sql = "INSERT INTO configuracionalmacen (nombre_almacen, capacidad_maxima, horario_apertura, horario_cierre, responsable) 
                VALUES (?, ?, ?, ?, ?)";
        $result = $this->db->execute($sql, [$nombreAlmacen, $capacidadMaxima, $horarioApertura, $horarioCierre, $responsable]);

        if ($result) {
            return $this->db->getConnection()->lastInsertId();
        }
        return false;
    }

    /**
     * Obtiene todas las configuraciones de almacén
     */
    public function getTodasConfiguraciones()
    {
        $sql = "SELECT ca.*, u.nombre as responsable_nombre, u.apellido as responsable_apellido
                FROM configuracionalmacen ca
                LEFT JOIN usuarios u ON ca.responsable = u.id_usuario
                ORDER BY ca.nombre_almacen";

        return $this->db->select($sql);
    }

    /**
     * Actualiza configuración de almacén
     */
    public function actualizar($idConfig, $datos)
    {
        $fields = [];
        $params = [];

        if (isset($datos['nombre_almacen'])) {
            $fields[] = "nombre_almacen = ?";
            $params[] = $datos['nombre_almacen'];
        }

        if (isset($datos['capacidad_maxima'])) {
            $fields[] = "capacidad_maxima = ?";
            $params[] = $datos['capacidad_maxima'];
        }

        if (isset($datos['horario_apertura'])) {
            $fields[] = "horario_apertura = ?";
            $params[] = $datos['horario_apertura'];
        }

        if (isset($datos['horario_cierre'])) {
            $fields[] = "horario_cierre = ?";
            $params[] = $datos['horario_cierre'];
        }

        if (isset($datos['responsable'])) {
            $fields[] = "responsable = ?";
            $params[] = $datos['responsable'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $idConfig;
        $sql = "UPDATE configuracionalmacen SET " . implode(', ', $fields) . " WHERE id_config = ?";

        return $this->db->execute($sql, $params);
    }

    /**
     * Obtiene configuración principal del almacén
     */
    public function getConfiguracionPrincipal()
    {
        $sql = "SELECT ca.*, u.nombre as responsable_nombre, u.apellido as responsable_apellido
                FROM configuracionalmacen ca
                LEFT JOIN usuarios u ON ca.responsable = u.id_usuario
                LIMIT 1";

        $result = $this->db->select($sql);
        return $result[0] ?? null;
    }

    /**
     * Verifica si el almacén está en horario de operación
     */
    public function estaEnHorarioOperacion($idConfig = null)
    {
        $config = $idConfig ? $this->find($idConfig) : $this->getConfiguracionPrincipal();

        if (!$config || !$config['horario_apertura'] || !$config['horario_cierre']) {
            return true; // Si no hay horarios definidos, asumimos 24/7
        }

        $horaActual = date('H:i:s');
        $apertura = $config['horario_apertura'];
        $cierre = $config['horario_cierre'];

        // Horario normal (no cruza medianoche)
        if ($apertura <= $cierre) {
            return ($horaActual >= $apertura && $horaActual <= $cierre);
        }
        // Horario nocturno (cruza medianoche)
        else {
            return ($horaActual >= $apertura || $horaActual <= $cierre);
        }
    }

    /**
     * Obtiene utilización actual del almacén
     */
    public function getUtilizacionAlmacen($idConfig = null)
    {
        $config = $idConfig ? $this->find($idConfig) : $this->getConfiguracionPrincipal();

        if (!$config || !$config['capacidad_maxima']) {
            return [
                'capacidad_maxima' => 0,
                'stock_actual' => 0,
                'porcentaje_utilizacion' => 0,
                'espacio_disponible' => 0
            ];
        }

        // Obtener stock total actual
        $sqlStock = "SELECT SUM(stock_actual) as stock_total FROM productos WHERE estado = 'activo'";
        $resultStock = $this->db->selectOne($sqlStock);
        $stockTotal = $resultStock['stock_total'] ?? 0;

        $capacidadMaxima = $config['capacidad_maxima'];
        $porcentajeUtilizacion = $capacidadMaxima > 0 ? ($stockTotal / $capacidadMaxima) * 100 : 0;
        $espacioDisponible = max(0, $capacidadMaxima - $stockTotal);

        return [
            'capacidad_maxima' => $capacidadMaxima,
            'stock_actual' => $stockTotal,
            'porcentaje_utilizacion' => round($porcentajeUtilizacion, 2),
            'espacio_disponible' => $espacioDisponible
        ];
    }

    /**
     * Obtiene estadísticas del almacén
     */
    public function getEstadisticas()
    {
        $utilizacion = $this->getUtilizacionAlmacen();
        $config = $this->getConfiguracionPrincipal();
        $enHorario = $this->estaEnHorarioOperacion();

        // Productos por categoría
        $sqlCategorias = "SELECT c.nombre as categoria, COUNT(p.id_producto) as cantidad, SUM(p.stock_actual) as stock
                         FROM productos p
                         LEFT JOIN subcategorias s ON p.id_subcategoria = s.id_subcategoria
                         LEFT JOIN categorias c ON s.id_categoria = c.id_categoria
                         WHERE p.estado = 'activo'
                         GROUP BY c.id_categoria, c.nombre
                         ORDER BY stock DESC";
        $categorias = $this->db->select($sqlCategorias);

        // Productos con stock bajo
        $sqlStockBajo = "SELECT COUNT(*) as productos_stock_bajo
                        FROM productos 
                        WHERE stock_actual <= stock_minimo AND estado = 'activo'";
        $resultStockBajo = $this->db->selectOne($sqlStockBajo);
        $productosStockBajo = $resultStockBajo['productos_stock_bajo'] ?? 0;

        return [
            'almacen' => $config,
            'utilizacion' => $utilizacion,
            'en_horario_operacion' => $enHorario,
            'productos_stock_bajo' => $productosStockBajo,
            'productos_por_categoria' => $categorias
        ];
    }

    /**
     * Inicializa configuración por defecto
     */
    public function inicializarConfiguracionDefecto($nombreAlmacen = 'Almacén Principal', $responsable = null)
    {
        // Verificar si ya existe configuración
        $existente = $this->getConfiguracionPrincipal();
        if ($existente) {
            return $existente['id_config'];
        }

        return $this->crear(
            $nombreAlmacen,
            1000, // Capacidad por defecto
            '08:00:00', // Horario apertura
            '18:00:00', // Horario cierre
            $responsable
        );
    }
}
