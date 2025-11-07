<?php
/**
 * Transfer Staff ID Endpoint
 * Admin-only endpoint to change a user's staff_id across all database tables
 */

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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Admin authentication via header
$rolesHeader = $_SERVER['HTTP_X_USER_ROLES'] ?? '';
$callerRoles = [];
if ($rolesHeader) {
    $decoded = json_decode($rolesHeader, true);
    if (is_array($decoded)) $callerRoles = $decoded;
}
if (!in_array('Admin', $callerRoles, true)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden: Admin only']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$oldStaffId = $data['old_staff_id'] ?? '';
$newStaffId = $data['new_staff_id'] ?? '';

if (empty($oldStaffId) || empty($newStaffId)) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing old_staff_id or new_staff_id']);
    exit;
}

// Validate new staff ID format
if (!preg_match('/^\d{4}$/', $newStaffId)) {
    http_response_code(400);
    echo json_encode(['error' => 'New staff ID must be exactly 4 digits']);
    exit;
}

try {
    // Start transaction
    $pdo->beginTransaction();

    // Check if old staff ID exists
    $stmt = $pdo->prepare('SELECT id, name FROM users WHERE staff_id = ?');
    $stmt->execute([$oldStaffId]);
    $user = $stmt->fetch();

    if (!$user) {
        $pdo->rollBack();
        http_response_code(404);
        echo json_encode(['error' => 'User with old staff ID not found']);
        exit;
    }

    // Check if new staff ID is already in use
    $stmt = $pdo->prepare('SELECT id FROM users WHERE staff_id = ?');
    $stmt->execute([$newStaffId]);
    if ($stmt->fetch()) {
        $pdo->rollBack();
        http_response_code(409);
        echo json_encode(['error' => 'New staff ID is already in use']);
        exit;
    }

    // Update users table (both id and staff_id since they're the same)
    $stmt = $pdo->prepare('UPDATE users SET id = ?, staff_id = ? WHERE staff_id = ?');
    $stmt->execute([$newStaffId, $newStaffId, $oldStaffId]);

    // Update user_roles table
    $stmt = $pdo->prepare('UPDATE user_roles SET user_id = ? WHERE user_id = ?');
    $stmt->execute([$newStaffId, $oldStaffId]);

    // Update inspections table (auditor1_id and auditor2_id)
    $stmt = $pdo->prepare('UPDATE inspections SET auditor1_id = ? WHERE auditor1_id = ?');
    $stmt->execute([$newStaffId, $oldStaffId]);

    $stmt = $pdo->prepare('UPDATE inspections SET auditor2_id = ? WHERE auditor2_id = ?');
    $stmt->execute([$newStaffId, $oldStaffId]);

    // Commit transaction
    $pdo->commit();

    // Fetch updated user with roles
    $stmt = $pdo->prepare('SELECT * FROM users WHERE staff_id = ?');
    $stmt->execute([$newStaffId]);
    $updatedUser = $stmt->fetch();

    $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
    $stmt->execute([$newStaffId]);
    $updatedUser['roles'] = array_column($stmt->fetchAll(), 'role');

    echo json_encode([
        'success' => true,
        'message' => "Staff ID transferred from {$oldStaffId} to {$newStaffId}",
        'user' => $updatedUser
    ]);

} catch (Throwable $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['error' => 'Transfer failed: ' . $e->getMessage()]);
}
