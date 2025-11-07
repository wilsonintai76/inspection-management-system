<?php
// DEV HELPER: Add total_assets column to departments table
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
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM departments LIKE 'total_assets'");
    $exists = $stmt->fetch();
    
    if ($exists) {
        echo json_encode([
            'ok' => true,
            'message' => 'Column total_assets already exists',
            'action' => 'none'
        ]);
    } else {
        // Add the column
        $pdo->exec("ALTER TABLE departments ADD COLUMN total_assets INT DEFAULT 0 AFTER acronym");
        
        echo json_encode([
            'ok' => true,
            'message' => 'Column total_assets added successfully',
            'action' => 'created'
        ]);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
