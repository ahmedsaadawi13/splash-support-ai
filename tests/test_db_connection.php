<?php
// FILE: /tests/test_db_connection.php

/**
 * SplashSupportAI - Database Connection Test
 * Run this file to verify your database connection is working
 */

require_once __DIR__ . '/../config/config.php';
require_once APP_PATH . '/core/Database.php';

echo "=== SplashSupportAI Database Connection Test ===\n\n";

try {
    $db = new Database();
    $conn = $db->getConnection();

    echo "✓ Database connection successful!\n\n";

    // Test query
    $db->query("SELECT COUNT(*) as count FROM tenants");
    $result = $db->single();

    echo "✓ Query execution successful!\n";
    echo "  Found {$result['count']} tenant(s) in database\n\n";

    // Test platform admin user
    $db->query("SELECT * FROM users WHERE email = 'admin@splashsupportai.com' LIMIT 1");
    $admin = $db->single();

    if ($admin) {
        echo "✓ Platform admin user found\n";
        echo "  Email: {$admin['email']}\n";
        echo "  Role: {$admin['role']}\n";
        echo "  Default Password: password\n\n";
    } else {
        echo "✗ Platform admin user not found\n\n";
    }

    echo "=== Test Complete ===\n";
} catch (Exception $e) {
    echo "✗ Database connection failed!\n";
    echo "Error: " . $e->getMessage() . "\n\n";
    echo "Please check your database configuration in .env file\n";
}
