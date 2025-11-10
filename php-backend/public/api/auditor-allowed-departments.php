<?php
// API: Get departments that an auditor is allowed to audit
// Based on cross-audit assignments (admins can see all departments)

require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method !== 'GET') {
        http_response_code(405);
        echo json_encode(['error' => 'Method Not Allowed']);
        exit;
    }
    
    $auditorId = $_GET['auditor_id'] ?? null;
    
    if (!$auditorId) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing auditor_id parameter']);
        exit;
    }
    
    // Check if auditor is Admin - admins can see all departments
    $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ? AND role = "Admin"');
    $stmt->execute([$auditorId]);
    $isAdmin = (bool)$stmt->fetch();
    
    if ($isAdmin) {
        // Admin: return all departments
        $stmt = $pdo->query('SELECT * FROM departments ORDER BY name');
        $departments = $stmt->fetchAll();
        
        echo json_encode([
            'is_admin' => true,
            'allowed_departments' => $departments,
            'count' => count($departments)
        ]);
        exit;
    }
    
    // Get auditor's department
    $stmt = $pdo->prepare('SELECT department_id FROM users WHERE id = ?');
    $stmt->execute([$auditorId]);
    $auditor = $stmt->fetch();
    
    if (!$auditor) {
        http_response_code(404);
        echo json_encode(['error' => 'Auditor not found']);
        exit;
    }
    
    $auditorDeptId = $auditor['department_id'];
    
    // Get departments that this auditor's department can audit
    // Based on active cross-audit assignments
    $stmt = $pdo->prepare('
        SELECT DISTINCT
            d.id,
            d.name,
            d.acronym,
            d.total_assets,
            d.created_at,
            d.updated_at,
            caa.id as assignment_id,
            caa.notes
        FROM departments d
        INNER JOIN cross_audit_assignments caa 
            ON d.id = caa.target_department_id
            AND caa.auditor_department_id = ?
            AND caa.active = 1
        WHERE d.id != ?
        ORDER BY d.name
    ');
    $stmt->execute([$auditorDeptId, $auditorDeptId]);
    $departments = $stmt->fetchAll();
    
    echo json_encode([
        'is_admin' => false,
        'auditor_department_id' => $auditorDeptId,
        'allowed_departments' => $departments,
        'count' => count($departments)
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
