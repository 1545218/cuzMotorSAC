<?php

/**
 * Clase Database - Manejo de conexión a la base de datos con PDO
 * Sistema de Inventario Cruz Motor S.A.C.
 */

class Database
{
    private static $instance = null;
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset;

    private function __construct()
    {
        $this->host = DB_HOST;
        $this->dbname = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;

        $this->connect();
    }

    /**
     * Singleton para obtener la instancia de la base de datos
     */
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Establece la conexión con la base de datos
     */
    private function connect()
    {
        try {
            $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset}"
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

            // Log de conexión exitosa
            Logger::info("Conexión a base de datos establecida correctamente", [
                'host' => $this->host,
                'database' => $this->dbname
            ]);
        } catch (PDOException $e) {
            Logger::error("Error de conexión a base de datos: " . $e->getMessage(), [
                'host' => $this->host,
                'database' => $this->dbname
            ]);
            throw new Exception("Error de conexión a la base de datos: " . $e->getMessage());
        }
    }

    /**
     * Obtiene la conexión PDO
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Ejecuta una consulta SELECT
     */
    public function select($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            Logger::error("Error en consulta SELECT: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta SELECT: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta SELECT y retorna solo un registro
     */
    public function selectOne($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            return $stmt->fetch();
        } catch (PDOException $e) {
            Logger::error("Error en consulta SELECT ONE: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta SELECT: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta INSERT
     */
    public function insert($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            Logger::info("Consulta INSERT ejecutada correctamente", [
                'affected_rows' => $stmt->rowCount()
            ]);

            // Retornar el último ID insertado para que los modelos puedan obtener el registro creado
            $lastId = $this->connection->lastInsertId();
            return $lastId ? $lastId : false;
        } catch (PDOException $e) {
            Logger::error("Error en consulta INSERT: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta INSERT: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta UPDATE
     */
    public function update($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            Logger::info("Consulta UPDATE ejecutada correctamente", [
                'affected_rows' => $stmt->rowCount()
            ]);
            return $result;
        } catch (PDOException $e) {
            Logger::error("Error en consulta UPDATE: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta UPDATE: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta DELETE
     */
    public function delete($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            $result = $stmt->execute($params);
            Logger::info("Consulta DELETE ejecutada correctamente", [
                'affected_rows' => $stmt->rowCount()
            ]);
            return $result;
        } catch (PDOException $e) {
            Logger::error("Error en consulta DELETE: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta DELETE: " . $e->getMessage());
        }
    }

    /**
     * Ejecuta una consulta genérica
     */
    public function execute($sql, $params = [])
    {
        try {
            $stmt = $this->connection->prepare($sql);
            return $stmt->execute($params);
        } catch (PDOException $e) {
            Logger::error("Error en consulta EXECUTE: " . $e->getMessage(), [
                'sql' => $sql,
                'params' => $params
            ]);
            throw new Exception("Error en consulta: " . $e->getMessage());
        }
    }

    /**
     * Inicia una transacción
     */
    public function beginTransaction()
    {
        Logger::info("Iniciando transacción de base de datos");
        return $this->connection->beginTransaction();
    }

    /**
     * Confirma una transacción
     */
    public function commit()
    {
        Logger::info("Confirmando transacción de base de datos");
        return $this->connection->commit();
    }

    /**
     * Cancela una transacción
     */
    public function rollback()
    {
        Logger::warning("Cancelando transacción de base de datos - Rollback ejecutado");
        return $this->connection->rollback();
    }

    /**
     * Verifica si hay una transacción activa
     */
    public function inTransaction()
    {
        return $this->connection->inTransaction();
    }

    /**
     * Obtiene el último ID insertado
     */
    public function lastInsertId()
    {
        return $this->connection->lastInsertId();
    }

    /**
     * Cuenta registros en una tabla
     */
    public function count($table, $where = [], $params = [])
    {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$table}";

            if (!empty($where)) {
                $conditions = [];
                foreach ($where as $field => $value) {
                    $conditions[] = "{$field} = :{$field}";
                }
                $sql .= " WHERE " . implode(' AND ', $conditions);
            }

            $result = $this->selectOne($sql, $params);
            return (int) $result['total'];
        } catch (Exception $e) {
            Logger::error("Error al contar registros en tabla: " . $e->getMessage(), [
                'table' => $table,
                'where' => $where
            ]);
            throw $e;
        }
    }

    /**
     * Obtiene información del estado de la conexión
     */
    public function getConnectionInfo()
    {
        return [
            'host' => $this->host,
            'database' => $this->dbname,
            'charset' => $this->charset,
            'status' => $this->connection ? 'connected' : 'disconnected'
        ];
    }

    /**
     * Cierra la conexión
     */
    public function close()
    {
        Logger::info("Cerrando conexión a base de datos");
        $this->connection = null;
    }

    /**
     * Destructor para cerrar conexión automáticamente
     */
    public function __destruct()
    {
        $this->close();
    }
}
