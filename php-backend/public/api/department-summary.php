<?php
// Department Summary Files API
// Upload, view, and delete summary documents for departments

require_once __DIR__ . '/../src/cors.php';
require_once __DIR__ . '/../src/db.php';

$method = $_SERVER['REQUEST_METHOD'];

function get_json_body() {
    $raw = file_get_contents('php://input');
    if (!$raw) return [];
    $json = json_decode($raw, true);
    return is_array($json) ? $json : [];
}

try {
    switch ($method) {
        case 'GET':
            // Get all summary files for a department
            $departmentId = $_GET['department_id'] ?? null;
            
            if (!$departmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing department_id parameter']);
                break;
            }
            
            $stmt = $pdo->prepare('
                SELECT * FROM department_summary_files 
                WHERE department_id = ? 
                ORDER BY uploaded_at DESC
            ');
            $stmt->execute([$departmentId]);
            $files = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'files' => $files,
                'count' => count($files)
            ]);
            break;
            
        case 'POST':
            // Upload multiple summary files
            $departmentId = $_POST['department_id'] ?? null;
            
            if (!$departmentId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing department_id']);
                break;
            }
            
            if (empty($_FILES['files'])) {
                http_response_code(400);
                echo json_encode(['error' => 'No files uploaded']);
                break;
            }
            
            // Create uploads directory if it doesn't exist
            $uploadDir = __DIR__ . '/../uploads/department-summaries/' . $departmentId . '/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            $uploadedFiles = [];
            $files = $_FILES['files'];
            
            // Handle multiple files
            $fileCount = count($files['name']);
            for ($i = 0; $i < $fileCount; $i++) {
                if ($files['error'][$i] !== UPLOAD_ERR_OK) {
                    continue;
                }
                
                $filename = basename($files['name'][$i]);
                $tmpName = $files['tmp_name'][$i];
                $filesize = $files['size'][$i];
                
                // Generate unique filename
                $ext = pathinfo($filename, PATHINFO_EXTENSION);
                $basename = pathinfo($filename, PATHINFO_FILENAME);
                $uniqueName = $basename . '_' . time() . '_' . uniqid() . '.' . $ext;
                $filepath = $uploadDir . $uniqueName;
                
                if (move_uploaded_file($tmpName, $filepath)) {
                    // Store relative path
                    $relativePath = 'uploads/department-summaries/' . $departmentId . '/' . $uniqueName;
                    
                    // Insert into database
                    $stmt = $pdo->prepare('
                        INSERT INTO department_summary_files 
                        (department_id, filename, filepath, filesize) 
                        VALUES (?, ?, ?, ?)
                    ');
                    $stmt->execute([$departmentId, $filename, $relativePath, $filesize]);
                    
                    $uploadedFiles[] = [
                        'id' => $pdo->lastInsertId(),
                        'filename' => $filename,
                        'filepath' => $relativePath,
                        'filesize' => $filesize
                    ];
                }
            }
            
            if (empty($uploadedFiles)) {
                http_response_code(500);
                echo json_encode(['error' => 'Failed to upload any files']);
                break;
            }
            
            echo json_encode([
                'success' => true,
                'message' => count($uploadedFiles) . ' file(s) uploaded successfully',
                'files' => $uploadedFiles
            ]);
            break;
            
        case 'DELETE':
            // Delete a summary file
            $fileId = $_GET['id'] ?? null;
            
            if (!$fileId) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing file id']);
                break;
            }
            
            // Get file info
            $stmt = $pdo->prepare('SELECT filepath FROM department_summary_files WHERE id = ?');
            $stmt->execute([$fileId]);
            $file = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$file) {
                http_response_code(404);
                echo json_encode(['error' => 'File not found']);
                break;
            }
            
            // Delete physical file
            $fullPath = __DIR__ . '/../' . $file['filepath'];
            if (file_exists($fullPath)) {
                unlink($fullPath);
            }
            
            // Delete from database
            $stmt = $pdo->prepare('DELETE FROM department_summary_files WHERE id = ?');
            $stmt->execute([$fileId]);
            
            echo json_encode([
                'success' => true,
                'message' => 'File deleted successfully'
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
