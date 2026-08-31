<?php
/**
 * Login by phone or email + password. Returns the SAME generic error
 * whether the identifier doesn't exist or the password is wrong —
 * never reveals which, to prevent account enumeration.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');

if (session_status() === PHP_SESSION_NONE) {
    // Set cookie path to '/' so the session cookie is sent for ALL
    // pages under this domain, not just /api/. Without this, the
    // browser scopes the cookie to /repairKar-fixed-v7/api/ and
    // other pages (profile, bookings, etc.) never receive it.
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
$identifier = trim((string) ($input['identifier'] ?? $input['phone'] ?? $input['email'] ?? ''));
$password = (string) ($input['password'] ?? '');

// Keep the admin panel account in sync with the ADMIN_* .env values
// (creates it on first run, re-hashes when ADMIN_PASSWORD changes).
// A sync failure must never block a regular user's login.
try {
    ensureAdminAccountFromEnv($pdo);
} catch (Exception $e) {
    error_log('Admin account sync failed: ' . $e->getMessage());
}

if ($identifier === '' || $password === '') {
    jsonResponse(['message' => 'Invalid phone/email or password.'], 401);
}

// Determine whether this looks like an email or a phone, and normalize
// accordingly before querying — same logic pattern as forgot-password.html's
// dual-identifier detection.
if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
    $column = 'email';
    $value = strtolower($identifier);
} else {
    $column = 'phone';
    $value = isValidPakPhone($identifier) ? normalizePakPhone($identifier) : $identifier;
}

if (!in_array($column, ['email', 'phone'], true)) {
    jsonResponse(['message' => 'Invalid login identifier.'], 400);
}

$stmt = $pdo->prepare("SELECT id, name, role, password_hash, failed_attempts, locked_until FROM users WHERE {$column} = :value");
$stmt->execute(['value' => $value]);
$user = $stmt->fetch();

// Same generic error whether the account doesn't exist or the password
// is wrong — this is deliberate, not a missing detail.
if (!$user) {
    jsonResponse(['message' => 'Invalid phone/email or password.'], 401);
}

// ---- Brute-force protection using the schema's failed_attempts/locked_until columns ----
if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
    $minutesLeft = (int) ceil((strtotime($user['locked_until']) - time()) / 60);
    jsonResponse(['message' => "Account is temporarily locked. Try again in {$minutesLeft} minute(s)."], 429);
}

if (!password_verify($password, $user['password_hash'])) {
    $newAttempts = (int) $user['failed_attempts'] + 1;
    $maxAttempts = 5;

    if ($newAttempts >= $maxAttempts) {
        // Lock for 15 minutes after 5 consecutive failures
        $lockStmt = $pdo->prepare(
            "UPDATE users SET failed_attempts = :attempts, locked_until = NOW() + INTERVAL 15 MINUTE WHERE id = :id"
        );
        $lockStmt->execute(['attempts' => $newAttempts, 'id' => $user['id']]);
    } else {
        $failStmt = $pdo->prepare("UPDATE users SET failed_attempts = :attempts WHERE id = :id");
        $failStmt->execute(['attempts' => $newAttempts, 'id' => $user['id']]);
    }

    jsonResponse(['message' => 'Invalid phone/email or password.'], 401);
}

// ---- Success — reset failed attempts counter ----
if ((int) $user['failed_attempts'] > 0 || $user['locked_until'] !== null) {
    $resetStmt = $pdo->prepare("UPDATE users SET failed_attempts = 0, locked_until = NULL WHERE id = :id");
    $resetStmt->execute(['id' => $user['id']]);
}

session_regenerate_id(true);
$_SESSION['user_id'] = (int) $user['id'];
$_SESSION['role'] = $user['role'];
$_SESSION['name'] = $user['name'];

jsonResponse([
    'success' => true,
    'user_id' => (int) $user['id'],
    'role'    => $user['role'],
    'name'    => $user['name'],
]);