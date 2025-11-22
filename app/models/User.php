<?php
// FILE: /app/models/User.php

/**
 * SplashSupportAI - User Model
 */

class User extends Model {
    protected $table = 'users';

    public function findByEmail($email) {
        $this->db->query("SELECT * FROM {$this->table} WHERE email = :email LIMIT 1");
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function create($data) {
        if (isset($data['password'])) {
            $data['password_hash'] = SecurityHelper::hashPassword($data['password']);
            unset($data['password']);
        }
        return $this->insert($data);
    }

    public function updatePassword($userId, $newPassword) {
        $this->db->query("UPDATE {$this->table} SET password_hash = :password_hash WHERE id = :id");
        $this->db->bind(':password_hash', SecurityHelper::hashPassword($newPassword));
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function updateLastLogin($userId) {
        $this->db->query("UPDATE {$this->table} SET last_login_at = NOW(), failed_login_attempts = 0, locked_until = NULL WHERE id = :id");
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function incrementFailedAttempts($userId) {
        $this->db->query("UPDATE {$this->table} SET failed_login_attempts = failed_login_attempts + 1 WHERE id = :id");
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function lockAccount($userId, $until) {
        $this->db->query("UPDATE {$this->table} SET locked_until = :until WHERE id = :id");
        $this->db->bind(':until', date('Y-m-d H:i:s', $until));
        $this->db->bind(':id', $userId);
        return $this->db->execute();
    }

    public function getUsersByRole($tenantId, $role) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND role = :role AND status = 'active'");
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':role', $role);
        return $this->db->all();
    }

    public function getTeams($userId) {
        $sql = "SELECT t.* FROM teams t
                JOIN team_users tu ON t.id = tu.team_id
                WHERE tu.user_id = :user_id";
        return $this->query($sql, array(':user_id' => $userId));
    }
}
