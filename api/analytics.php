<?php
/**
 * Mason Construction Services Inc.
 * Analytics Data Endpoint (Admin only)
 * GET /api/analytics.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Contact.php';

// Require admin authentication
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', 405);
}

$contact   = new Contact();
$analytics = $contact->getAnalytics();

sendResponse([
    'success' => true,
    'data'    => $analytics,
]);
