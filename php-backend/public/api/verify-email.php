<?php
/**
 * Email Verification Endpoint
 * Verifies user email using token
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
    
    if (empty($data['token'])) {
        http_response_code(400);
        echo json_encode(['error' => 'Verification token is required']);
        exit;
    }
    
    $token = trim($data['token']);
    
    try {
        // Use global $pdo from db.php
        
        // Find user with this token
        $stmt = $pdo->prepare("
            SELECT id, staff_id, name, email, email_verified, verification_token_expires
            FROM users 
            WHERE verification_token = :token
        ");
        $stmt->execute([':token' => $token]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            http_response_code(404);
            echo json_encode(['error' => 'Invalid verification token']);
            exit;
        }
        
        // Check if already verified
        if ($user['email_verified']) {
            http_response_code(200);
            echo json_encode([
                'message' => 'Email already verified',
                'staff_id' => $user['staff_id']
            ]);
            exit;
        }
        
        // Check if token expired
        $now = new DateTime();
        $expires = new DateTime($user['verification_token_expires']);
        
        if ($now > $expires) {
            http_response_code(410);
            echo json_encode(['error' => 'Verification token has expired']);
            exit;
        }
        
        // Update user as verified
        $stmt = $pdo->prepare("
            UPDATE users 
            SET email_verified = 1,
                status = 'Verified',
                verification_token = NULL,
                verification_token_expires = NULL
            WHERE id = :id
        ");
        $stmt->execute([':id' => $user['id']]);
        
        // Send welcome email
        try {
            $mailer = new BrevoMailer(
                BREVO_SMTP_USERNAME,
                BREVO_SMTP_PASSWORD,
                BREVO_FROM_EMAIL,
                BREVO_FROM_NAME
            );
            
            $mailer->sendWelcomeEmail(
                $user['email'],
                $user['name'],
                $user['staff_id']
            );
        } catch (Exception $e) {
            error_log("Welcome email error: " . $e->getMessage());
        }
        
        http_response_code(200);
        echo json_encode([
            'message' => 'Email verified successfully',
            'staff_id' => $user['staff_id'],
            'name' => $user['name']
        ]);
        
    } catch (PDOException $e) {
        error_log("Email verification error: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Verification failed. Please try again.']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
}
