<?php
/**
 * Submit or update a review for a completed booking.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
ensureReviewPhotoColumn($pdo);

// Accept both multipart/form-data (when a photo is attached) and JSON.
$isMultipart = strpos((string) ($_SERVER['CONTENT_TYPE'] ?? ''), 'multipart/form-data') === 0;
if ($isMultipart) {
    $input = $_POST;
} else {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
}

$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
$rating    = filter_var($input['rating'] ?? null, FILTER_VALIDATE_INT);
$comment   = isset($input['comment']) ? trim(strip_tags((string)$input['comment'])) : null;

if (!$bookingId || !$rating || $rating < 1 || $rating > 5) {
    jsonResponse(['message' => 'Please provide a valid booking ID and rating between 1 and 5.'], 400);
}

if ($comment !== null && mb_strlen($comment) > 1000) {
    jsonResponse(['message' => 'Review comment cannot exceed 1000 characters.'], 400);
}

// ---- Optional photo upload (multipart only) ----
// Mirrors update-mechanic-profile.php: finfo-validated image, random
// filename, 5MB cap. A null $photoPath means "keep any existing photo".
$photoPath = null;
if ($isMultipart && !empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];

    // Validate the REAL content type — never trust the client-supplied mime.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

    if (!isset($allowedMimes[$realMime])) {
        jsonResponse(['message' => 'Photo must be a JPG, PNG, or WebP image.'], 400);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(['message' => 'Photo must be smaller than 5MB.'], 400);
    }
    // Extra guard: reject files that are not decodable images at all.
    if (@getimagesize($file['tmp_name']) === false) {
        jsonResponse(['message' => 'That file does not look like a valid image.'], 400);
    }

    $uploadDir = __DIR__ . '/../assets/uploads/reviews/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$realMime];
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        jsonResponse(['message' => 'Could not save the uploaded photo. Please try again.'], 500);
    }

    $photoPath = '../assets/uploads/reviews/' . $filename;
}

try {
    // 1. Verify booking exists, belongs to this user, and is completed
    $stmt = $pdo->prepare(
        "SELECT id, user_id, mechanic_id, status
         FROM bookings
         WHERE id = :bid"
    );
    $stmt->execute(['bid' => $bookingId]);
    $booking = $stmt->fetch();

    if (!$booking) {
        jsonResponse(['message' => 'Booking not found.'], 404);
    }

    if ((int)$booking['user_id'] !== $currentUserId) {
        jsonResponse(['message' => 'You can only review bookings made from your account.'], 403);
    }

    if ($booking['status'] !== 'completed') {
        jsonResponse(['message' => 'Reviews can only be submitted for completed jobs.'], 400);
    }

    $mechanicId = (int) $booking['mechanic_id'];

    // Keep the previous photo so it can be cleaned up if replaced.
    $oldStmt = $pdo->prepare("SELECT photo_path FROM reviews WHERE booking_id = :bid");
    $oldStmt->execute(['bid' => $bookingId]);
    $oldPhotoPath = (string) ($oldStmt->fetchColumn() ?: '');

    // 2. Insert or update review
    if ($photoPath !== null) {
        $revStmt = $pdo->prepare(
            "INSERT INTO reviews (booking_id, rating, comment, photo_path, created_at)
             VALUES (:bid, :rating, :comment, :photo, NOW())
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), photo_path = VALUES(photo_path), created_at = NOW()"
        );
        $revStmt->execute([
            'bid'     => $bookingId,
            'rating'  => $rating,
            'comment' => $comment ?: null,
            'photo'   => $photoPath,
        ]);
    } else {
        $revStmt = $pdo->prepare(
            "INSERT INTO reviews (booking_id, rating, comment, created_at)
             VALUES (:bid, :rating, :comment, NOW())
             ON DUPLICATE KEY UPDATE rating = VALUES(rating), comment = VALUES(comment), created_at = NOW()"
        );
        $revStmt->execute([
            'bid'     => $bookingId,
            'rating'  => $rating,
            'comment' => $comment ?: null,
        ]);
    }

    // Delete the replaced photo file — ONLY files inside uploads/reviews.
    if ($photoPath !== null && $oldPhotoPath !== '' && $oldPhotoPath !== $photoPath) {
        $oldReal = realpath(__DIR__ . '/' . $oldPhotoPath);
        $dirReal = realpath(__DIR__ . '/../assets/uploads/reviews/');
        if ($oldReal && $dirReal && strpos($oldReal, $dirReal) === 0 && is_file($oldReal)) {
            @unlink($oldReal);
        }
    }

    // 3. Recalculate average rating & review count for the mechanic and update mechanics table
    $calcStmt = $pdo->prepare(
        "SELECT ROUND(AVG(r.rating), 1) AS avg_r, COUNT(r.id) AS cnt_r
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE b.mechanic_id = :mid"
    );
    $calcStmt->execute(['mid' => $mechanicId]);
    $calc = $calcStmt->fetch();

    $newAvg = (float) ($calc['avg_r'] ?? $rating);
    $newCount = (int) ($calc['cnt_r'] ?? 1);

    $updStmt = $pdo->prepare(
        "UPDATE mechanics
         SET avg_rating = :avg_r, review_count = :cnt_r
         WHERE id = :mid"
    );
    $updStmt->execute([
        'avg_r' => $newAvg,
        'cnt_r' => $newCount,
        'mid'   => $mechanicId,
    ]);

    jsonResponse([
        'success'      => true,
        'message'      => 'Thank you! Your review has been submitted successfully.',
        'avg_rating'   => $newAvg,
        'review_count' => $newCount,
    ]);
} catch (Exception $e) {
    error_log('submit-review.php failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Could not submit review. Please try again later.'], 500);
}
?>
