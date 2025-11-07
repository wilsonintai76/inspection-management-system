<?php
/**
 * Test Brevo SMTP Email Sending
 * Run this to verify your Brevo configuration is working
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/src/brevo_mailer.php';

echo "=== Brevo SMTP Test ===\n\n";

// Display configuration (hide password)
echo "SMTP Server: smtp-relay.brevo.com:587\n";
echo "Username: " . BREVO_SMTP_USERNAME . "\n";
echo "Password: " . str_repeat('*', strlen(BREVO_SMTP_PASSWORD)) . "\n";
echo "From Email: " . BREVO_FROM_EMAIL . "\n";
echo "From Name: " . BREVO_FROM_NAME . "\n";
echo "Brevo API Key: " . (defined('BREVO_API_KEY') && BREVO_API_KEY ? '(set)' : '(not set)') . "\n\n";

// Prompt for test email
echo "Enter your email address to receive a test message: ";
$testEmail = trim(fgets(STDIN));

if (empty($testEmail) || !filter_var($testEmail, FILTER_VALIDATE_EMAIL)) {
    die("Invalid email address\n");
}

echo "\nSending test email to: $testEmail\n";

try {
    $mailer = new BrevoMailer(
        BREVO_SMTP_USERNAME,
        BREVO_SMTP_PASSWORD,
        BREVO_FROM_EMAIL,
        BREVO_FROM_NAME
    );
    
    $subject = "Test Email from Inspectable";
    
    $htmlBody = "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
    </head>
    <body style='font-family: Arial, sans-serif; padding: 20px;'>
        <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 20px; text-align: center; border-radius: 10px 10px 0 0;'>
            <h1 style='color: white; margin: 0;'>Test Email</h1>
        </div>
        <div style='background: #f9f9f9; padding: 20px; border-radius: 0 0 10px 10px;'>
            <p style='font-size: 16px;'>Hello!</p>
            <p style='font-size: 16px;'>This is a test email from Inspectable to verify your Brevo SMTP configuration.</p>
            <p style='font-size: 16px; margin-top: 30px;'><strong>Configuration Details:</strong></p>
            <ul style='font-size: 14px; color: #666;'>
                <li>SMTP Server: smtp-relay.brevo.com:587</li>
                <li>Username: " . BREVO_SMTP_USERNAME . "</li>
                <li>From: " . BREVO_FROM_EMAIL . "</li>
                <li>Sent at: " . date('Y-m-d H:i:s') . "</li>
            </ul>
            <p style='font-size: 16px; color: #28a745; font-weight: bold; margin-top: 30px;'>✅ Brevo SMTP is working correctly!</p>
        </div>
    </body>
    </html>";
    
    $textBody = "Hello!\n\n"
              . "This is a test email from Inspectable to verify your Brevo SMTP configuration.\n\n"
              . "Configuration Details:\n"
              . "- SMTP Server: smtp-relay.brevo.com:587\n"
              . "- Username: " . BREVO_SMTP_USERNAME . "\n"
              . "- From: " . BREVO_FROM_EMAIL . "\n"
              . "- Sent at: " . date('Y-m-d H:i:s') . "\n\n"
              . "✅ Brevo SMTP is working correctly!";
    
    $success = $mailer->send($testEmail, 'Test User', $subject, $htmlBody, $textBody);
    
    if ($success) {
        echo "\n✅ SUCCESS! Test email sent successfully.\n";
        echo "Please check your inbox at: $testEmail\n";
        echo "Note: If you don't see it, check your spam folder.\n";
    } else {
        echo "\n❌ FAILED! Email could not be sent.\n";
        echo "Check the error log for details.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ ERROR: " . $e->getMessage() . "\n";
}

echo "\n=== Test Complete ===\n";
