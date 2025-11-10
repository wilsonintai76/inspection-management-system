<?php
// Test cross-audit API endpoint
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';

echo "Testing cross-audit API...\n\n";

// Test 1: Check if we can query the table
try {
    $stmt = $pdo->query("SELECT * FROM cross_audit_assignments");
    echo "✓ Can query cross_audit_assignments table\n";
} catch (Exception $e) {
    echo "✗ Cannot query table: " . $e->getMessage() . "\n";
    exit(1);
}

// Test 2: Check departments exist
try {
    $stmt = $pdo->query("SELECT id, name FROM departments LIMIT 5");
    $depts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "✓ Found " . count($depts) . " departments:\n";
    foreach ($depts as $d) {
        echo "  - [{$d['id']}] {$d['name']}\n";
    }
} catch (Exception $e) {
    echo "✗ Cannot query departments: " . $e->getMessage() . "\n";
}

// Test 3: Check users table for admin
try {
    $stmt = $pdo->query("SELECT id, name FROM users LIMIT 3");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n✓ Found " . count($users) . " users:\n";
    foreach ($users as $u) {
        echo "  - [{$u['id']}] {$u['name']}\n";
    }
} catch (Exception $e) {
    echo "\n✗ Cannot query users: " . $e->getMessage() . "\n";
}

// Test 4: Check user_roles for Admin
try {
    $stmt = $pdo->query("SELECT user_id, role FROM user_roles WHERE role = 'Admin' LIMIT 3");
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "\n✓ Found " . count($admins) . " admin users:\n";
    foreach ($admins as $a) {
        echo "  - User ID: {$a['user_id']} ({$a['role']})\n";
    }
} catch (Exception $e) {
    echo "\n✗ Cannot query user_roles: " . $e->getMessage() . "\n";
}

echo "\n\nIf all checks pass, the API should work.\n";
echo "Network Error usually means:\n";
echo "1. PHP backend is not running (check http://localhost)\n";
echo "2. CORS issue (check browser console)\n";
echo "3. Wrong API URL in Vue frontend\n";
