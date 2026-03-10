<?php
/**
 * AgriConnect – Application Configuration
 * =========================================
 * Central configuration file for database and app settings.
 * Change DB_HOST, DB_USER, DB_PASS, DB_NAME to match your server.
 */

// === Database Configuration ===
define('DB_HOST', 'localhost');       // MySQL host (usually localhost)
define('DB_USER', 'root');            // MySQL username
define('DB_PASS', '');                // MySQL password
define('DB_NAME', 'agriconnect');     // Database name

// === Application Settings ===
define('APP_NAME',    'AgriConnect');
define('APP_VERSION', '1.0.0');
define('APP_URL',     'http://localhost/Agriconnect'); // Change to your domain

// === File Upload Settings ===
define('UPLOAD_DIR',      __DIR__ . '/../uploads/');   // Upload directory path
define('MAX_FILE_SIZE',   5 * 1024 * 1024);            // 5MB max upload size
define('ALLOWED_TYPES',   ['image/jpeg', 'image/png', 'image/webp', 'image/gif']);

// === Error Reporting (set to 0 in production) ===
error_reporting(E_ALL);
ini_set('display_errors', 1);

// === Session Start ===
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// === Timezone ===
date_default_timezone_set('Asia/Kolkata');  // IST for India

// === CORS Headers (for API calls from JS) ===
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}
