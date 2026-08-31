<?php
/**
 * Update a mechanic gig status or delete it.
 * Accepted JSON body: { "gig_id": 12, "action": "pause" | "resume" | "delete" }
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

if (($_SESSION['role'] ?? null) !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can update gigs.'], 403);
}

$payload = json_decode((string) file_get_contents('php://input'), true);
if (!is_array($payload)) {
    $payload = $_POST;
}

$gigId = filter_var($payload['gig_id'] ?? null, FILTER_VALIDATE_INT);
$action = strtolower((string) ($payload['action'] ?? ''));

if ($gigId === false || $gigId <= 0) {
    jsonResponse(['message' => 'Valid gig_id is required.'], 400);
}
if (!in_array($action, ['pause', 'resume', 'delete'], true)) {
    jsonResponse(['message' => 'Invalid action.'], 400);
}

$mechanicStmt = $pdo->prepare('SELECT id FROM mechanics WHERE user_id = :user_id');
$mechanicStmt->execute(['user_id' => $_SESSION['user_id']]);
$mechanic = $mechanicStmt->fetch();
if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic profile not found.'], 404);
}

if ($action === 'delete') {
    $deleteStmt = $pdo->prepare('DELETE FROM gigs WHERE id = :gig_id AND mechanic_id = :mechanic_id');
    $deleteStmt->execute([
        'gig_id' => $gigId,
        'mechanic_id' => (int) $mechanic['id'],
    ]);

    if ($deleteStmt->rowCount() === 0) {
        jsonResponse(['message' => 'Gig not found or not owned by this mechanic.'], 404);
    }

    jsonResponse(['success' => true, 'action' => 'delete']);
}

$activeValue = $action === 'resume' ? 1 : 0;
$updateStmt = $pdo->prepare('UPDATE gigs SET active = :active WHERE id = :gig_id AND mechanic_id = :mechanic_id');
$updateStmt->execute([
    'active' => $activeValue,
    'gig_id' => $gigId,
    'mechanic_id' => (int) $mechanic['id'],
]);

if ($updateStmt->rowCount() === 0) {
    jsonResponse(['message' => 'Gig not found or not owned by this mechanic.'], 404);
}

jsonResponse(['success' => true, 'action' => $action, 'active' => (bool) $activeValue]);
