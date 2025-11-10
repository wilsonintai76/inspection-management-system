<?php
// Helper API: Get eligible auditors for a specific department
// Returns only auditors who have active cross-audit assignment for that department

require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }
    
    $departmentId = $_GET['department_id'] ?? null;
    
    if (!$departmentId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing department_id parameter']);
        exit;
    }
    
    // Get auditors with active cross-audit assignment for this department
    // Exclude auditors from this department itself
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            u.id,
            u.staff_id,
            u.name,
            u.email,
            u.department_id,
            d.name as own_department_name,
            caa.id as assignment_id
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.role = "Auditor"
        INNER JOIN cross_audit_assignments caa 
            ON u.id = caa.auditor_id 
            AND caa.assigned_department_id = ?
            AND caa.active = 1
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.department_id != ? OR u.department_id IS NULL
        ORDER BY u.name
    ');
    $stmt->execute([$departmentId, $departmentId]);
    $auditors = $stmt->fetchAll();
    
    echo json_encode([
        'eligible_auditors' => $auditors,
        'count' => count($auditors)
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
