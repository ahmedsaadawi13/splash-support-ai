<?php
// FILE: /app/models/Tenant.php

/**
 * SplashSupportAI - Tenant Model
 */

class Tenant extends Model {
    protected $table = 'tenants';

    public function findByCode($code) {
        $this->db->query("SELECT * FROM {$this->table} WHERE code = :code LIMIT 1");
        $this->db->bind(':code', $code);
        return $this->db->single();
    }

    public function getActiveSubscription($tenantId) {
        $sql = "SELECT ts.*, p.name as plan_name, p.price, p.max_users, p.max_tickets_per_month,
                       p.max_ai_tokens_per_month, p.max_channels, p.features_json
                FROM tenant_subscriptions ts
                JOIN plans p ON ts.plan_id = p.id
                WHERE ts.tenant_id = :tenant_id
                AND ts.status = 'active'
                ORDER BY ts.created_at DESC
                LIMIT 1";
        return $this->querySingle($sql, array(':tenant_id' => $tenantId));
    }

    public function getUsage($tenantId) {
        $this->db->query("SELECT * FROM tenant_usage WHERE tenant_id = :tenant_id LIMIT 1");
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->single();
    }

    public function updateUsage($tenantId, $data) {
        $setParts = array();
        foreach ($data as $key => $value) {
            $setParts[] = "{$key} = :{$key}";
        }
        $setClause = implode(', ', $setParts);

        $this->db->query("UPDATE tenant_usage SET {$setClause}, updated_at = NOW() WHERE tenant_id = :tenant_id");
        foreach ($data as $key => $value) {
            $this->db->bind(':' . $key, $value);
        }
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->execute();
    }

    public function isQuotaExceeded($tenantId, $quotaType) {
        $subscription = $this->getActiveSubscription($tenantId);
        $usage = $this->getUsage($tenantId);

        if (!$subscription || !$usage) {
            return true;
        }

        switch ($quotaType) {
            case 'tickets':
                $max = $subscription['max_tickets_per_month'];
                $current = $usage['current_ticket_count_month'];
                break;
            case 'ai_tokens':
                $max = $subscription['max_ai_tokens_per_month'];
                $current = $usage['current_ai_tokens_used_month'];
                break;
            default:
                return false;
        }

        // Null means unlimited
        if ($max === null) {
            return false;
        }

        return $current >= $max;
    }
}
