<?php
require_once 'src/db.php';

$staffId = '1891';

$stmt = $pdo->prepare("
    SELECT staff_id, name, personal_email, email, 
           email_verified, status, 
           verification_token, verification_token_expires
    FROM users 
    WHERE staff_id = ?
");
$stmt->execute([$staffId]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($user) {
    echo "=== User Record for Staff ID $staffId ===\n\n";
    foreach ($user as $key => $value) {
        echo str_pad($key . ':', 30) . ($value ?? 'NULL') . "\n";
    }
    
    echo "\n=== Verification Status ===\n";
    echo "email_verified: " . ($user['email_verified'] ? 'YES (1)' : 'NO (0)') . "\n";
    echo "status: " . $user['status'] . "\n";
    echo "Can login? " . ($user['email_verified'] && $user['status'] === 'Verified' ? 'YES' : 'NO') . "\n";
    
    if (!$user['email_verified']) {
        echo "\nPROBLEM: email_verified is still 0!\n";
    }
    if ($user['status'] !== 'Verified') {
        echo "\nPROBLEM: status is '" . $user['status'] . "' instead of 'Verified'!\n";
    }
} else {
    echo "User with staff ID $staffId not found!\n";
}
