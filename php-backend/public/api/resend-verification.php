<?php
/**
 * Resend Verification Email Endpoint
 * Resends verification email to user
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
require_once $srcPath . '/brevo_mailer.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['email'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Email is required']);
        exit;
    }
    
    $email = trim($data['email']);
    
    try {
        // Use global $pdo from db.php
        
        // Find user by email
        $stmt = $pdo->prepare("
            SELECT id, staff_id, name, email, email_verified, verification_token
            FROM users 
            WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            // Don't reveal if email exists
            http_response_code(200);
            echo json_encode(['message' => 'If the email exists, a verification email will be sent']);
            exit;
        }
        
        // Check if already verified
        if ($user['email_verified']) {
            http_response_code(200);
            echo json_encode(['message' => 'Email is already verified']);
            exit;
        }
        
        // Generate new verification token
        $verification_token = bin2hex(random_bytes(32));
        $verification_expires = date('Y-m-d H:i:s', strtotime('+24 hours'));
        
        // Update token
        $stmt = $pdo->prepare("
            UPDATE users 
            SET verification_token = :token,
                verification_token_expires = :expires
            WHERE id = :id
        ");
        $stmt->execute([
            ':token' => $verification_token,
            ':expires' => $verification_expires,
            ':id' => $user['id']
        ]);
        
        // Send verification email
        try {
            $mailer = new BrevoMailer(
                BREVO_SMTP_USERNAME,
                BREVO_SMTP_PASSWORD,
                BREVO_FROM_EMAIL,
                BREVO_FROM_NAME
            );
            
            $email_sent = $mailer->sendVerificationEmail(
                $user['email'],
                $user['name'],
                $verification_token,
                APP_URL
            );
            
            if (!$email_sent) {
                error_log("Failed to resend verification email to {$email}");
            }
        } catch (Exception $e) {
            error_log("Email error: " . $e->getMessage());
        }
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Verification email sent. Please check your inbox.',
            'email_sent' => $email_sent ?? false
        ]);
        
    } catch (PDOException $e) {
        error_log("Resend verification error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Failed to send verification email. Please try again.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
