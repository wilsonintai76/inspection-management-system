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
  $password = $d['password'] ?? '';

  if (!preg_match('/^\d{4}$/', (string)$id) || $password === '') {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  $stmt = $pdo->prepare('SELECT id, name, email, password_hash, must_change_password, status FROM users WHERE id = ?');
  $stmt->execute([$id]);
  $user = $stmt->fetch();
  if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  if ($user['status'] !== 'Verified') {
    http_response_code(403);
    echo json_encode(['error' => 'Account not verified']);
    exit;
  }

  if (empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Invalid credentials']);
    exit;
  }

  // Load roles
  $r = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
  $r->execute([$user['id']]);
  $roles = array_column($r->fetchAll(), 'role');

  echo json_encode([
    'id' => $user['id'],
    'name' => $user['name'],
    'email' => $user['email'],
    'roles' => $roles,
    'must_change_password' => (int)$user['must_change_password'] === 1
  ]);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['error' => $e->getMessage()]);
}
