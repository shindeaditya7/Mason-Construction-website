<?php
/**
 * Mason Construction Services Inc.
 * Admin Login Endpoint
 * POST /api/login.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Admin.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$username = $input['username'] ?? '';
$password = $input['password'] ?? '';

$admin  = new Admin();
$result = $admin->login($username, $password);

sendResponse($result, $result['success'] ? 200 : 401);
