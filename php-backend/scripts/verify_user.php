<?php
require_once __DIR__ . '/../config.php';

// Get staff_id from command line argument
$staff_id = $argv[1] ?? '1891';

try {
    // Create PDO connection
    $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4';
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
    // Update user status to Verified
    $stmt = $pdo->prepare('UPDATE users SET status = ?, email_verified = ? WHERE staff_id = ?');
    $stmt->execute(['Verified', 1, $staff_id]);
    
    echo "Rows affected: " . $stmt->rowCount() . PHP_EOL;
    
    echo "Rows affected: " . $stmt->rowCount() . PHP_EOL;
    
    if ($stmt->rowCount() > 0) {
        echo "User $staff_id has been verified successfully!" . PHP_EOL;
        
        // Fetch and display updated user info
        $stmt2 = $pdo->prepare('SELECT staff_id, name, email, status, email_verified FROM users WHERE staff_id = ?');
        $stmt2->execute([$staff_id]);
        
        if ($user = $stmt2->fetch()) {
            echo "\nUpdated User Info:" . PHP_EOL;
            echo "Staff ID: " . $user['staff_id'] . PHP_EOL;
            echo "Name: " . $user['name'] . PHP_EOL;
            echo "Email: " . $user['email'] . PHP_EOL;
            echo "Status: " . $user['status'] . PHP_EOL;
            echo "Email Verified: " . $user['email_verified'] . PHP_EOL;
        }
    } else {
        echo "No user found with staff_id: $staff_id" . PHP_EOL;
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . PHP_EOL;
}
