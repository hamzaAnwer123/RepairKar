<?php
/**
 * Return earnings derived from completed mechanic bookings.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$period = $_GET['period'] ?? 'all';
$allowedPeriods = ['today', 'week', 'month', 'all'];
if (!in_array($period, $allowedPeriods, true)) $period = 'all';

$mechanicStmt = $pdo->prepare('SELECT id FROM mechanics WHERE user_id = :uid');
$mechanicStmt->execute(['uid' => $currentUserId]);
$mechanicId = $mechanicStmt->fetchColumn();
if (!$mechanicId) jsonResponse(['message' => 'Mechanic profile not found.'], 404);

$dateCondition = match ($period) {
    'today' => ' AND DATE(b.updated_at) = CURDATE()',
    'week' => ' AND b.updated_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)',
    'month' => ' AND b.updated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)',
    default => '',
};

$summaryStmt = $pdo->prepare(
    "SELECT COUNT(*) AS completed_jobs,
            COALESCE(SUM(COALESCE(g.price_min, 0)), 0) AS total_earnings,
            COALESCE(AVG(COALESCE(g.price_min, 0)), 0) AS average_job
     FROM bookings b
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status = 'completed' {$dateCondition}"
);
$summaryStmt->execute(['mid' => (int) $mechanicId]);
$summary = $summaryStmt->fetch();

$pendingStmt = $pdo->prepare("SELECT COUNT(*) FROM bookings WHERE mechanic_id = :mid AND status IN ('pending', 'accepted', 'en_route')");
$pendingStmt->execute(['mid' => (int) $mechanicId]);

$transactionsStmt = $pdo->prepare(
    "SELECT b.id, b.updated_at, b.address, u.name AS customer_name,
            g.title AS service_title, COALESCE(g.price_min, 0) AS amount
     FROM bookings b
     JOIN users u ON u.id = b.user_id
     LEFT JOIN gigs g ON g.id = b.gig_id
     WHERE b.mechanic_id = :mid AND b.status = 'completed' {$dateCondition}
     ORDER BY b.updated_at DESC
     LIMIT 50"
);
$transactionsStmt->execute(['mid' => (int) $mechanicId]);

$transactions = [];
foreach ($transactionsStmt->fetchAll() as $row) {
    $transactions[] = [
        'bookingId' => (int) $row['id'],
        'date' => $row['updated_at'],
        'customerName' => $row['customer_name'] ?: 'Customer',
        'serviceTitle' => $row['service_title'] ?: 'Repair service',
        'address' => $row['address'] ?: 'Service location',
        'amount' => (float) $row['amount'],
    ];
}

jsonResponse([
    'period' => $period,
    'summary' => [
        'totalEarnings' => (float) $summary['total_earnings'],
        'completedJobs' => (int) $summary['completed_jobs'],
        'averageJob' => (float) $summary['average_job'],
        'activeBookings' => (int) $pendingStmt->fetchColumn(),
    ],
    'transactions' => $transactions,
]);
