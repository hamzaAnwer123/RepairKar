<?php
/**
 * Return full detail for a single booking.
 * Accessible by the mechanic assigned to it, or the user who created it.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (!$bookingId) {
    jsonResponse(['error' => 'Invalid booking_id'], 400);
}

$currentUserId = (int) $_SESSION['user_id'];

try {
    // Fetch booking with all valid joined info
    $stmt = $pdo->prepare(
        "SELECT b.id, b.status, b.address, b.lat, b.lng,
                b.created_at, b.scheduled_time,
                b.user_id, u.name AS user_name, u.phone AS user_phone,
                m.id AS mechanic_id, m.user_id AS mechanic_user_id,
                m.shop_name, m.category AS mechanic_category,
                mu.name AS mechanic_name, mu.phone AS mechanic_phone,
                g.title AS gig_title, g.description AS gig_description,
                g.price_min, g.price_max
         FROM bookings b
         JOIN users u ON u.id = b.user_id
         JOIN mechanics m ON m.id = b.mechanic_id
         JOIN users mu ON mu.id = m.user_id
         LEFT JOIN gigs g ON g.id = b.gig_id
         WHERE b.id = :bid"
    );
    $stmt->execute(['bid' => $bookingId]);
    $b = $stmt->fetch();

    if (!$b) {
        jsonResponse(['error' => 'Booking not found'], 404);
    }

    // Only allow the booking's user or the assigned mechanic's user to see this
    if ((int)$b['user_id'] !== $currentUserId && (int)$b['mechanic_user_id'] !== $currentUserId) {
        jsonResponse(['error' => 'Forbidden'], 403);
    }

    $serviceTitle = $b['gig_title'] ?: ($b['mechanic_category']
        ? ucfirst(str_replace('-', ' ', $b['mechanic_category'])) . ' Service'
        : 'General Repair Service');

    $description = $b['gig_description'] ?: ($b['mechanic_category']
        ? ucfirst(str_replace('-', ' ', $b['mechanic_category'])) . ' repair requested by customer.'
        : 'Standard service request.');

    jsonResponse([
        'booking' => [
            'id'              => (int) $b['id'],
            'status'          => $b['status'],
            'serviceTitle'    => $serviceTitle,
            'description'     => $description,
            'address'         => $b['address'] ?: 'Location not provided',
            'scheduledTime'   => $b['scheduled_time'],
            'createdAt'       => $b['created_at'],
            'userName'        => $b['user_name'] ?: 'Customer',
            'userPhone'       => $b['user_phone'] ?: '',
            'shopName'        => $b['shop_name'] ?: ($b['mechanic_name'] ?: 'Mechanic'),
            'mechanicName'    => $b['mechanic_name'] ?: '',
            'mechanicPhone'   => $b['mechanic_phone'] ?: '',
            'mechanicUserId'  => (int) $b['mechanic_user_id'],
            'priceMin'        => $b['price_min'] !== null ? (float) $b['price_min'] : null,
            'priceMax'        => $b['price_max'] !== null ? (float) $b['price_max'] : null,
        ]
    ]);
} catch (Exception $e) {
    error_log('get-booking-detail.php failed: ' . $e->getMessage());
    jsonResponse(['error' => 'Could not load booking details.'], 500);
}
