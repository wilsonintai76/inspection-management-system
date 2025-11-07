<?php
/**
 * Self-Registration Endpoint
 * Allows users to register themselves with email verification
 */

// Portable includes: support both deployed WAMP path and repo path
$srcPath = __DIR__ . '/../src';
$configPath = __DIR__ . '/../config.php';
if (!is_dir($srcPath) || !file_exists($configPath)) {
    $srcPath = __DIR__ . '/../../src';
    $configPath = __DIR__ . '/../../config.php';
}
require_once $srcPath . '/cors.php';
require_once $configPath;
require_once $srcPath . '/db.php';
require_once $srcPath . '/password_utils.php';
require_once $srcPath . '/brevo_mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    $required = ['name', 'email', 'password', 'staff_id'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: {$field}"]);
            exit;
        }
    }
    
    $name = trim($data['name']);
    $email = trim($data['email']);
    $password = $data['password'];
    $staff_id = trim($data['staff_id']);
    $phone = isset($data['phone']) ? trim($data['phone']) : null;
    $department_id = isset($data['department_id']) ? intval($data['department_id']) : null;
    
    // Validate staff_id format (4 digits)
    if (!preg_match('/^\d{4}$/', $staff_id)) {
        http_response_code(400);
        echo json_encode(['error' => 'Staff ID must be exactly 4 digits']);
        exit;
    }
    
    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(['error' => 'Invalid email address']);
        exit;
    }
    
    // Validate password strength (minimum 8 characters)
    if (strlen($password) < 8) {
        http_response_code(400);
        echo json_encode(['error' => 'Password must be at least 8 characters']);
        exit;
    }
    
    try {
        
        // Check if staff_id already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE staff_id = :staff_id");
        $stmt->execute([':staff_id' => $staff_id]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Staff ID already registered']);
            exit;
        }
        
        // Check if email already exists
        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        if ($stmt->fetch()) {
            http_response_code(409);
            echo json_encode(['error' => 'Email already registered']);
            exit;
        }
        
        // Generate unique ID
        $user_id = uniqid('user_', true);
        
        // Hash password
        $password_hash = hash_password($password);
        
        // Generate verification token
        $verification_token = bin2hex(random_bytes(32));
        $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Insert user
        $stmt = $pdo->prepare("
            INSERT INTO users (
                id, staff_id, name, email, phone, department_id, 
                password_hash, must_change_password, email_verified,
                verification_token, verification_token_expires, status
            ) VALUES (
                :id, :staff_id, :name, :email, :phone, :department_id,
                :password_hash, 0, 0,
                :verification_token, :verification_token_expires, 'Unverified'
            )
        ");
        
        $stmt->execute([
            ':id' => $user_id,
            ':staff_id' => $staff_id,
            ':name' => $name,
            ':email' => $email,
            ':phone' => $phone,
            ':department_id' => $department_id,
            ':password_hash' => $password_hash,
            ':verification_token' => $verification_token,
            ':verification_token_expires' => $verification_expires
        ]);
        
        // Assign default role (Viewer)
        $stmt = $pdo->prepare("INSERT INTO user_roles (user_id, role) VALUES (:user_id, 'Viewer')");
        $stmt->execute([':user_id' => $user_id]);
        
        // Send verification email using Brevo
        try {
            $mailer = new BrevoMailer(
                BREVO_SMTP_USERNAME,
                BREVO_SMTP_PASSWORD,
                BREVO_FROM_EMAIL,
                BREVO_FROM_NAME
            );
            
            $email_sent = $mailer->sendVerificationEmail(
                $email,
                $name,
                $verification_token,
                APP_URL
            );
            
            if (!$email_sent) {
                error_log("Failed to send verification email to {$email}");
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
        }
        
        http_response_code(201);
        echo json_encode([
            'message' => 'Registration successful. Please check your email to verify your account.',
            'user_id' => $user_id,
            'staff_id' => $staff_id,
            'email_sent' => $email_sent ?? false
        ]);
        
    } catch (PDOException $e) {
        error_log("Registration error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Registration failed. Please try again.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
