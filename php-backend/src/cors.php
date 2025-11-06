<?php
require_once __DIR__ . '/../config.php';

header('Content-Type: application/json');

if (isset($_SERVER['HTTP_ORIGIN'])) {
    // For local dev, restrict to configured origin
    header('Access-Control-Allow-Origin: ' . ALLOWED_ORIGIN);
    header('Vary: Origin');
}

header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}
