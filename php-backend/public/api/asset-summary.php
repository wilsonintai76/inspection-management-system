<?php
// Asset inspection summary API
// Provides summary statistics and detailed asset lists

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

try {
    switch ($method) {
        case 'GET':
            $action = $_GET['action'] ?? 'summary';
            
            if ($action === 'summary') {
                // Get summary by department
                // Total assets = all records
                // Inspected = is_inspected = 1
                // Not inspected = is_inspected = 0
                
                $departmentFilter = isset($_GET['department_id']) && $_GET['department_id'] !== '' 
                    ? (int)$_GET['department_id'] 
                    : null;
                
                $sql = '
                    SELECT 
                        d.id as department_id,
                        d.name as department_name,
                        COUNT(a.id) as total_assets,
                        SUM(CASE WHEN a.is_inspected = 1 THEN 1 ELSE 0 END) as assets_inspected,
                        SUM(CASE WHEN a.is_inspected = 0 THEN 1 ELSE 0 END) as assets_not_inspected,
                        ROUND(
                            (SUM(CASE WHEN a.is_inspected = 1 THEN 1 ELSE 0 END) / COUNT(a.id)) * 100, 
                            2
                        ) as percentage_inspected
                    FROM departments d
                    LEFT JOIN asset_inspections a ON d.id = a.department_id
                ';
                
                if ($departmentFilter) {
                    $sql .= ' WHERE d.id = ?';
                }
                
                $sql .= ' GROUP BY d.id, d.name ORDER BY d.name';
                
                if ($departmentFilter) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute([$departmentFilter]);
                } else {
                    $stmt = $pdo->query($sql);
                }
                
                $summary = $stmt->fetchAll();
                
                // Calculate overall totals
                $totalAssets = 0;
                $totalInspected = 0;
                $totalNotInspected = 0;
                
                foreach ($summary as $row) {
                    $totalAssets += $row['total_assets'];
                    $totalInspected += $row['assets_inspected'];
                    $totalNotInspected += $row['assets_not_inspected'];
                }
                
                $overallPercentage = $totalAssets > 0 
                    ? round(($totalInspected / $totalAssets) * 100, 2) 
                    : 0;
                
                echo json_encode([
                    'summary' => $summary,
                    'overall' => [
                        'total_assets' => $totalAssets,
                        'assets_inspected' => $totalInspected,
                        'assets_not_inspected' => $totalNotInspected,
                        'percentage_inspected' => $overallPercentage
                    ]
                ]);
                
            } elseif ($action === 'assets') {
                // Get detailed asset list with filters
                $departmentId = isset($_GET['department_id']) && $_GET['department_id'] !== '' 
                    ? (int)$_GET['department_id'] 
                    : null;
                $inspected = isset($_GET['inspected']) ? (int)$_GET['inspected'] : null;
                $search = isset($_GET['search']) ? trim($_GET['search']) : '';
                $batchId = isset($_GET['batch_id']) && $_GET['batch_id'] !== '' 
                    ? (int)$_GET['batch_id'] 
                    : null;
                
                // Join to locations using department + location name to derive authoritative supervisor
                $sql = '
                    SELECT 
                        a.*,
                        d.name as department_name,
                        b.filename as batch_filename,
                        b.upload_date,
                        u.name as inspected_by_name,
                        l.supervisor as supervisor
                    FROM asset_inspections a
                    LEFT JOIN departments d ON a.department_id = d.id
                    LEFT JOIN asset_upload_batches b ON a.batch_id = b.id
                    LEFT JOIN users u ON CAST(a.inspected_by AS CHAR) COLLATE utf8mb4_unicode_ci = u.id COLLATE utf8mb4_unicode_ci
                    LEFT JOIN locations l ON l.department_id = a.department_id AND l.name = a.lokasi_terkini
                    WHERE 1=1
                ';
                
                $params = [];
                
                if ($departmentId) {
                    $sql .= ' AND a.department_id = ?';
                    $params[] = $departmentId;
                }
                
                if ($inspected !== null) {
                    $sql .= ' AND a.is_inspected = ?';
                    $params[] = $inspected;
                }
                
                if ($batchId) {
                    $sql .= ' AND a.batch_id = ?';
                    $params[] = $batchId;
                }
                
                if ($search) {
                    $sql .= ' AND (
                        a.label LIKE ? OR 
                        a.jenis_aset LIKE ? OR 
                        l.supervisor LIKE ? OR
                        a.lokasi_terkini LIKE ?
                    )';
                    $searchPattern = '%' . $search . '%';
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                    $params[] = $searchPattern;
                }
                
                $sql .= ' ORDER BY a.created_at DESC';
                
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $assets = $stmt->fetchAll();
                
                echo json_encode($assets);
                
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Invalid action']);
            }
            break;
            
        case 'PUT':
            // Mark asset as inspected
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['error' => 'Missing asset id']);
                break;
            }
            
            $d = get_json_body();
            $isInspected = isset($d['is_inspected']) ? (int)$d['is_inspected'] : 1;
            $inspectedBy = $d['inspected_by'] ?? null;
            $inspectedDate = $d['inspected_date'] ?? date('Y-m-d');
            $notes = $d['notes'] ?? null;
            
            $stmt = $pdo->prepare('
                UPDATE asset_inspections 
                SET is_inspected = ?, 
                    inspected_date = ?, 
                    inspected_by = ?,
                    notes = ?
                WHERE id = ?
            ');
            $stmt->execute([$isInspected, $inspectedDate, $inspectedBy, $notes, $_GET['id']]);
            
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
