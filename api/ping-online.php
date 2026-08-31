<?php
/**
 * Mechanic presence ping — updates last_active and optionally lat/lng.
 * Scopes the UPDATE to the logged-in mechanic's OWN row only, derived
 * from their session — never from an ID passed by the client.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

if (($_SESSION['role'] ?? null) !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can update presence.'], 403);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$isOnline = !empty($input['is_online']);

$lat = isset($input['lat']) ? filter_var($input['lat'], FILTER_VALIDATE_FLOAT) : null;
$lng = isset($input['lng']) ? filter_var($input['lng'], FILTER_VALIDATE_FLOAT) : null;
$accuracy = isset($input['accuracy']) ? filter_var($input['accuracy'], FILTER_VALIDATE_FLOAT) : null;

// A fix is only usable as the mechanic's position when the coordinates are
// valid AND accurate enough. Without this gate, an IP-level fix (which can
// be tens of kilometres off — sometimes the wrong city entirely) would
// overwrite a good position and the live map would show the mechanic
// somewhere they are not.
$coordsUsable = ($lat !== null && $lat !== false && $lng !== null && $lng !== false
    && $lat >= -90 && $lat <= 90 && $lng >= -180 && $lng <= 180)
    && ($accuracy === null || $accuracy <= MAX_ACCEPTED_GPS_ACCURACY_M);

// The position freshness column gates "live" results (live map + SOS);
// make sure it exists before writing to it.
ensureMechanicPositionAtColumn($pdo);

if ($isOnline) {
    if ($coordsUsable) {
        // position_at records WHEN the stored lat/lng was confirmed by the
        // mechanic's device — "live" results only trust fresh positions.
        $stmt = $pdo->prepare(
            "UPDATE mechanics
             SET last_active = NOW(), lat = :lat, lng = :lng, position_at = NOW()
             WHERE user_id = :user_id"
        );
        $stmt->execute(['lat' => $lat, 'lng' => $lng, 'user_id' => $_SESSION['user_id']]);
    } else {
        // Keep the mechanic's presence fresh but leave the stored position
        // untouched — a stale position_at ages the mechanic out of "live"
        // results instead of showing them at an outdated/wrong location.
        $stmt = $pdo->prepare("UPDATE mechanics SET last_active = NOW() WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $_SESSION['user_id']]);
    }
} else {
    // Going offline — push last_active into the past so the "online in
    // last 2 minutes" checks used elsewhere immediately treat them as
    // offline, rather than waiting for the 2-minute window to expire.
    $stmt = $pdo->prepare("UPDATE mechanics SET last_active = NOW() - INTERVAL 1 DAY WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $_SESSION['user_id']]);
}

jsonResponse(['success' => true]);