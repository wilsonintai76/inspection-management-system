<?php
/**
 * Password utility functions
 */

/**
 * Generate a secure random password
 * @param int $length Password length (default: 12)
 * @return string Generated password
 */
function generate_password($length = 12) {
    $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $lowercase = 'abcdefghijklmnopqrstuvwxyz';
    $numbers = '0123456789';
    $special = '!@#$%^&*';
    
    $all_chars = $uppercase . $lowercase . $numbers . $special;
    
    // Ensure at least one of each type
    $password = '';
    $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
    $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
    $password .= $numbers[random_int(0, strlen($numbers) - 1)];
    $password .= $special[random_int(0, strlen($special) - 1)];
    
    // Fill the rest randomly
    for ($i = 4; $i < $length; $i++) {
        $password .= $all_chars[random_int(0, strlen($all_chars) - 1)];
    }
    
    // Shuffle the password
    return str_shuffle($password);
}

/**
 * Hash a password using PHP's password_hash
 * @param string $password Plain text password
 * @return string Hashed password
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify a password against its hash
 * @param string $password Plain text password
 * @param string $hash Hashed password
 * @return bool True if password matches
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Send password to user email
 * @param string $email User's email address
 * @param string $staff_id User's staff ID
 * @param string $password Generated password
 * @param string $name User's name
 * @return bool Success status
 */
function send_password_email($email, $staff_id, $password, $name) {
    try {
        require_once __DIR__ . '/brevo_mailer.php';
        require_once __DIR__ . '/../config.php';
        
        $mailer = new BrevoMailer(
            BREVO_SMTP_USERNAME,
            BREVO_SMTP_PASSWORD,
            BREVO_FROM_EMAIL,
            BREVO_FROM_NAME
        );
        
        return $mailer->sendPasswordEmail($email, $name, $staff_id, $password);
    } catch (Exception $e) {
        error_log("Failed to send password email: " . $e->getMessage());
        error_log("=== PASSWORD EMAIL (FALLBACK LOG) ===");
        error_log("To: $email ($name)");
        error_log("Staff ID: $staff_id");
        error_log("Temporary Password: $password");
        error_log("=====================================");
        return false;
    }
}
