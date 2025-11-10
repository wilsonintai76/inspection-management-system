<?php
header('Content-Type: application/json');
echo json_encode([
    'name' => 'Inspectable API',
    'status' => 'ok',
    'endpoints' => [
        '/api/departments.php',
        '/api/locations.php',
        '/api/inspections.php',
        '/api/users.php',
        '/api/auth_login.php',
        '/api/change_password.php',
        '/api/cross-audit.php',
        '/api/eligible-auditors.php'
    ]
]);
