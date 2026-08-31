<?php
/**
 * Send a chat message on a booking. Supports text, image (multipart
 * upload), and location message types.
 *
 * CRITICAL: verifies the logged-in user is a participant in this
 * booking's conversation before allowing them to post — same pattern
 * as update-booking-status.php.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();
ensureMessageReadColumn($pdo);

$isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
$input = $isMultipart ? $_POST : (json_decode(file_get_contents('php://input'), true) ?? []);

$bookingId  = filter_var($input['booking_id'] ?? null, FILTER_VALIDATE_INT);
$mechanicId = filter_var($input['mechanic_id'] ?? null, FILTER_VALIDATE_INT);
$type       = $input['type'] ?? 'text';

$currentUserId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? '';

// ---- Resolve booking_id from mechanic_id if needed ----
if (!$bookingId && $mechanicId) {
    if ($role !== 'user') {
        jsonResponse(['message' => 'Only customers can start a new conversation.'], 403);
    }

    $mechanicExistsStmt = $pdo->prepare('SELECT id FROM mechanics WHERE id = :mid');
    $mechanicExistsStmt->execute(['mid' => $mechanicId]);
    if (!$mechanicExistsStmt->fetchColumn()) {
        jsonResponse(['message' => 'Mechanic not found.'], 404);
    }

    $lookupStmt = $pdo->prepare(
        "SELECT id FROM bookings
         WHERE user_id = :uid AND mechanic_id = :mid
         ORDER BY created_at DESC LIMIT 1"
    );
    $lookupStmt->execute(['uid' => $currentUserId, 'mid' => $mechanicId]);
    $bookingId = $lookupStmt->fetchColumn();

    if (!$bookingId) {
        $createStmt = $pdo->prepare(
            "INSERT INTO bookings (user_id, mechanic_id, gig_id, status, lat, lng, address, scheduled_time, created_at)
             VALUES (:uid, :mid, NULL, 'pending', 0, 0, 'Chat started before booking', NULL, NOW())"
        );
        $createStmt->execute([
            'uid' => $currentUserId,
            'mid' => $mechanicId,
        ]);
        $bookingId = (int) $pdo->lastInsertId();
    }
}

if (!$bookingId || !in_array($type, ['text', 'image', 'video', 'document', 'audio', 'location', 'live_location'], true)) {
    jsonResponse(['message' => 'Invalid request.'], 400);
}

// ---- Participant check ----
$stmt = $pdo->prepare(
    "SELECT b.user_id, m.user_id AS mechanic_user_id
     FROM bookings b JOIN mechanics m ON m.id = b.mechanic_id
     WHERE b.id = :id"
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(['message' => 'Conversation not found.'], 404);
}

$currentUserId = (int) $_SESSION['user_id'];
$isUser = $currentUserId === (int) $booking['user_id'];
$isMechanic = $currentUserId === (int) $booking['mechanic_user_id'];

if (!$isUser && !$isMechanic) {
    jsonResponse(['message' => 'You do not have permission to send messages in this conversation.'], 403);
}
$senderRole = $isMechanic ? 'mechanic' : 'user';

// ---- Build content based on type ----
if ($type === 'text') {
    $content = trim((string) ($input['content'] ?? ''));
    if ($content === '' || mb_strlen($content) > 2000) {
        jsonResponse(['message' => 'Message must be between 1 and 2000 characters.'], 400);
    }
} elseif ($type === 'location' || $type === 'live_location') {
    $lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
    $lng = filter_var($input['lng'] ?? null, FILTER_VALIDATE_FLOAT);
    if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
        jsonResponse(['message' => 'Invalid location.'], 400);
    }
    $content = json_encode(['lat' => $lat, 'lng' => $lng, 'address' => $input['address'] ?? null]);
    $liveLocationExpiresAt = $type === 'live_location' ? date('Y-m-d H:i:s', time() + min(max((int) ($input['duration_minutes'] ?? 15), 1), 480) * 60) : null;
} else { // uploaded media, document, or voice note
    $upload = $_FILES['file'] ?? $_FILES['image'] ?? null;
    if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['message' => 'No file uploaded.'], 400);
    }
    $file = $upload;

    // Check the REAL file type via finfo, never trust the client-sent
    // MIME type or the file extension alone — an attacker can rename
    // any file to .jpg.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    $allowedMimes = match ($type) {
        'image' => ['image/jpeg', 'image/png', 'image/webp', 'image/gif'],
        'video' => ['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'],
        'audio' => ['audio/mpeg', 'audio/ogg', 'audio/wav', 'audio/webm', 'audio/mp4', 'audio/x-m4a'],
        'document' => ['application/pdf', 'text/plain', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', 'application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        default => [],
    };
    if (!in_array($realMime, $allowedMimes, true)) {
        jsonResponse(['message' => 'File type is not supported.'], 400);
    }
    $maxBytes = $type === 'document' ? 20 * 1024 * 1024 : ($type === 'video' ? 50 * 1024 * 1024 : 25 * 1024 * 1024);
    $maxMb = (int) ($maxBytes / (1024 * 1024));
    if ($file['size'] > $maxBytes) {
        jsonResponse(['message' => "File is too large. Maximum size is {$maxMb}MB."], 400);
    }

    $uploadDir = __DIR__ . '/../assets/uploads/chat/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $extensionMap = [
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp', 'image/gif' => 'gif',
        'video/mp4' => 'mp4', 'video/webm' => 'webm', 'video/ogg' => 'ogv', 'video/quicktime' => 'mov',
        'audio/mpeg' => 'mp3', 'audio/ogg' => 'ogg', 'audio/wav' => 'wav', 'audio/webm' => 'webm', 'audio/mp4' => 'm4a', 'audio/x-m4a' => 'm4a',
        'application/pdf' => 'pdf', 'text/plain' => 'txt', 'application/msword' => 'doc', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
        'application/vnd.ms-excel' => 'xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
    ];
    $ext = $extensionMap[$realMime] ?? 'bin';
    // Random filename — never the original uploaded filename, prevents
    // directory traversal and overwrite attacks.
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        jsonResponse(['message' => 'Could not save the uploaded file.'], 500);
    }
    $content = '/assets/uploads/chat/' . $filename;
    $originalFilename = basename((string) $file['name']);
    $fileSize = (int) $file['size'];
}

$insertStmt = $pdo->prepare(
    "INSERT INTO messages (booking_id, sender_id, sender_role, message_type, content, original_filename, file_size, live_location_expires_at, created_at)
     VALUES (:booking_id, :sender_id, :sender_role, :type, :content, :original_filename, :file_size, :live_location_expires_at, NOW())"
);
$insertStmt->execute([
    'booking_id'  => $bookingId,
    'sender_id'   => $currentUserId,
    'sender_role' => $senderRole,
    'type'        => $type,
    'content'     => $content,
    'original_filename' => $originalFilename ?? null,
    'file_size' => $fileSize ?? null,
    'live_location_expires_at' => $liveLocationExpiresAt ?? null,
]);

jsonResponse([
    'success' => true,
    'message_id' => (int) $pdo->lastInsertId(),
    'booking_id' => (int) $bookingId,
], 201);
