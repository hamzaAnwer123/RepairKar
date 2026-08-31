<?php
/**
 * Return profile information for the authenticated user.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('GET');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
ensureUserPhotoColumn($pdo);

try {
    $stmt = $pdo->prepare("SELECT id, name, phone, email, role, city, photo_path, created_at FROM users WHERE id = :id");
    $stmt->execute(['id' => $currentUserId]);
    $user = $stmt->fetch();
} catch (PDOException $e) {
    error_log('get-user-profile.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Unable to load profile.'], 500);
}

if (!$user) {
    jsonResponse(['message' => 'User not found.'], 404);
}

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$defaultUserPhoto = $scriptDir . '/assets/images/placeholders/default-user-profile.png';

// Resolve the stored custom photo (if any) to a web-accessible URL,
// falling back to the shared placeholder.
$photoUrl = $defaultUserPhoto;
if (!empty($user['photo_path'])) {
    $cleaned = ltrim(str_replace('../', '', $user['photo_path']), '/');
    $photoUrl = $scriptDir . '/' . $cleaned;
}

jsonResponse([
    'id'           => (int) $user['id'],
    'name'         => $user['name'],
    'phone'        => $user['phone'] ?? '',
    'email'        => $user['email'] ?? '',
    'city'         => $user['city'] ?? '',
    'role'         => $user['role'],
    'created_at'   => $user['created_at'],
    'photo'        => $photoUrl,
    'has_custom_photo' => !empty($user['photo_path']),
]);
