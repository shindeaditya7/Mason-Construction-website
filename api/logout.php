<?php
/**
 * Mason Construction Services Inc.
 * Admin Logout Endpoint
 * POST /api/logout.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Admin.php';

$admin  = new Admin();
$result = $admin->logout();

sendResponse($result);
