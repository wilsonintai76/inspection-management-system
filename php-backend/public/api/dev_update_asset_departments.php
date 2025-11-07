<?php
// DEV HELPER: Update asset department mappings
// Re-maps all assets in asset_inspections to proper departments based on bahagian

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

function map_department($bahagian, $pdo) {
    if (empty($bahagian)) return null;
    
    // Try exact match first
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? OR acronym = ? LIMIT 1');
    $stmt->execute([$bahagian, $bahagian]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];
    
    // Try partial match
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name LIKE ? OR acronym LIKE ? LIMIT 1');
    $pattern = '%' . $bahagian . '%';
    $stmt->execute([$pattern, $pattern]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null;
}

try {
    // Get all assets with no department_id
    $stmt = $pdo->query('SELECT id, bahagian FROM asset_inspections WHERE department_id IS NULL');
    $assets = $stmt->fetchAll();
    
    $updated = 0;
    $updateStmt = $pdo->prepare('UPDATE asset_inspections SET department_id = ? WHERE id = ?');
    
    foreach ($assets as $asset) {
        $deptId = map_department($asset['bahagian'], $pdo);
        if ($deptId) {
            $updateStmt->execute([$deptId, $asset['id']]);
            $updated++;
        }
    }
    
    echo json_encode([
        'ok' => true,
        'total_assets' => count($assets),
        'updated' => $updated,
        'message' => "Updated $updated assets with department mapping"
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
