<?php
// FILE: /app/controllers/ReportsController.php

/**
 * SplashSupportAI - Reports Controller
 */

class ReportsController extends Controller {

    public function index() {
        $this->requireRole(array('tenant_admin', 'support_manager'));
        $tenantId = $this->getCurrentTenantId();

        $dateFrom = $this->input('date_from', date('Y-m-01'));
        $dateTo = $this->input('date_to', date('Y-m-t'));

        $ticketModel = $this->model('Ticket');

        // Get overview stats
        $stats = $ticketModel->getStats($tenantId, $dateFrom . ' 00:00:00', $dateTo . ' 23:59:59');

        // Tickets by status
        $db = new Database();
        $sql = "SELECT status, COUNT(*) as count
                FROM tickets
                WHERE tenant_id = :tenant_id
                AND created_at BETWEEN :date_from AND :date_to
                GROUP BY status";
        $db->query($sql);
        $db->bind(':tenant_id', $tenantId);
        $db->bind(':date_from', $dateFrom . ' 00:00:00');
        $db->bind(':date_to', $dateTo . ' 23:59:59');
        $ticketsByStatus = $db->all();

        // Tickets by channel
        $sql = "SELECT c.name as channel_name, COUNT(t.id) as count
                FROM tickets t
                LEFT JOIN channels c ON t.channel_id = c.id
                WHERE t.tenant_id = :tenant_id
                AND t.created_at BETWEEN :date_from AND :date_to
                GROUP BY c.name";
        $db->query($sql);
        $db->bind(':tenant_id', $tenantId);
        $db->bind(':date_from', $dateFrom . ' 00:00:00');
        $db->bind(':date_to', $dateTo . ' 23:59:59');
        $ticketsByChannel = $db->all();

        // Tickets by agent
        $sql = "SELECT u.name as agent_name, COUNT(t.id) as count
                FROM tickets t
                LEFT JOIN users u ON t.assignee_user_id = u.id
                WHERE t.tenant_id = :tenant_id
                AND t.created_at BETWEEN :date_from AND :date_to
                GROUP BY u.name";
        $db->query($sql);
        $db->bind(':tenant_id', $tenantId);
        $db->bind(':date_from', $dateFrom . ' 00:00:00');
        $db->bind(':date_to', $dateTo . ' 23:59:59');
        $ticketsByAgent = $db->all();

        // SLA compliance
        $sql = "SELECT
                    COUNT(*) as total_sla_tickets,
                    SUM(CASE WHEN first_response_met = 1 THEN 1 ELSE 0 END) as first_response_met_count,
                    SUM(CASE WHEN resolution_met = 1 THEN 1 ELSE 0 END) as resolution_met_count,
                    SUM(CASE WHEN breached = 1 THEN 1 ELSE 0 END) as breached_count
                FROM ticket_sla_status tss
                JOIN tickets t ON tss.ticket_id = t.id
                WHERE tss.tenant_id = :tenant_id
                AND t.created_at BETWEEN :date_from AND :date_to";
        $db->query($sql);
        $db->bind(':tenant_id', $tenantId);
        $db->bind(':date_from', $dateFrom . ' 00:00:00');
        $db->bind(':date_to', $dateTo . ' 23:59:59');
        $slaStats = $db->single();

        $this->view('reports/index', array(
            'stats' => $stats,
            'ticketsByStatus' => $ticketsByStatus,
            'ticketsByChannel' => $ticketsByChannel,
            'ticketsByAgent' => $ticketsByAgent,
            'slaStats' => $slaStats,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant
        ));
    }

    public function export() {
        $this->requireRole(array('tenant_admin', 'support_manager'));
        $tenantId = $this->getCurrentTenantId();

        $dateFrom = $this->input('date_from', date('Y-m-01'));
        $dateTo = $this->input('date_to', date('Y-m-t'));

        $db = new Database();
        $sql = "SELECT t.public_id, t.subject, t.customer_email, t.status, t.priority,
                       c.name as channel_name, u.name as assignee_name,
                       t.created_at, t.resolved_at
                FROM tickets t
                LEFT JOIN channels c ON t.channel_id = c.id
                LEFT JOIN users u ON t.assignee_user_id = u.id
                WHERE t.tenant_id = :tenant_id
                AND t.created_at BETWEEN :date_from AND :date_to
                ORDER BY t.created_at DESC";

        $db->query($sql);
        $db->bind(':tenant_id', $tenantId);
        $db->bind(':date_from', $dateFrom . ' 00:00:00');
        $db->bind(':date_to', $dateTo . ' 23:59:59');
        $tickets = $db->all();

        // Generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="tickets_report_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, array('Ticket ID', 'Subject', 'Customer Email', 'Status', 'Priority', 'Channel', 'Assignee', 'Created At', 'Resolved At'));

        foreach ($tickets as $ticket) {
            fputcsv($output, $ticket);
        }

        fclose($output);
        exit;
    }
}
