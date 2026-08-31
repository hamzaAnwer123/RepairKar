<?php
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
requireMethod('POST');
requireAuth();
ensureMessageReadColumn($pdo);
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$messageId = filter_var($input['message_id'] ?? null, FILTER_VALIDATE_INT);
$lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($input['lng'] ?? null, FILTER_VALIDATE_FLOAT);
if (!$messageId || $lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) jsonResponse(['message' => 'Invalid location update.'], 400);
$stmt = $pdo->prepare("UPDATE messages m JOIN bookings b ON b.id = m.booking_id JOIN mechanics mech ON mech.id = b.mechanic_id SET m.content = :content WHERE m.id = :id AND m.sender_id = :sender_id AND m.message_type = 'live_location' AND m.live_location_expires_at > NOW() AND (b.user_id = :user_id OR mech.user_id = :user_id_check)");
$payload = json_encode(['lat' => $lat, 'lng' => $lng, 'address' => $input['address'] ?? 'Live location']);
$stmt->execute(['content' => $payload, 'id' => $messageId, 'sender_id' => (int) $_SESSION['user_id'], 'user_id' => (int) $_SESSION['user_id'], 'user_id_check' => (int) $_SESSION['user_id']]);
if (!$stmt->rowCount()) jsonResponse(['message' => 'Live location has expired or is not yours.'], 409);
jsonResponse(['success' => true]);
