<?php
/**
 * Create a new booking. Requires an active user session. Supports both
 * a normal gig-based booking and an 'emergency' SOS booking from
 * user/live-map.html (no gig_id in that case).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

if (($_SESSION['role'] ?? '') !== 'user') {
    jsonResponse(['message' => 'Only customer accounts can create bookings.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$mechanicId = filter_var($input['mechanic_id'] ?? null, FILTER_VALIDATE_INT);
$gigId = isset($input['gig_id']) && $input['gig_id'] !== '' ? filter_var($input['gig_id'], FILTER_VALIDATE_INT) : null;
$lat = filter_var($input['lat'] ?? null, FILTER_VALIDATE_FLOAT);
$lng = filter_var($input['lng'] ?? null, FILTER_VALIDATE_FLOAT);
$address = trim((string) ($input['address'] ?? ''));
$scheduledTime = $input['scheduled_time'] ?? null;
$isEmergency = ($input['type'] ?? null) === 'emergency';

if ($lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
    jsonResponse(['message' => 'Invalid location.'], 400);
}

// ---- Emergency (SOS) booking: find the nearest online, verified mechanic
// ---- with a FRESH confirmed position, inside the same service radius the
// ---- live map uses. Dispatching an SOS to a mechanic whose "live
// ---- location" is actually an old fix from another city would send help
// ---- 155 km in the wrong direction.
if ($isEmergency) {
    ensureMechanicPositionAtColumn($pdo);
    $nearbyStmt = $pdo->query(
        "SELECT id, lat, lng FROM mechanics
         WHERE verified = 1 AND rejected = 0 AND last_active > NOW() - INTERVAL 2 MINUTE
           AND position_at > NOW() - INTERVAL " . (int) POSITION_FRESH_WINDOW_MIN . " MINUTE
           AND lat IS NOT NULL AND lng IS NOT NULL"
    );
    $candidates = $nearbyStmt->fetchAll();
    if (!empty($candidates)) {
        usort($candidates, fn($a, $b) =>
            haversineKm((float) $lat, (float) $lng, (float) $a['lat'], (float) $a['lng'])
            <=> haversineKm((float) $lat, (float) $lng, (float) $b['lat'], (float) $b['lng'])
        );
        // Drop candidates outside the service radius.
        $candidates = array_filter($candidates, fn($c) =>
            haversineKm((float) $lat, (float) $lng, (float) $c['lat'], (float) $c['lng']) <= NEARBY_MECHANIC_RADIUS_KM
        );
    }
    if (empty($candidates)) {
        jsonResponse([
            'message' => 'No mechanics are currently available within ' . (int) NEARBY_MECHANIC_RADIUS_KM . ' km. Please try again shortly.',
        ], 404);
    }
    $mechanicId = (int) $candidates[0]['id'];
    $address = $address !== '' ? $address : 'Live location (roadside assistance)';
} else {
    if (!$mechanicId) {
        jsonResponse(['message' => 'A mechanic must be selected.'], 400);
    }
    if (mb_strlen($address) < 5) {
        jsonResponse(['message' => 'Please enter your full address.'], 400);
    }
}

// ---- Verify the mechanic actually exists and is a real, active mechanic ----
$mechStmt = $pdo->prepare(
    "SELECT id FROM mechanics
     WHERE id = :id
       AND (verified = 1 OR (SELECT COUNT(*) FROM gigs g WHERE g.mechanic_id = mechanics.id AND g.active = 1) > 0)
       AND rejected = 0"
);
$mechStmt->execute(['id' => $mechanicId]);
if (!$mechStmt->fetch()) {
    jsonResponse(['message' => 'Selected mechanic is not available.'], 404);
}

// ---- If a gig was specified, confirm it actually belongs to this mechanic ----
if ($gigId !== null) {
    $gigStmt = $pdo->prepare("SELECT id FROM gigs WHERE id = :gig_id AND mechanic_id = :mechanic_id AND active = 1");
    $gigStmt->execute(['gig_id' => $gigId, 'mechanic_id' => $mechanicId]);
    if (!$gigStmt->fetch()) {
        jsonResponse(['message' => 'Selected service is not available from this mechanic.'], 400);
    }
}

$scheduledTimeSql = null;
if ($scheduledTime) {
    $ts = strtotime($scheduledTime);
    if ($ts === false || $ts < time()) {
        jsonResponse(['message' => 'Please choose a valid future date and time.'], 400);
    }
    $scheduledTimeSql = date('Y-m-d H:i:s', $ts);
}

$insertStmt = $pdo->prepare(
    "INSERT INTO bookings (user_id, mechanic_id, gig_id, status, lat, lng, address, scheduled_time, created_at)
     VALUES (:user_id, :mechanic_id, :gig_id, 'pending', :lat, :lng, :address, :scheduled_time, NOW())"
);
$insertStmt->execute([
    'user_id'        => $_SESSION['user_id'],
    'mechanic_id'    => $mechanicId,
    'gig_id'         => $gigId,
    'lat'            => $lat,
    'lng'            => $lng,
    'address'        => $address,
    'scheduled_time' => $scheduledTimeSql,
]);

jsonResponse(['success' => true, 'booking_id' => (int) $pdo->lastInsertId()], 201);