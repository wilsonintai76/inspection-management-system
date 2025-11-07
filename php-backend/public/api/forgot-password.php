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
require_once $srcPath . '/brevo_mailer.php';

header('Content-Type: application/json');

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

function ensure_reset_columns(PDO $pdo) {
    try {
        // Check information_schema for columns (works on MySQL 5.7+)
        $dbNameStmt = $pdo->query('SELECT DATABASE() as db');
        $db = $dbNameStmt->fetchColumn();
        $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token'");
        $check->execute([$db]);
        $exists = (int)$check->fetchColumn() > 0;
        if (!$exists) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
        }
        $check2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token_expires'");
        $check2->execute([$db]);
        $exists2 = (int)$check2->fetchColumn() > 0;
        if (!$exists2) {
            $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires TIMESTAMP NULL");
        }
        // Index (best-effort)
        try { $pdo->exec("CREATE INDEX idx_reset_token ON users(reset_token)"); } catch (Throwable $e3) {}
    } catch (Throwable $e) {
        // As a last resort, ignore and let subsequent queries fail noisily
    }
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }

    $d = get_json_body();
    $identifier = '';
    $byEmail = false;
    if (!empty($d['email'])) { $identifier = trim($d['email']); $byEmail = true; }
    if (!$identifier && !empty($d['staff_id'])) { $identifier = trim($d['staff_id']); }

    // Always respond success (to avoid enumeration)
    $genericOk = function() {
        echo json_encode(['ok' => true]);
        exit;
    };

    if (!$identifier) { $genericOk(); }

    // Find user
    if ($byEmail) {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$identifier]);
    } else {
        $stmt = $pdo->prepare('SELECT id, name, email FROM users WHERE staff_id = ? LIMIT 1');
        $stmt->execute([$identifier]);
    }
    $user = $stmt->fetch();
    if (!$user || empty($user['email'])) { $genericOk(); }

    // Ensure columns are present (safety for local dev)
    ensure_reset_columns($pdo);

    // Generate secure token (64 hex chars) and 1-hour expiry
    $token = bin2hex(random_bytes(32));
    $expires = (new DateTime('+1 hour'))->format('Y-m-d H:i:s');

    $upd = $pdo->prepare('UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?');
    $upd->execute([$token, $expires, $user['id']]);

    // Send email
    $mailer = new BrevoMailer(BREVO_SMTP_USERNAME, BREVO_SMTP_PASSWORD, BREVO_FROM_EMAIL, BREVO_FROM_NAME);
    $resetUrl = rtrim(APP_URL, '/') . '/#/reset-password?token=' . $token;
    $subject = 'Reset your Inspectable password';
    $html = "<p>Hello <strong>" . htmlspecialchars($user['name']) . "</strong>,</p>"
          . "<p>We received a request to reset your password. Click the button below to set a new password.</p>"
          . "<p><a href='{$resetUrl}' style='background:#10b981;color:#fff;padding:10px 16px;border-radius:6px;text-decoration:none;'>Reset Password</a></p>"
          . "<p>This link will expire in 1 hour. If you didn't request this, you can ignore this email.</p>";
    $text = "Hello {$user['name']},\n\nReset your password: {$resetUrl}\n(This link expires in 1 hour.)";
    $mailer->send($user['email'], $user['name'], $subject, $html, $text);

    $genericOk();
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
