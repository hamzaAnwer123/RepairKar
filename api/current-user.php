<?php
/**
 * Return the authenticated user's basic session identity for client navigation.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$role = (string) ($_SESSION['role'] ?? '');
$name = (string) ($_SESSION['name'] ?? '');
$phone = '';
$email = '';
$city = '';
$photo = null;
$createdAt = '';
$userPhotoPath = '';

try {
    $userStmt = $pdo->prepare("SELECT name, phone, email, role, city, photo_path, created_at FROM users WHERE id = :id");
    $userStmt->execute(['id' => $currentUserId]);
    $dbUser = $userStmt->fetch();
    if ($dbUser) {
        if (!empty($dbUser['name'])) {
            $name = (string) $dbUser['name'];
        }
        $phone = (string) ($dbUser['phone'] ?? '');
        $email = (string) ($dbUser['email'] ?? '');
        $city = (string) ($dbUser['city'] ?? '');
        $createdAt = (string) ($dbUser['created_at'] ?? '');
        $userPhotoPath = (string) ($dbUser['photo_path'] ?? '');
        if (!empty($dbUser['role'])) {
            $role = (string) $dbUser['role'];
        }
    }
} catch (PDOException $e) {
    error_log('current-user.php user lookup failed: ' . $e->getMessage());
}

// Regular users get their own custom photo (mechanics keep the shop photo).
if ($role !== 'mechanic' && $userPhotoPath !== '') {
    $cleaned = ltrim(str_replace('../', '', $userPhotoPath), '/');
    $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $photo = $scriptDir . '/' . $cleaned;
}

if ($role === 'mechanic') {
    $stmt = $pdo->prepare("SELECT shop_name, shop_photo_path FROM mechanics WHERE user_id = :uid");
    $stmt->execute(['uid' => $currentUserId]);
    $mechanic = $stmt->fetch();
    if ($mechanic) {
        if (!empty($mechanic['shop_name'])) {
            $name = $mechanic['shop_name'];
        }
        $rawPhoto = $mechanic['shop_photo_path'] ?: null;
        if ($rawPhoto) {
            $cleaned = ltrim(str_replace('../', '', $rawPhoto), '/');
            $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
            $photo = $scriptDir . '/' . $cleaned;
        }
    }
}

jsonResponse([
    'authenticated' => true,
    'id'            => $currentUserId,
    'user_id'       => $currentUserId,
    'name'          => $name,
    'phone'         => $phone,
    'email'         => $email,
    'city'          => $city,
    'role'          => $role,
    'photo'         => $photo,
    'created_at'    => $createdAt,
]);
