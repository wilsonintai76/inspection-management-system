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

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function ensure_reset_columns(PDO $pdo) {
    try {
        $dbNameStmt = $pdo->query('SELECT DATABASE() as db');
        $db = $dbNameStmt->fetchColumn();
        $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token'");
        $check->execute([$db]);
        if ((int)$check->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
        }
        $check2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token_expires'");
        $check2->execute([$db]);
        if ((int)$check2->fetchColumn() === 0) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires TIMESTAMP NULL");
        }
        try { $pdo->exec("CREATE INDEX idx_reset_token ON users(reset_token)"); } catch (Throwable $e3) {}
    } catch (Throwable $e) {}
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $d = get_json_body();
    $token = isset($d['token']) ? trim($d['token']) : '';
    $password = isset($d['password']) ? (string)$d['password'] : '';

    if ($token === '' || $password === '') {
        http_response_code(400);
        echo json_encode(['error' => 'Missing token or password']);
        exit;
    }

    // Optional minimal policy
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        exit;
    }

    // Ensure columns present
    ensure_reset_columns($pdo);

    $stmt = $pdo->prepare('SELECT id, reset_token_expires FROM users WHERE reset_token = ? LIMIT 1');
    $stmt->execute([$token]);
    $user = $stmt->fetch();
    if (!$user) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid or expired token']);
        exit;
    }

    if (!empty($user['reset_token_expires'])) {
        $expires = new DateTime($user['reset_token_expires']);
        if (new DateTime() > $expires) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid or expired token']);
            exit;
        }
    }

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $upd = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0, reset_token = NULL, reset_token_expires = NULL WHERE id = ?');
    $upd->execute([$hash, $user['id']]);

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
