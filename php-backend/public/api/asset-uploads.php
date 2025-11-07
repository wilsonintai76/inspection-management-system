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
            // Upload file
            if (!isset($_FILES['file'])) {
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
            
            $file = $_FILES['file'];
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
                echo json_encode(['error' => 'Invalid file type. Only CSV and Excel (.xlsx, .xls) are supported']);
                break;
            }
            
            if (isset($result['error'])) {
                http_response_code(400);
                echo json_encode(['error' => $result['error']]);
                break;
            }
            
            $data = $result['data'];
            $header = $result['header'];
            
            // Validate required columns
            $requiredColumns = ['Label', 'Jenis Aset', 'Pegawai Penempatan', 'Bahagian', 'Lokasi Terkini'];
            foreach ($requiredColumns as $col) {
                if (!in_array($col, $header)) {
                    http_response_code(400);
                    echo json_encode(['error' => "Missing required column: $col"]);
                    break 2;
                }
            }
            
            // Create batch record
            $notes = $_POST['notes'] ?? '';
            $stmt = $pdo->prepare('INSERT INTO asset_upload_batches (uploaded_by, filename, total_records, notes) VALUES (?, ?, ?, ?)');
            $stmt->execute([$uploadedBy, $filename, count($data), $notes]);
            $batchId = $pdo->lastInsertId();
            
            // Insert asset records
            $insertStmt = $pdo->prepare('
                INSERT INTO asset_inspections 
                (batch_id, label, jenis_aset, pegawai_penempatan, bahagian, lokasi_terkini, department_id) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ');
            
            $inserted = 0;
            foreach ($data as $row) {
                $label = $row['Label'] ?? '';
                $jenisAset = $row['Jenis Aset'] ?? '';
                $pegawai = $row['Pegawai Penempatan'] ?? '';
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
                'message' => "Successfully uploaded $inserted asset records"
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
