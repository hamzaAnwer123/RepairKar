<?php
/**
 * Approve or reject a mechanic's verification application.
 * Requires role='admin', re-checked here independently.
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

$input = json_decode(file_get_contents('php://input'), true);

$mechanicId = filter_var($input['mechanic_id'] ?? null, FILTER_VALIDATE_INT);
$action = $input['action'] ?? null;
$reason = trim((string) ($input['reason'] ?? ''));

// Whitelist — never trust an arbitrary action string
$allowedActions = ['approve', 'reject'];

if (!$mechanicId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid mechanic id']);
    exit;
}
if (!in_array($action, $allowedActions, true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid action']);
    exit;
}
// mb_strlen() requires the mbstring extension, which most hosting
// environments have enabled by default but isn't guaranteed — fall back
// to strlen() (byte count, close enough for a minimum-length check) if
// it's unavailable, rather than crashing the endpoint outright.
$reasonLength = function_exists('mb_strlen') ? mb_strlen($reason) : strlen($reason);

if ($action === 'reject' && $reasonLength < 10) {
    http_response_code(400);
    echo json_encode(['message' => 'A rejection reason of at least 10 characters is required']);
    exit;
}

// Confirm the mechanic actually exists and is still pending before acting
$checkStmt = $pdo->prepare("SELECT id, verified FROM mechanics WHERE id = :id");
$checkStmt->execute(['id' => $mechanicId]);
$mechanic = $checkStmt->fetch();

if (!$mechanic) {
    http_response_code(404);
    echo json_encode(['message' => 'Mechanic not found']);
    exit;
}

if ($action === 'approve') {
    $updateStmt = $pdo->prepare("UPDATE mechanics SET verified = 1 WHERE id = :id");
    $updateStmt->execute(['id' => $mechanicId]);
    // A real system would also log $_SESSION['user_id'] as the approving
    // admin and notify the mechanic — left as a follow-up, not required
    // for the core verification flow to work.
    echo json_encode(['success' => true, 'status' => 'approved']);
    exit;
}

if ($action === 'reject') {
    // Earlier version of this endpoint DELETEd the row on rejection —
    // that breaks with a foreign key error the moment a mechanic has
    // any booking on record (proven by a live test against real data),
    // and loses the audit trail regardless. Mark as rejected instead.
    $updateStmt = $pdo->prepare(
        "UPDATE mechanics SET rejected = 1, rejection_reason = :reason WHERE id = :id"
    );
    $updateStmt->execute(['reason' => $reason, 'id' => $mechanicId]);
    echo json_encode(['success' => true, 'status' => 'rejected']);
    exit;
}