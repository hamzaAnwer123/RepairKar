<?php
/**
 * Return all jobs/bookings for the logged-in mechanic with status filters and counts.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];

// Get mechanic record
$mStmt = $pdo->prepare("SELECT id, shop_name FROM mechanics WHERE user_id = :uid");
$mStmt->execute(['uid' => $currentUserId]);
$mechanic = $mStmt->fetch();

if (!$mechanic) {
    jsonResponse([
        'bookings' => [],
        'counts'   => ['all' => 0, 'pending' => 0, 'active' => 0, 'completed' => 0, 'cancelled' => 0]
    ]);
}

$mechanicId = (int) $mechanic['id'];

// Get status filter and search query
$statusFilter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$search = isset($_GET['q']) ? trim($_GET['q']) : '';

// 1. Calculate counts for badges
$countStmt = $pdo->prepare(
    "SELECT status, COUNT(*) as cnt
     FROM bookings
     WHERE mechanic_id = :mid
     GROUP BY status"
);
$countStmt->execute(['mid' => $mechanicId]);
$statusRows = $countStmt->fetchAll();

$counts = [
    'all'       => 0,
    'pending'   => 0,
    'active'    => 0,
    'completed' => 0,
    'cancelled' => 0
];

foreach ($statusRows as $sr) {
    $st = $sr['status'];
    $cnt = (int) $sr['cnt'];
    $counts['all'] += $cnt;
    if (isset($counts[$st])) {
        $counts[$st] = $cnt;
    }
    if ($st === 'accepted' || $st === 'en_route') {
        $counts['active'] += $cnt;
    }
}

// 2. Build query for bookings list
$sql = "SELECT b.id, b.status, b.address, b.created_at, b.scheduled_time,
               u.name AS user_name, u.phone AS user_phone,
               g.title AS gig_title, g.price_min, g.price_max,
               m.category AS mechanic_category,
               r.rating, r.comment AS review_comment
        FROM bookings b
        JOIN users u ON u.id = b.user_id
        JOIN mechanics m ON m.id = b.mechanic_id
        LEFT JOIN gigs g ON g.id = b.gig_id
        LEFT JOIN reviews r ON r.booking_id = b.id
        WHERE b.mechanic_id = :mid";

$params = ['mid' => $mechanicId];

if ($statusFilter === 'pending') {
    $sql .= " AND b.status = 'pending'";
} elseif ($statusFilter === 'active' || $statusFilter === 'accepted') {
    $sql .= " AND b.status IN ('accepted', 'en_route')";
} elseif ($statusFilter === 'completed') {
    $sql .= " AND b.status = 'completed'";
} elseif ($statusFilter === 'cancelled') {
    $sql .= " AND b.status = 'cancelled'";
}

if (!empty($search)) {
    $sql .= " AND (u.name LIKE :search OR g.title LIKE :search OR b.address LIKE :search OR m.category LIKE :search)";
    $params['search'] = '%' . $search . '%';
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$bookings = [];
foreach ($rows as $r) {
    $desc = $r['gig_title'] ?: ($r['mechanic_category'] ? ucfirst(str_replace('-', ' ', $r['mechanic_category'])) . ' Service' : 'General Repair Service');
    $bookings[] = [
        'id'            => (int) $r['id'],
        'status'        => $r['status'],
        'userName'      => $r['user_name'] ?: 'Customer',
        'userPhone'     => $r['user_phone'] ?: '',
        'description'   => $desc,
        'address'       => $r['address'] ?: 'Location not provided',
        'createdAt'     => $r['created_at'],
        'scheduledTime' => $r['scheduled_time'],
        'priceMin'      => $r['price_min'] !== null ? (float) $r['price_min'] : null,
        'priceMax'      => $r['price_max'] !== null ? (float) $r['price_max'] : null,
        'rating'        => $r['rating'] !== null ? (float) $r['rating'] : null,
        'reviewComment' => $r['review_comment'] ?: null,
    ];
}

jsonResponse([
    'bookings' => $bookings,
    'counts'   => $counts,
]);
?>
