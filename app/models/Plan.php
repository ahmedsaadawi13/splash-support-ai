<?php
// FILE: /app/models/Plan.php

/**
 * SplashSupportAI - Subscription Plan Model
 */

class Plan extends Model {
    protected $table = 'plans';

    public function getActivePlans() {
        $this->db->query("SELECT * FROM {$this->table} WHERE is_active = 1 ORDER BY price ASC");
        return $this->db->all();
    }

    public function getFeatures($planId) {
        $plan = $this->findById($planId);
        if ($plan && $plan['features_json']) {
            return json_decode($plan['features_json'], true);
        }
        return array();
    }
}
