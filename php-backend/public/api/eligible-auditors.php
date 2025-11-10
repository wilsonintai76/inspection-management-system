<?php
// Helper API: Get eligible auditors for a specific department
// Returns only auditors whose departments have active cross-audit assignment for target department

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
    
    // Get eligible auditors:
    // 1. Admins with Auditor role (can audit any department)
    // 2. Auditors whose departments have active cross-audit assignment for target department
    // Exclude non-admin auditors from the target department itself (conflict of interest)
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            u.id,
            u.staff_id,
            u.name,
            u.email,
            u.department_id,
            d.name as own_department_name,
            d.acronym as own_department_acronym,
            CASE 
                WHEN admin_ur.role = "Admin" THEN -1
                ELSE caa.id 
            END as assignment_id
        FROM users u
        INNER JOIN user_roles ur ON u.id = ur.user_id AND ur.role = "Auditor"
        LEFT JOIN user_roles admin_ur ON u.id = admin_ur.user_id AND admin_ur.role = "Admin"
        LEFT JOIN cross_audit_assignments caa 
            ON u.department_id = caa.auditor_department_id 
            AND caa.target_department_id = ?
            AND caa.active = 1
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE 
            admin_ur.role = "Admin" 
            OR (caa.id IS NOT NULL AND (u.department_id != ? OR u.department_id IS NULL))
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
