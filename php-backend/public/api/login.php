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

$method = $_SERVER['REQUEST_METHOD'];

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

try {
    if ($method === 'POST') {
        $d = get_json_body();
        
        if (empty($d['staff_id']) || empty($d['password'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Staff ID and password are required']);
            exit;
        }

        // Find user by staff_id
        $stmt = $pdo->prepare('SELECT * FROM users WHERE staff_id = ?');
        $stmt->execute([$d['staff_id']]);
        $user = $stmt->fetch();

        if (!$user) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid staff ID or password']);
            exit;
        }

        // Verify password
        if (!password_verify($d['password'], $user['password_hash'])) {
            http_response_code(401);
            echo json_encode(['error' => 'Invalid staff ID or password']);
            exit;
        }

        // Check if email is verified (for self-registered users)
        if (!$user['email_verified']) {
            http_response_code(403);
            echo json_encode([
                'error' => 'Email not verified. Please check your email and verify your account.',
                'email' => $user['email'],
                'requires_verification' => true
            ]);
            exit;
        }

        // Check if user is verified
        if ($user['status'] !== 'Verified') {
            http_response_code(403);
            echo json_encode(['error' => 'Account not verified. Please contact administrator.']);
            exit;
        }

        // Fetch user roles
        $r = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
        $r->execute([$user['id']]);
        $user['roles'] = array_column($r->fetchAll(), 'role');

        // Remove sensitive data
        unset($user['password_hash']);

        // Return user data and must_change_password flag
        echo json_encode([
            'success' => true,
            'user' => $user,
            'must_change_password' => (bool)$user['must_change_password']
        ]);
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
