<?php
// FILE: /app/controllers/TicketsController.php

/**
 * SplashSupportAI - Tickets Controller
 */

class TicketsController extends Controller {

    public function index() {
        $tenantId = $this->getCurrentTenantId();

        $filters = array(
            'status' => $this->input('status'),
            'priority' => $this->input('priority'),
            'channel_id' => $this->input('channel_id'),
            'assigned_to_me' => $this->input('assigned_to_me')
        );

        $page = max(1, (int)$this->input('page', 1));
        $limit = ITEMS_PER_PAGE;
        $offset = ($page - 1) * $limit;

        $ticketModel = $this->model('Ticket');
        $tickets = $ticketModel->getTicketsForUser($tenantId, $this->currentUser['id'], $filters, $limit, $offset);

        $channelModel = $this->model('Channel');
        $channels = $channelModel->findByTenant($tenantId);

        $this->view('tickets/index', array(
            'tickets' => $tickets,
            'channels' => $channels,
            'filters' => $filters,
            'page' => $page,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant
        ));
    }

    public function view($publicId) {
        $tenantId = $this->getCurrentTenantId();

        $ticketModel = $this->model('Ticket');
        $ticket = $ticketModel->findByPublicId($tenantId, $publicId);

        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        // Verify tenant access
        if (!SecurityHelper::validateTenantAccess($this->currentUser['tenant_id'], $ticket['tenant_id'], $this->currentUser['role'])) {
            $this->error('Access denied', 403);
        }

        $messages = $ticketModel->getMessages($ticket['id']);

        // Get SLA status
        $slaModel = $this->model('SLAPolicy');
        $slaStatus = $slaModel->checkAndUpdateSLAStatus($ticket['id']);

        // Get KB suggestions if AI is available
        $kbSuggestions = array();
        if (AIHelper::checkQuota($tenantId)['allowed']) {
            $aiResult = AIHelper::searchKnowledgeBase($tenantId, $ticket['subject'], 3);
            $kbSuggestions = $aiResult['results'];
        }

        $this->view('tickets/view', array(
            'ticket' => $ticket,
            'messages' => $messages,
            'slaStatus' => $slaStatus,
            'kbSuggestions' => $kbSuggestions,
            'user' => $this->currentUser,
            'tenant' => $this->currentTenant,
            'csrf_token' => $this->generateCsrf()
        ));
    }

    public function create() {
        $tenantId = $this->getCurrentTenantId();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->handleCreate();
        } else {
            $channelModel = $this->model('Channel');
            $channels = $channelModel->getActiveChannels($tenantId);

            $this->view('tickets/create', array(
                'channels' => $channels,
                'user' => $this->currentUser,
                'tenant' => $this->currentTenant,
                'csrf_token' => $this->generateCsrf()
            ));
        }
    }

    private function handleCreate() {
        $this->verifyCsrf();
        $tenantId = $this->getCurrentTenantId();

        // Check quota
        $tenantModel = $this->model('Tenant');
        if ($tenantModel->isQuotaExceeded($tenantId, 'tickets')) {
            $this->flash('error', 'Monthly ticket quota exceeded. Please upgrade your plan.');
            $this->redirect('/tickets');
        }

        $errors = ValidationHelper::validate($_POST, array(
            'subject' => 'required|max:500',
            'message' => 'required',
            'customer_email' => 'required|email',
            'priority' => 'required|enum:low,normal,high,urgent'
        ));

        if (!empty($errors)) {
            $channelModel = $this->model('Channel');
            $channels = $channelModel->getActiveChannels($tenantId);

            $this->view('tickets/create', array(
                'errors' => $errors,
                'channels' => $channels,
                'user' => $this->currentUser,
                'tenant' => $this->currentTenant,
                'csrf_token' => $this->generateCsrf()
            ));
            return;
        }

        try {
            $ticketModel = $this->model('Ticket');
            $customerModel = $this->model('Customer');

            // Find or create customer
            $customerId = $customerModel->findOrCreate($tenantId, array(
                'name' => $this->input('customer_name'),
                'email' => $this->input('customer_email'),
                'phone' => $this->input('customer_phone')
            ));

            // Create ticket
            $ticketData = array(
                'tenant_id' => $tenantId,
                'public_id' => $ticketModel->generatePublicId($tenantId),
                'channel_id' => $this->input('channel_id'),
                'customer_id' => $customerId,
                'subject' => $this->input('subject'),
                'customer_name' => $this->input('customer_name'),
                'customer_email' => $this->input('customer_email'),
                'customer_phone' => $this->input('customer_phone'),
                'priority' => $this->input('priority'),
                'type' => $this->input('type', 'question'),
                'status' => 'new'
            );

            $ticketId = $ticketModel->insert($ticketData);
            $ticket = $ticketModel->findById($ticketId);

            // Add first message
            $ticketModel->addMessage($tenantId, $ticketId, array(
                'sender_type' => 'customer',
                'body_text' => $this->input('message')
            ));

            // Apply routing rules
            $routingModel = $this->model('RoutingRule');
            $assignment = $routingModel->applyRules($tenantId, $ticket);

            if ($assignment) {
                if (isset($assignment['user_id'])) {
                    $ticketModel->assign($ticketId, $assignment['user_id'], null);
                } elseif (isset($assignment['team_id'])) {
                    $ticketModel->assign($ticketId, null, $assignment['team_id']);
                }
            }

            // Create SLA tracking
            $slaModel = $this->model('SLAPolicy');
            $policies = $slaModel->getActivePolicies($tenantId);
            if (!empty($policies)) {
                $slaModel->createSLAStatus($tenantId, $ticketId, $policies[0]['id'], $ticket['created_at']);
            }

            // Update usage
            $tenantModel->updateUsage($tenantId, array(
                'current_ticket_count_month' => 'current_ticket_count_month + 1'
            ));

            // Log activity
            $activityLog = $this->model('ActivityLog');
            $activityLog->log($tenantId, $this->currentUser['id'], 'ticket', $ticketId, 'created', "Ticket {$ticket['public_id']} created");

            $this->flash('success', 'Ticket created successfully');
            $this->redirect('/tickets/view/' . $ticket['public_id']);
        } catch (Exception $e) {
            $this->flash('error', 'Failed to create ticket');
            $this->redirect('/tickets/create');
        }
    }

    public function reply($publicId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tickets/view/' . $publicId);
        }

        $this->verifyCsrf();
        $tenantId = $this->getCurrentTenantId();

        $ticketModel = $this->model('Ticket');
        $ticket = $ticketModel->findByPublicId($tenantId, $publicId);

        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        $message = $this->input('message');
        $isInternal = (bool)$this->input('is_internal', 0);

        if (empty($message)) {
            $this->flash('error', 'Message cannot be empty');
            $this->redirect('/tickets/view/' . $publicId);
        }

        try {
            $ticketModel->addMessage($tenantId, $ticket['id'], array(
                'sender_type' => 'agent',
                'sender_user_id' => $this->currentUser['id'],
                'body_text' => $message,
                'is_internal' => $isInternal ? 1 : 0
            ));

            // Update ticket status if it's new
            if ($ticket['status'] === 'new') {
                $ticketModel->updateStatus($ticket['id'], 'open');
            }

            // Log activity
            $activityLog = $this->model('ActivityLog');
            $activityLog->log($tenantId, $this->currentUser['id'], 'ticket', $ticket['id'], 'replied', "Agent replied to ticket {$ticket['public_id']}");

            $this->flash('success', 'Reply sent');
        } catch (Exception $e) {
            $this->flash('error', 'Failed to send reply');
        }

        $this->redirect('/tickets/view/' . $publicId);
    }

    public function updateStatus($publicId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->redirect('/tickets/view/' . $publicId);
        }

        $this->verifyCsrf();
        $tenantId = $this->getCurrentTenantId();

        $ticketModel = $this->model('Ticket');
        $ticket = $ticketModel->findByPublicId($tenantId, $publicId);

        if (!$ticket) {
            $this->error('Ticket not found', 404);
        }

        $newStatus = $this->input('status');
        $allowedStatuses = array('new', 'open', 'pending', 'on_hold', 'resolved', 'closed');

        if (!in_array($newStatus, $allowedStatuses)) {
            $this->flash('error', 'Invalid status');
            $this->redirect('/tickets/view/' . $publicId);
        }

        try {
            $ticketModel->updateStatus($ticket['id'], $newStatus);

            $activityLog = $this->model('ActivityLog');
            $activityLog->log($tenantId, $this->currentUser['id'], 'ticket', $ticket['id'], 'status_changed', "Ticket status changed to {$newStatus}");

            $this->flash('success', 'Ticket status updated');
        } catch (Exception $e) {
            $this->flash('error', 'Failed to update status');
        }

        $this->redirect('/tickets/view/' . $publicId);
    }

    public function aiSuggest($publicId) {
        $tenantId = $this->getCurrentTenantId();

        $ticketModel = $this->model('Ticket');
        $ticket = $ticketModel->findByPublicId($tenantId, $publicId);

        if (!$ticket) {
            $this->json(array('error' => 'Ticket not found'), 404);
        }

        // Check AI quota
        $quota = AIHelper::checkQuota($tenantId);
        if (!$quota['allowed']) {
            $this->json(array('error' => 'AI quota exceeded'), 429);
        }

        $messages = $ticketModel->getMessages($ticket['id']);
        $lastMessage = end($messages);

        $kbModel = $this->model('KBArticle');
        $kbArticles = $kbModel->search($tenantId, $ticket['subject'], 3);

        $result = AIHelper::suggestReply($tenantId, $ticket, $lastMessage, $kbArticles);

        $this->json(array(
            'success' => true,
            'suggestions' => $result['suggestions']
        ));
    }
}
