<?php
/**
 * Small authenticated WebRTC signaling relay. Media stays peer-to-peer;
 * this endpoint only transports offers, answers, and ICE candidates.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireAuth();
$currentUserId = (int) $_SESSION['user_id'];

$pdo->exec("CREATE TABLE IF NOT EXISTS call_signals (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    signal_type VARCHAR(20) NOT NULL,
    payload JSON NOT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_call_signals (booking_id, id),
    CONSTRAINT fk_call_signal_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
    $signalType = $input['signal_type'] ?? '';
    $payload = $input['payload'] ?? null;
} else {
    $signalType = '';
    $payload = null;
}

if (!$bookingId) jsonResponse(['message' => 'Invalid booking.'], 400);

$participantStmt = $pdo->prepare(
    "SELECT b.user_id, m.user_id AS mechanic_user_id
     FROM bookings b JOIN mechanics m ON m.id = b.mechanic_id WHERE b.id = :id"
);
$participantStmt->execute(['id' => $bookingId]);
$booking = $participantStmt->fetch();
if (!$booking || !in_array($currentUserId, [(int) $booking['user_id'], (int) $booking['mechanic_user_id']], true)) {
    jsonResponse(['message' => 'Conversation not found.'], 404);
}

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $after = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT) ?: 0;
    $stmt = $pdo->prepare(
        'SELECT id, sender_id, signal_type, payload FROM call_signals
         WHERE booking_id = :booking_id AND sender_id <> :sender_id AND id > :after
         ORDER BY id ASC LIMIT 50'
    );
    $stmt->execute(['booking_id' => $bookingId, 'sender_id' => $currentUserId, 'after' => $after]);
    $signals = array_map(function ($row) {
        return ['id' => (int) $row['id'], 'type' => $row['signal_type'], 'payload' => json_decode($row['payload'], true)];
    }, $stmt->fetchAll());
    jsonResponse(['signals' => $signals]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !in_array($signalType, ['offer', 'answer', 'ice', 'hangup'], true) || !is_array($payload)) {
    jsonResponse(['message' => 'Invalid signaling request.'], 400);
}

$stmt = $pdo->prepare('INSERT INTO call_signals (booking_id, sender_id, signal_type, payload) VALUES (:booking_id, :sender_id, :signal_type, :payload)');
$stmt->execute([
    'booking_id' => $bookingId,
    'sender_id' => $currentUserId,
    'signal_type' => $signalType,
    'payload' => json_encode($payload),
]);
jsonResponse(['success' => true, 'id' => (int) $pdo->lastInsertId()], 201);
