<?php
/**
 * Return incoming pending booking requests for the logged-in mechanic.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

// Get mechanic details
$mStmt = $pdo->prepare("SELECT id, lat, lng FROM mechanics WHERE user_id = :uid");
$mStmt->execute(['uid' => $currentUserId]);
$mechanic = $mStmt->fetch();

if (!$mechanic) {
    jsonResponse(['requests' => []]);
}

$mechanicId = (int) $mechanic['id'];
$mechLat = $mechanic['lat'] !== null ? (float) $mechanic['lat'] : null;
$mechLng = $mechanic['lng'] !== null ? (float) $mechanic['lng'] : null;

// Fetch pending bookings for this mechanic
$stmt = $pdo->prepare(
    "SELECT b.id, b.lat, b.lng, b.address, b.created_at, b.scheduled_time,
            u.name AS user_name, u.phone AS user_phone,
            g.title AS gig_title, m.category AS mechanic_category
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     JOIN mechanics m ON m.id = b.mechanic_id
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status = 'pending'
     ORDER BY b.created_at DESC"
);
$stmt->execute(['mid' => $mechanicId]);
$rows = $stmt->fetchAll();

$requests = [];
foreach ($rows as $r) {
    $dist = null;
    if ($mechLat !== null && $mechLng !== null && $r['lat'] !== null && $r['lng'] !== null) {
        $dist = round(haversineKm($mechLat, $mechLng, (float) $r['lat'], (float) $r['lng']), 1);
    }

    $desc = $r['gig_title'] ?: ($r['mechanic_category'] ? ucfirst(str_replace('-', ' ', $r['mechanic_category'])) . ' Service' : 'General Repair Service');

    $requests[] = [
        'id'              => (int) $r['id'],
        'userName'        => $r['user_name'] ?: 'Customer',
        'userPhoto'       => null,
        'userPhone'       => $r['user_phone'],
        'isUrgent'        => str_contains(strtolower($r['address'] ?? ''), 'roadside') || str_contains(strtolower($r['address'] ?? ''), 'sos'),
        'description'     => $desc,
        'distanceKm'      => $dist,
        'area'            => $r['address'] ?: 'Nearby area',
        'vehicleOrDevice' => ucfirst(str_replace('-', ' ', $r['mechanic_category'] ?? 'Vehicle')),
        'createdAt'       => $r['created_at'],
    ];
}

jsonResponse(['requests' => $requests]);
