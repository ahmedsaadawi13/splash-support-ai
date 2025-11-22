<?php
// FILE: /app/models/SLAPolicy.php

/**
 * SplashSupportAI - SLA Policy Model
 */

class SLAPolicy extends Model {
    protected $table = 'sla_policies';

    public function getActivePolicies($tenantId) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND is_active = 1");
        $this->db->bind(':tenant_id', $tenantId);
        return $this->db->all();
    }

    public function createSLAStatus($tenantId, $ticketId, $policyId, $ticketCreatedAt) {
        $policy = $this->findById($policyId);
        if (!$policy) {
            return false;
        }

        $data = array(
            'tenant_id' => $tenantId,
            'ticket_id' => $ticketId,
            'policy_id' => $policyId,
            'first_response_due_at' => DateHelper::calculateSLADue($ticketCreatedAt, $policy['first_response_time_minutes']),
            'resolution_due_at' => DateHelper::calculateSLADue($ticketCreatedAt, $policy['resolution_time_minutes']),
            'first_response_met' => 0,
            'resolution_met' => 0,
            'breached' => 0
        );

        $statusModel = new Model();
        $statusModel->table = 'ticket_sla_status';
        return $statusModel->insert($data);
    }

    public function checkAndUpdateSLAStatus($ticketId) {
        $statusModel = new Model();
        $statusModel->table = 'ticket_sla_status';

        $sql = "SELECT * FROM ticket_sla_status WHERE ticket_id = :ticket_id LIMIT 1";
        $statusModel->db->query($sql);
        $statusModel->db->bind(':ticket_id', $ticketId);
        $status = $statusModel->db->single();

        if (!$status) {
            return null;
        }

        $ticketModel = new Ticket();
        $ticket = $ticketModel->findById($ticketId);

        $updates = array();

        // Check first response
        if (!$status['first_response_met'] && $ticket['first_response_at']) {
            $updates['first_response_met'] = 1;
        }

        // Check resolution
        if (!$status['resolution_met'] && $ticket['resolved_at']) {
            $updates['resolution_met'] = 1;
        }

        // Check for breach
        if (!$status['breached']) {
            if (DateHelper::isSLABreached($status['first_response_due_at']) && !$ticket['first_response_at']) {
                $updates['breached'] = 1;
            } elseif (DateHelper::isSLABreached($status['resolution_due_at']) && !$ticket['resolved_at']) {
                $updates['breached'] = 1;
            }
        }

        if (!empty($updates)) {
            $updates['last_checked_at'] = DateHelper::now();
            $statusModel->update($status['id'], $updates);
        }

        return $statusModel->findById($status['id']);
    }
}
