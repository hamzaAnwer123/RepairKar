<?php
/**
 * Update a booking's status. Used for: user cancelling, mechanic
 * accepting/declining, mechanic marking en_route/completed.
 *
 * CRITICAL: verifies the logged-in user is actually a participant in
 * THIS booking (either the user or the mechanic's own user account)
 * before allowing any change — never trust booking_id alone as proof
 * of access, since any logged-in user could otherwise change any
 * booking by guessing IDs.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
$newStatus = $input['status'] ?? null;

$allowedStatuses = ['pending', 'accepted', 'en_route', 'completed', 'cancelled'];
if (!$bookingId || !in_array($newStatus, $allowedStatuses, true)) {
    jsonResponse(['message' => 'Invalid request.'], 400);
}

// ---- Ownership check ----
$stmt = $pdo->prepare(
    "SELECT b.id, b.status, b.user_id, m.user_id AS mechanic_user_id
     FROM bookings b
     JOIN mechanics m ON m.id = b.mechanic_id
     WHERE b.id = :id"
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(['message' => 'Booking not found.'], 404);
}

$currentUserId = (int) $_SESSION['user_id'];
$isOwnerUser = $currentUserId === (int) $booking['user_id'];
$isOwnerMechanic = $currentUserId === (int) $booking['mechanic_user_id'];

if (!$isOwnerUser && !$isOwnerMechanic) {
    jsonResponse(['message' => 'You do not have permission to modify this booking.'], 403);
}

// ---- Valid transitions (prevents e.g. jumping straight from pending to completed) ----
$validTransitions = [
    'pending'   => ['accepted', 'cancelled'],
    'accepted'  => ['en_route', 'completed', 'cancelled'],
    'en_route'  => ['completed', 'cancelled'],
    'completed' => [],
    'cancelled' => [],
];
if (!in_array($newStatus, $validTransitions[$booking['status']] ?? [], true)) {
    jsonResponse(['message' => 'This status change is not allowed from the current state.'], 400);
}

// ---- Role restriction: only the mechanic can accept/mark en_route/complete;
// either party can cancel ----
$mechanicOnlyStatuses = ['accepted', 'en_route', 'completed'];
if (in_array($newStatus, $mechanicOnlyStatuses, true) && !$isOwnerMechanic) {
    jsonResponse(['message' => 'Only the mechanic can perform this action.'], 403);
}

$updateStmt = $pdo->prepare("UPDATE bookings SET status = :status WHERE id = :id");
$updateStmt->execute(['status' => $newStatus, 'id' => $bookingId]);

jsonResponse(['success' => true, 'status' => $newStatus]);