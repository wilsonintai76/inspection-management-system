<?php
/**
 * Brevo SMTP Email Service
 * Uses Brevo (formerly SendinBlue) SMTP to send emails
 */

class BrevoMailer {
    private $smtp_host = 'smtp-relay.brevo.com';
    private $smtp_port = 587;
    private $smtp_username;
    private $smtp_password;
    private $from_email;
    private $from_name;
    private $api_key; // Brevo HTTP API key (preferred on Windows)
    
    public function __construct($smtp_username, $smtp_password, $from_email, $from_name = 'Inspectable') {
        $this->smtp_username = $smtp_username;
        $this->smtp_password = $smtp_password;
        $this->from_email = $from_email;
        $this->from_name = $from_name;
        // Auto-wire API key from config if defined
        if (defined('BREVO_API_KEY')) {
            $this->api_key = BREVO_API_KEY;
        }
    }
    
    /**
     * Send email using Brevo SMTP
     * @param string $to_email Recipient email address
     * @param string $to_name Recipient name
     * @param string $subject Email subject
     * @param string $html_body HTML email body
     * @param string $text_body Plain text email body (optional)
     * @return bool Success status
     */
    public function send($to_email, $to_name, $subject, $html_body, $text_body = '') {
        try {
            // Prefer Brevo HTTP API when API key is available
            if (!empty($this->api_key)) {
                return $this->sendViaApi($to_email, $to_name, $subject, $html_body, $text_body);
            }

            // Fallback to PHP mail() (note: mail() cannot authenticate SMTP on Windows)
            $boundary = md5(uniqid(time()));
            
            // Build email headers
            $headers = [];
            $headers[] = "From: {$this->from_name} <{$this->from_email}>";
            $headers[] = "Reply-To: {$this->from_email}";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            
            // Build email body
            $message = "--{$boundary}\r\n";
            
            // Plain text version
            if (!empty($text_body)) {
                $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
                $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
                $message .= $text_body . "\r\n";
                $message .= "--{$boundary}\r\n";
            }
            
            // HTML version
            $message .= "Content-Type: text/html; charset=UTF-8\r\n";
            $message .= "Content-Transfer-Encoding: 7bit\r\n\r\n";
            $message .= $html_body . "\r\n";
            $message .= "--{$boundary}--";
            
            // Send using PHP mail() with basic settings (no auth)
            $success = mail(
                $to_email,
                $subject,
                $message,
                implode("\r\n", $headers)
            );
            
            if (!$success) {
                error_log("Brevo Email Failed: " . error_get_last()['message']);
                return false;
            }
            
            error_log("Brevo Email Sent: To={$to_email}, Subject={$subject}");
            return true;
            
        } catch (Exception $e) {
            error_log("Brevo Email Error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Send email via Brevo HTTP API (recommended)
     */
    private function sendViaApi($to_email, $to_name, $subject, $html_body, $text_body = '') {
        $url = 'https://api.brevo.com/v3/smtp/email';
        $payload = [
            'sender' => [
                'name' => $this->from_name,
                'email' => $this->from_email,
            ],
            'to' => [
                [ 'email' => $to_email, 'name' => $to_name ]
            ],
            'subject' => $subject,
            'htmlContent' => $html_body,
        ];
        if (!empty($text_body)) {
            $payload['textContent'] = $text_body;
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'accept: application/json',
            'content-type: application/json',
            'api-key: ' . $this->api_key,
        ]);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Disable SSL verification for local dev
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            error_log('Brevo API CURL error: ' . $curlErr);
            return false;
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            error_log('Brevo API Sent: To=' . $to_email . ', Subject=' . $subject . ', Code=' . $httpCode);
            return true;
        }

        error_log('Brevo API failed. HTTP ' . $httpCode . ' Response: ' . $response);
        return false;
    }
    
    /**
     * Send verification email
     */
    public function sendVerificationEmail($to_email, $to_name, $verification_token, $base_url) {
        $verification_url = "{$base_url}/#/verify-email?token={$verification_token}";
        
        $subject = "Verify Your Email - Inspectable";
        
        $html_body = $this->getVerificationEmailTemplate($to_name, $verification_url);
        
        $text_body = "Welcome to Inspectable!\n\n"
                   . "Please verify your email address by clicking the link below:\n"
                   . "{$verification_url}\n\n"
                   . "This link will expire in 24 hours.\n\n"
                   . "If you did not create this account, please ignore this email.";
        
        return $this->send($to_email, $to_name, $subject, $html_body, $text_body);
    }
    
    /**
     * Send welcome email after verification
     */
    public function sendWelcomeEmail($to_email, $to_name, $staff_id) {
        $subject = "Welcome to Inspectable!";
        
        $html_body = $this->getWelcomeEmailTemplate($to_name, $staff_id);
        
        $text_body = "Welcome to Inspectable!\n\n"
                   . "Your email has been verified successfully.\n"
                   . "Your Staff ID is: {$staff_id}\n\n"
                   . "You can now log in using your Staff ID and password.\n\n"
                   . "Thank you for joining us!";
        
        return $this->send($to_email, $to_name, $subject, $html_body, $text_body);
    }
    
    /**
     * Send password reset email
     */
    public function sendPasswordEmail($to_email, $to_name, $staff_id, $password) {
        $subject = "Your Inspectable Account Password";
        
        $html_body = $this->getPasswordEmailTemplate($to_name, $staff_id, $password);
        
        $text_body = "Hello {$to_name},\n\n"
                   . "Your account has been created by an administrator.\n\n"
                   . "Staff ID: {$staff_id}\n"
                   . "Temporary Password: {$password}\n\n"
                   . "You will be required to change this password upon your first login.\n\n"
                   . "Please keep this information secure.";
        
        return $this->send($to_email, $to_name, $subject, $html_body, $text_body);
    }
    
    /**
     * Verification email HTML template
     */
    private function getVerificationEmailTemplate($name, $verification_url) {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
        <h1 style='color: white; margin: 0;'>Verify Your Email</h1>
    </div>
    
    <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
        <p style='font-size: 16px;'>Hello <strong>{$name}</strong>,</p>
        
        <p style='font-size: 16px;'>Thank you for registering with Inspectable! Please verify your email address to complete your registration.</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='{$verification_url}' 
               style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; 
                      padding: 15px 30px; 
                      text-decoration: none; 
                      border-radius: 5px; 
                      display: inline-block; 
                      font-weight: bold;'>
                Verify Email Address
            </a>
        </div>
        
        <p style='font-size: 14px; color: #666;'>Or copy and paste this link into your browser:</p>
        <p style='font-size: 12px; color: #007bff; word-break: break-all;'>{$verification_url}</p>
        
        <p style='font-size: 14px; color: #666; margin-top: 30px;'>
            <strong>This link will expire in 24 hours.</strong>
        </p>
        
        <p style='font-size: 14px; color: #666;'>
            If you did not create this account, please ignore this email.
        </p>
    </div>
    
    <div style='text-align: center; margin-top: 20px; color: #999; font-size: 12px;'>
        <p>&copy; 2025 Inspectable. All rights reserved.</p>
    </div>
</body>
</html>";
    }
    
    /**
     * Welcome email HTML template
     */
    private function getWelcomeEmailTemplate($name, $staff_id) {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
        <h1 style='color: white; margin: 0;'>Welcome to Inspectable!</h1>
    </div>
    
    <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
        <p style='font-size: 16px;'>Hello <strong>{$name}</strong>,</p>
        
        <p style='font-size: 16px;'>Your email has been verified successfully! Your account is now active.</p>
        
        <div style='background: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <p style='margin: 0; font-size: 14px; color: #666;'>Your Staff ID</p>
            <p style='margin: 10px 0 0 0; font-size: 24px; font-weight: bold; color: #667eea;'>{$staff_id}</p>
        </div>
        
        <p style='font-size: 16px;'>You can now log in using your Staff ID and the password you created during registration.</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='http://localhost:5174/#/login' 
               style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; 
                      padding: 15px 30px; 
                      text-decoration: none; 
                      border-radius: 5px; 
                      display: inline-block; 
                      font-weight: bold;'>
                Log In Now
            </a>
        </div>
        
        <p style='font-size: 14px; color: #666;'>
            If you have any questions, please contact your system administrator.
        </p>
    </div>
    
    <div style='text-align: center; margin-top: 20px; color: #999; font-size: 12px;'>
        <p>&copy; 2025 Inspectable. All rights reserved.</p>
    </div>
</body>
</html>";
    }
    
    /**
     * Password email HTML template
     */
    private function getPasswordEmailTemplate($name, $staff_id, $password) {
        return "
<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
</head>
<body style='font-family: Arial, sans-serif; line-height: 1.6; color: #333; max-width: 600px; margin: 0 auto; padding: 20px;'>
    <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center; border-radius: 10px 10px 0 0;'>
        <h1 style='color: white; margin: 0;'>Your Account Details</h1>
    </div>
    
    <div style='background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px;'>
        <p style='font-size: 16px;'>Hello <strong>{$name}</strong>,</p>
        
        <p style='font-size: 16px;'>Your account has been created by an administrator. Here are your login credentials:</p>
        
        <div style='background: white; padding: 20px; border-radius: 5px; margin: 20px 0;'>
            <p style='margin: 0 0 10px 0; font-size: 14px; color: #666;'>Staff ID</p>
            <p style='margin: 0 0 20px 0; font-size: 20px; font-weight: bold; color: #667eea;'>{$staff_id}</p>
            
            <p style='margin: 0 0 10px 0; font-size: 14px; color: #666;'>Temporary Password</p>
            <p style='margin: 0; font-size: 20px; font-weight: bold; color: #764ba2; font-family: monospace;'>{$password}</p>
        </div>
        
        <div style='background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin: 20px 0;'>
            <p style='margin: 0; font-size: 14px; color: #856404;'>
                <strong>⚠️ Important:</strong> You will be required to change this password upon your first login.
            </p>
        </div>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='http://localhost:5174/#/login' 
               style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); 
                      color: white; 
                      padding: 15px 30px; 
                      text-decoration: none; 
                      border-radius: 5px; 
                      display: inline-block; 
                      font-weight: bold;'>
                Log In Now
            </a>
        </div>
        
        <p style='font-size: 14px; color: #666;'>
            Please keep this information secure and do not share it with anyone.
        </p>
    </div>
    
    <div style='text-align: center; margin-top: 20px; color: #999; font-size: 12px;'>
        <p>&copy; 2025 Inspectable. All rights reserved.</p>
    </div>
</body>
</html>";
    }
}
