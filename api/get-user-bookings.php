<?php
/**
 * Return bookings list for the authenticated user.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$limit = filter_input(INPUT_GET, 'limit', FILTER_VALIDATE_INT) ?: 20;

$stmt = $pdo->prepare(
    "SELECT b.id, b.status, b.address, b.created_at, b.scheduled_time,
            m.shop_name, m.shop_photo_path, m.category,
            g.title AS gig_title
     FROM bookings b
     JOIN mechanics m ON m.id = b.mechanic_id
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.user_id = :uid
     ORDER BY b.created_at DESC
     LIMIT :limit"
);
$stmt->bindValue(':uid', $currentUserId, PDO::PARAM_INT);
$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

$bookings = [];
foreach ($rows as $r) {
    $timeStr = date('M j, Y • g:i A', strtotime($r['created_at']));
    $bookings[] = [
        'id'            => (int) $r['id'],
        'status'        => $r['status'],
        'address'       => $r['address'],
        'createdAt'     => $timeStr,
        'scheduledTime' => $r['scheduled_time'],
        'mechanicName'  => $r['shop_name'] ?: 'Mechanic',
        'mechanicPhoto' => $r['shop_photo_path'] ?: null,
        'serviceTitle'  => $r['gig_title'] ?: ($r['category'] ? ucfirst(str_replace('-', ' ', $r['category'])) . ' Service' : 'Repair Service'),
    ];
}

jsonResponse(['bookings' => $bookings]);
