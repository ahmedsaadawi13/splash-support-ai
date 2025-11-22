<?php
// FILE: /app/controllers/ApiController.php

/**
 * SplashSupportAI - API Controller
 * Handles public REST API requests
 */

class ApiController {

    private $tenant = null;
    private $apiKey = null;

    public function __construct() {
        header('Content-Type: application/json');
        $this->authenticate();
    }

    private function authenticate() {
        $apiKey = $_SERVER['HTTP_X_API_KEY'] ?? null;

        if (!$apiKey) {
            http_response_code(401);
            echo json_encode(array('error' => 'API key required'));
            exit;
        }

        $db = new Database();
        $db->query("SELECT tak.*, t.* FROM tenant_api_keys tak
                    JOIN tenants t ON tak.tenant_id = t.id
                    WHERE tak.api_key = :api_key AND tak.is_active = 1 AND t.status = 'active'
                    LIMIT 1");
        $db->bind(':api_key', $apiKey);
        $result = $db->single();

        if (!$result) {
            http_response_code(401);
            echo json_encode(array('error' => 'Invalid API key'));
            exit;
        }

        $this->tenant = $result;
        $this->apiKey = $apiKey;

        // Update last used
        $db->query("UPDATE tenant_api_keys SET last_used_at = NOW() WHERE api_key = :api_key");
        $db->bind(':api_key', $apiKey);
        $db->execute();

        // Track API usage
        $db->query("UPDATE tenant_usage SET current_api_calls_month = current_api_calls_month + 1 WHERE tenant_id = :tenant_id");
        $db->bind(':tenant_id', $this->tenant['tenant_id']);
        $db->execute();
    }

    public function tickets() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->createTicket();
        } elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
            $this->listTickets();
        } else {
            http_response_code(405);
            echo json_encode(array('error' => 'Method not allowed'));
        }
    }

    private function createTicket() {
        $input = json_decode(file_get_contents('php://input'), true);

        if (!$input) {
            http_response_code(400);
            echo json_encode(array('error' => 'Invalid JSON'));
            return;
        }

        // Check quota
        $tenantModel = new Tenant();
        if ($tenantModel->isQuotaExceeded($this->tenant['tenant_id'], 'tickets')) {
            http_response_code(429);
            echo json_encode(array('error' => 'Monthly ticket quota exceeded'));
            return;
        }

        // Validate required fields
        if (empty($input['subject']) || empty($input['message'])) {
            http_response_code(400);
            echo json_encode(array('error' => 'Subject and message are required'));
            return;
        }

        if (empty($input['customer']['email'])) {
            http_response_code(400);
            echo json_encode(array('error' => 'Customer email is required'));
            return;
        }

        try {
            $ticketModel = new Ticket();
            $customerModel = new Customer();

            // Find or create customer
            $customerId = $customerModel->findOrCreate($this->tenant['tenant_id'], array(
                'name' => $input['customer']['name'] ?? 'Unknown',
                'email' => $input['customer']['email'],
                'phone' => $input['customer']['phone'] ?? null
            ));

            // Find API channel
            $channelModel = new Channel();
            $apiChannels = $channelModel->findByType($this->tenant['tenant_id'], 'api');
            $channelId = !empty($apiChannels) ? $apiChannels[0]['id'] : null;

            // Create ticket
            $ticketData = array(
                'tenant_id' => $this->tenant['tenant_id'],
                'public_id' => $ticketModel->generatePublicId($this->tenant['tenant_id']),
                'channel_id' => $channelId,
                'customer_id' => $customerId,
                'subject' => $input['subject'],
                'customer_name' => $input['customer']['name'] ?? 'Unknown',
                'customer_email' => $input['customer']['email'],
                'customer_phone' => $input['customer']['phone'] ?? null,
                'priority' => $input['priority'] ?? 'normal',
                'type' => $input['type'] ?? 'question',
                'status' => 'new'
            );

            $ticketId = $ticketModel->insert($ticketData);
            $ticket = $ticketModel->findById($ticketId);

            // Add first message
            $ticketModel->addMessage($this->tenant['tenant_id'], $ticketId, array(
                'sender_type' => 'customer',
                'body_text' => $input['message']
            ));

            // Apply routing rules
            $routingModel = new RoutingRule();
            $assignment = $routingModel->applyRules($this->tenant['tenant_id'], $ticket);

            if ($assignment) {
                if (isset($assignment['user_id'])) {
                    $ticketModel->assign($ticketId, $assignment['user_id'], null);
                } elseif (isset($assignment['team_id'])) {
                    $ticketModel->assign($ticketId, null, $assignment['team_id']);
                }
            }

            // Update usage
            $tenantModel->updateUsage($this->tenant['tenant_id'], array(
                'current_ticket_count_month' => 'current_ticket_count_month + 1'
            ));

            http_response_code(201);
            echo json_encode(array(
                'status' => 'success',
                'ticket_id' => $ticketId,
                'public_id' => $ticket['public_id'],
                'message' => 'Ticket created successfully'
            ));
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array('error' => 'Failed to create ticket'));
        }
    }

    private function listTickets() {
        $status = $_GET['status'] ?? null;
        $page = max(1, (int)($_GET['page'] ?? 1));
        $limit = min(100, max(1, (int)($_GET['limit'] ?? 25)));
        $offset = ($page - 1) * $limit;

        $ticketModel = new Ticket();

        $filters = array();
        if ($status) {
            $filters['status'] = $status;
        }

        // For API, we don't filter by user, return all tenant tickets
        $db = new Database();
        $where = array("tenant_id = :tenant_id");
        $params = array(':tenant_id' => $this->tenant['tenant_id']);

        if ($status) {
            $where[] = "status = :status";
            $params[':status'] = $status;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT * FROM tickets WHERE {$whereClause} ORDER BY created_at DESC LIMIT :limit OFFSET :offset";
        $db->query($sql);
        foreach ($params as $key => $value) {
            $db->bind($key, $value);
        }
        $db->bind(':limit', $limit, PDO::PARAM_INT);
        $db->bind(':offset', $offset, PDO::PARAM_INT);

        $tickets = $db->all();

        echo json_encode(array(
            'status' => 'success',
            'data' => $tickets,
            'page' => $page,
            'limit' => $limit
        ));
    }

    public function reply($publicId) {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(array('error' => 'Method not allowed'));
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true);

        $ticketModel = new Ticket();
        $ticket = $ticketModel->findByPublicId($this->tenant['tenant_id'], $publicId);

        if (!$ticket) {
            http_response_code(404);
            echo json_encode(array('error' => 'Ticket not found'));
            return;
        }

        if (empty($input['message'])) {
            http_response_code(400);
            echo json_encode(array('error' => 'Message is required'));
            return;
        }

        $senderType = $input['from'] ?? 'customer';
        if (!in_array($senderType, array('customer', 'agent'))) {
            $senderType = 'customer';
        }

        try {
            $ticketModel->addMessage($this->tenant['tenant_id'], $ticket['id'], array(
                'sender_type' => $senderType,
                'body_text' => $input['message']
            ));

            if ($ticket['status'] === 'new') {
                $ticketModel->updateStatus($ticket['id'], 'open');
            }

            http_response_code(200);
            echo json_encode(array(
                'status' => 'success',
                'message' => 'Reply added successfully'
            ));
        } catch (Exception $e) {
            http_response_code(500);
            echo json_encode(array('error' => 'Failed to add reply'));
        }
    }

    public function show($publicId) {
        $ticketModel = new Ticket();
        $ticket = $ticketModel->findByPublicId($this->tenant['tenant_id'], $publicId);

        if (!$ticket) {
            http_response_code(404);
            echo json_encode(array('error' => 'Ticket not found'));
            return;
        }

        $messages = $ticketModel->getMessages($ticket['id']);

        echo json_encode(array(
            'status' => 'success',
            'ticket' => $ticket,
            'messages' => $messages
        ));
    }
}
