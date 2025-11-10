<?php
// Local development config (WAMP)
// NOTE: Sensitive values are read from environment variables.
// Configure these in your web server environment or a local, untracked file.

define('DB_HOST', getenv('DB_HOST') ?: '127.0.0.1');
define('DB_NAME', getenv('DB_NAME') ?: 'inspectable');
define('DB_USER', getenv('DB_USER') ?: 'root');
define('DB_PASS', getenv('DB_PASS') ?: '');

// Allowed origin for CORS during development (Vite dev server)
define('ALLOWED_ORIGIN', getenv('ALLOWED_ORIGIN') ?: 'http://localhost:5176');

// Brevo SMTP Configuration
// SMTP Server: smtp-relay.brevo.com:587
define('BREVO_SMTP_USERNAME', getenv('BREVO_SMTP_USERNAME') ?: '');
define('BREVO_SMTP_PASSWORD', getenv('BREVO_SMTP_PASSWORD') ?: '');
define('BREVO_FROM_EMAIL', getenv('BREVO_FROM_EMAIL') ?: 'noreply@example.com');
define('BREVO_FROM_NAME', getenv('BREVO_FROM_NAME') ?: 'Inspectable');

// Brevo HTTP API (preferred)
define('BREVO_API_KEY', getenv('BREVO_API_KEY') ?: '');

// Application URL (used for links in emails)
define('APP_URL', getenv('APP_URL') ?: 'http://localhost:5176');

// Default password for bulk-imported users
// Users must change this on first login (must_change_password = 1)
define('DEFAULT_IMPORT_PASSWORD', getenv('DEFAULT_IMPORT_PASSWORD') ?: 'PolikuInspect@2025');
