<?php
/**
 * Toggle a service listing's active flag (moderation action — lets an
 * admin hide or restore a gig without deleting it).
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

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$gigId = filter_var($input['gig_id'] ?? null, FILTER_VALIDATE_INT);

if (!$gigId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid gig id']);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id, active FROM gigs WHERE id = :id");
$checkStmt->execute(['id' => $gigId]);
$gig = $checkStmt->fetch();

if (!$gig) {
    http_response_code(404);
    echo json_encode(['message' => 'Service listing not found']);
    exit;
}

$newActive = ((int) $gig['active'] === 1) ? 0 : 1;

$updateStmt = $pdo->prepare("UPDATE gigs SET active = :active WHERE id = :id");
$updateStmt->execute(['active' => $newActive, 'id' => $gigId]);

echo json_encode([
    'success' => true,
    'active'  => (bool) $newActive,
]);
