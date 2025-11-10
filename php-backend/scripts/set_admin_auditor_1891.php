<?php
require_once __DIR__ . '/../src/db.php';

$staffId = '1891';

// Get user ID
$stmt = $pdo->prepare('SELECT id, staff_id, name FROM users WHERE staff_id = ?');
$stmt->execute([$staffId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User with staff_id $staffId not found!\n";
    exit(1);
}

$userId = $user['id'];
echo "User: {$user['name']} (ID: {$userId})\n\n";

// Get current roles
$stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
$stmt->execute([$userId]);
$currentRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "Current roles: " . implode(', ', $currentRoles) . "\n";

// Required roles
$requiredRoles = ['Admin', 'Auditor'];

// Check and add missing roles
foreach ($requiredRoles as $role) {
    if (!in_array($role, $currentRoles)) {
        $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
        $stmt->execute([$userId, $role]);
        echo "✓ Added role: $role\n";
    } else {
        echo "✓ Already has role: $role\n";
    }
}

// Get updated roles
$stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
$stmt->execute([$userId]);
$updatedRoles = $stmt->fetchAll(PDO::FETCH_COLUMN);

echo "\nUpdated roles: " . implode(', ', $updatedRoles) . "\n";
