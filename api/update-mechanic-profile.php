<?php
/**
 * Update mechanic profile details and optional shop photo.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

if (($_SESSION['role'] ?? '') !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can update this profile.'], 403);
}

$currentUserId = (int) $_SESSION['user_id'];
$isMultipart = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');

// Handle photo upload
if ($isMultipart && !empty($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $file = $_FILES['photo'];
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $realMime = $finfo->file($file['tmp_name']);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($realMime, $allowedMimes, true)) {
        jsonResponse(['message' => 'File must be an image (JPEG, PNG, or WebP).'], 400);
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        jsonResponse(['message' => 'Image must be smaller than 5MB.'], 400);
    }
    if (@getimagesize($file['tmp_name']) === false) {
        jsonResponse(['message' => 'That file does not look like a valid image.'], 400);
    }

    $uploadDir = __DIR__ . '/../assets/uploads/shops/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $ext = match ($realMime) {
        'image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp',
    };
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $uploadDir . $filename)) {
        jsonResponse(['message' => 'Could not save uploaded photo.'], 500);
    }

    $photoPath = '../assets/uploads/shops/' . $filename;
    $pStmt = $pdo->prepare("UPDATE mechanics SET shop_photo_path = :photo WHERE user_id = :uid");
    $pStmt->execute(['photo' => $photoPath, 'uid' => $currentUserId]);

    // Return a web-accessible URL for the browser.
    $cleaned   = ltrim(str_replace('../', '', $photoPath), '/');
    $scriptDir = rtrim(dirname(dirname($_SERVER['SCRIPT_NAME'])), '/\\');
    $photoUrl  = $scriptDir . '/' . $cleaned;

    jsonResponse(['success' => true, 'photo' => $photoUrl]);
}

// Handle JSON text details, while also supporting form-encoded payloads.
$rawBody = (string) file_get_contents('php://input');
$input = [];
if ($rawBody !== '') {
    $decoded = json_decode($rawBody, true);
    if (is_array($decoded)) {
        $input = $decoded;
    }
}
if ($input === [] && !empty($_POST)) {
    $input = $_POST;
}

$shopName = trim((string) ($input['shop_name'] ?? ''));
$category = trim((string) ($input['category'] ?? ''));
$bio      = trim((string) ($input['bio'] ?? ''));
$address  = trim((string) ($input['address'] ?? ''));

if ($shopName === '') {
    jsonResponse(['message' => 'Shop name is required.'], 400);
}

$stmt = $pdo->prepare(
    "UPDATE mechanics SET shop_name = :sname, category = :cat, bio = :bio, address = :addr WHERE user_id = :uid"
);
$stmt->execute([
    'sname' => $shopName,
    'cat'   => $category ?: 'car',
    'bio'   => $bio,
    'addr'  => $address !== '' ? $address : null,
    'uid'   => $currentUserId,
]);

// Also keep users table name updated
$uStmt = $pdo->prepare("UPDATE users SET name = :name WHERE id = :uid");
$uStmt->execute(['name' => $shopName, 'uid' => $currentUserId]);
$_SESSION['name'] = $shopName;

jsonResponse(['success' => true, 'message' => 'Shop details updated successfully.']);
