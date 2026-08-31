<?php
/**
 * Create a new gig listing. Requires an active mechanic session.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

if (($_SESSION['role'] ?? null) !== 'mechanic') {
    jsonResponse(['message' => 'Only mechanics can create gigs.'], 403);
}

$mechStmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = :user_id");
$mechStmt->execute(['user_id' => $_SESSION['user_id']]);
$mechanic = $mechStmt->fetch();
if (!$mechanic) {
    jsonResponse(['message' => 'Mechanic profile not found.'], 404);
}
$mechanicId = (int) $mechanic['id'];

$categoryWhitelist = ['washing-machine', 'fridge', 'ac', 'car', 'bike', 'electrician', 'plumber', 'generator', 'towing', 'tire', 'battery'];
$category = $_POST['category'] ?? '';
$title = trim((string) ($_POST['title'] ?? ''));
$description = trim((string) ($_POST['description'] ?? ''));
$priceMin = filter_var($_POST['price_min'] ?? null, FILTER_VALIDATE_FLOAT);
$priceMax = isset($_POST['price_max']) && $_POST['price_max'] !== '' ? filter_var($_POST['price_max'], FILTER_VALIDATE_FLOAT) : null;

if (!in_array($category, $categoryWhitelist, true)) {
    jsonResponse(['message' => 'Invalid category.'], 400);
}
if (mb_strlen($title) < 5 || mb_strlen($title) > 150) {
    jsonResponse(['message' => 'Title must be between 5 and 150 characters.'], 400);
}
if (mb_strlen($description) < 20 || mb_strlen($description) > 1000) {
    jsonResponse(['message' => 'Description must be between 20 and 1000 characters.'], 400);
}
if ($priceMin === false || $priceMin <= 0) {
    jsonResponse(['message' => 'Enter a valid minimum price.'], 400);
}
if ($priceMax !== null && $priceMax < $priceMin) {
    jsonResponse(['message' => 'Maximum price must be greater than or equal to the minimum.'], 400);
}
// Schema has price_max NOT NULL — default to price_min when not provided
if ($priceMax === null) {
    $priceMax = $priceMin;
}

// ---- Photo upload (optional, first photo only for the schema's single photo_path column) ----
$photoPath = null;
if (!empty($_FILES['photos']) && !empty($_FILES['photos']['tmp_name'][0])) {
    $tmpName = $_FILES['photos']['tmp_name'][0];
    $error = $_FILES['photos']['error'][0];

    if ($error === UPLOAD_ERR_OK) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $realMime = $finfo->file($tmpName);
        $allowedMimes = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'];

        if (!isset($allowedMimes[$realMime])) {
            jsonResponse(['message' => 'Photo must be a real image (jpg, png, or webp).'], 400);
        }
        if ($_FILES['photos']['size'][0] > 5 * 1024 * 1024) {
            jsonResponse(['message' => 'Each photo must be smaller than 5MB.'], 400);
        }
        if (@getimagesize($tmpName) === false) {
            jsonResponse(['message' => 'That file does not look like a valid image.'], 400);
        }

        $uploadDir = __DIR__ . '/../assets/uploads/gigs/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        $filename = bin2hex(random_bytes(16)) . '.' . $allowedMimes[$realMime];
        if (move_uploaded_file($tmpName, $uploadDir . $filename)) {
            $photoPath = '/assets/uploads/gigs/' . $filename;
        }
    }
}

$insertStmt = $pdo->prepare(
    "INSERT INTO gigs (mechanic_id, title, description, price_min, price_max, photo_path, active, created_at)
     VALUES (:mechanic_id, :title, :description, :price_min, :price_max, :photo_path, 1, NOW())"
);
$insertStmt->execute([
    'mechanic_id' => $mechanicId,
    'title'       => $title,
    'description' => $description,
    'price_min'   => $priceMin,
    'price_max'   => $priceMax,
    'photo_path'  => $photoPath,
]);

// Keep mechanic category in sync
try {
    $updateCategory = $pdo->prepare("UPDATE mechanics SET category = :category WHERE id = :id");
    $updateCategory->execute(['category' => $category, 'id' => $mechanicId]);
} catch (Exception $e) {
    // Non-fatal
}

jsonResponse(['success' => true, 'gig_id' => (int) $pdo->lastInsertId()], 201);