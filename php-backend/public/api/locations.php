<?php
require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';

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
                $stmt = $pdo->prepare('SELECT * FROM locations WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $row = $stmt->fetch();
                if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); break; }
                echo json_encode($row);
            } else {
                if (isset($_GET['department_id']) && $_GET['department_id'] !== '') {
                    $stmt = $pdo->prepare('SELECT * FROM locations WHERE department_id = ? ORDER BY name');
                    $stmt->execute([$_GET['department_id']]);
                    echo json_encode($stmt->fetchAll());
                } else {
                    $stmt = $pdo->query('SELECT * FROM locations ORDER BY name');
                    echo json_encode($stmt->fetchAll());
                }
            }
            break;
        case 'POST':
            $d = get_json_body();
            $stmt = $pdo->prepare('INSERT INTO locations (name, department_id, supervisor, contact_number) VALUES (?, ?, ?, ?)');
            $stmt->execute([$d['name'] ?? '', $d['department_id'] ?? null, $d['supervisor'] ?? null, $d['contact_number'] ?? null]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
            break;
        case 'PUT':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $d = get_json_body();
            $stmt = $pdo->prepare('UPDATE locations SET name = ?, department_id = ?, supervisor = ?, contact_number = ? WHERE id = ?');
            $stmt->execute([$d['name'] ?? '', $d['department_id'] ?? null, $d['supervisor'] ?? null, $d['contact_number'] ?? null, $_GET['id']]);
            echo json_encode(['updated' => true]);
            break;
        case 'DELETE':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $stmt = $pdo->prepare('DELETE FROM locations WHERE id = ?');
            $stmt->execute([$_GET['id']]);
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
