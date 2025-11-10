<?php
// Cross-department audit assignment API
// Admin assigns departments to audit other departments (department-level, not individual auditors)

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

// Check if user is Admin
function is_admin($userId, $pdo) {
    $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ? AND role = "Admin"');
    $stmt->execute([$userId]);
    return (bool)$stmt->fetch();
}

try {
    switch ($method) {
        case 'GET':
            // Get all department-to-department cross-audit assignments
            $userId = $_GET['user_id'] ?? null;
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            if (!is_admin($userId, $pdo)) {
                http_response_code(403);
                echo json_encode(['error' => 'Only admins can view cross-audit assignments']);
                break;
            }
            
            // Get all assignments with department names
            $sql = "
                SELECT 
                    ca.*,
                    d1.name as auditor_department_name,
                    d1.acronym as auditor_department_acronym,
                    d2.name as target_department_name,
                    d2.acronym as target_department_acronym,
                    u.name as assigned_by_name
                FROM cross_audit_assignments ca
                JOIN departments d1 ON ca.auditor_department_id = d1.id
                JOIN departments d2 ON ca.target_department_id = d2.id
                JOIN users u ON ca.assigned_by_admin_id = u.id
                ORDER BY ca.created_at DESC
            ";
            
            $stmt = $pdo->query($sql);
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'assignments' => $assignments,
                'count' => count($assignments)
            ]);
            break;
            
        case 'POST':
            // Create new department-to-department assignment
            $body = get_json_body();
            $adminId = $body['admin_id'] ?? null;
            $auditorDeptId = $body['auditor_department_id'] ?? null;
            $targetDeptId = $body['target_department_id'] ?? null;
            $notes = $body['notes'] ?? '';
            
            if (!$adminId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            if (!is_admin($adminId, $pdo)) {
                http_response_code(403);
                echo json_encode(['error' => 'Only Admin can create cross-audit assignments']);
                break;
            }
            
            if (!$auditorDeptId || !$targetDeptId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing auditor_department_id or target_department_id']);
                break;
            }
            
            // Validate: Cannot assign department to audit itself
            if ($auditorDeptId == $targetDeptId) {
                http_response_code(400);
                echo json_encode(['error' => 'Department cannot audit itself']);
                break;
            }
            
            // Check for existing assignment
            $stmt = $pdo->prepare('
                SELECT id FROM cross_audit_assignments 
                WHERE auditor_department_id = ? AND target_department_id = ?
            ');
            $stmt->execute([$auditorDeptId, $targetDeptId]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'This cross-audit assignment already exists']);
                break;
            }
            
            // Create assignment
            $stmt = $pdo->prepare('
                INSERT INTO cross_audit_assignments 
                (auditor_department_id, target_department_id, assigned_by_admin_id, notes, active) 
                VALUES (?, ?, ?, ?, 1)
            ');
            $stmt->execute([$auditorDeptId, $targetDeptId, $adminId, $notes]);
            
            $assignmentId = $pdo->lastInsertId();
            
            // Fetch created assignment with details
            $stmt = $pdo->prepare('
                SELECT 
                    ca.*,
                    d1.name as auditor_department_name,
                    d2.name as target_department_name,
                    u.name as assigned_by_name
                FROM cross_audit_assignments ca
                JOIN departments d1 ON ca.auditor_department_id = d1.id
                JOIN departments d2 ON ca.target_department_id = d2.id
                JOIN users u ON ca.assigned_by_admin_id = u.id
                WHERE ca.id = ?
            ');
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'message' => 'Cross-audit assignment created',
                'assignment' => $assignment
            ]);
            break;
            
        case 'PUT':
            // Update assignment (activate/deactivate or edit notes)
            $body = get_json_body();
            $adminId = $body['admin_id'] ?? null;
            $assignmentId = $body['assignment_id'] ?? null;
            $active = $body['active'] ?? null;
            $notes = $body['notes'] ?? null;
            
            if (!$adminId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            if (!is_admin($adminId, $pdo)) {
                http_response_code(403);
                echo json_encode(['error' => 'Only Admin can update assignments']);
                break;
            }
            
            if (!$assignmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing assignment_id']);
                break;
            }
            
            $updates = [];
            $params = [];
            
            if ($active !== null) {
                $updates[] = 'active = ?';
                $params[] = $active ? 1 : 0;
            }
            
            if ($notes !== null) {
                $updates[] = 'notes = ?';
                $params[] = $notes;
            }
            
            if (empty($updates)) {
                http_response_code(400);
                echo json_encode(['error' => 'Nothing to update']);
                break;
            }
            
            $params[] = $assignmentId;
            
            $stmt = $pdo->prepare('
                UPDATE cross_audit_assignments 
                SET ' . implode(', ', $updates) . '
                WHERE id = ?
            ');
            $stmt->execute($params);
            
            echo json_encode([
                'success' => true,
                'message' => 'Assignment updated'
            ]);
            break;
            
        case 'DELETE':
            // Delete assignment (Admin only)
            $body = get_json_body();
            $adminId = $body['admin_id'] ?? null;
            $assignmentId = $body['assignment_id'] ?? null;
            
            if (!$adminId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            if (!is_admin($adminId, $pdo)) {
                http_response_code(403);
                echo json_encode(['error' => 'Only Admin can delete assignments']);
                break;
            }
            
            if (!$assignmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing assignment_id']);
                break;
            }
            
            $stmt = $pdo->prepare('DELETE FROM cross_audit_assignments WHERE id = ?');
            $stmt->execute([$assignmentId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'Assignment deleted'
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
