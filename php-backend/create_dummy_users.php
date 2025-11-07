<?php
/**
 * Create Dummy Users with Different Roles
 * Run this script to create test users for RBAC testing
 */

require_once __DIR__ . '/src/db.php';

echo "=== Creating Dummy Users for RBAC Testing ===\n\n";

// Ensure IT department exists
try {
    $stmt = $pdo->query("SELECT id FROM departments WHERE name = 'Information Technology' LIMIT 1");
    $dept = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$dept) {
        echo "Creating IT Department...\n";
        $stmt = $pdo->prepare("INSERT INTO departments (name, created_at, updated_at) VALUES (?, NOW(), NOW())");
        $stmt->execute(['Information Technology']);
        $it_dept_id = $pdo->lastInsertId();
        echo "✓ IT Department created (ID: $it_dept_id)\n\n";
    } else {
        $it_dept_id = $dept['id'];
        echo "✓ IT Department exists (ID: $it_dept_id)\n\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Password hash for "password123"
$passwordHash = password_hash('password123', PASSWORD_BCRYPT);

// Define dummy users
$dummyUsers = [
    [
        'id' => '2001',
        'staff_id' => '2001',
        'name' => 'Ahmad Viewer',
        'email' => 'ahmad.viewer@poliku.edu.my',
        'personal_email' => 'ahmad.viewer@gmail.com',
        'phone' => '012-3456001',
        'department_id' => $it_dept_id,
        'role' => 'Viewer',
        'description' => 'Can only view dashboard overview'
    ],
    [
        'id' => '2002',
        'staff_id' => '2002',
        'name' => 'Siti Asset Officer',
        'email' => 'siti.asset@poliku.edu.my',
        'personal_email' => 'siti.asset@gmail.com',
        'phone' => '012-3456002',
        'department_id' => $it_dept_id,
        'role' => 'Asset Officer',
        'description' => 'Can manage IT department inspections and departments'
    ],
    [
        'id' => '2003',
        'staff_id' => '2003',
        'name' => 'Kumar Auditor',
        'email' => 'kumar.auditor@poliku.edu.my',
        'personal_email' => 'kumar.auditor@gmail.com',
        'phone' => '012-3456003',
        'department_id' => $it_dept_id,
        'role' => 'Auditor',
        'description' => 'Can manage schedule (add/remove self)'
    ],
    [
        'id' => '2004',
        'staff_id' => '2004',
        'name' => 'Admin SuperUser',
        'email' => 'admin@poliku.edu.my',
        'personal_email' => 'admin.super@gmail.com',
        'phone' => '012-3456004',
        'department_id' => $it_dept_id,
        'role' => 'Admin',
        'description' => 'Full access to all features'
    ]
];

echo "Creating dummy users...\n\n";

foreach ($dummyUsers as $user) {
    try {
        // Delete existing user if exists
        $stmt = $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?");
        $stmt->execute([$user['id']]);
        
        $stmt = $pdo->prepare("DELETE FROM users WHERE staff_id = ?");
        $stmt->execute([$user['staff_id']]);
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                id, staff_id, name, email, personal_email, phone, 
                department_id, password_hash, must_change_password, 
                email_verified, status, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, 1, 'Verified', NOW(), NOW())
        ");
        
        $stmt->execute([
            $user['id'],
            $user['staff_id'],
            $user['name'],
            $user['email'],
            $user['personal_email'],
            $user['phone'],
            $user['department_id'],
            $passwordHash
        ]);
        
        // Insert role
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (?, ?)");
        $stmt->execute([$user['id'], $user['role']]);
        
        echo "✓ Created: {$user['name']}\n";
        echo "  Staff ID: {$user['staff_id']}\n";
        echo "  Email: {$user['email']}\n";
        echo "  Role: {$user['role']}\n";
        echo "  Description: {$user['description']}\n";
        echo "  Password: password123\n\n";
        
    } catch (Exception $e) {
        echo "✗ Failed to create {$user['name']}: " . $e->getMessage() . "\n\n";
    }
}

// Verify all users
echo "=== Verification ===\n\n";
try {
    $stmt = $pdo->query("
        SELECT 
            u.staff_id,
            u.name,
            u.email,
            u.status,
            GROUP_CONCAT(ur.role) as roles,
            d.name as department
        FROM users u
        LEFT JOIN user_roles ur ON u.id = ur.user_id
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.staff_id IN ('2001', '2002', '2003', '2004')
        GROUP BY u.id
        ORDER BY u.staff_id
    ");
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($users) > 0) {
        echo "All dummy users created successfully:\n\n";
        foreach ($users as $user) {
            echo "Staff ID: {$user['staff_id']} | {$user['name']} | Role: {$user['roles']} | Dept: {$user['department']}\n";
        }
        echo "\n";
    } else {
        echo "No users found. Something went wrong.\n\n";
    }
} catch (Exception $e) {
    echo "Error during verification: " . $e->getMessage() . "\n\n";
}

echo "=== Test Credentials ===\n";
echo "Viewer:        Staff ID: 2001, Password: password123\n";
echo "Asset Officer: Staff ID: 2002, Password: password123\n";
echo "Auditor:       Staff ID: 2003, Password: password123\n";
echo "Admin:         Staff ID: 2004, Password: password123\n";
echo "\nYou can now test each role by logging in with these credentials!\n";
