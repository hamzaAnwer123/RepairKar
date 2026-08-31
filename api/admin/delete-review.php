<?php
/**
 * Delete any review (moderation action). Mirrors api/delete-review.php:
 * the stored photo is removed and the mechanic's cached rating stats
 * are recalculated. Requires role='admin', re-checked here independently.
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
$reviewId = filter_var($input['review_id'] ?? null, FILTER_VALIDATE_INT);

if (!$reviewId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid review id']);
    exit;
}

try {
    $stmt = $pdo->prepare(
        "SELECT r.id, r.photo_path, b.mechanic_id
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE r.id = :rid"
    );
    $stmt->execute(['rid' => $reviewId]);
    $review = $stmt->fetch();

    if (!$review) {
        http_response_code(404);
        echo json_encode(['message' => 'Review not found']);
        exit;
    }

    $mechanicId = (int) $review['mechanic_id'];

    $delStmt = $pdo->prepare("DELETE FROM reviews WHERE id = :rid");
    $delStmt->execute(['rid' => $reviewId]);

    // Remove the stored photo file (deleteStoredUpload only unlinks
    // files genuinely inside assets/uploads).
    if (!empty($review['photo_path'])) {
        deleteStoredUpload($review['photo_path']);
    }

    // Recalculate the mechanic's cached rating stats (same as
    // api/delete-review.php / api/submit-review.php).
    $calcStmt = $pdo->prepare(
        "SELECT ROUND(AVG(r.rating), 1) AS avg_r, COUNT(r.id) AS cnt_r
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE b.mechanic_id = :mid"
    );
    $calcStmt->execute(['mid' => $mechanicId]);
    $calc = $calcStmt->fetch();

    $updStmt = $pdo->prepare(
        "UPDATE mechanics
         SET avg_rating = :avg_r, review_count = :cnt_r
         WHERE id = :mid"
    );
    $updStmt->execute([
        'avg_r' => (float) ($calc['avg_r'] ?? 0),
        'cnt_r' => (int) ($calc['cnt_r'] ?? 0),
        'mid'   => $mechanicId,
    ]);

    echo json_encode([
        'success'      => true,
        'avg_rating'   => (float) ($calc['avg_r'] ?? 0),
        'review_count' => (int) ($calc['cnt_r'] ?? 0),
    ]);
} catch (PDOException $e) {
    error_log('admin/delete-review.php failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Could not delete the review. Please try again.']);
}
