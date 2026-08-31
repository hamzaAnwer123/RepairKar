<?php
/**
 * Nearby ONLINE mechanics for the live roadside/SOS map
 * (user/live-map.html). Different from get-mechanics.php:
 * - Only returns mechanics active in the last 2 minutes (truly "nearby
 *   right now", not just registered somewhere)
 * - Includes phone number directly (needed for the one-tap "Call
 *   Mechanic" button on that screen) and an ETA estimate
 * - No category filter — an emergency doesn't know what's broken yet
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$lat = filter_input(INPUT_GET, 'lat', FILTER_VALIDATE_FLOAT);
$lng = filter_input(INPUT_GET, 'lng', FILTER_VALIDATE_FLOAT);

if ($lat === false || $lat === null || $lng === false || $lng === null
    || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    jsonResponse(['message' => 'Valid lat/lng are required.'], 400);
}

// Only mechanics active in the last 2 minutes AND with a known position
// that is itself FRESH. The position_at gate is what prevents the
// "mechanic shown in the wrong city" problem: presence can stay fresh
// while a device's GPS fails, but the stored position then ages out of
// these results instead of showing a mechanic at an outdated location.
ensureMechanicPositionAtColumn($pdo);
$stmt = $pdo->prepare(
    "SELECT m.id, m.shop_name, m.category, m.lat, m.lng, m.avg_rating, m.address,
            m.shop_photo_path, u.phone, u.photo_path AS user_photo
     FROM mechanics m
     JOIN users u ON u.id = m.user_id
     WHERE m.verified = 1 AND m.rejected = 0
       AND m.last_active > NOW() - INTERVAL 2 MINUTE
       AND m.position_at > NOW() - INTERVAL " . (int) POSITION_FRESH_WINDOW_MIN . " MINUTE
       AND m.lat IS NOT NULL AND m.lng IS NOT NULL
       AND u.phone IS NOT NULL"
);
$stmt->execute();
$rows = $stmt->fetchAll();

$AVERAGE_SPEED_KMH = 30; // rough city-driving estimate for the ETA shown on the SOS screen

// Photo URL building mirrors api/get-mechanics.php: stored paths are
// relative to the site pages ('../assets/...' or '/assets/...'), so they
// are normalised to a root-absolute URL that works from any page depth.
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');

$mechanics = array_map(function ($m) use ($lat, $lng, $AVERAGE_SPEED_KMH, $scriptDir) {
    $distanceKm = haversineKm((float) $lat, (float) $lng, (float) $m['lat'], (float) $m['lng']);

    $photoUrl = null;
    foreach ([$m['shop_photo_path'], $m['user_photo']] as $stored) {
        if (!empty($stored)) {
            $photoUrl = $scriptDir . '/' . ltrim(str_replace('../', '', (string) $stored), '/');
            break;
        }
    }

    return [
        'id'          => (int) $m['id'],
        'shopName'    => $m['shop_name'],
        'category'    => $m['category'],
        'address'     => $m['address'] ?: null,
        'photo'       => $photoUrl,
        'lat'         => (float) $m['lat'],
        'lng'         => (float) $m['lng'],
        'rating'      => $m['avg_rating'] !== null ? (float) $m['avg_rating'] : null,
        'phone'       => $m['phone'],
        'distanceKm'  => $distanceKm,
        'etaMinutes'  => max(1, (int) round(($distanceKm / $AVERAGE_SPEED_KMH) * 60)),
        'isOnline'    => true, // query already filters to online-only
    ];
}, $rows);

// Nearest first, keep only mechanics inside the service radius (a mechanic
// 155 km away in another city is not roadside assistance), then cap to the
// 10 closest.
usort($mechanics, fn($a, $b) => $a['distanceKm'] <=> $b['distanceKm']);
$mechanics = array_values(array_filter(
    $mechanics,
    fn($m) => $m['distanceKm'] <= NEARBY_MECHANIC_RADIUS_KM
));
$mechanics = array_slice($mechanics, 0, 10);

jsonResponse([
    'mechanics'      => $mechanics,
    'searchRadiusKm' => NEARBY_MECHANIC_RADIUS_KM,
]);