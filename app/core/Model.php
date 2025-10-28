<?php

/**
 * Clase Model - Clase base para todos los modelos
 * Sistema de Inventario Cruz Motor S.A.C.
 */

abstract class Model
{
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $timestamps = true;

    public function __construct()
    {
        try {
            $this->db = Database::getInstance();
            Logger::debug("Modelo inicializado correctamente", [
                'model' => get_class($this),
                'table' => $this->table
            ]);
        } catch (Exception $e) {
            Logger::error("Error al inicializar modelo: " . $e->getMessage(), [
                'model' => get_class($this)
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene todos los registros
     */
    public function all($orderBy = null)
    {
        $sql = "SELECT * FROM {$this->table}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        return $this->db->select($sql);
    }

    /**
     * Busca un registro por ID
     */
    public function find($id)
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Busca registros con condiciones
     */
    public function where($conditions, $params = [], $orderBy = null, $limit = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$conditions}";

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit) {
            $sql .= " LIMIT {$limit}";
        }

        return $this->db->select($sql, $params);
    }

    /**
     * Busca un registro con condiciones
     */
    public function whereOne($conditions, $params = [])
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} WHERE {$conditions}",
            $params
        );
    }

    /**
     * Crea un nuevo registro
     */
    public function create($data)
    {
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);

        // Agregar timestamps si están habilitados y las columnas existen en la tabla
        if ($this->timestamps) {
            try {
                $hasCreated = (bool)$this->db->selectOne("SHOW COLUMNS FROM {$this->table} LIKE 'created_at'");
                $hasUpdated = (bool)$this->db->selectOne("SHOW COLUMNS FROM {$this->table} LIKE 'updated_at'");
            } catch (Exception $e) {
                // En caso de error en la consulta de metadatos, asumir que no existen
                $hasCreated = false;
                $hasUpdated = false;
            }

            if ($hasCreated) {
                $filteredData['created_at'] = date('Y-m-d H:i:s');
            }
            if ($hasUpdated) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        $fields = array_keys($filteredData);
        $placeholders = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                VALUES (" . implode(', ', $placeholders) . ")";
        try {
            $id = $this->db->insert($sql, array_values($filteredData));
        } catch (Exception $e) {
            // Si falla por columnas de timestamps (p. ej. created_at/updated_at no existen), reintentar sin timestamps
            $msg = $e->getMessage();
            if (strpos($msg, 'Unknown column') !== false && $this->timestamps) {
                // Eliminar campos de timestamp y reintentar
                unset($filteredData['created_at'], $filteredData['updated_at']);
                $fields = array_keys($filteredData);
                $placeholders = array_fill(0, count($fields), '?');
                $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") 
                        VALUES (" . implode(', ', $placeholders) . ")";
                try {
                    $id = $this->db->insert($sql, array_values($filteredData));
                } catch (Exception $e2) {
                    // Re-throw el error original para no ocultar problemas reales
                    throw $e2;
                }
            } else {
                throw $e;
            }
        }

        if ($id) {
            return $this->find($id);
        }

        // Si la inserción no devolvió un ID (p. ej. tabla sin AUTO_INCREMENT), intentar obtener el último registro
        try {
            $last = $this->getLastInserted();
            if ($last) {
                return $last;
            }
        } catch (Exception $e) {
            Logger::warning('No se pudo obtener último registro insertado como fallback: ' . $e->getMessage(), ['model' => get_class($this)]);
        }

        return false;
    }

    /**
     * Actualiza un registro
     */
    public function update($id, $data)
    {
        // Filtrar solo campos permitidos
        $filteredData = $this->filterFillable($data);

        // Agregar timestamp de actualización
        if ($this->timestamps) {
            try {
                $hasUpdated = (bool)$this->db->selectOne("SHOW COLUMNS FROM {$this->table} LIKE 'updated_at'");
            } catch (Exception $e) {
                $hasUpdated = false;
            }

            if ($hasUpdated) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        $fields = array_keys($filteredData);
        $setClause = implode(' = ?, ', $fields) . ' = ?';

        $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";

        $params = array_values($filteredData);
        $params[] = $id;

        $rowsAffected = $this->db->update($sql, $params);

        if ($rowsAffected > 0) {
            return $this->find($id);
        }

        return false;
    }

    /**
     * Elimina un registro
     */
    public function delete($id)
    {
        return $this->db->delete(
            "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?",
            [$id]
        );
    }

    /**
     * Eliminación suave (soft delete)
     */
    public function softDelete($id)
    {
        return $this->update($id, ['deleted_at' => date('Y-m-d H:i:s')]);
    }

    /**
     * Cuenta registros
     */
    public function count($conditions = null, $params = [])
    {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";

        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }

        $result = $this->db->selectOne($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * Verifica si existe un registro
     */
    public function exists($conditions, $params = [])
    {
        return $this->count($conditions, $params) > 0;
    }

    /**
     * Paginación
     */
    public function paginate($page = 1, $perPage = 10, $conditions = null, $params = [], $orderBy = null)
    {
        $offset = ($page - 1) * $perPage;

        // Contar total de registros
        $totalRecords = $this->count($conditions, $params);

        // Obtener registros de la página actual
        $sql = "SELECT * FROM {$this->table}";

        if ($conditions) {
            $sql .= " WHERE {$conditions}";
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        $sql .= " LIMIT {$perPage} OFFSET {$offset}";

        $records = $this->db->select($sql, $params);

        return [
            'data' => $records,
            'current_page' => $page,
            'per_page' => $perPage,
            'total' => $totalRecords,
            'total_pages' => ceil($totalRecords / $perPage),
            'has_next' => $page < ceil($totalRecords / $perPage),
            'has_prev' => $page > 1
        ];
    }

    /**
     * Buscar registros
     */
    public function search($searchTerm, $searchFields = [], $conditions = null, $params = [])
    {
        if (empty($searchFields)) {
            Logger::error("Error en búsqueda: No se especificaron campos de búsqueda", [
                'model' => get_class($this),
                'table' => $this->table,
                'search_term' => $searchTerm
            ]);
            throw new Exception("Debe especificar al menos un campo de búsqueda");
        }

        $searchConditions = [];
        $searchParams = [];

        foreach ($searchFields as $field) {
            $searchConditions[] = "{$field} LIKE ?";
            $searchParams[] = "%{$searchTerm}%";
        }

        $searchWhere = "(" . implode(" OR ", $searchConditions) . ")";

        if ($conditions) {
            $searchWhere = "({$conditions}) AND {$searchWhere}";
            $searchParams = array_merge($params, $searchParams);
        }

        return $this->where($searchWhere, $searchParams);
    }

    /**
     * Ejecuta consulta SQL personalizada
     */
    public function query($sql, $params = [])
    {
        return $this->db->select($sql, $params);
    }

    /**
     * Inicia transacción
     */
    public function beginTransaction()
    {
        return $this->db->beginTransaction();
    }

    /**
     * Confirma transacción
     */
    public function commit()
    {
        return $this->db->commit();
    }

    /**
     * Revierte transacción
     */
    public function rollback()
    {
        return $this->db->rollback();
    }

    /**
     * Filtra datos según campos permitidos
     */
    private function filterFillable($data)
    {
        if (empty($this->fillable)) {
            return $data;
        }

        return array_intersect_key($data, array_flip($this->fillable));
    }

    /**
     * Valida datos antes de guardar
     */
    protected function validate($data, $rules = [])
    {
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? trim($data[$field]) : '';
            $ruleArray = explode('|', $rule);

            foreach ($ruleArray as $singleRule) {
                if ($singleRule === 'required' && empty($value)) {
                    $errors[$field] = "El campo {$field} es requerido";
                    break;
                }

                if ($singleRule === 'unique' && !empty($value)) {
                    if ($this->exists("{$field} = ?", [$value])) {
                        $errors[$field] = "El valor del campo {$field} ya existe";
                        break;
                    }
                }

                if (strpos($singleRule, 'min:') === 0 && !empty($value)) {
                    $min = (int) substr($singleRule, 4);
                    if (strlen($value) < $min) {
                        $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
                        break;
                    }
                }

                if (strpos($singleRule, 'max:') === 0 && !empty($value)) {
                    $max = (int) substr($singleRule, 4);
                    if (strlen($value) > $max) {
                        $errors[$field] = "El campo {$field} no puede tener más de {$max} caracteres";
                        break;
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Obtiene el último registro insertado
     */
    public function getLastInserted()
    {
        return $this->db->selectOne(
            "SELECT * FROM {$this->table} ORDER BY {$this->primaryKey} DESC LIMIT 1"
        );
    }

    /**
     * Trunca la tabla (elimina todos los registros)
     */
    public function truncate()
    {
        return $this->db->execute("TRUNCATE TABLE {$this->table}");
    }

    /**
     * Obtiene registros activos (si el modelo maneja estado activo/inactivo)
     */
    public function active()
    {
        return $this->where('activo = ?', [1]);
    }

    /**
     * Obtiene registros inactivos
     */
    public function inactive()
    {
        return $this->where('activo = ?', [0]);
    }
}
