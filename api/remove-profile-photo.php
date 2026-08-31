<?php
/**
 * Remove the authenticated user's custom profile photo and fall back
 * to the shared default placeholder.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
ensureUserPhotoColumn($pdo);

$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$defaultPhotoUrl = $scriptDir . '/assets/images/placeholders/default-user-profile.png';

try {
    $oldStmt = $pdo->prepare("SELECT photo_path FROM users WHERE id = :uid");
    $oldStmt->execute(['uid' => $currentUserId]);
    $oldPath = (string) ($oldStmt->fetchColumn() ?: '');

    $updateStmt = $pdo->prepare("UPDATE users SET photo_path = NULL WHERE id = :uid");
    $updateStmt->execute(['uid' => $currentUserId]);

    // Delete the old file — but ONLY if it lives inside uploads/profiles
    // (guards against path traversal via a tampered DB value).
    if ($oldPath !== '') {
        $oldReal = realpath(__DIR__ . '/' . $oldPath);
        $dirReal = realpath(__DIR__ . '/../assets/uploads/profiles/');
        if ($oldReal && $dirReal && strpos($oldReal, $dirReal) === 0 && is_file($oldReal)) {
            @unlink($oldReal);
        }
    }
} catch (PDOException $e) {
    error_log('remove-profile-photo.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Could not remove the profile photo. Please try again.'], 500);
}

jsonResponse(['success' => true, 'message' => 'Profile photo removed.', 'photo' => $defaultPhotoUrl]);
