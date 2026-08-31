<?php
/**
 * Poll for messages on a booking. Same participant check as
 * send-message.php — a user must not be able to read another
 * conversation by guessing a booking_id.
 *
 * Supports two lookup modes:
 *   1. booking_id  — direct booking lookup (primary)
 *   2. mechanic_id — find the latest booking between current user
 *                     and this mechanic (fallback when navigating
 *                     from booking.html before a booking is confirmed)
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();
ensureMessageReadColumn($pdo);

$bookingId  = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
$mechanicId = filter_input(INPUT_GET, 'mechanic_id', FILTER_VALIDATE_INT);
$after      = filter_input(INPUT_GET, 'after', FILTER_VALIDATE_INT) ?: 0;

$currentUserId = (int) $_SESSION['user_id'];

// ---- Resolve booking_id from mechanic_id if needed ----
if (!$bookingId && $mechanicId) {
    $lookupStmt = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE user_id = :uid AND mechanic_id = :mid
         ORDER BY created_at DESC LIMIT 1"
    );
    $lookupStmt->execute(['uid' => $currentUserId, 'mid' => $mechanicId]);
    $bookingId = $lookupStmt->fetchColumn();
}

if (!$bookingId && $mechanicId) {
    $mechInfoStmt = $pdo->prepare(
        "SELECT m.shop_name, m.shop_photo_path, m.last_active, u.phone
         FROM mechanics m
         JOIN users u ON u.id = m.user_id
         WHERE m.id = :mid"
    );
    $mechInfoStmt->execute(['mid' => $mechanicId]);
    $mechRow = $mechInfoStmt->fetch();

    $response = [
        'messages' => [],
        'booking_id' => null,
        'mechanic' => [
            'name' => $mechRow['shop_name'] ?? 'Mechanic',
            'photo' => $mechRow['shop_photo_path'] ?: null,
            'phone' => $mechRow['phone'] ?: null,
            'online' => $mechRow['last_active'] ? (strtotime($mechRow['last_active']) > time() - 300) : false,
        ],
    ];
    jsonResponse($response);
}

if (!$bookingId) {
    jsonResponse(['message' => 'Invalid request.'], 400);
}

$bookingStmt = $pdo->prepare(
    "SELECT b.user_id, b.mechanic_id, m.user_id AS mechanic_user_id
     FROM bookings b JOIN mechanics m ON m.id = b.mechanic_id
     WHERE b.id = :id"
);
$bookingStmt->execute(['id' => $bookingId]);
$booking = $bookingStmt->fetch();

if (!$booking) {
    jsonResponse(['message' => 'Conversation not found.'], 404);
}

if ($currentUserId !== (int) $booking['user_id'] && $currentUserId !== (int) $booking['mechanic_user_id']) {
    jsonResponse(['message' => 'You do not have permission to view this conversation.'], 403);
}

// ---- Fetch messages across all bookings between this user and mechanic ----
$userId = (int) $booking['user_id'];
$mechanicId = (int) $booking['mechanic_id'];

$stmt = $pdo->prepare(
         "SELECT id, sender_role, message_type, content, original_filename, file_size, live_location_expires_at, read_at, created_at
     FROM messages
     WHERE booking_id IN (SELECT id FROM bookings WHERE user_id = :uid AND mechanic_id = :mid)
             AND (id > :after OR (message_type = 'live_location' AND live_location_expires_at > NOW()))
     ORDER BY id ASC"
);
$stmt->execute(['uid' => $userId, 'mid' => $mechanicId, 'after' => $after]);
$rows = $stmt->fetchAll();

$messages = array_map(function ($row) {
    $out = [
        'id'         => (int) $row['id'],
        'senderRole' => $row['sender_role'],
        'type'       => $row['message_type'],
        'createdAt'  => $row['created_at'],
        'originalFilename' => $row['original_filename'],
        'fileSize' => $row['file_size'] ? (int) $row['file_size'] : null,
        'liveLocationExpiresAt' => $row['live_location_expires_at'],
        'readAt'         => $row['read_at'] ? true : false,
    ];
    if ($row['message_type'] === 'location' || $row['message_type'] === 'live_location') {
        $decoded = json_decode($row['content'], true) ?: [];
        $out['lat'] = $decoded['lat'] ?? null;
        $out['lng'] = $decoded['lng'] ?? null;
        $out['address'] = $decoded['address'] ?? null;
    } else {
        $out['content'] = $row['content'];
    }
    return $out;
}, $rows);

$response = ['messages' => $messages, 'booking_id' => (int) $bookingId];

// ---- On first load (after=0), include mechanic info for the chat header ----
if ($after === 0) {
    $mechStmt = $pdo->prepare(
        "SELECT m.shop_name, m.shop_photo_path, m.last_active, u.phone
         FROM mechanics m
         JOIN users u ON u.id = m.user_id
         WHERE m.id = :mid"
    );
    $mechStmt->execute(['mid' => (int) $booking['mechanic_id']]);
    $mechRow = $mechStmt->fetch();

    if ($mechRow) {
        $isOnline = $mechRow['last_active']
            ? (strtotime($mechRow['last_active']) > time() - 300)
            : false;
        $response['mechanic'] = [
            'name'   => $mechRow['shop_name'],
            'photo'  => $mechRow['shop_photo_path'] ?: null,
            'phone'  => $mechRow['phone'] ?: null,
            'online' => $isOnline,
        ];
    }

    // Customer info for the mechanic's chat header (photo URL follows
    // the same '../'-strip convention as current-user.php).
    $custStmt = $pdo->prepare(
        "SELECT name, photo_path FROM users WHERE id = :uid"
    );
    $custStmt->execute(['uid' => (int) $booking['user_id']]);
    $custRow = $custStmt->fetch();

    if ($custRow) {
        $custPhoto = null;
        if (!empty($custRow['photo_path'])) {
            $cleaned = ltrim(str_replace('../', '', $custRow['photo_path']), '/');
            $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $custPhoto = $scriptDir . '/' . $cleaned;
        }
        $response['customer'] = [
            'name'  => $custRow['name'] ?: 'Customer',
            'photo' => $custPhoto,
        ];
    }
}

jsonResponse($response);