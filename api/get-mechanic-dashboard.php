<?php
/**
 * Return dashboard metrics and status for logged-in mechanic.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

$mStmt = $pdo->prepare("SELECT id, shop_name, shop_photo_path, avg_rating, review_count, last_active FROM mechanics WHERE user_id = :uid");
$mStmt->execute(['uid' => $currentUserId]);
$mechanic = $mStmt->fetch();

if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic profile not found.'], 404);
}

$mechanicId = (int) $mechanic['id'];

// Completed jobs count
$jobsStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE mechanic_id = :mid AND status = 'completed'");
$jobsStmt->execute(['mid' => $mechanicId]);
$jobsDone = (int) $jobsStmt->fetchColumn();

// Estimate today's earnings from completed bookings today
$earningsStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(g.price_min), 0)
     FROM bookings b
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status = 'completed' AND DATE(b.updated_at) = CURDATE()"
);
$earningsStmt->execute(['mid' => $mechanicId]);
$todayEarnings = (float) $earningsStmt->fetchColumn();

$yesterdayEarningsStmt = $pdo->prepare(
    "SELECT COALESCE(SUM(g.price_min), 0)
     FROM bookings b
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status = 'completed' AND DATE(b.updated_at) = CURDATE() - INTERVAL 1 DAY"
);
$yesterdayEarningsStmt->execute(['mid' => $mechanicId]);
$yesterdayEarnings = (float) $yesterdayEarningsStmt->fetchColumn();

$responseStmt = $pdo->prepare(
    "SELECT COUNT(*) AS total_requests,
            SUM(status IN ('accepted', 'en_route', 'completed')) AS answered_requests
     FROM bookings
     WHERE mechanic_id = :mid"
);
$responseStmt->execute(['mid' => $mechanicId]);
$response = $responseStmt->fetch();

$responseRate = null;
if ((int) $response['total_requests'] > 0) {
    $responseRate = round(((int) $response['answered_requests'] / (int) $response['total_requests']) * 100);
}

// Online status check (active within 5 minutes)
$isOnline = $mechanic['last_active'] ? (strtotime($mechanic['last_active']) > time() - 300) : false;

$rating = (float) $mechanic['avg_rating'] > 0 ? (float) $mechanic['avg_rating'] : null;
$earningsTrendPercent = null;
if ($yesterdayEarnings > 0) {
    $earningsTrendPercent = round((($todayEarnings - $yesterdayEarnings) / $yesterdayEarnings) * 100);
}

// Convert stored relative filesystem path to a web-accessible URL.
$rawPhoto = $mechanic['shop_photo_path'] ?: null;
$photoUrl = null;
if ($rawPhoto) {
    $cleaned  = ltrim(str_replace('../', '', $rawPhoto), '/');
    $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $photoUrl  = $scriptDir . '/' . $cleaned;
}

jsonResponse([
    'shopName'             => $mechanic['shop_name'] ?: null,
    'photo'                => $photoUrl,
    'isOnline'             => (bool) $isOnline,
    'todayEarnings'        => $todayEarnings,
    'earningsTrendPercent' => $earningsTrendPercent,
    'jobsDone'             => $jobsDone,
    'avgRating'            => $rating,
    'responseRate'         => $responseRate,
]);
