<?php
/**
 * Delete one of the authenticated user's own reviews.
 * Ownership is verified through the review's booking, the stored photo
 * file is removed, and the mechanic's cached rating stats are recalculated.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
ensureReviewPhotoColumn($pdo);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$reviewId = filter_var($input['review_id'] ?? null, FILTER_VALIDATE_INT);

if (!$reviewId) {
    jsonResponse(['message' => 'Please provide a valid review ID.'], 400);
}

try {
    // Verify the review exists AND belongs to a booking of this user.
    $stmt = $pdo->prepare(
        "SELECT r.id, r.photo_path, b.mechanic_id
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE r.id = :rid AND b.user_id = :uid"
    );
    $stmt->execute(['rid' => $reviewId, 'uid' => $currentUserId]);
    $review = $stmt->fetch();

    if (!$review) {
        jsonResponse(['message' => 'Review not found or it does not belong to your account.'], 404);
    }

    $mechanicId = (int) $review['mechanic_id'];

    $delStmt = $pdo->prepare("DELETE FROM reviews WHERE id = :rid");
    $delStmt->execute(['rid' => $reviewId]);

    // Remove the stored photo file — ONLY files inside uploads/reviews.
    if (!empty($review['photo_path'])) {
        $oldReal = realpath(__DIR__ . '/' . $review['photo_path']);
        $dirReal = realpath(__DIR__ . '/../assets/uploads/reviews/');
        if ($oldReal && $dirReal && strpos($oldReal, $dirReal) === 0 && is_file($oldReal)) {
            @unlink($oldReal);
        }
    }

    // Recalculate the mechanic's cached rating stats (same as submit-review.php).
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

    jsonResponse([
        'success'      => true,
        'message'      => 'Your review has been deleted.',
        'avg_rating'   => (float) ($calc['avg_r'] ?? 0),
        'review_count' => (int) ($calc['cnt_r'] ?? 0),
    ]);
} catch (PDOException $e) {
    error_log('delete-review.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Could not delete the review. Please try again.'], 500);
}
