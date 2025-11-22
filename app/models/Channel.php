<?php
// FILE: /app/models/Channel.php

/**
 * SplashSupportAI - Channel Model
 */

class Channel extends Model {
    protected $table = 'channels';

    public function getActiveChannels($tenantId) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND is_active = 1");
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->all();
    }

    public function findByType($tenantId, $type) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND type = :type");
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':type', $type);
        return $this->db->all();
    }
}
