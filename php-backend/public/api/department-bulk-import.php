<?php
// Bulk import departments/locations from multiple CSV/Excel files
// Creates locations and uninspected assets for asset inspection workflow

require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';
require_once __DIR__ . '/../../vendor/autoload.php'; // For PhpSpreadsheet

use PhpOffice\PhpSpreadsheet\IOFactory;

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method Not Allowed']);
    exit;
}

// Helper function to find or create department
function find_or_create_department($departmentName, $acronym, $pdo) {
    // Try to find existing department
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? OR acronym = ?');
    $stmt->execute([$departmentName, $acronym]);
    $dept = $stmt->fetch();
    
    if ($dept) {
        return $dept['id'];
    }
    
    // Create new department
    $stmt = $pdo->prepare('INSERT INTO departments (name, acronym, total_assets) VALUES (?, ?, 0)');
    $stmt->execute([$departmentName, $acronym]);
    return $pdo->lastInsertId();
}

// Helper function to find or create location
function find_or_create_location($locationName, $departmentId, $pdo) {
    // Try to find existing location
    $stmt = $pdo->prepare('SELECT id FROM locations WHERE name = ? AND department_id = ?');
    $stmt->execute([$locationName, $departmentId]);
    $loc = $stmt->fetch();
    
    if ($loc) {
        return $loc['id'];
    }
    
    // Create new location
    $stmt = $pdo->prepare('INSERT INTO locations (name, department_id) VALUES (?, ?)');
    $stmt->execute([$locationName, $departmentId]);
    return $pdo->lastInsertId();
}

try {
    // Optional overwrite: clear existing departments/locations and related inspection data
    $overwrite = $_POST['overwrite'] ?? null;
    $shouldOverwrite = in_array(strtolower((string)$overwrite), ['1', 'true', 'yes', 'on'], true);
    
    if ($shouldOverwrite) {
        $pdo->beginTransaction();
        try {
            // Clear inspection-related tables if present (in order to handle FK constraints)
            if ($pdo->query("SHOW TABLES LIKE 'asset_inspections'")->rowCount() > 0) {
                $pdo->exec('DELETE FROM asset_inspections');
            }
            if ($pdo->query("SHOW TABLES LIKE 'asset_upload_batches'")->rowCount() > 0) {
                $pdo->exec('DELETE FROM asset_upload_batches');
            }
            if ($pdo->query("SHOW TABLES LIKE 'asset_uploads'")->rowCount() > 0) {
                $pdo->exec('DELETE FROM asset_uploads');
            }
            // Clear master tables (locations first due to FK to departments)
            $pdo->exec('DELETE FROM locations');
            $pdo->exec('DELETE FROM departments');
            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(['error' => 'Failed to clear existing data: ' . $e->getMessage()]);
            exit;
        }
    }
    
    if (empty($_FILES['files'])) {
        http_response_code(400);
        echo json_encode(['error' => 'No files uploaded']);
        exit;
    }

    $files = $_FILES['files'];
    $allRows = [];
    $errors = [];
    $locationsCreated = 0;
    $departmentsCreated = 0;
    
    // Track created locations and departments to avoid counting duplicates
    $createdLocations = [];
    $createdDepartments = [];

    // Process all uploaded files
    $fileCount = count($files['name']);
    for ($i = 0; $i < $fileCount; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = "File {$files['name'][$i]}: Upload error";
            continue;
        }

        $tmpName = $files['tmp_name'][$i];
        $filename = $files['name'][$i];

        try {
            // Read file with PhpSpreadsheet
            $spreadsheet = IOFactory::load($tmpName);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            if (count($rows) < 2) {
                $errors[] = "File $filename: No data rows found";
                continue;
            }

            // Get headers (first row)
            $headers = array_map('trim', array_map('strtolower', $rows[0]));
            
            // Find column indices: department and location are required; supervisor optional
            $supervisorIdx = array_search('pegawai penempatan', $headers);
            $bahagianIdx = array_search('bahagian', $headers);
            $locationIdx = array_search('lokasi terkini', $headers);

            if ($bahagianIdx === false || $locationIdx === false) {
                $errors[] = "File $filename: Missing required columns (Bahagian, Lokasi Terkini)";
                continue;
            }

            // Extract data rows (skip header)
            for ($j = 1; $j < count($rows); $j++) {
                $row = $rows[$j];
                
                // Skip rows with empty department or location
                if (empty($row[$bahagianIdx]) || trim($row[$bahagianIdx]) === '' ||
                    empty($row[$locationIdx]) || trim($row[$locationIdx]) === '') {
                    continue;
                }

                $allRows[] = [
                    'supervisor' => $supervisorIdx !== false ? trim($row[$supervisorIdx] ?? '') : '',
                    'bahagian' => trim($row[$bahagianIdx]),
                    'location_name' => trim($row[$locationIdx]),
                    'source_file' => $filename
                ];
            }

        } catch (Exception $e) {
            $errors[] = "File $filename: {$e->getMessage()}";
        }
    }

    // Create departments and locations with supervisor data
    foreach ($allRows as $data) {
        try {
            // Find or create department
            $deptId = find_or_create_department($data['bahagian'], '', $pdo);
            if (!$deptId) {
                $stmt = $pdo->prepare('INSERT INTO departments (name) VALUES (?)');
                $stmt->execute([$data['bahagian']]);
                $deptId = $pdo->lastInsertId();
            }
            
            // Track if this is a newly created department
            if (!isset($createdDepartments[$deptId])) {
                $departmentsCreated++;
                $createdDepartments[$deptId] = true;
            }

            // Find or create location
            $locId = find_or_create_location($data['location_name'], $deptId, $pdo);
            if (!isset($createdLocations[$locId])) {
                $locationsCreated++;
                $createdLocations[$locId] = true;
            }
            
            // Update location supervisor if provided in the file
            if (!empty($data['supervisor'])) {
                $stmt = $pdo->prepare('UPDATE locations SET supervisor = ? WHERE id = ?');
                $stmt->execute([$data['supervisor'], $locId]);
            }

        } catch (Exception $e) {
            $errors[] = "Row from {$data['source_file']}: {$e->getMessage()}";
        }
    }

    echo json_encode([
        'success' => true,
        'assets_created' => 0,
        'locations_created' => $locationsCreated,
        'departments_created' => $departmentsCreated,
        'total_rows' => count($allRows),
        'errors' => $errors
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
