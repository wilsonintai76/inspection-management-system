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

function generate_password($length = 10) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789@#%&';
    $result = '';
    for ($i = 0; $i < $length; $i++) {
        $result .= $chars[random_int(0, strlen($chars) - 1)];
    }
    return $result;
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare('SELECT * FROM users WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $row = $stmt->fetch();
                if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); break; }
                // fetch roles
                $r = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
                $r->execute([$_GET['id']]);
                $row['roles'] = array_column($r->fetchAll(), 'role');
                echo json_encode($row);
            } else {
                // Get all users with their roles
                $stmt = $pdo->query('SELECT * FROM users ORDER BY created_at DESC');
                $users = $stmt->fetchAll();
                
                // Fetch roles for each user
                foreach ($users as &$user) {
                    $r = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ?');
                    $r->execute([$user['id']]);
                    $user['roles'] = array_column($r->fetchAll(), 'role');
                }
                
                echo json_encode($users);
            }
            break;
        case 'POST':
            $d = get_json_body();
            
            // Validate staff_id (must be 4 digits)
            if (!isset($d['staff_id']) || !preg_match('/^\d{4}$/', (string)$d['staff_id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid staff ID. Must be 4 digits.']);
                break;
            }

            // Generate unique user id from staff_id or use email as fallback
            $userId = $d['staff_id'];

            // Auto-generate password if not provided
            $plainPassword = $d['password'] ?? generate_password(10);
            $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT);
            $mustChange = 1; // force password change on first login

            // Admin-created users are auto-verified (email_verified = 1, status = Verified)
            $emailVerified = 1;
            $status = 'Verified';

            $stmt = $pdo->prepare('INSERT INTO users (id, staff_id, name, email, personal_email, phone, department_id, photo_url, password_hash, must_change_password, email_verified, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $userId, $d['staff_id'], $d['name'] ?? '', $d['email'] ?? '', $d['personal_email'] ?? null, $d['phone'] ?? null,
                $d['department_id'] ?? null, $d['photo_url'] ?? null, $passwordHash, $mustChange, $emailVerified, $status
            ]);
            if (!empty($d['roles']) && is_array($d['roles'])) {
                $ur = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
                foreach ($d['roles'] as $role) { $ur->execute([$userId, $role]); }
            }
            // Try to email the password (may fail on local dev)
            $mailSent = false;
            if (!empty($d['email'])) {
                $subject = 'Your Inspectable Account Credentials';
                $message = "Hello {$d['name']},\n\nYour account has been created.\n\nStaff ID: {$d['staff_id']}\nTemporary Password: {$plainPassword}\n\nPlease log in and change your password immediately.";
                $headers = 'From: no-reply@localhost' . "\r\n";
                $mailSent = @mail($d['email'], $subject, $message, $headers);
            }

            echo json_encode(['id' => $userId, 'staff_id' => $d['staff_id'], 'generated_password' => $plainPassword, 'email_sent' => $mailSent]);
            break;
        case 'PUT':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $d = get_json_body();
            // If password provided, update hash and clear must_change flag unless explicitly set
            if (isset($d['password']) && $d['password'] !== '') {
                $hash = password_hash($d['password'], PASSWORD_BCRYPT);
                $pdo->prepare('UPDATE users SET password_hash = ?, must_change_password = 0 WHERE id = ?')->execute([$hash, $_GET['id']]);
            }
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, personal_email = ?, phone = ?, department_id = ?, photo_url = ?, status = ? WHERE id = ?');
            $stmt->execute([
                $d['name'] ?? '', $d['email'] ?? '', $d['personal_email'] ?? null, $d['phone'] ?? null,
                $d['department_id'] ?? null, $d['photo_url'] ?? null, $d['status'] ?? 'Unverified', $_GET['id']
            ]);
            if (array_key_exists('roles', $d) && is_array($d['roles'])) {
                $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$_GET['id']]);
                $ur = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
                foreach ($d['roles'] as $role) { $ur->execute([$_GET['id'], $role]); }
            }
            echo json_encode(['updated' => true]);
            break;
        case 'DELETE':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $pdo->prepare('DELETE FROM user_roles WHERE user_id = ?')->execute([$_GET['id']]);
            $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$_GET['id']]);
            echo json_encode(['deleted' => true]);
            break;
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
