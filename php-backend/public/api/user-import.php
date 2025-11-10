<?php
// User bulk import API
// Handles CSV and Excel file uploads for bulk user creation

$srcPath = __DIR__ . '/../src';
$configPath = __DIR__ . '/../config.php';
if (!is_dir($srcPath) || !file_exists($configPath)) {
    $srcPath = __DIR__ . '/../../src';
    $configPath = __DIR__ . '/../../config.php';
}
require_once $srcPath . '/cors.php';
require_once $configPath;
require_once $srcPath . '/db.php';
// Password utilities (generation + hashing + optional email send)
require_once $srcPath . '/password_utils.php';

$method = $_SERVER['REQUEST_METHOD'];

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

// Map Department name to department_id
function map_department($departmentName, $pdo) {
    if (empty($departmentName)) return null;
    
    // Try exact match first
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? OR acronym = ? LIMIT 1');
    $stmt->execute([$departmentName, $departmentName]);
    $row = $stmt->fetch();
    if ($row) return $row['id'];
    
    // Try partial match
    $stmt = $pdo->prepare('SELECT id FROM departments WHERE name LIKE ? OR acronym LIKE ? LIMIT 1');
    $pattern = '%' . $departmentName . '%';
    $stmt->execute([$pattern, $pattern]);
    $row = $stmt->fetch();
    return $row ? $row['id'] : null;
}

// Validate role
function validate_role($role) {
    $validRoles = ['Admin', 'Asset Officer', 'Auditor', 'Viewer'];
    return in_array($role, $validRoles);
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

// Parse Excel file
function parse_excel($filepath) {
    if (!class_exists('PhpOffice\PhpSpreadsheet\IOFactory')) {
        return ['error' => 'Excel support not installed. Please use CSV format or install PhpSpreadsheet via Composer.'];
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
            // Import users from file(s)
            if (!isset($_FILES['files']) && !isset($_FILES['file'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No file uploaded']);
                break;
            }
            
            // Get user ID from request
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
                echo json_encode(['error' => 'Only Admin can import users']);
                break;
            }
            
            // Handle multiple files or single file
            $files = [];
            if (isset($_FILES['files'])) {
                $fileCount = count($_FILES['files']['name']);
                for ($i = 0; $i < $fileCount; $i++) {
                    if ($_FILES['files']['error'][$i] === UPLOAD_ERR_OK) {
                        $files[] = [
                            'name' => $_FILES['files']['name'][$i],
                            'tmp_name' => $_FILES['files']['tmp_name'][$i],
                            'error' => $_FILES['files']['error'][$i],
                        ];
                    }
                }
            } elseif (isset($_FILES['file'])) {
                if ($_FILES['file']['error'] === UPLOAD_ERR_OK) {
                    $files[] = [
                        'name' => $_FILES['file']['name'],
                        'tmp_name' => $_FILES['file']['tmp_name'],
                        'error' => $_FILES['file']['error'],
                    ];
                }
            }
            
            if (empty($files)) {
                http_response_code(400);
                echo json_encode(['error' => 'No valid files to upload']);
                break;
            }
            
            // Parse all files and collect data
            $allData = [];
            $requiredColumns = ['Staff ID', 'Name', 'Email', 'Department', 'Role'];
            $optionalColumns = ['Phone', 'Personal Email'];
            $firstFileHeader = null;
            $errors = [];
            
            foreach ($files as $file) {
                $filename = $file['name'];
                $tmpPath = $file['tmp_name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
                
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
                
                $allData = array_merge($allData, $data);
            }
            
            // Process users
            $imported = 0;
            $skipped = 0;
            $rowNumber = 1;
            $generatedCredentials = []; // collect temp passwords to return to admin (not stored in plain text elsewhere)
            
            foreach ($allData as $row) {
                $rowNumber++;
                
                $staffId = trim($row['Staff ID'] ?? '');
                $name = trim($row['Name'] ?? '');
                $email = trim($row['Email'] ?? '');
                $departmentName = trim($row['Department'] ?? '');
                $role = trim($row['Role'] ?? '');
                $phone = trim($row['Phone'] ?? '');
                $personalEmail = trim($row['Personal Email'] ?? '');
                
                // Skip empty rows
                if (empty($staffId) || empty($name) || empty($email)) {
                    $errors[] = "Row $rowNumber: Missing required fields (Staff ID, Name, or Email)";
                    $skipped++;
                    continue;
                }
                
                // Validate staff ID format (4 digits)
                if (!preg_match('/^\d{4}$/', $staffId)) {
                    $errors[] = "Row $rowNumber: Invalid Staff ID '$staffId' (must be 4 digits)";
                    $skipped++;
                    continue;
                }
                
                // Validate email format
                if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = "Row $rowNumber: Invalid email format '$email'";
                    $skipped++;
                    continue;
                }
                
                // Validate role
                if (!validate_role($role)) {
                    $errors[] = "Row $rowNumber: Invalid role '$role' (must be: Admin, Asset Officer, Auditor, or Viewer)";
                    $skipped++;
                    continue;
                }
                
                // Check for duplicate staff ID
                $stmt = $pdo->prepare('SELECT id FROM users WHERE staff_id = ?');
                $stmt->execute([$staffId]);
                if ($stmt->fetch()) {
                    $errors[] = "Row $rowNumber: Staff ID '$staffId' already exists";
                    $skipped++;
                    continue;
                }
                
                // Check for duplicate email
                $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
                $stmt->execute([$email]);
                if ($stmt->fetch()) {
                    $errors[] = "Row $rowNumber: Email '$email' already exists";
                    $skipped++;
                    continue;
                }
                
                // Map department
                $departmentId = map_department($departmentName, $pdo);
                if (!$departmentId && !empty($departmentName)) {
                    $errors[] = "Row $rowNumber: Department '$departmentName' not found";
                }
                
                // Generate user ID
                $userId = strtolower(str_replace(' ', '', $name)) . '_' . $staffId;
                
                try {
                    // Generate secure temporary password
                    $tempPassword = generate_password(12);
                    $passwordHash = hash_password($tempPassword);
                    $mustChange = 1; // force change at first login

                    // Decide verification/status policy: admin bulk import -> auto-verified
                    $emailVerified = 1; // user can log in immediately
                    $status = 'Verified';

                    // Insert user with password hash
                    $stmt = $pdo->prepare('
                        INSERT INTO users 
                        (id, staff_id, name, email, personal_email, phone, department_id, photo_url, password_hash, must_change_password, email_verified, status) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, NULL, ?, ?, ?, ?)
                    ');
                    $stmt->execute([
                        $userId,
                        $staffId,
                        $name,
                        $email,
                        $personalEmail ?: null,
                        $phone ?: null,
                        $departmentId,
                        $passwordHash,
                        $mustChange,
                        $emailVerified,
                        $status
                    ]);

                    // Assign role
                    $stmt = $pdo->prepare('INSERT INTO user_roles (user_id, role) VALUES (?, ?)');
                    $stmt->execute([$userId, $role]);

                    $imported++;

                    // Collect credential (limit collection to first 200 for response size control)
                    if (count($generatedCredentials) < 200) {
                        $generatedCredentials[] = [
                            'staff_id' => $staffId,
                            'user_id' => $userId,
                            'email' => $email,
                            'name' => $name,
                            'temporary_password' => $tempPassword
                        ];
                    }
                } catch (PDOException $e) {
                    $errors[] = "Row $rowNumber: Database error - " . $e->getMessage();
                    $skipped++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'users_imported' => $imported,
                'users_skipped' => $skipped,
                'files_processed' => count($files),
                'errors' => $errors,
                'credentials' => $generatedCredentials,
                'message' => count($files) === 1 
                    ? "Successfully imported $imported users" . ($skipped > 0 ? " ($skipped skipped)" : "")
                    : "Successfully processed " . count($files) . " files: imported $imported users" . ($skipped > 0 ? " ($skipped skipped)" : "")
            ]);
            break;
            
        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method Not Allowed']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
