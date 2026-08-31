<?php
/**
 * Booking stats for the authenticated user's profile page.
 * Returns status counts plus the account's member-since date.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

try {
    $stmt = $pdo->prepare(
        "SELECT status, COUNT(*) AS total
         FROM bookings
         WHERE user_id = :uid
         GROUP BY status"
    );
    $stmt->execute(['uid' => $currentUserId]);
    $rows = $stmt->fetchAll();

    $createdStmt = $pdo->prepare("SELECT created_at FROM users WHERE id = :uid");
    $createdStmt->execute(['uid' => $currentUserId]);
    $createdAt = (string) ($createdStmt->fetchColumn() ?: '');
} catch (PDOException $e) {
    error_log('get-user-stats.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Unable to load stats.'], 500);
}

$total = 0;
$completed = 0;
$cancelled = 0;
$active = 0;

foreach ($rows as $r) {
    $count = (int) $r['total'];
    $total += $count;
    switch ($r['status']) {
        case 'completed':
            $completed += $count;
            break;
        case 'cancelled':
            $cancelled += $count;
            break;
        case 'pending':
        case 'accepted':
        case 'en_route':
            $active += $count;
            break;
    }
}

jsonResponse([
    'total_bookings' => $total,
    'completed'      => $completed,
    'cancelled'      => $cancelled,
    'active'         => $active,
    'created_at'     => $createdAt,
]);
