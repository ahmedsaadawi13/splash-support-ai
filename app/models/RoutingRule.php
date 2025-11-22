<?php
// FILE: /app/models/RoutingRule.php

/**
 * SplashSupportAI - Routing Rule Model
 */

class RoutingRule extends Model {
    protected $table = 'routing_rules';

    public function getActiveRules($tenantId) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND is_active = 1 ORDER BY priority_order ASC");
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->all();
    }

    public function applyRules($tenantId, $ticket) {
        $rules = $this->getActiveRules($tenantId);

        foreach ($rules as $rule) {
            if ($this->matchesCriteria($ticket, $rule['criteria_json'])) {
                return $this->executeAction($rule['action_json']);
            }
        }

        return null;
    }

    private function matchesCriteria($ticket, $criteriaJson) {
        $criteria = json_decode($criteriaJson, true);
        if (!$criteria) {
            return false;
        }

        $field = $criteria['field'] ?? null;
        $operator = $criteria['operator'] ?? 'equals';
        $value = $criteria['value'] ?? null;

        if (!$field || !isset($ticket[$field])) {
            return false;
        }

        $ticketValue = strtolower($ticket[$field]);
        $compareValue = strtolower($value);

        switch ($operator) {
            case 'equals':
                return $ticketValue === $compareValue;
            case 'contains':
                return strpos($ticketValue, $compareValue) !== false;
            case 'starts_with':
                return strpos($ticketValue, $compareValue) === 0;
            default:
                return false;
        }
    }

    private function executeAction($actionJson) {
        $action = json_decode($actionJson, true);
        if (!$action) {
            return null;
        }

        return $action;
    }
}
