<?php
// Portable includes: support both deployed WAMP path and repo path
$srcPath = __DIR__ . '/../src';
$configPath = __DIR__ . '/../config.php';
if (!is_dir($srcPath) || !file_exists($configPath)) {
    $srcPath = __DIR__ . '/../../src';
    $configPath = __DIR__ . '/../../config.php';
}
require_once $srcPath . '/cors.php';
require_once $configPath;
require_once $srcPath . '/db.php';

header('Content-Type: application/json');

try {
    // Staff IDs to reset
    $staffIds = ['2001','2002','2003','2004','2005','2006'];

    $password = 'password123';
    $hash = password_hash($password, PASSWORD_BCRYPT);

    $updated = 0;
    $notFound = [];

    foreach ($staffIds as $sid) {
        // Find user id by staff_id
        $stmt = $pdo->prepare('SELECT id FROM users WHERE staff_id = ?');
        $stmt->execute([$sid]);
        $row = $stmt->fetch();
        if (!$row) { $notFound[] = $sid; continue; }
        $uid = $row['id'];

        // Update password hash and ensure verified status
        $u = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, email_verified = 1, status = "Verified" WHERE id = ?');
        $u->execute([$hash, $uid]);
        $updated++;
    }

    echo json_encode([
        'success' => true,
        'updated' => $updated,
        'not_found' => $notFound,
        'password_set' => $password
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
