<?php
// FILE: /app/models/ActivityLog.php

/**
 * SplashSupportAI - Activity Log Model
 */

class ActivityLog extends Model {
    protected $table = 'activity_logs';

    public function log($tenantId, $userId, $entityType, $entityId, $action, $description = null) {
        $data = array(
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'action' => $action,
            'description' => $description,
            'ip_address' => SecurityHelper::getClientIp(),
            'user_agent' => SecurityHelper::getUserAgent()
        );

        return $this->insert($data);
    }

    public function getRecentActivity($tenantId, $limit = 50) {
        $sql = "SELECT al.*, u.name as user_name
                FROM {$this->table} al
                LEFT JOIN users u ON al.user_id = u.id
                WHERE al.tenant_id = :tenant_id
                ORDER BY al.created_at DESC
                LIMIT :limit";

        $this->db->query($sql);
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);

        return $this->db->all();
    }
}
