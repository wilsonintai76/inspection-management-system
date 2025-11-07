<?php
// Admin-only endpoint to verify/unverify a user and set roles without direct SQL

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

$method = $_SERVER['REQUEST_METHOD'];

function json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function get_user_by_identifier($pdo, $idOrStaffId) {
    // id is usually same as staff_id in this app; support both
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ? OR staff_id = ?');
    $stmt->execute([$idOrStaffId, $idOrStaffId]);
    return $stmt->fetch();
}

try {
    if ($method !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    // Simple admin guard via header: X-User-Roles: JSON array like ["Admin", ...]
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

    $d = json_body();
    $identifier = $d['id'] ?? $d['staff_id'] ?? null;
    if (!$identifier) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing id or staff_id']);
        exit;
    }

    $user = get_user_by_identifier($pdo, $identifier);
    if (!$user) {
        http_response_code(404);
        echo json_encode(['error' => 'User not found']);
        exit;
    }

    $verified = isset($d['verified']) ? (bool)$d['verified'] : true;
    $status = $verified ? 'Verified' : 'Unverified';
    $emailVerified = $verified ? 1 : 0;
    $mustChange = isset($d['must_change_password']) ? (int)(bool)$d['must_change_password'] : 0;

    // Optional: update department
    $departmentId = array_key_exists('department_id', $d) ? $d['department_id'] : $user['department_id'];

    $stmt = $pdo->prepare('UPDATE users SET status = ?, email_verified = ?, must_change_password = ?, department_id = ? WHERE id = ?');
    $stmt->execute([$status, $emailVerified, $mustChange, $departmentId, $user['id']]);

    // Optional roles replacement
    if (array_key_exists('roles', $d) && is_array($d['roles'])) {
        $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$user['id']]);
        $ins = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
        foreach ($d['roles'] as $role) {
            $ins->execute([$user['id'], $role]);
        }
    }

    // Fetch updated user + roles
    $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
    $stmt->execute([$user['id']]);
    $updated = $stmt->fetch();
    $r = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
    $r->execute([$user['id']]);
    $updated['roles'] = array_column($r->fetchAll(), 'role');

    echo json_encode(['updated' => true, 'user' => $updated]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
