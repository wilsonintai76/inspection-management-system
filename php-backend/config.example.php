<?php
// Copy this file to config.php and set your local values.
// For security, prefer setting sensitive values via environment variables.

// Database configuration (defaults shown for local WAMP)
define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'inspectable');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Allowed origin for CORS during development
// If you serve Vue dev on a different port, update this
define('ALLOWED_ORIGIN', getenv('ALLOWED_ORIGIN') ?: 'http://localhost:5176');

// Brevo SMTP Configuration (leave blank if using API only)
define('BREVO_SMTP_USERNAME', getenv('BREVO_SMTP_USERNAME') ?: '');
define('BREVO_SMTP_PASSWORD', getenv('BREVO_SMTP_PASSWORD') ?: '');
define('BREVO_FROM_EMAIL', getenv('BREVO_FROM_EMAIL') ?: 'noreply@yourdomain.com');
define('BREVO_FROM_NAME', getenv('BREVO_FROM_NAME') ?: 'Inspectable');

// Brevo HTTP API Key (preferred method)
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');

// Application URL (used for deep links in emails)
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:5176');
