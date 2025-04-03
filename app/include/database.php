<?php
/**
 * Database Connection Handler
 * Uses PDO with prepared statements to prevent SQL injection
 */
class Database {
    // Database credentials (should be in environment variables in production)
    private $host = 'localhost';
    private $db_name = 'phpshop';
    private $username = 'root';
    private $password = '123456';
    private $conn;

    // Establish database connection
    public function getConnection() {
        $this->conn = null;

        try {
            // Create PDO instance with error handling
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name};charset=utf8mb4",
                $this->username,
                $this->password,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => true // For better performance
                ]
            );

            // Set timezone for database connections
            $this->conn->exec("SET time_zone = '+00:00'");

        } catch(PDOException $e) {
            // Log error securely (don't expose details in production)
            error_log("Database connection error: " . $e->getMessage());
            
            // Return user-friendly message
            throw new Exception("Database connection failed. Please try again later.");
        }

        return $this->conn;
    }

    /**
     * Execute a prepared statement with parameters
     * 
     * @param string $sql The SQL query with placeholders
     * @param array $params Associative array of parameters
     * @return PDOStatement The executed statement
     */
    public function executeQuery($sql, $params = []) {
        try {
            $stmt = $this->getConnection()->prepare($sql);
            
            // Bind parameters securely
            foreach ($params as $key => $value) {
                $paramType = $this->determineParamType($value);
                $stmt->bindValue($key, $value, $paramType);
            }
            
            $stmt->execute();
            return $stmt;
            
        } catch (PDOException $e) {
            error_log("Query error: " . $e->getMessage() . " [SQL: $sql]");
            throw new Exception("Database operation failed.");
        }
    }

    /**
     * Determine PDO parameter type based on value
     */
    private function determineParamType($value) {
        if (is_int($value)) return PDO::PARAM_INT;
        if (is_bool($value)) return PDO::PARAM_BOOL;
        if (is_null($value)) return PDO::PARAM_NULL;
        return PDO::PARAM_STR;
    }

    /**
     * Begin a transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit a transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback a transaction
     */
    public function rollBack() {
        return $this->getConnection()->rollBack();
    }

    /**
     * Get last inserted ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }
}
?>