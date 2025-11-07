<?php
// DEV HELPER: Check department mapping status
$srcPath = __DIR__ . '/../src';
$configPath = __DIR__ . '/../config.php';
if (!is_dir($srcPath) || !file_exists($configPath)) {
    $srcPath = __DIR__ . '/../../src';
    $configPath = __DIR__ . '/../../config.php';
}
require_once $srcPath . '/cors.php';
require_once $configPath;
require_once $srcPath . '/db.php';

header('Content-Type: application/json');

try {
    // Get count by department_id
    $stmt = $pdo->query('
        SELECT 
            department_id,
            COUNT(*) as count,
            GROUP_CONCAT(DISTINCT bahagian SEPARATOR " | ") as dept_names
        FROM asset_inspections 
        GROUP BY department_id
    ');
    $counts = $stmt->fetchAll();
    
    // Get sample unmapped
    $stmt = $pdo->query('
        SELECT label, bahagian, department_id 
        FROM asset_inspections 
        WHERE department_id IS NULL 
        LIMIT 10
    ');
    $unmappedSample = $stmt->fetchAll();
    
    // Get unique bahagian names
    $stmt = $pdo->query('
        SELECT DISTINCT bahagian, COUNT(*) as count 
        FROM asset_inspections 
        GROUP BY bahagian
        ORDER BY count DESC
    ');
    $uniqueDepts = $stmt->fetchAll();
    
    echo json_encode([
        'ok' => true,
        'counts_by_dept_id' => $counts,
        'unmapped_sample' => $unmappedSample,
        'unique_bahagian' => $uniqueDepts
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
