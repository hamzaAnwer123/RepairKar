<?php
/**
 * Return all reviews the authenticated user has posted, with the
 * mechanic / booking context needed to render them on the My Reviews page.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT r.id, r.rating, r.comment, r.photo_path, r.created_at,
                b.id AS booking_id, m.id AS mechanic_id, m.shop_name, m.shop_photo_path, m.category,
                g.title AS gig_title
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         JOIN mechanics m ON m.id = b.mechanic_id
         LEFT JOIN gigs g ON g.id = b.gig_id
         WHERE b.user_id = :uid
         ORDER BY r.created_at DESC"
    );
    $stmt->execute(['uid' => $currentUserId]);
    $rows = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log('get-my-reviews.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Unable to load your reviews.'], 500);
}

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$defaultMechanicPhoto = $scriptDir . '/assets/images/placeholders/default-mechnaic-profile.png';

$reviews = array_map(function ($r) use ($scriptDir, $defaultMechanicPhoto) {
    // Resolve the review photo (if any) to a web-accessible URL.
    $photo = null;
    if (!empty($r['photo_path'])) {
        $cleaned = ltrim(str_replace('../', '', $r['photo_path']), '/');
        $photo = $scriptDir . '/' . $cleaned;
    }

    // Same mechanic photo resolution used by the booking endpoints.
    $mechanicPhoto = $defaultMechanicPhoto;
    if (!empty($r['shop_photo_path'])) {
        $cleaned = ltrim(str_replace('../', '', $r['shop_photo_path']), '/');
        $mechanicPhoto = $scriptDir . '/' . $cleaned;
    }

    return [
        'id'            => (int) $r['id'],
        'booking_id'    => (int) $r['booking_id'],
        'mechanic_id'   => (int) $r['mechanic_id'],
        'mechanic_name' => $r['shop_name'] ?: 'Mechanic',
        'mechanic_photo'=> $mechanicPhoto,
        'service_title' => $r['gig_title'] ?: ($r['category'] ? ucfirst(str_replace('-', ' ', $r['category'])) . ' Service' : 'Repair Service'),
        'rating'        => (int) $r['rating'],
        'comment'       => $r['comment'],
        'photo'         => $photo,
        'date'          => date('M j, Y', strtotime($r['created_at'])),
    ];
}, $rows);

jsonResponse(['reviews' => $reviews, 'total' => count($reviews)]);
