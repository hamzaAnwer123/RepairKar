<?php
/**
 * Return all unique conversations grouped by user/mechanic with their latest message and unread count.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();
ensureMessageReadColumn($pdo);

$currentUserId = (int) $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'user';

if ($role === 'mechanic') {
    // Get mechanic ID
    $mStmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = :uid");
    $mStmt->execute(['uid' => $currentUserId]);
    $mechanicId = $mStmt->fetchColumn();

    if (!$mechanicId) {
        jsonResponse(['conversations' => []]);
    }

    // Get unique conversations grouped by customer user_id
    $sql = "SELECT b.user_id,
                   MAX(b.id) AS booking_id,
                   u.name AS user_name,
                   u.phone AS user_phone,
                   u.photo_path AS user_photo_path,
                   (SELECT m2.content FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = b.user_id AND b2.mechanic_id = :mid_content)
                    ORDER BY m2.id DESC LIMIT 1) AS last_message,
                   (SELECT m2.message_type FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = b.user_id AND b2.mechanic_id = :mid_type)
                    ORDER BY m2.id DESC LIMIT 1) AS last_type,
                   (SELECT m2.created_at FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = b.user_id AND b2.mechanic_id = :mid_time)
                    ORDER BY m2.id DESC LIMIT 1) AS last_time,
                                     COUNT(DISTINCT unread_messages.id) AS unread_count
            FROM bookings b
            JOIN users u ON u.id = b.user_id
                        LEFT JOIN messages unread_messages
                                ON unread_messages.booking_id = b.id
                             AND unread_messages.sender_role = 'user'
                             AND unread_messages.read_at IS NULL
            WHERE b.mechanic_id = :mid_where
            GROUP BY b.user_id, u.name, u.phone, u.photo_path
            ORDER BY COALESCE(last_time, MAX(b.created_at)) DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'mid_content' => $mechanicId,
        'mid_type' => $mechanicId,
        'mid_time' => $mechanicId,
        'mid_where' => $mechanicId,
    ]);
    $rows = $stmt->fetchAll();

    $conversations = [];
    foreach ($rows as $r) {
        $preview = $r['last_message'] ?: 'No messages yet';
        if ($r['last_type'] === 'image') $preview = 'Photo';
        if ($r['last_type'] === 'video') $preview = 'Video';
        if ($r['last_type'] === 'document') $preview = 'Document';
        if ($r['last_type'] === 'audio') $preview = 'Voice message';
        if ($r['last_type'] === 'location') $preview = 'Shared location';
        if ($r['last_type'] === 'live_location') $preview = 'Live location';

        $timeStr = '';
        if ($r['last_time']) {
            $timeStr = date('g:i A', strtotime($r['last_time']));
        }

        // Build a web-accessible URL for the customer's profile photo
        // (same convention as the mechanic branch below).
        $photoUrl = null;
        if (!empty($r['user_photo_path'])) {
            $cleaned = ltrim(str_replace('../', '', $r['user_photo_path']), '/');
            $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $photoUrl = $scriptDir . '/' . $cleaned;
        }

        $conversations[] = [
            'bookingId'          => (int) $r['booking_id'],
            'userName'           => $r['user_name'] ?: 'Customer',
            'userPhoto'          => $photoUrl ?: '../assets/images/placeholders/default-user-profile.png',
            'userPhone'          => $r['user_phone'],
            'userOnline'         => true,
            'lastMessageTime'    => $timeStr,
            'lastMessagePreview' => $preview,
            'lastMessageType'    => $r['last_type'],
            'unreadCount'        => (int) $r['unread_count'] > 0 ? (int) $r['unread_count'] : 0,
        ];
    }

    jsonResponse(['conversations' => $conversations]);
} else {
    // User role: get unique conversations grouped by mechanic_id
    $sql = "SELECT b.mechanic_id,
                   MAX(b.id) AS booking_id,
                   m.shop_name,
                   m.shop_photo_path,
                   m.last_active,
                   u.phone AS mech_phone,
                   COUNT(DISTINCT unread_messages.id) AS unread_count,
                   (SELECT m2.content FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = :uid_content AND b2.mechanic_id = b.mechanic_id)
                    ORDER BY m2.id DESC LIMIT 1) AS last_message,
                   (SELECT m2.message_type FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = :uid_type AND b2.mechanic_id = b.mechanic_id)
                    ORDER BY m2.id DESC LIMIT 1) AS last_type,
                   (SELECT m2.created_at FROM messages m2 
                    WHERE m2.booking_id IN (SELECT b2.id FROM bookings b2 WHERE b2.user_id = :uid_time AND b2.mechanic_id = b.mechanic_id)
                    ORDER BY m2.id DESC LIMIT 1) AS last_time
            FROM bookings b
            JOIN mechanics m ON m.id = b.mechanic_id
            JOIN users u ON u.id = m.user_id
            LEFT JOIN messages unread_messages
                ON unread_messages.booking_id = b.id
               AND unread_messages.sender_role = 'mechanic'
               AND unread_messages.read_at IS NULL
            WHERE b.user_id = :uid_where
            GROUP BY b.mechanic_id, m.shop_name, m.shop_photo_path, m.last_active, u.phone
            ORDER BY COALESCE(last_time, MAX(b.created_at)) DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'uid_content' => $currentUserId,
        'uid_type' => $currentUserId,
        'uid_time' => $currentUserId,
        'uid_where' => $currentUserId,
    ]);
    $rows = $stmt->fetchAll();

    $conversations = [];
    $now = time();
    foreach ($rows as $r) {
        $preview = $r['last_message'] ?: 'Start a conversation';
        if ($r['last_type'] === 'image') $preview = 'Photo';
        if ($r['last_type'] === 'video') $preview = 'Video';
        if ($r['last_type'] === 'document') $preview = 'Document';
        if ($r['last_type'] === 'audio') $preview = 'Voice message';
        if ($r['last_type'] === 'location') $preview = 'Shared location';
        if ($r['last_type'] === 'live_location') $preview = 'Live location';

        $timeStr = '';
        if ($r['last_time']) {
            $timeStr = date('g:i A', strtotime($r['last_time']));
        }

        $isOnline = $r['last_active'] ? (strtotime($r['last_active']) > ($now - 300)) : true;

        $photoUrl = null;
        if (!empty($r['shop_photo_path'])) {
            $cleaned = ltrim(str_replace('../', '', $r['shop_photo_path']), '/');
            $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $photoUrl = $scriptDir . '/' . $cleaned;
        }

        $conversations[] = [
            'bookingId'          => (int) $r['booking_id'],
            'mechanicId'         => (int) $r['mechanic_id'],
            'userName'           => $r['shop_name'] ?: 'Mechanic',
            'userPhoto'          => $photoUrl ?: '../assets/images/placeholders/default-mechnaic-profile.png',
            'userPhone'          => $r['mech_phone'],
            'userOnline'         => (bool) $isOnline,
            'lastMessageTime'    => $timeStr,
            'lastMessagePreview' => $preview,
            'lastMessageType'    => $r['last_type'],
            'unreadCount'        => (int) $r['unread_count'] > 0 ? (int) $r['unread_count'] : 0,
        ];
    }

    jsonResponse(['conversations' => $conversations]);
}
