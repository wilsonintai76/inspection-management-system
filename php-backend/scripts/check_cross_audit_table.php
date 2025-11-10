<?php
// Quick script to check if cross_audit_assignments table exists

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../src/db.php';

echo "Checking database for cross_audit_assignments table...\n\n";

try {
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'cross_audit_assignments'");
    $table = $stmt->fetch();
    
    if ($table) {
        echo "✓ Table 'cross_audit_assignments' EXISTS\n\n";
        
        // Show table structure
        echo "Table structure:\n";
        $stmt = $pdo->query("DESCRIBE cross_audit_assignments");
        $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($columns as $col) {
            echo "  - {$col['Field']} ({$col['Type']}) {$col['Key']}\n";
        }
        
        // Show count
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM cross_audit_assignments");
        $count = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\nTotal assignments: {$count['count']}\n";
        
    } else {
        echo "✗ Table 'cross_audit_assignments' DOES NOT EXIST\n\n";
        echo "ACTION REQUIRED:\n";
        echo "1. Open phpMyAdmin\n";
        echo "2. Select your database\n";
        echo "3. Go to SQL tab\n";
        echo "4. Copy and paste the contents of:\n";
        echo "   php-backend/sql/migrate_cross_audit_dept_level.sql\n";
        echo "5. Click 'Go' to execute\n\n";
        echo "Or run this command in MySQL:\n";
        echo "mysql -u {$dbUser} -p {$dbName} < php-backend/sql/migrate_cross_audit_dept_level.sql\n";
    }
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
