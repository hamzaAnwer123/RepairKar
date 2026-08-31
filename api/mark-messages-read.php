<?php
/**
 * Mark the other participant's messages as read for one conversation.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();
ensureMessageReadColumn($pdo);

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
$currentUserId = (int) $_SESSION['user_id'];

if (!$bookingId) {
    jsonResponse(['message' => 'Invalid request.'], 400);
}

$bookingStmt = $pdo->prepare(
    "SELECT b.user_id, m.user_id AS mechanic_user_id
     FROM bookings b
     JOIN mechanics m ON m.id = b.mechanic_id
     WHERE b.id = :id"
);
$bookingStmt->execute(['id' => $bookingId]);
$booking = $bookingStmt->fetch();

if (!$booking) {
    jsonResponse(['message' => 'Conversation not found.'], 404);
}

if ($currentUserId !== (int) $booking['user_id'] && $currentUserId !== (int) $booking['mechanic_user_id']) {
    jsonResponse(['message' => 'You do not have permission to update this conversation.'], 403);
}

$updateStmt = $pdo->prepare(
    "UPDATE messages
     SET read_at = NOW()
     WHERE booking_id = :booking_id
       AND sender_id <> :sender_id
       AND read_at IS NULL"
);
$updateStmt->execute([
    'booking_id' => $bookingId,
    'sender_id' => $currentUserId,
]);

jsonResponse(['success' => true, 'marked_count' => $updateStmt->rowCount()]);
