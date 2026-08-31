<?php
/**
 * Permanently delete a user account (and everything owned by it).
 * Requires role='admin', re-checked here independently.
 * Admin accounts and the acting admin themselves can never be deleted.
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
$userId = filter_var($input['user_id'] ?? null, FILTER_VALIDATE_INT);

if (!$userId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid user id']);
    exit;
}

if ($userId === (int) $_SESSION['user_id']) {
    http_response_code(400);
    echo json_encode(['message' => 'You cannot delete the account you are logged in with.']);
    exit;
}

$userStmt = $pdo->prepare("SELECT id, role, photo_path FROM users WHERE id = :id");
$userStmt->execute(['id' => $userId]);
$user = $userStmt->fetch();

if (!$user) {
    http_response_code(404);
    echo json_encode(['message' => 'User not found']);
    exit;
}

if ($user['role'] === 'admin') {
    http_response_code(400);
    echo json_encode(['message' => 'Admin accounts cannot be deleted.']);
    exit;
}

try {
    // Collect owned uploads BEFORE the cascade delete removes the rows.
    $filesToDelete = [$user['photo_path']];

    // Mechanic-owned files: CNIC, shop photos, gig photos, reviews on the shop.
    $mechanicStmt = $pdo->prepare("SELECT id, cnic_doc_path, shop_photo_path FROM mechanics WHERE user_id = :uid");
    $mechanicStmt->execute(['uid' => $userId]);

    foreach ($mechanicStmt->fetchAll() as $mechanic) {
        $filesToDelete[] = $mechanic['cnic_doc_path'];
        $filesToDelete[] = $mechanic['shop_photo_path'];

        $gigStmt = $pdo->prepare("SELECT photo_path FROM gigs WHERE mechanic_id = :mid");
        $gigStmt->execute(['mid' => $mechanic['id']]);
        foreach ($gigStmt->fetchAll() as $gig) {
            $filesToDelete[] = $gig['photo_path'];
        }

        $reviewStmt = $pdo->prepare(
            "SELECT r.photo_path
             FROM reviews r
             JOIN bookings b ON b.id = r.booking_id
             WHERE b.mechanic_id = :mid"
        );
        $reviewStmt->execute(['mid' => $mechanic['id']]);
        foreach ($reviewStmt->fetchAll() as $review) {
            $filesToDelete[] = $review['photo_path'];
        }
    }

    // Reviews written BY this user (photos attached to their bookings).
    $ownReviewStmt = $pdo->prepare(
        "SELECT r.photo_path
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE b.user_id = :uid"
    );
    $ownReviewStmt->execute(['uid' => $userId]);
    foreach ($ownReviewStmt->fetchAll() as $review) {
        $filesToDelete[] = $review['photo_path'];
    }

    // Chat uploads sent by this user (content holds the stored path).
    $chatStmt = $pdo->prepare(
        "SELECT content FROM messages
         WHERE sender_id = :uid AND message_type IN ('image', 'video', 'document', 'audio')"
    );
    $chatStmt->execute(['uid' => $userId]);
    foreach ($chatStmt->fetchAll() as $message) {
        $filesToDelete[] = $message['content'];
    }

    $deleteStmt = $pdo->prepare("DELETE FROM users WHERE id = :id");
    $deleteStmt->execute(['id' => $userId]);

    // Rows are gone — now the files (uploads/profiles, /shops, /gigs,
    // /reviews and /chat).
    foreach ($filesToDelete as $storedPath) {
        deleteStoredUpload($storedPath);
    }

    echo json_encode(['success' => true]);
} catch (PDOException $e) {
    // 23000 = integrity constraint violation. A mechanic whose shop has
    // bookings cannot be removed: bookings.mechanic_id is ON DELETE
    // RESTRICT by design so booking history stays intact.
    if ($e->getCode() === '23000') {
        http_response_code(409);
        echo json_encode(['message' => 'This account is a mechanic with bookings on record. Those bookings must be removed first, so the account cannot be deleted.']);
        exit;
    }
    error_log('admin/delete-user.php failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Could not delete the account. Please try again.']);
}
