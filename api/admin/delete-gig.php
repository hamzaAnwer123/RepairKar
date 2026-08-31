<?php
/**
 * Permanently delete a service listing (moderation action).
 * Bookings referencing the gig keep their history — the FK sets
 * gig_id to NULL. Requires role='admin', re-checked here independently.
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
require_once __DIR__ . '/../../includes/functions.php';

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$gigId = filter_var($input['gig_id'] ?? null, FILTER_VALIDATE_INT);

if (!$gigId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid gig id']);
    exit;
}

try {
    $checkStmt = $pdo->prepare("SELECT id, photo_path FROM gigs WHERE id = :id");
    $checkStmt->execute(['id' => $gigId]);
    $gig = $checkStmt->fetch();

    if (!$gig) {
        http_response_code(404);
        echo json_encode(['message' => 'Service listing not found']);
        exit;
    }

    $delStmt = $pdo->prepare("DELETE FROM gigs WHERE id = :id");
    $delStmt->execute(['id' => $gigId]);

    if (!empty($gig['photo_path'])) {
        deleteStoredUpload($gig['photo_path']);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    error_log('admin/delete-gig.php failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Could not delete the listing. Please try again.']);
}
