<?php
// FILE: /app/models/Ticket.php

/**
 * SplashSupportAI - Ticket Model
 */

class Ticket extends Model {
    protected $table = 'tickets';

    public function findByPublicId($tenantId, $publicId) {
        $this->db->query("SELECT * FROM {$this->table} WHERE tenant_id = :tenant_id AND public_id = :public_id LIMIT 1");
        $this->db->bind(':tenant_id', $tenantId);
        $this->db->bind(':public_id', $publicId);
        return $this->db->single();
    }

    public function generatePublicId($tenantId) {
        // Get last ticket number for tenant
        $this->db->query("SELECT public_id FROM {$this->table} WHERE tenant_id = :tenant_id ORDER BY id DESC LIMIT 1");
        $this->db->bind(':tenant_id', $tenantId);
        $last = $this->db->single();

        if ($last) {
            $lastNumber = (int) str_replace('TCK-', '', $last['public_id']);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 10001;
        }

        return 'TCK-' . $nextNumber;
    }

    public function getTicketsForUser($tenantId, $userId, $filters = array(), $limit = 25, $offset = 0) {
        $where = array("t.tenant_id = :tenant_id");
        $params = array(':tenant_id' => $tenantId);

        // Filter by assignment
        if (isset($filters['assigned_to_me']) && $filters['assigned_to_me']) {
            $where[] = "t.assignee_user_id = :user_id";
            $params[':user_id'] = $userId;
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $where[] = "t.status = :status";
            $params[':status'] = $filters['status'];
        }

        // Filter by priority
        if (!empty($filters['priority'])) {
            $where[] = "t.priority = :priority";
            $params[':priority'] = $filters['priority'];
        }

        // Filter by channel
        if (!empty($filters['channel_id'])) {
            $where[] = "t.channel_id = :channel_id";
            $params[':channel_id'] = $filters['channel_id'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT t.*, c.name as channel_name, u.name as assignee_name
                FROM {$this->table} t
                LEFT JOIN channels c ON t.channel_id = c.id
                LEFT JOIN users u ON t.assignee_user_id = u.id
                WHERE {$whereClause}
                ORDER BY t.created_at DESC
                LIMIT :limit OFFSET :offset";

        $this->db->query($sql);
        foreach ($params as $key => $value) {
            $this->db->bind($key, $value);
        }
        $this->db->bind(':limit', $limit, PDO::PARAM_INT);
        $this->db->bind(':offset', $offset, PDO::PARAM_INT);

        return $this->db->all();
    }

    public function getMessages($ticketId) {
        $sql = "SELECT tm.*, u.name as sender_name
                FROM ticket_messages tm
                LEFT JOIN users u ON tm.sender_user_id = u.id
                WHERE tm.ticket_id = :ticket_id
                ORDER BY tm.created_at ASC";
        return $this->query($sql, array(':ticket_id' => $ticketId));
    }

    public function addMessage($tenantId, $ticketId, $data) {
        $data['tenant_id'] = $tenantId;
        $data['ticket_id'] = $ticketId;

        $messageModel = new Model();
        $messageModel->table = 'ticket_messages';
        $messageId = $messageModel->insert($data);

        // Update ticket timestamps
        $updateData = array();
        if ($data['sender_type'] === 'agent' || $data['sender_type'] === 'ai') {
            $updateData['last_agent_reply_at'] = DateHelper::now();
            if (!isset($this->findById($ticketId)['first_response_at'])) {
                $updateData['first_response_at'] = DateHelper::now();
            }
        } elseif ($data['sender_type'] === 'customer') {
            $updateData['last_customer_reply_at'] = DateHelper::now();
        }

        if (!empty($updateData)) {
            $this->update($ticketId, $updateData);
        }

        return $messageId;
    }

    public function updateStatus($ticketId, $status) {
        $data = array('status' => $status);
        if ($status === 'resolved' || $status === 'closed') {
            $data['resolved_at'] = DateHelper::now();
        }
        return $this->update($ticketId, $data);
    }

    public function assign($ticketId, $userId = null, $teamId = null) {
        $data = array();
        if ($userId !== null) {
            $data['assignee_user_id'] = $userId;
        }
        if ($teamId !== null) {
            $data['team_id'] = $teamId;
        }
        return $this->update($ticketId, $data);
    }

    public function getStats($tenantId, $dateFrom = null, $dateTo = null) {
        $where = "tenant_id = :tenant_id";
        $params = array(':tenant_id' => $tenantId);

        if ($dateFrom) {
            $where .= " AND created_at >= :date_from";
            $params[':date_from'] = $dateFrom;
        }
        if ($dateTo) {
            $where .= " AND created_at <= :date_to";
            $params[':date_to'] = $dateTo;
        }

        $sql = "SELECT
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'new' THEN 1 ELSE 0 END) as new_count,
                    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed_count,
                    SUM(CASE WHEN priority = 'urgent' THEN 1 ELSE 0 END) as urgent_count
                FROM {$this->table}
                WHERE {$where}";

        return $this->querySingle($sql, $params);
    }
}
