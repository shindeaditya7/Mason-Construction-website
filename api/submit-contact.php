<?php
/**
 * Mason Construction Services Inc.
 * Contact Form Submission Endpoint
 * POST /api/submit-contact.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Contact.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', 405);
}

// Accept JSON body or form-encoded body
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

if (empty($input)) {
    sendError('No data received.', 400);
}

$contact = new Contact();
$result  = $contact->submit($input);

sendResponse($result, $result['success'] ? 200 : 422);
