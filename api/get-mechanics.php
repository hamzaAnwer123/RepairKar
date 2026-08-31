<?php
/**
 * Search/browse mechanics & individual gigs — used by user/home.html.
 * Returns each gig as an individual card listing.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');

$category = $_GET['category'] ?? null;
$sort = $_GET['sort'] ?? 'distance';
$lat = isset($_GET['lat']) ? filter_var($_GET['lat'], FILTER_VALIDATE_FLOAT) : null;
$lng = isset($_GET['lng']) ? filter_var($_GET['lng'], FILTER_VALIDATE_FLOAT) : null;

// Whitelist sort values
$allowedSorts = ['distance', 'rating', 'price', 'available'];
if (!in_array($sort, $allowedSorts, true)) {
    $sort = 'distance';
}

$categoryWhitelist = ['washing-machine', 'fridge', 'ac', 'car', 'bike', 'electrician', 'plumber', 'generator', 'towing', 'tire', 'battery'];
if ($category !== null && $category !== '' && !in_array($category, $categoryWhitelist, true)) {
    jsonResponse(['message' => 'Invalid category'], 400);
}

// Query mechanics: verified OR any mechanic who has an active gig / registered
$sql = "SELECT m.id, m.shop_name, m.category, m.bio, m.shop_photo_path, m.lat, m.lng,
               COALESCE((SELECT ROUND(AVG(r.rating), 1) FROM reviews r JOIN bookings b ON b.id = r.booking_id WHERE b.mechanic_id = m.id), m.avg_rating, 0) AS avg_rating,
               COALESCE(NULLIF((SELECT COUNT(r.id) FROM reviews r JOIN bookings b ON b.id = r.booking_id WHERE b.mechanic_id = m.id), 0), m.review_count, 0) AS review_count,
               m.last_active, m.verified,
               (SELECT MIN(g.price_min) FROM gigs g WHERE g.mechanic_id = m.id AND g.active = 1) AS starting_price,
               (SELECT COUNT(*) FROM gigs g WHERE g.mechanic_id = m.id AND g.active = 1) AS total_gigs
        FROM mechanics m
        WHERE (m.verified = 1 OR (SELECT COUNT(*) FROM gigs g2 WHERE g2.mechanic_id = m.id AND g2.active = 1) > 0)
          AND m.rejected = 0";
$params = [];

if ($category !== null && $category !== '') {
    $sql .= " AND m.category = :category";
    $params['category'] = $category;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rawMechanics = $stmt->fetchAll();

$now = time();
$mechanics = [];

foreach ($rawMechanics as $m) {
    $dist = ($lat !== null && $lng !== null && $m['lat'] !== null && $m['lng'] !== null)
        ? round(haversineKm((float) $lat, (float) $lng, (float) $m['lat'], (float) $m['lng']), 1)
        : null;

    $isOnline = $m['last_active'] ? (strtotime($m['last_active']) > ($now - 300)) : true;
    $startPrice = $m['starting_price'] !== null ? (float) $m['starting_price'] : null;
    $reviewCount = (int) $m['review_count'];
    $rating = ($reviewCount > 0 && $m['avg_rating'] > 0) ? (float) $m['avg_rating'] : null;

    $photoUrl = null;
    if (!empty($m['shop_photo_path'])) {
        $cleaned = ltrim(str_replace('../', '', $m['shop_photo_path']), '/');
        $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
        $photoUrl = $scriptDir . '/' . $cleaned;
    }
    $fallbackPhoto = '../assets/images/placeholders/default-mechnaic-profile.png';

    $mechanics[] = [
        'id'            => (int) $m['id'],
        'shopName'      => $m['shop_name'],
        'shop_name'     => $m['shop_name'],
        'category'      => $m['category'],
        'bio'           => $m['bio'] ?: '',
        'photo'         => $photoUrl ?: $fallbackPhoto,
        'shop_photo_path' => $photoUrl ?: $fallbackPhoto,
        'rating'        => $rating,
        'avg_rating'    => $rating,
        'reviewCount'   => $reviewCount,
        'review_count'  => $reviewCount,
        'startingPrice' => $startPrice,
        'starting_price'=> $startPrice,
        'distanceKm'    => $dist,
        'distance_km'   => $dist,
        'isOnline'      => (bool) $isOnline,
        'is_online'     => (bool) $isOnline,
        'totalGigs'     => (int) $m['total_gigs'],
        'verified'      => (bool) $m['verified'],
    ];
}

// Fetch active gigs for these mechanics
$mechanicIds = array_column($mechanics, 'id');
$gigsByMechanic = [];

if (!empty($mechanicIds)) {
    $placeholders = implode(',', array_fill(0, count($mechanicIds), '?'));
    $gigsStmt = $pdo->prepare(
        "SELECT id, mechanic_id, title, description, price_min, price_max, photo_path
         FROM gigs
         WHERE active = 1 AND mechanic_id IN ($placeholders)
         ORDER BY created_at DESC"
    );
    $gigsStmt->execute($mechanicIds);
    $rawGigs = $gigsStmt->fetchAll();

    foreach ($rawGigs as $g) {
        $mId = (int) $g['mechanic_id'];
        $gigsByMechanic[$mId][] = [
            'id'          => (int) $g['id'],
            'title'       => $g['title'],
            'description' => $g['description'],
            'priceMin'    => (float) $g['price_min'],
            'price_min'   => (float) $g['price_min'],
            'priceMax'    => $g['price_max'] !== null ? (float) $g['price_max'] : (float) $g['price_min'],
            'price_max'   => $g['price_max'] !== null ? (float) $g['price_max'] : (float) $g['price_min'],
            'photo'       => $g['photo_path'],
            'photo_path'  => $g['photo_path'],
        ];
    }
}

$categoryPrices = [
    'washing-machine' => 500,
    'fridge'          => 800,
    'ac'              => 1000,
    'car'             => 1000,
    'bike'            => 400,
    'electrician'     => 500,
    'plumber'         => 500,
    'generator'       => 800,
];

// Build 1 clean, consistent shop card per mechanic
$cards = [];
foreach ($mechanics as $m) {
    $mGigs = $gigsByMechanic[$m['id']] ?? [];
    $firstGigId = !empty($mGigs) ? (int) $mGigs[0]['id'] : null;

    if (!empty($mGigs)) {
        $minP = min(array_column($mGigs, 'priceMin'));
        $maxP = max(array_column($mGigs, 'priceMax'));
        $desc = !empty($mGigs[0]['description']) ? $mGigs[0]['description'] : $m['bio'];
    } else {
        $baseP = $categoryPrices[$m['category']] ?? 500;
        $minP = $m['startingPrice'] !== null ? $m['startingPrice'] : $baseP;
        $maxP = $minP;
        $desc = $m['bio'];
    }

    $cards[] = [
        'id'            => (int) $m['id'],
        'gigId'         => $firstGigId,
        'mechanicId'    => (int) $m['id'],
        'shopName'      => $m['shopName'],
        'shop_name'     => $m['shop_name'],
        'gigTitle'      => null,
        'gig_title'     => null,
        'gigDescription'=> $desc,
        'gig_description'=> $desc,
        'bio'           => $m['bio'],
        'category'      => $m['category'],
        'photo'         => $m['photo'],
        'mechanicPhoto' => $m['photo'],
        'rating'        => $m['rating'],
        'avg_rating'    => $m['avg_rating'],
        'reviewCount'   => $m['reviewCount'],
        'review_count'  => $m['review_count'],
        'priceMin'      => (float) $minP,
        'priceMax'      => (float) $maxP,
        'startingPrice' => (float) $minP,
        'starting_price'=> (float) $minP,
        'distanceKm'    => $m['distanceKm'],
        'distance_km'   => $m['distance_km'],
        'isOnline'      => $m['isOnline'],
        'is_online'     => $m['is_online'],
        'verified'      => $m['verified'],
    ];
}

// ---- Sort ----
usort($cards, function ($a, $b) use ($sort) {
    return match ($sort) {
        'rating'    => $b['rating'] <=> $a['rating'],
        'price'     => ($a['startingPrice'] ?? PHP_INT_MAX) <=> ($b['startingPrice'] ?? PHP_INT_MAX),
        'available' => ($b['isOnline'] ? 1 : 0) <=> ($a['isOnline'] ? 1 : 0) ?: (($a['distanceKm'] ?? PHP_INT_MAX) <=> ($b['distanceKm'] ?? PHP_INT_MAX)),
        default     => ($a['distanceKm'] ?? PHP_INT_MAX) <=> ($b['distanceKm'] ?? PHP_INT_MAX),
    };
});

jsonResponse(['mechanics' => $cards, 'total' => count($cards)]);