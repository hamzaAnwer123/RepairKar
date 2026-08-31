<?php
/**
 * Return accepted bookings for the logged-in mechanic.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

// Get mechanic record
$mStmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = :uid");
$mStmt->execute(['uid' => $currentUserId]);
$mechanic = $mStmt->fetch();

if (!$mechanic) {
    jsonResponse(['bookings' => []]);
}

$mechanicId = (int) $mechanic['id'];

// Fetch accepted bookings for this mechanic
$stmt = $pdo->prepare(
    "SELECT b.id, b.address, b.created_at, b.scheduled_time,\n            u.name AS user_name, u.phone AS user_phone,\n            g.title AS gig_title, m.category AS mechanic_category\n     FROM bookings b\n     JOIN users u ON u.id = b.user_id\n     JOIN mechanics m ON m.id = b.mechanic_id\n     LEFT JOIN gigs g ON g.id = b.gig_id\n     WHERE b.mechanic_id = :mid AND b.status = 'accepted'\n     ORDER BY b.created_at DESC"
);
$stmt->execute(['mid' => $mechanicId]);
$rows = $stmt->fetchAll();

$bookings = [];
foreach ($rows as $r) {
    $desc = $r['gig_title'] ?: ($r['mechanic_category'] ? ucfirst(str_replace('-', ' ', $r['mechanic_category'])) . ' Service' : 'General Repair Service');
    $bookings[] = [
        'id'            => (int) $r['id'],
        'userName'      => $r['user_name'] ?: 'Customer',
        'userPhoto'     => null,
        'userPhone'     => $r['user_phone'],
        'description'   => $desc,
        'address'       => $r['address'] ?: 'Location not provided',
        'createdAt'     => $r['created_at'],
        'scheduledTime' => $r['scheduled_time'],
    ];
}

jsonResponse(['bookings' => $bookings]);
?>
