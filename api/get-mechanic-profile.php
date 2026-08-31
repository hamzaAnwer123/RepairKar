<?php
/**
 * Full public profile for one mechanic — used by
 * user/mechanic-profile.html and user/booking.html.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');

$id = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : (isset($_GET['mechanic_id']) ? filter_var($_GET['mechanic_id'], FILTER_VALIDATE_INT) : null);
$gigId = isset($_GET['gig_id']) ? filter_var($_GET['gig_id'], FILTER_VALIDATE_INT) : null;

// If id not provided but gig_id provided, look up mechanic from gig
if (!$id && $gigId) {
    $gStmt = $pdo->prepare("SELECT mechanic_id FROM gigs WHERE id = :gid");
    $gStmt->execute(['gid' => $gigId]);
    $id = $gStmt->fetchColumn();
}

if (!$id) {
    jsonResponse(['message' => 'Invalid mechanic id'], 400);
}

$stmt = $pdo->prepare(
    "SELECT id, shop_name, category, bio, address, shop_photo_path, verified, avg_rating,
            review_count, last_active, lat, lng, created_at
     FROM mechanics
     WHERE id = :id AND rejected = 0"
);
$stmt->execute(['id' => $id]);
$mechanic = $stmt->fetch();

if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic not found'], 404);
}

$gigsStmt = $pdo->prepare(
    "SELECT id, title, description, price_min, price_max, photo_path
     FROM gigs WHERE mechanic_id = :id AND active = 1 ORDER BY created_at DESC"
);
$gigsStmt->execute(['id' => $id]);
$gigs = $gigsStmt->fetchAll();

$reviewsStmt = $pdo->prepare(
    "SELECT r.rating, r.comment, r.photo_path, r.created_at, u.name AS reviewer_name
     FROM reviews r
     JOIN bookings b ON b.id = r.booking_id
     JOIN users u ON u.id = b.user_id
     WHERE b.mechanic_id = :id
     ORDER BY r.created_at DESC
     LIMIT 20"
);
$reviewsStmt->execute(['id' => $id]);
$reviews = $reviewsStmt->fetchAll();

    $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $fallbackPhoto = $scriptDir . '/assets/images/placeholders/default-mechnaic-profile.png';

    $photoUrl = null;
    if (!empty($mechanic['shop_photo_path'])) {
        $cleaned = ltrim(str_replace('../', '', $mechanic['shop_photo_path']), '/');
        $photoUrl = $scriptDir . '/' . $cleaned;
    }
    $categoryBasePrices = [
        'washing-machine' => 500,
        'fridge'          => 800,
        'ac'              => 1000,
        'car'             => 1000,
        'bike'            => 400,
        'electrician'     => 500,
        'plumber'         => 500,
        'generator'       => 800,
    ];

    $startingPrice = 500;
    if (!empty($gigs)) {
        $startingPrice = min(array_column($gigs, 'price_min'));
    } elseif (!empty($mechanic['category']) && isset($categoryBasePrices[$mechanic['category']])) {
        $startingPrice = $categoryBasePrices[$mechanic['category']];
    }

    // Calculate live reviews count and average rating
    $revSummaryStmt = $pdo->prepare(
        "SELECT ROUND(AVG(r.rating), 1) AS live_avg, COUNT(r.id) AS live_count
         FROM reviews r
         JOIN bookings b ON b.id = r.booking_id
         WHERE b.mechanic_id = :mid"
    );
    $revSummaryStmt->execute(['mid' => $id]);
    $revSummary = $revSummaryStmt->fetch();

    $reviewCount = (int) ($revSummary['live_count'] ?? 0);
    $avgRating = $reviewCount > 0 ? (float) $revSummary['live_avg'] : ($mechanic['avg_rating'] > 0 ? (float)$mechanic['avg_rating'] : null);

    // Sync back to mechanics table if different
    if ($reviewCount !== (int)$mechanic['review_count'] || ($avgRating !== null && abs($avgRating - (float)$mechanic['avg_rating']) > 0.01)) {
        $sync = $pdo->prepare("UPDATE mechanics SET avg_rating = :r, review_count = :c WHERE id = :mid");
        $sync->execute(['r' => $avgRating ?: 0, 'c' => $reviewCount, 'mid' => $id]);
    }

    jsonResponse([
        'id'            => (int) $mechanic['id'],
        'shopName'      => $mechanic['shop_name'],
        'shop_name'     => $mechanic['shop_name'],
        'category'      => $mechanic['category'],
        'bio'           => $mechanic['bio'] ?: 'Professional automotive and repair specialist with years of hands-on experience.',
        'photo'         => $photoUrl ?: $fallbackPhoto,
        'shop_photo_path' => $photoUrl ?: $fallbackPhoto,
        'isOnline'      => (bool) ($mechanic['last_active'] ? (strtotime($mechanic['last_active']) > time() - 300) : true),
        'member_since'  => !empty($mechanic['created_at']) ? date('M Y', strtotime($mechanic['created_at'])) : null,
        'rating'        => $avgRating,
        'reviewCount'   => $reviewCount,
        'review_count'  => $reviewCount,
        'startingPrice' => (float) $startingPrice,
        'starting_price'=> (float) $startingPrice,
    'address'       => $mechanic['address'] ?: 'On-demand mobile repair service',
    'lat'           => $mechanic['lat'] ? (float) $mechanic['lat'] : 24.8607,
    'lng'           => $mechanic['lng'] ? (float) $mechanic['lng'] : 67.0011,
    'gigs'          => array_map(fn($g) => [
        'id'          => (int) $g['id'],
        'title'       => $g['title'],
        'description' => $g['description'],
        'priceMin'    => (float) $g['price_min'],
        'price_min'   => (float) $g['price_min'],
        'priceMax'    => $g['price_max'] !== null ? (float) $g['price_max'] : (float) $g['price_min'],
        'price_max'   => $g['price_max'] !== null ? (float) $g['price_max'] : (float) $g['price_min'],
        'photo'       => $g['photo_path'],
        'photo_path'  => $g['photo_path'],
    ], $gigs),
    'reviews'       => array_map(function ($r) use ($scriptDir) {
        // Resolve an optional review photo to a web-accessible URL.
        $reviewPhoto = null;
        if (!empty($r['photo_path'])) {
            $cleaned = ltrim(str_replace('../', '', $r['photo_path']), '/');
            $reviewPhoto = $scriptDir . '/' . $cleaned;
        }
        return [
            'rating'        => (int) $r['rating'],
            'comment'       => $r['comment'],
            'photo'         => $reviewPhoto,
            'reviewerName'  => $r['reviewer_name'],
            'reviewer_name' => $r['reviewer_name'],
            'date'          => date('M j, Y', strtotime($r['created_at'])),
        ];
    }, $reviews),
]);