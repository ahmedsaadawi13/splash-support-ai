<?php
// FILE: /tests/test_ticket_creation.php

/**
 * SplashSupportAI - Ticket Creation Test
 * Run this file to test ticket creation functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/core/Model.php';
require_once APP_PATH . '/models/Ticket.php';
require_once APP_PATH . '/models/Customer.php';
require_once APP_PATH . '/helpers/DateHelper.php';

echo "=== SplashSupportAI Ticket Creation Test ===\n\n";

try {
    // Get first tenant
    $db = new Database();
    $db->query("SELECT * FROM tenants WHERE status = 'active' LIMIT 1");
    $tenant = $db->single();

    if (!$tenant) {
        echo "✗ No active tenant found. Please run database.sql first.\n";
        exit;
    }

    echo "Testing with tenant: {$tenant['name']}\n\n";

    // Create customer
    $customerModel = new Customer();
    $customerId = $customerModel->findOrCreate($tenant['id'], array(
        'name' => 'Test Customer',
        'email' => 'test@example.com',
        'phone' => '+1234567890'
    ));

    echo "✓ Customer created/found (ID: {$customerId})\n";

    // Create ticket
    $ticketModel = new Ticket();
    $publicId = $ticketModel->generatePublicId($tenant['id']);

    $ticketData = array(
        'tenant_id' => $tenant['id'],
        'public_id' => $publicId,
        'customer_id' => $customerId,
        'subject' => 'Test Ticket - ' . date('Y-m-d H:i:s'),
        'customer_name' => 'Test Customer',
        'customer_email' => 'test@example.com',
        'priority' => 'normal',
        'type' => 'question',
        'status' => 'new'
    );

    $ticketId = $ticketModel->insert($ticketData);
    echo "✓ Ticket created (ID: {$ticketId}, Public ID: {$publicId})\n";

    // Add message
    $ticketModel->addMessage($tenant['id'], $ticketId, array(
        'sender_type' => 'customer',
        'body_text' => 'This is a test message'
    ));

    echo "✓ Message added to ticket\n";

    // Retrieve ticket
    $ticket = $ticketModel->findByPublicId($tenant['id'], $publicId);
    $messages = $ticketModel->getMessages($ticketId);

    echo "✓ Ticket retrieved successfully\n";
    echo "  Status: {$ticket['status']}\n";
    echo "  Messages: " . count($messages) . "\n\n";

    echo "=== Test Complete ===\n";
} catch (Exception $e) {
    echo "✗ Test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
