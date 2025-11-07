<?php
// DEV HELPER: Create asset inspection tables if they don't exist
// Call this once to set up the schema for asset inspection feature

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
    $actions = [];
    
    // Check if users table exists
    $checkUsers = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($checkUsers->rowCount() === 0) {
        throw new Exception('Users table does not exist. Please create base schema first.');
    }
    
    // Create asset_upload_batches table
    $sql1 = "
    CREATE TABLE IF NOT EXISTS asset_upload_batches (
        id INT AUTO_INCREMENT PRIMARY KEY,
        uploaded_by VARCHAR(191) NOT NULL,
        filename VARCHAR(255) NOT NULL,
        total_records INT NOT NULL DEFAULT 0,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        INDEX idx_upload_date (upload_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql1);
    $actions[] = 'Created or verified table: asset_upload_batches';
    
    // Add foreign key separately if not exists
    try {
        $pdo->exec("ALTER TABLE asset_upload_batches ADD CONSTRAINT fk_uploaded_by FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE CASCADE");
        $actions[] = 'Added foreign key: uploaded_by -> users';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false && strpos($e->getMessage(), 'already exists') === false) {
            $actions[] = 'Foreign key already exists or error: ' . $e->getMessage();
        } else {
            $actions[] = 'Foreign key uploaded_by already exists';
        }
    }
    
    // Create asset_inspections table
    $sql2 = "
    CREATE TABLE IF NOT EXISTS asset_inspections (
        id INT AUTO_INCREMENT PRIMARY KEY,
        batch_id INT NOT NULL,
        label VARCHAR(255) NOT NULL COMMENT 'Asset label/ID from file',
        jenis_aset VARCHAR(255) COMMENT 'Asset type',
        pegawai_penempatan VARCHAR(255) COMMENT 'Officer assignment',
        bahagian VARCHAR(255) COMMENT 'Department/Division name from file',
        lokasi_terkini VARCHAR(255) COMMENT 'Current location',
        department_id INT NULL COMMENT 'Mapped department from bahagian',
        is_inspected TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Mark as inspected',
        inspected_date DATE NULL,
        inspected_by VARCHAR(191) NULL,
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_batch (batch_id),
        INDEX idx_department (department_id),
        INDEX idx_inspected (is_inspected),
        INDEX idx_label (label)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ";
    
    $pdo->exec($sql2);
    $actions[] = 'Created or verified table: asset_inspections';
    
    // Add foreign keys separately
    try {
        $pdo->exec("ALTER TABLE asset_inspections ADD CONSTRAINT fk_batch_id FOREIGN KEY (batch_id) REFERENCES asset_upload_batches(id) ON DELETE CASCADE");
        $actions[] = 'Added foreign key: batch_id -> asset_upload_batches';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false && strpos($e->getMessage(), 'already exists') === false) {
            $actions[] = 'FK batch_id error: ' . $e->getMessage();
        } else {
            $actions[] = 'Foreign key batch_id already exists';
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE asset_inspections ADD CONSTRAINT fk_asset_department_id FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL");
        $actions[] = 'Added foreign key: department_id -> departments';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false && strpos($e->getMessage(), 'already exists') === false) {
            $actions[] = 'FK department_id error: ' . $e->getMessage();
        } else {
            $actions[] = 'Foreign key department_id already exists';
        }
    }
    
    try {
        $pdo->exec("ALTER TABLE asset_inspections ADD CONSTRAINT fk_asset_inspected_by FOREIGN KEY (inspected_by) REFERENCES users(id) ON DELETE SET NULL");
        $actions[] = 'Added foreign key: inspected_by -> users';
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate key name') === false && strpos($e->getMessage(), 'already exists') === false) {
            $actions[] = 'FK inspected_by error: ' . $e->getMessage();
        } else {
            $actions[] = 'Foreign key inspected_by already exists';
        }
    }
    
    echo json_encode([
        'ok' => true,
        'message' => 'Asset inspection tables created successfully',
        'actions' => $actions
    ]);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
