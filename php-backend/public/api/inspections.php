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

// Check if auditor can audit a specific department (cross-audit validation)
function can_audit_department($auditorId, $departmentId, $pdo) {
    if (!$auditorId || !$departmentId) return true; // If no auditor/dept specified, allow
    
    // Check if auditor is Admin - admins can audit any department
    $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ? AND role = "Admin"');
    $stmt->execute([$auditorId]);
    if ($stmt->fetch()) {
        return true; // Admins bypass all cross-audit restrictions
    }
    
    // Get auditor's own department
    $stmt = $pdo->prepare('SELECT department_id FROM users WHERE id = ?');
    $stmt->execute([$auditorId]);
    $auditor = $stmt->fetch();
    
    if (!$auditor) {
        return false; // Auditor not found
    }
    
    $auditorDeptId = $auditor['department_id'];
    
    // Cannot audit own department (conflict of interest) - unless admin (already checked above)
    if ($auditorDeptId == $departmentId) {
        return false;
    }
    
    // Check if there's an active department-to-department assignment
    // Auditor's department must be assigned to audit the target department
    $stmt = $pdo->prepare('
        SELECT id FROM cross_audit_assignments 
        WHERE auditor_department_id = ? AND target_department_id = ? AND active = 1
    ');
    $stmt->execute([$auditorDeptId, $departmentId]);
    
    return (bool)$stmt->fetch(); // Must have active department assignment
}

try {
    switch ($method) {
        case 'GET':
            if (isset($_GET['id'])) {
                $stmt = $pdo->prepare('SELECT * FROM inspections WHERE id = ?');
                $stmt->execute([$_GET['id']]);
                $row = $stmt->fetch();
                if (!$row) { http_response_code(404); echo json_encode(['error' => 'Not found']); break; }
                echo json_encode($row);
            } else {
                if (isset($_GET['department_id']) && $_GET['department_id'] !== '') {
                    $stmt = $pdo->prepare('SELECT i.* FROM inspections i INNER JOIN locations l ON i.location_id = l.id WHERE l.department_id = ? ORDER BY i.inspection_date DESC');
                    $stmt->execute([$_GET['department_id']]);
                    echo json_encode($stmt->fetchAll());
                } else {
                    $stmt = $pdo->query('SELECT * FROM inspections ORDER BY inspection_date DESC');
                    echo json_encode($stmt->fetchAll());
                }
            }
            break;
        case 'POST':
            $d = get_json_body();
            
            // Get department ID from location
            $locationId = $d['location_id'] ?? null;
            if ($locationId) {
                $stmt = $pdo->prepare('SELECT department_id FROM locations WHERE id = ?');
                $stmt->execute([$locationId]);
                $location = $stmt->fetch();
                $departmentId = $location ? $location['department_id'] : null;
                
                // Validate auditors can audit this department
                if ($departmentId) {
                    $auditor1 = $d['auditor1_id'] ?? null;
                    $auditor2 = $d['auditor2_id'] ?? null;
                    
                    if ($auditor1 && !can_audit_department($auditor1, $departmentId, $pdo)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Auditor 1 cannot audit this department (no cross-audit assignment or own department)']);
                        break;
                    }
                    
                    if ($auditor2 && !can_audit_department($auditor2, $departmentId, $pdo)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Auditor 2 cannot audit this department (no cross-audit assignment or own department)']);
                        break;
                    }
                }
            }
            
            $stmt = $pdo->prepare('INSERT INTO inspections (location_id, inspection_date, status, auditor1_id, auditor2_id) VALUES (?, ?, ?, ?, ?)');
            $stmt->execute([
                $d['location_id'] ?? null,
                $d['inspection_date'] ?? date('Y-m-d'),
                $d['status'] ?? 'Pending',
                $d['auditor1_id'] ?? null,
                $d['auditor2_id'] ?? null,
            ]);
            echo json_encode(['id' => $pdo->lastInsertId()]);
            break;
        case 'PUT':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $d = get_json_body();
            
            // Get department ID from location
            $locationId = $d['location_id'] ?? null;
            if ($locationId) {
                $stmt = $pdo->prepare('SELECT department_id FROM locations WHERE id = ?');
                $stmt->execute([$locationId]);
                $location = $stmt->fetch();
                $departmentId = $location ? $location['department_id'] : null;
                
                // Validate auditors can audit this department
                if ($departmentId) {
                    $auditor1 = $d['auditor1_id'] ?? null;
                    $auditor2 = $d['auditor2_id'] ?? null;
                    
                    if ($auditor1 && !can_audit_department($auditor1, $departmentId, $pdo)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Auditor 1 cannot audit this department (no cross-audit assignment or own department)']);
                        break;
                    }
                    
                    if ($auditor2 && !can_audit_department($auditor2, $departmentId, $pdo)) {
                        http_response_code(403);
                        echo json_encode(['error' => 'Auditor 2 cannot audit this department (no cross-audit assignment or own department)']);
                        break;
                    }
                }
            }
            
            $stmt = $pdo->prepare('UPDATE inspections SET location_id = ?, inspection_date = ?, status = ?, auditor1_id = ?, auditor2_id = ? WHERE id = ?');
            $stmt->execute([
                $d['location_id'] ?? null,
                $d['inspection_date'] ?? date('Y-m-d'),
                $d['status'] ?? 'Pending',
                $d['auditor1_id'] ?? null,
                $d['auditor2_id'] ?? null,
                $_GET['id']
            ]);
            echo json_encode(['updated' => true]);
            break;
        case 'DELETE':
            if (!isset($_GET['id'])) { http_response_code(400); echo json_encode(['error' => 'Missing id']); break; }
            $stmt = $pdo->prepare('DELETE FROM inspections WHERE id = ?');
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
