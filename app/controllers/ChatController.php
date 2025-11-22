<?php
// FILE: /app/controllers/ChatController.php

/**
 * SplashSupportAI - Chat Widget Controller
 */

class ChatController {

    public function __construct() {
        header('Content-Type: application/json');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            exit;
        }
    }

    public function init() {
        $tenantCode = $_POST['tenant_code'] ?? $_GET['tenant_code'] ?? null;

        if (!$tenantCode) {
            echo json_encode(array('error' => 'Tenant code required'));
            return;
        }

        $tenantModel = new Tenant();
        $tenant = $tenantModel->findByCode($tenantCode);

        if (!$tenant) {
            echo json_encode(array('error' => 'Invalid tenant'));
            return;
        }

        // Check if session exists
        $sessionToken = $_POST['session_token'] ?? $_GET['session_token'] ?? null;

        if ($sessionToken) {
            $db = new Database();
            $db->query("SELECT * FROM chat_sessions WHERE session_token = :token AND tenant_id = :tenant_id LIMIT 1");
            $db->bind(':token', $sessionToken);
            $db->bind(':tenant_id', $tenant['id']);
            $session = $db->single();

            if ($session) {
                echo json_encode(array(
                    'status' => 'success',
                    'session_token' => $sessionToken,
                    'session' => $session
                ));
                return;
            }
        }

        // Create new session
        $sessionToken = SecurityHelper::generateSessionToken();

        $db = new Database();
        $db->query("INSERT INTO chat_sessions (tenant_id, session_token, status) VALUES (:tenant_id, :token, 'open')");
        $db->bind(':tenant_id', $tenant['id']);
        $db->bind(':token', $sessionToken);
        $db->execute();

        $sessionId = $db->lastInsertId();

        echo json_encode(array(
            'status' => 'success',
            'session_token' => $sessionToken,
            'session_id' => $sessionId,
            'tenant_name' => $tenant['name']
        ));
    }

    public function message() {
        $input = json_decode(file_get_contents('php://input'), true);

        $sessionToken = $input['session_token'] ?? null;
        $message = $input['message'] ?? null;

        if (!$sessionToken || !$message) {
            echo json_encode(array('error' => 'Session token and message required'));
            return;
        }

        $db = new Database();
        $db->query("SELECT * FROM chat_sessions WHERE session_token = :token LIMIT 1");
        $db->bind(':token', $sessionToken);
        $session = $db->single();

        if (!$session) {
            echo json_encode(array('error' => 'Invalid session'));
            return;
        }

        // Update session info if provided
        if (!empty($input['customer_name']) && !$session['customer_name']) {
            $db->query("UPDATE chat_sessions SET customer_name = :name WHERE id = :id");
            $db->bind(':name', $input['customer_name']);
            $db->bind(':id', $session['id']);
            $db->execute();
        }

        if (!empty($input['customer_email']) && !$session['customer_email']) {
            $db->query("UPDATE chat_sessions SET customer_email = :email WHERE id = :id");
            $db->bind(':email', $input['customer_email']);
            $db->bind(':id', $session['id']);
            $db->execute();
        }

        // Create or get ticket
        $ticketId = $session['ticket_id'];

        if (!$ticketId) {
            // Create ticket for this chat session
            $ticketModel = new Ticket();
            $channelModel = new Channel();

            $chatChannels = $channelModel->findByType($session['tenant_id'], 'web_chat');
            $channelId = !empty($chatChannels) ? $chatChannels[0]['id'] : null;

            $ticketData = array(
                'tenant_id' => $session['tenant_id'],
                'public_id' => $ticketModel->generatePublicId($session['tenant_id']),
                'channel_id' => $channelId,
                'subject' => 'Chat conversation - ' . date('Y-m-d H:i:s'),
                'customer_name' => $session['customer_name'] ?? 'Website Visitor',
                'customer_email' => $session['customer_email'] ?? null,
                'priority' => 'normal',
                'type' => 'question',
                'status' => 'new'
            );

            $ticketId = $ticketModel->insert($ticketData);

            // Update session with ticket ID
            $db->query("UPDATE chat_sessions SET ticket_id = :ticket_id WHERE id = :id");
            $db->bind(':ticket_id', $ticketId);
            $db->bind(':id', $session['id']);
            $db->execute();
        }

        // Add message to ticket
        $ticketModel = new Ticket();
        $ticketModel->addMessage($session['tenant_id'], $ticketId, array(
            'sender_type' => 'customer',
            'body_text' => $message
        ));

        // Try AI chatbot reply
        $quota = AIHelper::checkQuota($session['tenant_id']);
        $aiReply = null;

        if ($quota['allowed']) {
            $chatbotResult = AIHelper::chatbotReply($session['tenant_id'], $message);

            if ($chatbotResult['should_escalate']) {
                // Escalate to human
                $aiReply = $chatbotResult['reply'];
                $ticketModel->updateStatus($ticketId, 'open');
            } else {
                // Auto reply
                $aiReply = $chatbotResult['reply'];
                $ticketModel->addMessage($session['tenant_id'], $ticketId, array(
                    'sender_type' => 'ai',
                    'body_text' => $aiReply
                ));
            }
        }

        echo json_encode(array(
            'status' => 'success',
            'ai_reply' => $aiReply,
            'ticket_id' => $ticketId
        ));
    }

    public function getMessages() {
        $sessionToken = $_GET['session_token'] ?? null;

        if (!$sessionToken) {
            echo json_encode(array('error' => 'Session token required'));
            return;
        }

        $db = new Database();
        $db->query("SELECT * FROM chat_sessions WHERE session_token = :token LIMIT 1");
        $db->bind(':token', $sessionToken);
        $session = $db->single();

        if (!$session || !$session['ticket_id']) {
            echo json_encode(array('messages' => array()));
            return;
        }

        $ticketModel = new Ticket();
        $messages = $ticketModel->getMessages($session['ticket_id']);

        echo json_encode(array('messages' => $messages));
    }
}
