<?php
// DEV HELPER: Import locations from asset data
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

// Location data extracted from CSV with department mapping
$locationsData = [
    // JABATAN KEJURUTERAAN AWAM (ID: 7)
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'BILIK KULIAH 29', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'BILIK KULIAH 42', 'supervisor' => 'Joynna Chong'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Bilik Stor Jabatan Awam', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'BILIK UNIT PENGURUSAN FASILITI', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'DEWAN KULIAH 2', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Jabatan Kejuruteraan Awam Aras 1 Bengkel Paip Plumbing', 'supervisor' => 'MUHAMAD ZAKWAN BIN ZAKARIAH'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'MAKMAL KOMPUTER JKA', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Pejabat Stor Utama', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Pusat Sumber JKA', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Ruang Legar JKM/JKA', 'supervisor' => 'Nazmiah binti Nawi'],
    ['dept_name' => 'JABATAN KEJURUTERAAN AWAM', 'location' => 'Ruang Pejabat Jab, Awam', 'supervisor' => 'Joynna Chong'],
    
    // JABATAN KEJURUTERAAN ELEKTRIK (ID: 12)
    ['dept_name' => 'JABATAN KEJURUTERAAN ELEKTRIK', 'location' => 'Bilik KJ JKE', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    ['dept_name' => 'JABATAN KEJURUTERAAN ELEKTRIK', 'location' => 'Ruang Pejabat Jabatan JKE', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    
    // JABATAN KEJURUTERAAN MEKANIKAL (ID: 13)
    ['dept_name' => 'JABATAN KEJURUTERAAN MEKANIKAL', 'location' => 'Bilik KJ Mekanikal', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    
    // JABATAN PENGAJIAN AM (ID: 11)
    ['dept_name' => 'JABATAN PENGAJIAN AM', 'location' => 'Jabatan Pengajian Am', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    
    // JABATAN SUKAN & KOKURIKULUM (ID: 9)
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'BILIK 1', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'Bilik Stor JSKK3', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'DICONVERT MENJADI DWN SUKAN', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'Gymnasium', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'KUARTERS KELAS DAYA', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'RUANG 1', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'RUANG 2', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'Stor JSKK1', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    ['dept_name' => 'JABATAN SUKAN & KOKURIKULUM', 'location' => 'Stor JSKK2', 'supervisor' => 'MUSTAFFA BIN ABDUL RAHMAN'],
    
    // JABATAN TEKNOLOGI MAKLUMAT & KOMUNIKASI (ID: 14)
    ['dept_name' => 'JABATAN TEKNOLOGI MAKLUMAT & KOMUNIKASI', 'location' => 'BILIK KUALITI DAN DIGITAL', 'supervisor' => 'SHAHRULNIZAM BIN BAHARI'],
    
    // PEJABAT TIMBALAN PENGARAH SOKONGAN AKADEMIK (ID: 8)
    ['dept_name' => 'PEJABAT TIMBALAN PENGARAH SOKONGAN AKADEMIK', 'location' => 'BILIK PEGAWAI KESELAMATAN PKS', 'supervisor' => 'SHAHRULNIZAM BIN BAHARI'],
    
    // UNIT LATIHAN & PENDIDIKAN LANJUTAN (ID: 10)
    ['dept_name' => 'UNIT LATIHAN & PENDIDIKAN LANJUTAN', 'location' => 'BILIK KULPL', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    ['dept_name' => 'UNIT LATIHAN & PENDIDIKAN LANJUTAN', 'location' => 'BILIK PLPL', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    ['dept_name' => 'UNIT LATIHAN & PENDIDIKAN LANJUTAN', 'location' => 'BILIK SEMINAR 1', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
    ['dept_name' => 'UNIT LATIHAN & PENDIDIKAN LANJUTAN', 'location' => 'BILIK SEMINAR 2', 'supervisor' => 'Noor Munirah Binti Mohd Noor'],
];

try {
    $inserted = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($locationsData as $loc) {
        // Get department ID by name
        $stmt = $pdo->prepare('SELECT id FROM departments WHERE name = ? LIMIT 1');
        $stmt->execute([$loc['dept_name']]);
        $dept = $stmt->fetch();
        
        if (!$dept) {
            $errors[] = "Department not found: {$loc['dept_name']}";
            $skipped++;
            continue;
        }
        
        $deptId = $dept['id'];
        
        // Check if location already exists
        $stmt = $pdo->prepare('SELECT id FROM locations WHERE department_id = ? AND name = ? LIMIT 1');
        $stmt->execute([$deptId, $loc['location']]);
        $existing = $stmt->fetch();
        
        if ($existing) {
            $skipped++;
            continue;
        }
        
        // Insert location
        $stmt = $pdo->prepare('INSERT INTO locations (name, department_id, supervisor) VALUES (?, ?, ?)');
        $stmt->execute([$loc['location'], $deptId, $loc['supervisor']]);
        $inserted++;
    }
    
    echo json_encode([
        'ok' => true,
        'total_locations' => count($locationsData),
        'inserted' => $inserted,
        'skipped' => $skipped,
        'errors' => $errors,
        'message' => "Imported $inserted locations, skipped $skipped duplicates"
    ], JSON_PRETTY_PRINT);
    
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'ok' => false,
        'error' => $e->getMessage()
    ]);
}
