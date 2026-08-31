<?php
/**
 * Returns all gigs owned by the logged-in mechanic.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

if (($_SESSION['role'] ?? null) !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can view gigs.'], 403);
}

$mechanicStmt = $pdo->prepare('SELECT id FROM mechanics WHERE user_id = :user_id');
$mechanicStmt->execute(['user_id' => $_SESSION['user_id']]);
$mechanic = $mechanicStmt->fetch();

if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic profile not found.'], 404);
}

$gigsStmt = $pdo->prepare(
    'SELECT id, title, description, price_min, price_max, photo_path, active, created_at
     FROM gigs
     WHERE mechanic_id = :mechanic_id
     ORDER BY created_at DESC'
);
$gigsStmt->execute(['mechanic_id' => (int) $mechanic['id']]);
$gigs = $gigsStmt->fetchAll();

jsonResponse([
    'gigs' => array_map(static function (array $gig): array {
        return [
            'id' => (int) $gig['id'],
            'title' => $gig['title'],
            'description' => $gig['description'],
            'priceMin' => (float) $gig['price_min'],
            'priceMax' => $gig['price_max'] !== null ? (float) $gig['price_max'] : null,
            'photo' => $gig['photo_path'],
            'active' => (bool) $gig['active'],
            'createdAt' => $gig['created_at'],
        ];
    }, $gigs),
]);
