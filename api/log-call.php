<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireMethod('POST');
requireAuth();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$bookingId = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
$callId = filter_var($input['call_id'] ?? null, FILTER_VALIDATE_INT);
$callType = $input['call_type'] ?? null;
$status = $input['status'] ?? null;
if (!$bookingId || !in_array($callType, ['voice', 'video'], true) || !in_array($status, ['ringing', 'accepted', 'declined', 'missed', 'ended'], true)) jsonResponse(['message' => 'Invalid call data.'], 400);
$userId = (int) $_SESSION['user_id'];
$check = $pdo->prepare("SELECT b.id FROM bookings b JOIN mechanics m ON m.id = b.mechanic_id WHERE b.id = :id AND (b.user_id = :user_id OR m.user_id = :user_id_check)");
$check->execute(['id' => $bookingId, 'user_id' => $userId, 'user_id_check' => $userId]);
if (!$check->fetchColumn()) jsonResponse(['message' => 'Conversation not found.'], 404);
if (!$callId) {
    $stmt = $pdo->prepare('INSERT INTO calls (booking_id, caller_id, call_type, status) VALUES (:booking_id, :caller_id, :call_type, :status)');
    $stmt->execute(['booking_id' => $bookingId, 'caller_id' => $userId, 'call_type' => $callType, 'status' => $status]);
    jsonResponse(['success' => true, 'call_id' => (int) $pdo->lastInsertId()], 201);
}
$update = $pdo->prepare("UPDATE calls SET status = :status, ended_at = CASE WHEN :status_end = 'ended' THEN NOW() ELSE ended_at END, duration_seconds = CASE WHEN :status_duration = 'ended' THEN TIMESTAMPDIFF(SECOND, started_at, NOW()) ELSE duration_seconds END WHERE id = :id AND booking_id = :booking_id");
$update->execute(['status' => $status, 'status_end' => $status, 'status_duration' => $status, 'id' => $callId, 'booking_id' => $bookingId]);
jsonResponse(['success' => true]);
