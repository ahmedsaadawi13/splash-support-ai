<?php
// FILE: /tests/test_ai_helper.php

/**
 * SplashSupportAI - AI Helper Test
 * Run this file to test AI helper functionality
 */

require_once __DIR__ . '/../config/config.php';
require_once APP_PATH . '/core/Database.php';
require_once APP_PATH . '/helpers/AIHelper.php';

echo "=== SplashSupportAI AI Helper Test ===\n\n";

try {
    // Get first tenant
    $db = new Database();
    $db->query("SELECT * FROM tenants LIMIT 1");
    $tenant = $db->single();

    if (!$tenant) {
        echo "✗ No tenant found\n";
        exit;
    }

    echo "Testing with tenant: {$tenant['name']}\n\n";

    // Test 1: Check AI quota
    echo "Test 1: Checking AI quota...\n";
    $quota = AIHelper::checkQuota($tenant['id']);
    echo "  Allowed: " . ($quota['allowed'] ? 'Yes' : 'No') . "\n";
    if (isset($quota['remaining'])) {
        echo "  Remaining: {$quota['remaining']}\n";
    }
    echo "✓ Test 1 passed\n\n";

    // Test 2: AI Suggest Reply
    echo "Test 2: Testing AI suggest reply...\n";
    $ticket = array(
        'subject' => 'I cannot login to my account',
        'status' => 'new'
    );
    $lastMessage = array(
        'body_text' => 'I keep getting error 500'
    );
    $result = AIHelper::suggestReply($tenant['id'], $ticket, $lastMessage);

    echo "  Suggestions: " . count($result['suggestions']) . "\n";
    echo "  Tokens used: {$result['tokens_used']}\n";
    if (!empty($result['suggestions'])) {
        echo "  First suggestion: {$result['suggestions'][0]['message']}\n";
    }
    echo "✓ Test 2 passed\n\n";

    // Test 3: Classify Intent
    echo "Test 3: Testing intent classification...\n";
    $ticket = array(
        'subject' => 'Question about my billing invoice'
    );
    $intent = AIHelper::classifyIntent($tenant['id'], $ticket);
    echo "  Intent: {$intent['intent']}\n";
    echo "  Confidence: {$intent['confidence']}\n";
    echo "✓ Test 3 passed\n\n";

    // Test 4: Chatbot Reply
    echo "Test 4: Testing chatbot reply...\n";
    $chatbotResult = AIHelper::chatbotReply($tenant['id'], 'Hello, I need help');
    echo "  Reply: {$chatbotResult['reply']}\n";
    echo "  Should escalate: " . ($chatbotResult['should_escalate'] ? 'Yes' : 'No') . "\n";
    echo "✓ Test 4 passed\n\n";

    echo "=== All Tests Complete ===\n";
    echo "\nNote: These are simulated AI responses.\n";
    echo "Replace AIHelper methods with real API calls to use production AI.\n";
} catch (Exception $e) {
    echo "✗ Test failed!\n";
    echo "Error: " . $e->getMessage() . "\n";
}
