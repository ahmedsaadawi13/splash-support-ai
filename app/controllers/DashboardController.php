<?php
// FILE: /app/controllers/DashboardController.php

/**
 * SplashSupportAI - Dashboard Controller
 */

class DashboardController extends Controller {

    public function index() {
        $tenantId = $this->getCurrentTenantId();

        if ($this->currentUser['role'] === 'platform_admin') {
            $this->platformAdminDashboard();
            return;
        }

        // Get stats
        $ticketModel = $this->model('Ticket');
        $stats = $ticketModel->getStats($tenantId, DateHelper::firstDayOfMonth(), DateHelper::lastDayOfMonth());

        // Get recent tickets
        $recentTickets = $ticketModel->getTicketsForUser(
            $tenantId,
            $this->currentUser['id'],
            array(),
            10
        );

        // Get notifications
        $notificationModel = $this->model('Notification');
        $notifications = $notificationModel->getUnreadForUser($this->currentUser['id'], 5);
        $unreadCount = $notificationModel->getUnreadCount($this->currentUser['id']);

        // Get subscription info
        $tenantModel = $this->model('Tenant');
        $subscription = $tenantModel->getActiveSubscription($tenantId);
        $usage = $tenantModel->getUsage($tenantId);

        $this->view('dashboard/index', array(
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'stats' => $stats,
            'recentTickets' => $recentTickets,
            'notifications' => $notifications,
            'unreadCount' => $unreadCount,
            'subscription' => $subscription,
            'usage' => $usage
        ));
    }

    private function platformAdminDashboard() {
        $tenantModel = $this->model('Tenant');
        $tenants = $tenantModel->findAll(50);

        $this->view('dashboard/platform_admin', array(
            'user' => $this->currentUser,
            'tenants' => $tenants
        ));
    }
}
