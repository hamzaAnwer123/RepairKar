<?php
/**
 * Delete a contact/support message. Requires role='admin', re-checked
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

if (!$messageId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid message id']);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id FROM contact_messages WHERE id = :id");
$checkStmt->execute(['id' => $messageId]);

if (!$checkStmt->fetch()) {
    http_response_code(404);
    echo json_encode(['message' => 'Message not found']);
    exit;
}

$delStmt = $pdo->prepare("DELETE FROM contact_messages WHERE id = :id");
$delStmt->execute(['id' => $messageId]);

echo json_encode(['success' => true]);
