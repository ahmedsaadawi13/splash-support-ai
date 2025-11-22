<?php
// FILE: /app/core/Model.php

/**
 * SplashSupportAI - Base Model Class
 * Parent class for all models
 */

class Model {
    protected $db;
    protected $table;

    /**
     * Constructor
     */
    public function __construct() {
        $this->db = new Database();
    }

    /**
     * Find record by ID
     */
    public function findById($id) {
        $this->db->query("SELECT * FROM {$this->table} WHERE id = :id LIMIT 1");
        $this->db->bind(':id', $id);
        return $this->db->single();
    }

    /**
     * Find all records
     */
    public function findAll($limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table}";
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $this->db->query($sql);
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        }
        return $this->db->all();
    }

    /**
     * Find records by tenant ID
     */
    public function findByTenant($tenantId, $limit = null, $offset = 0) {
        $sql = "SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id";
        if ($limit) {
            $sql .= " LIMIT :limit OFFSET :offset";
        }
        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        if ($limit) {
            $this->db->bind(':limit', $limit, PDO::PARAM_INT);
            $this->db->bind(':offset', $offset, PDO::PARAM_INT);
        }
        return $this->db->all();
    }

    /**
     * Insert record
     */
    public function insert($data) {
        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $this->db->query("INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})");

        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }

        $this->db->execute();
        return $this->db->lastInsertId();
    }

    /**
     * Update record
     */
    public function update($id, $data) {
        $setParts = array();
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $this->db->query("UPDATE {$this->table} SET {$setClause} WHERE id = :id");

        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        $this->db->bind(':id', $id);

        return $this->db->execute();
    }

    /**
     * Delete record
     */
    public function delete($id) {
        $this->db->query("DELETE FROM {$this->table} WHERE id = :id");
        $this->db->bind(':id', $id);
        return $this->db->execute();
    }

    /**
     * Count records
     */
    public function count($where = null, $params = array()) {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        if ($where) {
            $sql .= " WHERE {$where}";
        }
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $result = $this->db->single();
        return $result['count'];
    }

    /**
     * Execute custom query
     */
    public function query($sql, $params = array()) {
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->all();
    }

    /**
     * Execute custom query (single result)
     */
    public function querySingle($sql, $params = array()) {
        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        return $this->db->single();
    }

    /**
     * Begin transaction
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit transaction
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback transaction
     */
    public function rollback() {
        return $this->db->rollback();
    }
}
