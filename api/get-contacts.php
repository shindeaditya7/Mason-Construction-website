<?php
/**
 * Mason Construction Services Inc.
 * Get Contact Submissions Endpoint (Admin only)
 * GET /api/get-contacts.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Contact.php';

// Require admin authentication
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    sendError('Method not allowed. Use GET.', 405);
}

$filters = [
    'status' => $_GET['status'] ?? '',
    'search' => $_GET['search'] ?? '',
    'limit'  => isset($_GET['limit']) ? min((int)$_GET['limit'], 100) : 50,
    'offset' => isset($_GET['offset']) ? max((int)$_GET['offset'], 0) : 0,
];

$contact = new Contact();
$submissions = $contact->getAll($filters);
$total       = $contact->getCount($filters);

sendResponse([
    'success' => true,
    'data'    => $submissions,
    'total'   => $total,
    'limit'   => $filters['limit'],
    'offset'  => $filters['offset'],
]);
