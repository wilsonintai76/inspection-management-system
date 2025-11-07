<?php
// Developer utility to add reset_token columns (use for local WAMP only)
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
    $dbNameStmt = $pdo->query('SELECT DATABASE() as db');
    $db = $dbNameStmt->fetchColumn();
    $out = [ 'db' => $db, 'actions' => [] ];

    $check = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token'");
    $check->execute([$db]);
    if ((int)$check->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token VARCHAR(64) NULL");
        $out['actions'][] = 'Added column reset_token';
    } else { $out['actions'][] = 'reset_token exists'; }

    $check2 = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'users' AND COLUMN_NAME = 'reset_token_expires'");
    $check2->execute([$db]);
    if ((int)$check2->fetchColumn() === 0) {
        $pdo->exec("ALTER TABLE users ADD COLUMN reset_token_expires TIMESTAMP NULL");
        $out['actions'][] = 'Added column reset_token_expires';
    } else { $out['actions'][] = 'reset_token_expires exists'; }

    try { $pdo->exec("CREATE INDEX idx_reset_token ON users(reset_token)"); $out['actions'][] = 'Created idx_reset_token'; } catch (Throwable $e3) { $out['actions'][] = 'Index exists or failed to create'; }

    echo json_encode($out);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
