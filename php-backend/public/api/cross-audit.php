<?php
// Cross-department audit assignment API
// Admin assigns auditors to audit specific departments

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

// Check if user is Auditor
function is_auditor($userId, $pdo) {
    $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ? AND role = "Auditor"');
    $stmt->execute([$userId]);
    return (bool)$stmt->fetch();
}

try {
    switch ($method) {
        case 'GET':
            // Get all cross-audit assignments or auditor's assignments
            $userId = $_GET['user_id'] ?? null;
            $auditorId = $_GET['auditor_id'] ?? null;
            $departmentId = $_GET['department_id'] ?? null;
            
            if (!$userId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            $isAdmin = is_admin($userId, $pdo);
            $isAuditorUser = is_auditor($userId, $pdo);
            
            // Build query based on role and filters
            $query = '
                SELECT 
                    caa.id,
                    caa.auditor_id,
                    caa.assigned_department_id,
                    caa.assigned_by_admin_id,
                    caa.notes,
                    caa.active,
                    caa.created_at,
                    caa.updated_at,
                    u.name as auditor_name,
                    u.staff_id as auditor_staff_id,
                    u.department_id as auditor_own_department_id,
                    d.name as assigned_department_name,
                    d.acronym as assigned_department_acronym,
                    own_dept.name as auditor_own_department_name,
                    admin.name as assigned_by_name
                FROM cross_audit_assignments caa
                JOIN users u ON caa.auditor_id = u.id
                JOIN departments d ON caa.assigned_department_id = d.id
                LEFT JOIN departments own_dept ON u.department_id = own_dept.id
                LEFT JOIN users admin ON caa.assigned_by_admin_id = admin.id
                WHERE 1=1
            ';
            
            $params = [];
            
            // If auditor viewing own assignments
            if (!$isAdmin && $isAuditorUser) {
                $query .= ' AND caa.auditor_id = ? AND caa.active = 1';
                $params[] = $userId;
            }
            
            // Filter by specific auditor (admin view)
            if ($auditorId && $isAdmin) {
                $query .= ' AND caa.auditor_id = ?';
                $params[] = $auditorId;
            }
            
            // Filter by department
            if ($departmentId) {
                $query .= ' AND caa.assigned_department_id = ?';
                $params[] = $departmentId;
            }
            
            $query .= ' ORDER BY caa.created_at DESC';
            
            $stmt = $pdo->prepare($query);
            $stmt->execute($params);
            $assignments = $stmt->fetchAll();
            
            echo json_encode(['assignments' => $assignments]);
            break;
            
        case 'POST':
            // Create new cross-audit assignment (Admin only)
            $body = get_json_body();
            $adminId = $body['admin_id'] ?? null;
            $auditorId = $body['auditor_id'] ?? null;
            $departmentId = $body['department_id'] ?? null;
            $notes = $body['notes'] ?? '';
            
            if (!$adminId) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            if (!is_admin($adminId, $pdo)) {
                http_response_code(403);
                echo json_encode(['error' => 'Only Admin can assign cross-audits']);
                break;
            }
            
            if (!$auditorId || !$departmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing auditor_id or department_id']);
                break;
            }
            
            // Verify auditor is actually an Auditor
            if (!is_auditor($auditorId, $pdo)) {
                http_response_code(400);
                echo json_encode(['error' => 'User is not an Auditor']);
                break;
            }
            
            // Check if auditor is trying to audit their own department
            $stmt = $pdo->prepare('SELECT department_id FROM users WHERE id = ?');
            $stmt->execute([$auditorId]);
            $auditor = $stmt->fetch();
            
            if ($auditor && $auditor['department_id'] == $departmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Auditor cannot audit their own department (conflict of interest)']);
                break;
            }
            
            // Check for existing assignment
            $stmt = $pdo->prepare('
                SELECT id FROM cross_audit_assignments 
                WHERE auditor_id = ? AND assigned_department_id = ?
            ');
            $stmt->execute([$auditorId, $departmentId]);
            
            if ($stmt->fetch()) {
                http_response_code(409);
                echo json_encode(['error' => 'Assignment already exists']);
                break;
            }
            
            // Create assignment
            $stmt = $pdo->prepare('
                INSERT INTO cross_audit_assignments 
                (auditor_id, assigned_department_id, assigned_by_admin_id, notes, active) 
                VALUES (?, ?, ?, ?, 1)
            ');
            $stmt->execute([$auditorId, $departmentId, $adminId, $notes]);
            
            $assignmentId = $pdo->lastInsertId();
            
            // Fetch created assignment with details
            $stmt = $pdo->prepare('
                SELECT 
                    caa.*,
                    u.name as auditor_name,
                    d.name as assigned_department_name
                FROM cross_audit_assignments caa
                JOIN users u ON caa.auditor_id = u.id
                JOIN departments d ON caa.assigned_department_id = d.id
                WHERE caa.id = ?
            ');
            $stmt->execute([$assignmentId]);
            $assignment = $stmt->fetch();
            
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
