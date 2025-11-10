<?php
// Asset inspection upload API
// Handles Excel (.xlsx) and CSV file uploads with columns:
// Label, Jenis Aset, Pegawai Penempatan, Bahagian, Lokasi Terkini

$srcPath = __DIR__ . '/../src';
$configPath = __DIR__ . '/../config.php';
if (!is_dir($srcPath) || !file_exists($configPath)) {
    $srcPath = __DIR__ . '/../../src';
    $configPath = __DIR__ . '/../../config.php';
}
require_once $srcPath . '/cors.php';
require_once $configPath;
require_once $srcPath . '/db.php';
require_once $srcPath . '/upload-validator.php';

$method = $_SERVER['REQUEST_METHOD'];

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

// Map Bahagian (department name from file) to department_id
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

// Parse CSV file
function parse_csv($filepath) {
    $rows = [];
    $handle = fopen($filepath, 'r');
    if (!$handle) return ['error' => 'Cannot read file'];
    
    $header = fgetcsv($handle);
    if (!$header) {
        fclose($handle);
        return ['error' => 'Empty file or invalid format'];
    }
    
    while (($row = fgetcsv($handle)) !== false) {
        if (count($row) === count($header)) {
            $rows[] = array_combine($header, $row);
        }
    }
    fclose($handle);
    return ['data' => $rows, 'header' => $header];
}

// Parse Excel file (requires PhpSpreadsheet)
function parse_excel($filepath) {
    // Check if PhpSpreadsheet is available
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        return ['error' => 'Excel support not installed. Please install PhpSpreadsheet via Composer.'];
    }
    
    try {
        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $data = $sheet->toArray();
        
        if (empty($data)) {
            return ['error' => 'Empty Excel file'];
        }
        
        $header = array_shift($data);
        $rows = [];
        foreach ($data as $row) {
            if (count($row) === count($header)) {
                $rows[] = array_combine($header, $row);
            }
        }
        
        return ['data' => $rows, 'header' => $header];
    } catch (Exception $e) {
        return ['error' => 'Failed to parse Excel: ' . $e->getMessage()];
    }
}

try {
    switch ($method) {
        case 'POST':
            // Upload file(s) - supports single or multiple files
            if (!isset($_FILES['files']) && !isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                break;
            }
            
            // Get user ID from request (should come from session/auth)
            $uploadedBy = $_POST['user_id'] ?? null;
            if (!$uploadedBy) {
                http_response_code(401);
                echo json_encode(['error' => 'Unauthorized']);
                break;
            }
            
            // Verify user is Admin
            $stmt = $pdo->prepare('SELECT role FROM user_roles WHERE user_id = ? AND role = "Admin"');
            $stmt->execute([$uploadedBy]);
            if (!$stmt->fetch()) {
                http_response_code(403);
                echo json_encode(['error' => 'Only Admin can upload files']);
                break;
            }
            
            // Optional overwrite: clear previous asset inspection data first
            $overwrite = isset($_POST['overwrite']) ? $_POST['overwrite'] : null;
            $shouldOverwrite = in_array(strtolower((string)$overwrite), ['1','true','yes','on'], true);
            if ($shouldOverwrite) {
                // Wrap in transaction for safety
                $pdo->beginTransaction();
                try {
                    // Remove existing inspection assets and batches
                    $pdo->exec('DELETE FROM asset_inspections');
                    $pdo->exec('DELETE FROM asset_upload_batches');
                    // Reset department cached totals if any
                    if ($pdo->query("SHOW COLUMNS FROM departments LIKE 'total_assets'")->rowCount() > 0) {
                        $pdo->exec('UPDATE departments SET total_assets = 0');
                    }
                    $pdo->commit();
                } catch (Throwable $e) {
                    $pdo->rollBack();
                    http_response_code(500);
                    echo json_encode(['error' => 'Failed to clear previous data: ' . $e->getMessage()]);
                    break;
                }
            }

            // Handle multiple files or single file
            $files = [];
            if (isset($_FILES['files'])) {
                // Multiple files
                $fileCount = count($_FILES['files']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $files[] = [
                            'name' => $_FILES['files']['name'][$i],
                            'tmp_name' => $_FILES['files']['tmp_name'][$i],
                            'error' => $_FILES['files']['error'][$i],
                            'size' => $_FILES['files']['size'][$i],
                        ];
                    }
                }
            } elseif (isset($_FILES['file'])) {
                // Single file (backward compatibility)
                if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name' => $_FILES['file']['name'],
                        'tmp_name' => $_FILES['file']['tmp_name'],
                        'error' => $_FILES['file']['error'],
                        'size' => $_FILES['file']['size'],
                    ];
                }
            }
            
            if (empty($files)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid files to upload']);
                break;
            }
            
            // Validate all files before processing
            $allowedTypes = [
                'text/csv' => ['csv'],
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => ['xlsx'],
                'application/vnd.ms-excel' => ['xls']
            ];
            
            $validation = UploadValidator::validateMultipleFiles($files, [
                'allowed_types' => $allowedTypes,
                'max_size' => 10 * 1024 * 1024, // 10MB per file
                'max_total_size' => 50 * 1024 * 1024 // 50MB total
            ]);
            
            if (!$validation['valid']) {
                http_response_code(400);
                echo json_encode(['error' => $validation['error']]);
                break;
            }
            
            // Parse all files and collect data
            $allData = [];
            $allFilenames = [];
            $requiredColumns = ['Label', 'Jenis Aset', 'Pegawai Penempatan', 'Bahagian', 'Lokasi Terkini'];
            $firstFileHeader = null;
            
            foreach ($files as $file) {
                $filename = $file['name'];
                $tmpPath = $file['tmp_name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
                // Parse file based on extension
                if ($ext === 'csv') {
                    $result = parse_csv($tmpPath);
                } elseif (in_array($ext, ['xlsx', 'xls'])) {
                    $result = parse_excel($tmpPath);
                } else {
                    http_response_code(400);
                    echo json_encode(['error' => "Invalid file type for '$filename'. Only CSV and Excel (.xlsx, .xls) are supported"]);
                    break 2;
                }
                
                if (isset($result['error'])) {
                    http_response_code(400);
                    echo json_encode(['error' => "Error in '$filename': " . $result['error']]);
                    break 2;
                }
                
                $data = $result['data'];
                $header = $result['header'];
                
                // Validate required columns
                foreach ($requiredColumns as $col) {
                    if (!in_array($col, $header)) {
                        http_response_code(400);
                        echo json_encode(['error' => "Missing required column '$col' in file: $filename"]);
                        break 3;
                    }
                }
                
                // Ensure all files have the same column structure
                if ($firstFileHeader === null) {
                    $firstFileHeader = $header;
                } else {
                    if ($header !== $firstFileHeader) {
                        http_response_code(400);
                        echo json_encode(['error' => "Column mismatch: All files must have the same columns. File '$filename' has different columns."]);
                        break 2;
                    }
                }
                
                // Add data from this file
                $allData = array_merge($allData, $data);
                $allFilenames[] = $filename;
            }
            
            // Create batch record with combined filename
            $notes = $_POST['notes'] ?? '';
            $batchFilename = count($allFilenames) === 1 
                ? $allFilenames[0] 
                : count($allFilenames) . ' files: ' . implode(', ', array_map(function($name) {
                    return strlen($name) > 30 ? substr($name, 0, 27) . '...' : $name;
                }, $allFilenames));
            
            $stmt = $pdo->prepare('INSERT INTO asset_upload_batches (uploaded_by, filename, total_records, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$uploadedBy, $batchFilename, count($allData), $notes]);
            $batchId = $pdo->lastInsertId();
            
            // Insert asset records
            $insertStmt = $pdo->prepare('
                INSERT INTO asset_inspections 
                (batch_id, label, jenis_aset, pegawai_penempatan, bahagian, lokasi_terkini, department_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $inserted = 0;
            foreach ($allData as $row) {
                $label = $row['Label'] ?? '';
                $jenisAset = $row['Jenis Aset'] ?? '';
                // Ignore uploaded supervisor; authoritative source is Locations.supervisor
                $pegawai = '';
                $bahagian = $row['Bahagian'] ?? '';
                $lokasi = $row['Lokasi Terkini'] ?? '';
                
                // Skip empty rows
                if (empty($label)) continue;
                
                // Map department
                $departmentId = map_department($bahagian, $pdo);
                
                $insertStmt->execute([$batchId, $label, $jenisAset, $pegawai, $bahagian, $lokasi, $departmentId]);
                $inserted++;
            }
            
            // Update batch record count
            $pdo->prepare('UPDATE asset_upload_batches SET total_records = ? WHERE id = ?')->execute([$inserted, $batchId]);
            
            echo json_encode([
                'success' => true,
                'batch_id' => $batchId,
                'total_records' => $inserted,
                'files_processed' => count($files),
                'message' => count($files) === 1 
                    ? "Successfully uploaded $inserted asset records"
                    : "Successfully merged " . count($files) . " files with $inserted asset records"
            ]);
            break;
            
        case 'GET':
            // List upload batches
            $stmt = $pdo->query('
                SELECT b.*, u.name as uploaded_by_name 
                FROM asset_upload_batches b
                LEFT JOIN users u ON b.uploaded_by = u.id
                ORDER BY b.upload_date DESC
            ');
            $batches = $stmt->fetchAll();
            echo json_encode($batches);
            break;
            
        case 'DELETE':
            // Delete batch and all associated records
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing batch id']);
                break;
            }
            
            $pdo->prepare('DELETE FROM asset_upload_batches WHERE id = ?')->execute([$_GET['id']]);
            echo json_encode(['success' => true]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
