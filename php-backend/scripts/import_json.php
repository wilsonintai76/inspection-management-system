<?php
// Dev-only importer: reads JSON files and inserts into MySQL per schema
require_once __DIR__ . '/../src/db.php';

function load_json($name) {
    $file = __DIR__ . "/data/{$name}.json";
    if (!file_exists($file)) return null;
    $json = json_decode(file_get_contents($file), true);
    return $json;
}

try {
    // Departments
    if ($rows = load_json('departments')) {
        $stmt = $pdo->prepare('INSERT INTO departments (name, acronym) VALUES (?, ?)');
        foreach ($rows as $r) {
            $stmt->execute([$r['name'] ?? '', $r['acronym'] ?? null]);
        }
    }

    // Locations
    if ($rows = load_json('locations')) {
        $stmt = $pdo->prepare('INSERT INTO locations (name, department_id, supervisor, contact_number) VALUES (?, ?, ?, ?)');
        foreach ($rows as $r) {
            $stmt->execute([
                $r['name'] ?? '',
                $r['department_id'] ?? null,
                $r['supervisor'] ?? null,
                $r['contact_number'] ?? null,
            ]);
        }
    }

    // Users + roles
    if ($rows = load_json('users')) {
        $u = $pdo->prepare('INSERT INTO users (id, name, email, phone, department_id, photo_url, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
        $ur = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
        foreach ($rows as $r) {
            $u->execute([
                $r['id'], $r['name'] ?? '', $r['email'] ?? '', $r['phone'] ?? null,
                $r['department_id'] ?? null, $r['photo_url'] ?? null, $r['status'] ?? 'Unverified'
            ]);
            if (!empty($r['roles']) && is_array($r['roles'])) {
                foreach ($r['roles'] as $role) { $ur->execute([$r['id'], $role]); }
            }
        }
    }

    // Inspections
    if ($rows = load_json('inspections')) {
        $i = $pdo->prepare('INSERT INTO inspections (location_id, inspection_date, status, auditor1_id, auditor2_id) VALUES (?, ?, ?, ?, ?)');
        foreach ($rows as $r) {
            $i->execute([
                $r['location_id'] ?? null,
                substr(($r['inspection_date'] ?? date('Y-m-d')), 0, 10),
                $r['status'] ?? 'Pending',
                $r['auditor1_id'] ?? null,
                $r['auditor2_id'] ?? null,
            ]);
        }
    }

    echo json_encode(['ok' => true]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
