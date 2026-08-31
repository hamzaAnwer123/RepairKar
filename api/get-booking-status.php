<?php
/**
 * Get the current status of a booking.
 * Requires an active user session and the booking must belong to
 * the logged-in user (or their mechanic account).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (!$bookingId) {
    jsonResponse(['message' => 'Invalid booking id'], 400);
}

$stmt = $pdo->prepare(
    "SELECT
        b.id,
        b.status,
        b.lat        AS user_lat,
        b.lng        AS user_lng,
        b.address,
        b.scheduled_time,
        b.created_at,
        b.user_id,
        b.gig_id,
        g.title      AS gig_title,
        g.price_min,
        g.price_max,
        m.id         AS mechanic_id,
        m.user_id    AS mechanic_user_id,
        m.shop_name  AS mechanic_name,
        m.shop_photo_path AS mechanic_photo,
        m.lat        AS mechanic_lat,
        m.lng        AS mechanic_lng,
        m.last_active,
        u.phone      AS mechanic_phone
     FROM bookings b
     JOIN mechanics m  ON m.id  = b.mechanic_id
     JOIN users     u  ON u.id  = m.user_id
     LEFT JOIN gigs g  ON g.id  = b.gig_id
     WHERE b.id = :id"
);
$stmt->execute(['id' => $bookingId]);
$booking = $stmt->fetch();

if (!$booking) {
    jsonResponse(['message' => 'Booking not found.'], 404);
}

// Ownership check — logged-in user must own this booking or be the mechanic
$currentUserId = (int) $_SESSION['user_id'];
$isUser     = $currentUserId === (int) $booking['user_id'];
$isMechanic = $currentUserId === (int) $booking['mechanic_user_id'];

if (!$isUser && !$isMechanic) {
    jsonResponse(['message' => 'Access denied.'], 403);
}

// Is the mechanic online? (active within last 5 minutes)
$isOnline = $booking['last_active']
    ? (strtotime($booking['last_active']) > time() - 300)
    : false;

jsonResponse([
    'bookingId'      => (int) $booking['id'],
    'status'         => $booking['status'],
    'address'        => $booking['address'],
    'scheduledTime'  => $booking['scheduled_time'],
    'createdAt'      => $booking['created_at'],
    'gigTitle'       => $booking['gig_title'] ?: 'General Service',
    'priceMin'       => $booking['price_min'] ? (float) $booking['price_min'] : null,
    'priceMax'       => $booking['price_max'] ? (float) $booking['price_max'] : null,
    'mechanicId'     => (int) $booking['mechanic_id'],
    'mechanicName'   => $booking['mechanic_name'],
    'mechanicPhoto'  => $booking['mechanic_photo'],
    'mechanicPhone'  => $booking['mechanic_phone'],
    'mechanicOnline' => $isOnline,
    'mechanicLat'    => $booking['mechanic_lat'] ? (float) $booking['mechanic_lat'] : null,
    'mechanicLng'    => $booking['mechanic_lng'] ? (float) $booking['mechanic_lng'] : null,
    'userLat'        => $booking['user_lat']     ? (float) $booking['user_lat']     : 24.8607,
    'userLng'        => $booking['user_lng']     ? (float) $booking['user_lng']     : 67.0011,
]);
