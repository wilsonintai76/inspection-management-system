<?php
require_once __DIR__ . '/../src/db.php';

$staffId = '1891';

// Get current status
$stmt = $pdo->prepare('SELECT id, staff_id, name, email, status, email_verified FROM users WHERE staff_id = ?');
$stmt->execute([$staffId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    echo "User with staff_id $staffId not found!\n";
    exit(1);
}

echo "Current status:\n";
print_r($user);

// Update to Verified
$stmt = $pdo->prepare('UPDATE users SET status = ?, email_verified = 1 WHERE staff_id = ?');
$stmt->execute(['Verified', $staffId]);

echo "\nUser verified successfully!\n";

// Get updated status
$stmt = $pdo->prepare('SELECT id, staff_id, name, email, status, email_verified FROM users WHERE staff_id = ?');
$stmt->execute([$staffId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

echo "\nUpdated status:\n";
print_r($user);
