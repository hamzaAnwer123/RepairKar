<?php
/**
 * Return the logged-in mechanic's OWN profile details.
 * Used by mechanic/profile.html to populate the edit form on load.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

if (($_SESSION['role'] ?? '') !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can view this profile.'], 403);
}

$currentUserId = (int) $_SESSION['user_id'];

$stmt = $pdo->prepare(
    "SELECT shop_name, category, bio, address, shop_photo_path, verified
     FROM mechanics
     WHERE user_id = :uid"
);
$stmt->execute(['uid' => $currentUserId]);
$mechanic = $stmt->fetch();

if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic profile not found.'], 404);
}

// Convert stored relative filesystem path to a web-accessible URL.
// Stored as e.g. "../assets/uploads/shops/abc.jpg" (relative to /api/).
// We normalise it to an absolute URL path: "/RepairKar-fixed-v7/assets/uploads/shops/abc.jpg".
$rawPhoto = $mechanic['shop_photo_path'] ?: null;
$photoUrl = null;
if ($rawPhoto) {
    // Strip any leading "../" segments to get the path from the web root.
    $cleaned = ltrim(str_replace('../', '', $rawPhoto), '/');
    // Build the base URL (scheme + host + script base directory up to project root).
    $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $photoUrl  = $scriptDir . '/' . $cleaned;
}

jsonResponse([
    'shopName'   => $mechanic['shop_name'],
    'category'   => $mechanic['category'],
    'bio'        => $mechanic['bio'] ?: '',
    'address'    => $mechanic['address'] ?: '',
    'photo'      => $photoUrl,
    'isVerified' => (bool) $mechanic['verified'],
]);
