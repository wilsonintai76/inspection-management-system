<?php
require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';

function get_json_body() {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $json = json_decode($raw, true);
  return is_array($json) ? $json : [];
}

try {
  if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
  }

  $d = get_json_body();
  $id = $d['id'] ?? '';
  $current = $d['currentPassword'] ?? '';
  $new = $d['newPassword'] ?? '';

  if (!preg_match('/^\d{4}$/', (string)$id) || strlen($new) < 8) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid input']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $row = $stmt->fetch();
  if (!$row || empty($row['password_hash']) || !password_verify($current, $row['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Current password is incorrect']);
    exit;
  }

  $hash = password_hash($new, PASSWORD_BCRYPT);
  $upd = $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?');
  $upd->execute([$hash, $id]);

  echo json_encode(['changed' => true]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
