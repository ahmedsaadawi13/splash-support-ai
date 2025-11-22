<?php
// FILE: /app/models/Notification.php

/**
 * SplashSupportAI - Notification Model
 */

class Notification extends Model {
    protected $table = 'notifications';

    public function create($tenantId, $userId, $type, $title, $message) {
        $data = array(
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'is_read' => 0
        );
        return $this->insert($data);
    }

    public function getUnreadForUser($userId, $limit = 20) {
        $this->db->query("SELECT * FROM {$this->table} WHERE user_id = :user_id AND is_read = 0 ORDER BY created_at DESC LIMIT :limit");
        $this->db->bind(':user_id', $userId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        return $this->db->all();
    }

    public function markAsRead($notificationId) {
        $this->db->query("UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE id = :id");
        $this->db->bind(':id', $notificationId);
        return $this->db->execute();
    }

    public function markAllAsRead($userId) {
        $this->db->query("UPDATE {$this->table} SET is_read = 1, read_at = NOW() WHERE user_id = :user_id AND is_read = 0");
        $this->db->bind(':user_id', $userId);
        return $this->db->execute();
    }

    public function getUnreadCount($userId) {
        $this->db->query("SELECT COUNT(*) as count FROM {$this->table} WHERE user_id = :user_id AND is_read = 0");
        $this->db->bind(':user_id', $userId);
        $result = $this->db->single();
        return $result['count'];
    }
}
