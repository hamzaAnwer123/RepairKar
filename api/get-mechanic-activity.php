<?php
/**
 * Return recent activity for the logged-in mechanic (completed jobs, reviews, etc.).
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

$mStmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = :uid");
$mStmt->execute(['uid' => $currentUserId]);
$mechanicId = $mStmt->fetchColumn();

if (!$mechanicId) {
    jsonResponse(['activity' => []]);
}

$stmt = $pdo->prepare(
    "SELECT b.id, b.status, b.updated_at, u.name AS user_name, g.title AS gig_title, g.price_min
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status IN ('completed', 'accepted', 'en_route')
     ORDER BY b.updated_at DESC LIMIT 10"
);
$stmt->execute(['mid' => $mechanicId]);
$rows = $stmt->fetchAll();

$activity = [];
foreach ($rows as $r) {
    $timeAgo = 'Recently';
    $ts = strtotime($r['updated_at']);
    if ($ts) {
        $diff = time() - $ts;
        if ($diff < 3600) {
            $timeAgo = max(1, (int) round($diff / 60)) . ' mins ago';
        } elseif ($diff < 86400) {
            $timeAgo = (int) round($diff / 3600) . ' hours ago';
        } else {
            $timeAgo = (int) round($diff / 86400) . ' days ago';
        }
    }

    $title = $r['status'] === 'completed' ? 'Job Completed' : 'Service in Progress';
    $desc = ($r['gig_title'] ?: 'Repair Service') . ' for ' . ($r['user_name'] ?: 'Customer');

    $activity[] = [
        'type'        => $r['status'] === 'completed' ? 'completed' : 'payout',
        'title'       => $title,
        'description' => $desc,
        'amount'      => $r['status'] === 'completed' && $r['price_min'] ? (float) $r['price_min'] : null,
        'timeAgo'     => $timeAgo,
    ];
}

jsonResponse(['activity' => $activity]);
