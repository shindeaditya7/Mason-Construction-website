<?php
/**
 * Mason Construction Services Inc.
 * Update Contact Status / Notes Endpoint (Admin only)
 * POST /api/update-contact.php
 */

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/classes/Contact.php';

// Require admin authentication
requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed. Use POST.', 405);
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$id = isset($input['id']) ? (int)$input['id'] : 0;
if ($id <= 0) {
    sendError('A valid contact ID is required.', 400);
}

$contact = new Contact();
$result  = ['success' => false, 'message' => 'No action specified.'];

if (isset($input['status'])) {
    $result = $contact->updateStatus($id, $input['status']);
}

if (isset($input['admin_notes'])) {
    $result = $contact->addNote($id, $input['admin_notes']);
}

sendResponse($result, $result['success'] ? 200 : 422);
