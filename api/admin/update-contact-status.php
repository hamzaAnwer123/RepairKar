<?php
/**
 * Update a contact/support message's status through the workflow
 * new → in_progress → resolved. Requires role='admin', re-checked
 * here independently.
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$messageId = filter_var($input['message_id'] ?? null, FILTER_VALIDATE_INT);
$status = (string) ($input['status'] ?? '');

// Whitelist — never trust an arbitrary status string.
$allowedStatuses = ['new', 'in_progress', 'resolved'];

if (!$messageId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid message id']);
    exit;
}
if (!in_array($status, $allowedStatuses, true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid status']);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id FROM contact_messages WHERE id = :id");
$checkStmt->execute(['id' => $messageId]);

if (!$checkStmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Message not found']);
    exit;
}

$updateStmt = $pdo->prepare("UPDATE contact_messages SET status = :status WHERE id = :id");
$updateStmt->execute(['status' => $status, 'id' => $messageId]);

echo json_encode(['success' => true, 'status' => $status]);
