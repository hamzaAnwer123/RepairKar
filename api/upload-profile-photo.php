<?php
/**
 * Upload (or replace) the authenticated user's profile photo.
 * Mirrors update-mechanic-profile.php: multipart POST, finfo-validated
 * image, random filename, old custom photo removed after a successful save.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
ensureUserPhotoColumn($pdo);

if (empty($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['message' => 'Please choose a photo to upload.'], 400);
}

$file = $_FILES['photo'];

// Validate the REAL content type — never trust the client-supplied mime.
$finfo = new finfo(FILEINFO_MIME_TYPE);
$realMime = $finfo->file($file['tmp_name']);
$allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

if (!isset($allowedMimes[$realMime])) {
    jsonResponse(['message' => 'Photo must be a JPG, PNG, or WebP image.'], 400);
}
if ($file['size'] > 5 * 1024 * 1024) {
    jsonResponse(['message' => 'Photo must be smaller than 5MB.'], 400);
}
// Extra guard: reject files that are not decodable images at all.
if (@getimagesize($file['tmp_name']) === false) {
    jsonResponse(['message' => 'That file does not look like a valid image.'], 400);
}

$uploadDir = __DIR__ . '/../assets/uploads/profiles/';
if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$realMime];
if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
    jsonResponse(['message' => 'Could not save the uploaded photo. Please try again.'], 500);
}

$photoPath = '../assets/uploads/profiles/' . $filename;

try {
    // Keep the previous path so the old file can be cleaned up after the update.
    $oldStmt = $pdo->prepare("SELECT photo_path FROM users WHERE id = :uid");
    $oldStmt->execute(['uid' => $currentUserId]);
    $oldPath = (string) ($oldStmt->fetchColumn() ?: '');

    $updateStmt = $pdo->prepare("UPDATE users SET photo_path = :photo WHERE id = :uid");
    $updateStmt->execute(['photo' => $photoPath, 'uid' => $currentUserId]);

    // Delete the previous CUSTOM photo (only files inside uploads/profiles).
    if ($oldPath !== '') {
        $oldReal = realpath(__DIR__ . '/' . $oldPath);
        $dirReal = realpath($uploadDir);
        if ($oldReal && $dirReal && strpos($oldReal, $dirReal) === 0 && is_file($oldReal)) {
            @unlink($oldReal);
        }
    }
} catch (PDOException $e) {
    error_log('upload-profile-photo.php query failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Could not save the uploaded photo. Please try again.'], 500);
}

// Return a web-accessible URL for the browser (same convention as the
// mechanic photo endpoints).
$cleaned   = ltrim(str_replace('../', '', $photoPath), '/');
$scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
$photoUrl  = $scriptDir . '/' . $cleaned;

jsonResponse(['success' => true, 'message' => 'Profile photo updated.', 'photo' => $photoUrl]);
