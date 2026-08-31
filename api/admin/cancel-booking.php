<?php
/**
 * Cancel a booking on the user's behalf (support action).
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
$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);

if (!$bookingId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid booking id']);
    exit;
}

$checkStmt = $pdo->prepare("SELECT id, status FROM bookings WHERE id = :id");
$checkStmt->execute(['id' => $bookingId]);
$booking = $checkStmt->fetch();

if (!$booking) {
    http_response_code(404);
    echo json_encode(['message' => 'Booking not found']);
    exit;
}

// Only bookings still in progress can be cancelled — completed jobs
// and already-cancelled ones are left untouched.
$cancellable = ['pending', 'accepted', 'en_route'];
if (!in_array($booking['status'], $cancellable, true)) {
    http_response_code(400);
    echo json_encode(['message' => 'Only active bookings (pending, accepted or en route) can be cancelled.']);
    exit;
}

$updateStmt = $pdo->prepare("UPDATE bookings SET status = 'cancelled' WHERE id = :id");
$updateStmt->execute(['id' => $bookingId]);

echo json_encode(['success' => true, 'status' => 'cancelled']);
