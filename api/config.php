<?php
/**
 * Mason Construction Services Inc.
 * API Configuration File
 * Database credentials for Bluehost shared hosting
 */

// Database configuration
// Bluehost shared hosting does not support .env files natively.
// For added security, consider setting these via cPanel's PHP config or
// a non-web-accessible include file outside the public_html directory.
define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
define('DB_NAME', getenv('DB_NAME') ?: 'thematg5_mason_db');
define('DB_USER', getenv('DB_USER') ?: 'thematg5_Jitesh');
define('DB_PASS', getenv('DB_PASS') ?: 'Jitesh@16!');
define('DB_CHARSET', 'utf8mb4');

// Application settings
define('APP_NAME', 'Mason Construction Services Inc.');
define('ADMIN_EMAIL', 'mason@themasonconstruction.com');
define('SESSION_TIMEOUT', 3600); // 1 hour in seconds

// CORS headers - allow requests from the same domain
$allowed_origins = [
    'https://themasonconstruction.com',
    'https://www.themasonconstruction.com',
    'http://localhost',
    'http://127.0.0.1',
];

$origin = isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '';
if (in_array($origin, $allowed_origins)) {
    header('Access-Control-Allow-Origin: ' . $origin);
} elseif (empty($origin)) {
    // Same-origin request (no Origin header) – no CORS header needed
} else {
    // Unknown origin – deny cross-origin access
    http_response_code(403);
    exit(json_encode(['success' => false, 'message' => 'Cross-origin request denied.']));
}

header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json; charset=utf-8');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Error reporting (disable in production - log errors instead)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Ensure logs directory exists
if (!is_dir(__DIR__ . '/logs')) {
    mkdir(__DIR__ . '/logs', 0755, true);
}

/**
 * Send a JSON response and exit
 */
function sendResponse($data, $status_code = 200) {
    http_response_code($status_code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit();
}

/**
 * Send an error JSON response and exit
 */
function sendError($message, $status_code = 400) {
    sendResponse(['success' => false, 'message' => $message], $status_code);
}

/**
 * Sanitize input string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Check if admin is authenticated
 */
function requireAuth() {
    if (!isset($_SESSION['admin_id']) || !isset($_SESSION['admin_logged_in'])) {
        sendError('Unauthorized. Please log in.', 401);
    }
    // Check session timeout
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > SESSION_TIMEOUT)) {
        session_destroy();
        sendError('Session expired. Please log in again.', 401);
    }
    $_SESSION['last_activity'] = time();
}
