<?php
// FILE: /app/models/Customer.php

/**
 * SplashSupportAI - Customer Model
 */

class Customer extends Model {
    protected $table = 'customers';

    public function findByEmail($tenantId, $email) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND email = :email LIMIT 1");
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':email', $email);
        return $this->db->single();
    }

    public function findOrCreate($tenantId, $data) {
        if (!empty($data['email'])) {
            $existing = $this->findByEmail($tenantId, $data['email']);
            if ($existing) {
                return $existing['id'];
            }
        }

        $data['tenant_id'] = $tenantId;
        return $this->insert($data);
    }
}
