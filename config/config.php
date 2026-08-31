<?php
/**
 * Loads environment values from .env and defines them as constants.
 * No external Composer dependency — a small manual parser, matching
 * the "no-Composer" option we agreed on for a project this size.
 */

$envPath = __DIR__ . '/../.env';

if (!file_exists($envPath)) {
    http_response_code(500);
    die('Configuration error: .env file is missing. Copy .env.example to .env and fill in real values.');
}

$env = parse_ini_file($envPath);

// DB_PASS is intentionally NOT required to be non-empty — a blank
// password is the normal, correct default for local MySQL root access
// (XAMPP/WAMP/Laragon all ship this way). Only these three genuinely
// can't be blank.
$required = ['DB_HOST', 'DB_NAME', 'DB_USER'];
foreach ($required as $key) {
    if (!isset($env[$key]) || $env[$key] === '') {
        http_response_code(500);
        die('Configuration error: missing required .env value "' . htmlspecialchars($key) . '".');
    }
}

// DB_PASS just needs to be present as a key (even if empty) — an
// entirely missing line still means the .env is misconfigured.
if (!isset($env['DB_PASS'])) {
    http_response_code(500);
    die('Configuration error: missing required .env value "DB_PASS". Leave it blank (DB_PASS=) rather than omitting the line if your database has no password.');
}

define('DB_HOST', $env['DB_HOST']);
define('DB_NAME', $env['DB_NAME']);
define('DB_USER', $env['DB_USER']);
define('DB_PASS', $env['DB_PASS']);
define('BASE_URL', $env['BASE_URL'] ?? '');

// ---- Optional admin bootstrap account (see .env / .env.example) ----
// Consumed by ensureAdminAccountFromEnv() to keep the admin panel login
// in sync with the values configured here. Empty values disable syncing.
define('ADMIN_NAME', $env['ADMIN_NAME'] ?? '');
define('ADMIN_EMAIL', $env['ADMIN_EMAIL'] ?? '');
define('ADMIN_PASSWORD', $env['ADMIN_PASSWORD'] ?? '');
