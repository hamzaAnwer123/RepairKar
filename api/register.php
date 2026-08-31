<?php
/**
 * User/mechanic registration. Accepts phone OR email signup (per the
 * dual signup.html flow). Passwords are hashed, never stored plain.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');

if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'secure'   => $isHttps,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$name = trim((string) ($input['name'] ?? ''));
$password = (string) ($input['password'] ?? '');
$role = $input['role'] ?? '';
$signupMethod = $input['signup_method'] ?? 'phone';
$phone = isset($input['phone']) ? trim((string) $input['phone']) : null;
$email = isset($input['email']) ? trim((string) $input['email']) : null;

// ---- Validation (server-side, independent of any client-side checks) ----
if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
    jsonResponse(['message' => 'Please enter a valid name.'], 400);
}
if (!in_array($role, ['user', 'mechanic'], true)) {
    jsonResponse(['message' => 'Invalid account type.'], 400);
}
if (strlen($password) < 8) {
    jsonResponse(['message' => 'Password must be at least 8 characters.'], 400);
}

if ($signupMethod === 'email') {
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(['message' => 'Please enter a valid email address.'], 400);
    }
    $identifierColumn = 'email';
    $identifierValue = strtolower($email);
} else {
    if (!$phone || !isValidPakPhone($phone)) {
        jsonResponse(['message' => 'Please enter a valid phone number.'], 400);
    }
    $identifierColumn = 'phone';
    $identifierValue = normalizePakPhone($phone);
}

if (!in_array($identifierColumn, ['email', 'phone'], true)) {
    jsonResponse(['message' => 'Invalid registration identifier.'], 400);
}

// ---- Check for an existing account with the same identifier ----
try {
    $checkStmt = $pdo->prepare("SELECT id FROM users WHERE {$identifierColumn} = :identifier");
    $checkStmt->execute(['identifier' => $identifierValue]);
} catch (PDOException $e) {
    error_log('register.php identifier check failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Something went wrong. Please try again.'], 500);
}
if ($checkStmt->fetch()) {
    // Generic message — doesn't confirm which specific identifier collided,
    // reduces (does not fully prevent, since the field itself is required)
    // account enumeration.
    jsonResponse(['message' => 'An account with these details already exists.'], 409);
}

$passwordHash = password_hash($password, PASSWORD_DEFAULT);

try {
    $insertStmt = $pdo->prepare(
        "INSERT INTO users (name, {$identifierColumn}, password_hash, role, created_at)
         VALUES (:name, :identifier, :password_hash, :role, NOW())"
    );
    $insertStmt->execute([
        'name'          => $name,
        'identifier'    => $identifierValue,
        'password_hash' => $passwordHash,
        'role'          => $role,
    ]);
    $userId = (int) $pdo->lastInsertId();
} catch (PDOException $e) {
    error_log('register.php insert failed: ' . $e->getMessage());
    jsonResponse(['message' => 'Something went wrong. Please try again.'], 500);
}

// If registering as a mechanic, create the corresponding mechanics row
// (unverified by default — must go through admin approval)
if ($role === 'mechanic') {
    try {
        $mechStmt = $pdo->prepare(
            "INSERT INTO mechanics (user_id, shop_name, category, verified, created_at)
             VALUES (:user_id, :shop_name, 'car', 1, NOW())"
        );
        $mechStmt->execute([
            'user_id'   => $userId,
            'shop_name' => $name, // placeholder — mechanic can edit their real shop name on their profile page later
        ]);
    } catch (PDOException $e) {
        error_log('register.php mechanic insert failed: ' . $e->getMessage());
        jsonResponse(['message' => 'Something went wrong. Please try again.'], 500);
    }
}

// ---- Log the new user in immediately, with session fixation protection ----
session_regenerate_id(true);
$_SESSION['user_id'] = $userId;
$_SESSION['role'] = $role;
$_SESSION['name'] = $name;

jsonResponse(['success' => true, 'user_id' => $userId, 'role' => $role], 201);