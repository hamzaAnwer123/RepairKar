<?php
/**
 * Return full detail for a single booking — admin variant of
 * api/get-booking-detail.php with no ownership restriction.
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

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$bookingId = filter_input(INPUT_GET, 'booking_id', FILTER_VALIDATE_INT);
if (!$bookingId) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid booking id']);
    exit;
}

try {
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
        http_response_code(404);
        echo json_encode(['message' => 'Booking not found']);
        exit;
    }

    $serviceTitle = $b['gig_title'] ?: ($b['mechanic_category']
        ? ucfirst(str_replace('-', ' ', $b['mechanic_category'])) . ' Service'
        : 'General Repair Service');

    $description = $b['gig_description'] ?: ($b['mechanic_category']
        ? ucfirst(str_replace('-', ' ', $b['mechanic_category'])) . ' repair requested by customer.'
        : 'Standard service request.');

    echo json_encode([
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
            'priceMin'        => $b['price_min'] !== null ? (float) $b['price_min'] : null,
            'priceMax'        => $b['price_max'] !== null ? (float) $b['price_max'] : null,
        ]
    ]);
} catch (PDOException $e) {
    error_log('admin/get-booking-detail.php failed: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['message' => 'Could not load the booking. Please try again.']);
}
