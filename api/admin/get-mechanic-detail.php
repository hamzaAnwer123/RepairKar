<?php
/**
 * Returns full application detail for one mechanic. Requires role='admin'.
 * Re-checks role server-side independently of the page that links here —
 * an API endpoint must never rely on the UI hiding a button as its only
 * protection.
 */

header('Content-Type: application/json');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id']) || ($_SESSION['role'] ?? null) !== 'admin') {
    http_response_code(403);
    echo json_encode(['message' => 'Forbidden']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['message' => 'Method not allowed']);
    exit;
}

require_once __DIR__ . '/../../includes/db.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    echo json_encode(['message' => 'Invalid mechanic id']);
    exit;
}

$stmt = $pdo->prepare(
    "SELECT m.shop_name, m.category, m.bio, m.cnic_doc_path, m.shop_photo_path,
            u.name AS owner_name, u.phone AS owner_phone
     FROM mechanics m
     JOIN users u ON u.id = m.user_id
     WHERE m.id = :id"
);
$stmt->execute(['id' => $id]);
$row = $stmt->fetch();

if (!$row) {
    http_response_code(404);
    echo json_encode(['message' => 'Mechanic not found']);
    exit;
}

echo json_encode([
    'shopName'   => $row['shop_name'],
    'category'   => $row['category'],
    'bio'        => $row['bio'],
    'cnicDoc'    => $row['cnic_doc_path'],
    'shopPhoto'  => $row['shop_photo_path'],
    'ownerName'  => $row['owner_name'],
    'ownerPhone' => $row['owner_phone'],
]);