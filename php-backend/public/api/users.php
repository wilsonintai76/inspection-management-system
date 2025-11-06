<?php
require_once __DIR__ . '/../../src/cors.php';
require_once __DIR__ . '/../../src/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
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
            $stmt = $pdo->prepare('INSERT INTO users (id, name, email, phone, department_id, photo_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $d['id'], $d['name'] ?? '', $d['email'] ?? '', $d['phone'] ?? null,
                $d['department_id'] ?? null, $d['photo_url'] ?? null, $d['status'] ?? 'Unverified'
            ]);
            if (!empty($d['roles']) && is_array($d['roles'])) {
                $ur = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
                foreach ($d['roles'] as $role) { $ur->execute([$d['id'], $role]); }
            }
            echo json_encode(['id' => $d['id']]);
            break;
        case 'PUT':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $d = get_json_body();
            $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ?, department_id = ?, photo_url = ?, status = ? WHERE id = ?');
            $stmt->execute([
                $d['name'] ?? '', $d['email'] ?? '', $d['phone'] ?? null,
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
