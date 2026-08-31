<?php
/**
 * Update authenticated user or mechanic profile information.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$name = trim((string) ($input['name'] ?? ''));
$email = trim((string) ($input['email'] ?? ''));
$city = trim((string) ($input['city'] ?? ''));

if ($name === '' || mb_strlen($name) < 2) {
    jsonResponse(['message' => 'Please provide a valid full name.'], 400);
}

if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    jsonResponse(['message' => 'Please provide a valid email address.'], 400);
}

// Check if email already in use by another user
if ($email !== '') {
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE email = :email AND id != :uid");
    $checkStmt->execute(['email' => strtolower($email), 'uid' => $currentUserId]);
    if ($checkStmt->fetch()) {
        jsonResponse(['message' => 'This email address is already in use.'], 409);
    }
}

// Update users table
$updateStmt = $pdo->prepare(
    "UPDATE users SET name = :name, email = :email, city = :city WHERE id = :uid"
);
$updateStmt->execute([
    'name'  => $name,
    'email' => $email !== '' ? strtolower($email) : null,
    'city'  => $city !== '' ? $city : null,
    'uid'   => $currentUserId,
]);

$_SESSION['name'] = $name;

// If mechanic, also update mechanic-specific fields if passed
if (($_SESSION['role'] ?? '') === 'mechanic') {
    $shopName = trim((string) ($input['shop_name'] ?? $name));
    $bio = trim((string) ($input['bio'] ?? ''));
    if ($shopName !== '') {
        $mUpdate = $pdo->prepare("UPDATE mechanics SET shop_name = :sname, bio = :bio WHERE user_id = :uid");
        $mUpdate->execute(['sname' => $shopName, 'bio' => $bio, 'uid' => $currentUserId]);
    }
}

jsonResponse(['success' => true, 'message' => 'Profile updated successfully.', 'name' => $name]);
