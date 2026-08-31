<?php
/**
 * Change password for the authenticated user or mechanic.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$currentPassword = (string) ($input['current_password'] ?? '');
$newPassword = (string) ($input['new_password'] ?? '');

if ($currentPassword === '' || $newPassword === '') {
    jsonResponse(['message' => 'Please enter your current and new password.'], 400);
}

if (mb_strlen($newPassword) < 8) {
    jsonResponse(['message' => 'New password must be at least 8 characters.'], 400);
}

// Fetch current password hash
$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
$stmt->execute(['id' => $currentUserId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    jsonResponse(['message' => 'Current password is incorrect.'], 401);
}

// Update password
$newHash = password_hash($newPassword, PASSWORD_DEFAULT);
$updateStmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
$updateStmt->execute(['hash' => $newHash, 'id' => $currentUserId]);

jsonResponse(['success' => true, 'message' => 'Password changed successfully.']);
