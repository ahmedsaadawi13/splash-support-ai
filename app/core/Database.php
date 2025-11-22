<?php
// FILE: /app/core/Database.php

/**
 * SplashSupportAI - Database Connection Class
 * Handles PDO database connections with prepared statements
 */

class Database {
    private $host = DB_HOST;
    private $dbName = DB_NAME;
    private $username = DB_USER;
    private $password = DB_PASS;
    private $conn = null;
    private $stmt = null;

    /**
     * Get database connection
     */
    public function getConnection() {
        if ($this->conn === null) {
            try {
                $dsn = "mysql:host=" . $this->host . ";dbname=" . $this->dbName . ";charset=utf8mb4";
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
                );
                $this->conn = new PDO($dsn, $this->username, $this->password, $options);
            } catch(PDOException $e) {
                $this->logError("Database connection error: " . $e->getMessage());
                throw new Exception("Database connection failed");
            }
        }
        return $this->conn;
    }

    /**
     * Prepare SQL statement
     */
    public function query($sql) {
        $this->stmt = $this->getConnection()->prepare($sql);
        return $this;
    }

    /**
     * Bind values to prepared statement
     */
    public function bind($param, $value, $type = null) {
        if (is_null($type)) {
            switch (true) {
                case is_int($value):
                    $type = PDO::PARAM_INT;
                    break;
                case is_bool($value):
                    $type = PDO::PARAM_BOOL;
                    break;
                case is_null($value):
                    $type = PDO::PARAM_NULL;
                    break;
                default:
                    $type = PDO::PARAM_STR;
            }
        }
        $this->stmt->bindValue($param, $value, $type);
        return $this;
    }

    /**
     * Execute prepared statement
     */
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            $this->logError("Query execution error: " . $e->getMessage());
            throw new Exception("Database query failed");
        }
    }

    /**
     * Fetch single record
     */
    public function single() {
        $this->execute();
        return $this->stmt->fetch();
    }

    /**
     * Fetch all records
     */
    public function all() {
        $this->execute();
        return $this->stmt->fetchAll();
    }

    /**
     * Get row count
     */
    public function rowCount() {
        return $this->stmt->rowCount();
    }

    /**
     * Get last insert ID
     */
    public function lastInsertId() {
        return $this->getConnection()->lastInsertId();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->getConnection()->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->getConnection()->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->getConnection()->rollBack();
    }

    /**
     * Log errors
     */
    private function logError($message) {
        $logFile = LOG_PATH . '/error_log.txt';
        $timestamp = date('Y-m-d H:i:s');
        $logMessage = "[{$timestamp}] {$message}\n";
        file_put_contents($logFile, $logMessage, FILE_APPEND);
    }
}
