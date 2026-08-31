<?php
/**
 * Shared helper functions used across API endpoints.
 */

if (!function_exists('mb_strlen')) {
    // Polyfill for environments without the mbstring extension enabled
    // (not guaranteed on every host). Falls back to byte-length, which
    // is close enough for the minimum-length validation checks that use
    // this — exact multibyte character counting isn't required there.
    function mb_strlen(string $string, ?string $encoding = null): int {
        return strlen($string);
    }
}

function jsonResponse($data, int $statusCode = 200): void {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function isValidPakPhone(string $phone): bool {
    // Accepts +923XXXXXXXXX, 03XXXXXXXXX, or 3XXXXXXXXX
    return (bool) preg_match('/^(\+92|0)?3\d{9}$/', $phone);
}

function normalizePakPhone(string $phone): string {
    $digits = preg_replace('/\D/', '', $phone);
    $digits = ltrim($digits, '0');
    if (str_starts_with($digits, '92')) {
        $digits = substr($digits, 2);
    }
    return '+92' . $digits;
}

function requireAuth(): void {
    if (session_status() === PHP_SESSION_NONE) {
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || (isset($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
            || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https');

        // Cookie path must be '/' so the session cookie is sent for ALL pages on this host
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'secure'   => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }
    if (empty($_SESSION['user_id'])) {
        jsonResponse(['message' => 'Authentication required'], 401);
    }
}

function requireMethod(string $method): void {
    if ($_SERVER['REQUEST_METHOD'] !== $method) {
        jsonResponse(['message' => 'Method not allowed'], 405);
    }
}

/**
 * Ensure the users table has a photo_path column (self-migration for
 * databases created before profile photos were introduced). Mirrors the
 * ensureMessageReadColumn() pattern — one information_schema probe per
 * request, then a cached no-op.
 */
function ensureUserPhotoColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $columnStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'users'
           AND COLUMN_NAME = 'photo_path'"
    );
    $columnStmt->execute();
    if (!(int) $columnStmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE users ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL");
    }
    $checked = true;
}

/**
 * Ensure the reviews table has a photo_path column (self-migration for
 * databases created before review photos were introduced). Same pattern
 * as ensureMessageReadColumn()/ensureUserPhotoColumn().
 */
function ensureReviewPhotoColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $columnStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'reviews'
           AND COLUMN_NAME = 'photo_path'"
    );
    $columnStmt->execute();
    if (!(int) $columnStmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE reviews ADD COLUMN photo_path VARCHAR(255) DEFAULT NULL");
    }
    $checked = true;
}

/**
 * Ensure the contact_messages table exists (self-migration for databases
 * where schema.sql was not imported and no contact form has been submitted
 * yet). The public submit-contact.php endpoint creates it on demand, but
 * admin pages (sidebar badge, inbox, dashboard panel) also query it — so
 * they must guarantee the table exists too, otherwise a fresh install
 * would break the whole admin panel with an SQL error.
 */
function ensureContactMessagesTable(PDO $pdo): void {
    $pdo->exec("CREATE TABLE IF NOT EXISTS contact_messages (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED DEFAULT NULL,
        name VARCHAR(100) NOT NULL,
        email VARCHAR(150) NOT NULL,
        subject VARCHAR(150) NOT NULL,
        message TEXT NOT NULL,
        status ENUM('new', 'in_progress', 'resolved') NOT NULL DEFAULT 'new',
        created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        CONSTRAINT fk_contact_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_contact_status (status),
        INDEX idx_contact_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
}

/**
 * Seed/sync the admin panel account from the ADMIN_* values in .env.
 * Runs on every login attempt:
 *   - creates the admin account when it does not exist yet,
 *   - re-hashes the stored password whenever ADMIN_PASSWORD changes,
 *   - promotes an existing user holding ADMIN_EMAIL to role='admin'.
 * The database stays the runtime source of sessions; .env is the source
 * of truth for the admin credentials themselves.
 */
function ensureAdminAccountFromEnv(PDO $pdo): void {
    if (ADMIN_EMAIL === '' || ADMIN_PASSWORD === '' || !filter_var(ADMIN_EMAIL, FILTER_VALIDATE_EMAIL)) {
        return;
    }

    $email = strtolower(ADMIN_EMAIL);
    $name  = (ADMIN_NAME !== '' ? ADMIN_NAME : 'RepairKar Admin');
    $hash  = password_hash(ADMIN_PASSWORD, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id, name, role, password_hash FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if (!$admin) {
        $ins = $pdo->prepare(
            "INSERT INTO users (name, email, password_hash, role) VALUES (:name, :email, :hash, 'admin')"
        );
        $ins->execute(['name' => $name, 'email' => $email, 'hash' => $hash]);
        return;
    }

    // Keep role, display name, and password in sync with .env.
    $inSync = ($admin['role'] === 'admin')
        && ((string) $admin['name'] === $name)
        && password_verify(ADMIN_PASSWORD, (string) $admin['password_hash']);

    if ($inSync) {
        return;
    }

    $upd = $pdo->prepare(
        "UPDATE users SET name = :name, role = 'admin', password_hash = :hash WHERE id = :id"
    );
    $upd->execute(['name' => $name, 'hash' => $hash, 'id' => $admin['id']]);
}

/**
 * Haversine formula — distance in km between two lat/lng points.
 */
function haversineKm(float $lat1, float $lng1, float $lat2, float $lng2): float {
    $earthRadiusKm = 6371;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return round($earthRadiusKm * $c, 2);
}

function ensureMessageReadColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $columnStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'messages'
           AND COLUMN_NAME = 'read_at'"
    );
    $columnStmt->execute();
    if (!(int) $columnStmt->fetchColumn()) {
        $pdo->exec('ALTER TABLE messages ADD COLUMN read_at DATETIME NULL, ADD INDEX idx_messages_read (read_at)');
    }

    $typeStmt = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = 'message_type'");
    $type = (string) $typeStmt->fetchColumn();
    if ($type && strpos($type, "'live_location'") === false) {
        $pdo->exec("ALTER TABLE messages MODIFY message_type ENUM('text', 'image', 'video', 'document', 'audio', 'location', 'live_location') NOT NULL DEFAULT 'text'");
    }

    foreach (['live_location_expires_at' => 'TIMESTAMP NULL DEFAULT NULL', 'original_filename' => 'VARCHAR(255) DEFAULT NULL', 'file_size' => 'INT UNSIGNED DEFAULT NULL'] as $column => $definition) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'messages' AND COLUMN_NAME = :column_name");
        $stmt->execute(['column_name' => $column]);
        if (!(int) $stmt->fetchColumn()) $pdo->exec("ALTER TABLE messages ADD COLUMN {$column} {$definition}");
    }
    $checked = true;
}

/**
 * Delete an uploaded file from disk given its stored web path.
 * Stored values look like '../assets/uploads/reviews/x.jpg' or
 * '/assets/uploads/gigs/x.jpg' — leading '../' and '/' are normalised
 * away, and the file is only unlinked when the resolved path is
 * genuinely inside assets/uploads. The stored string is never trusted
 * on its own (same containment check as api/delete-review.php).
 */
function deleteStoredUpload(?string $storedPath): void {
    if ($storedPath === null || $storedPath === '') return;

    $relative = ltrim(str_replace('\\', '/', $storedPath), '/');
    $relative = preg_replace('~^(\.\./)+~', '', $relative);
    if (strpos($relative, 'assets/uploads/') !== 0) return;

    $baseReal = realpath(__DIR__ . '/../assets/uploads');
    $fileReal = realpath(__DIR__ . '/../' . $relative);
    if ($baseReal && $fileReal && strpos($fileReal, $baseReal) === 0 && is_file($fileReal)) {
        @unlink($fileReal);
    }
}

// ---- Nearby-mechanic tuning (shared by the live map AND SOS dispatch so
// ---- the two can never disagree about what "nearby" means) ----

// Radius (km) within which online mechanics are considered reachable for
// roadside assistance. Mechanics farther than this are not shown on the
// live map and are not matched to SOS bookings.
if (!defined('NEARBY_MECHANIC_RADIUS_KM')) {
    define('NEARBY_MECHANIC_RADIUS_KM', 50.0);
}

// GPS fixes less accurate than this (in meters) are treated as unusable
// for positioning — IP-level fixes can land in the wrong city entirely.
// Presence is still recorded, but the mechanic's stored position is not
// touched, so a wrong-city coordinate can never pose as "live location".
if (!defined('MAX_ACCEPTED_GPS_ACCURACY_M')) {
    define('MAX_ACCEPTED_GPS_ACCURACY_M', 20000.0);
}

// How long a mechanic's stored position stays trusted for "live" results.
// Positions are refreshed on every presence ping that carries a usable
// GPS fix, so only mechanics whose device stopped reporting location age out.
if (!defined('POSITION_FRESH_WINDOW_MIN')) {
    define('POSITION_FRESH_WINDOW_MIN', 15);
}

/**
 * Ensure the mechanics table has a position_at column (self-migration for
 * databases created before position freshness tracking was introduced).
 * Same information_schema-probe pattern as ensureUserPhotoColumn().
 */
function ensureMechanicPositionAtColumn(PDO $pdo): void {
    static $checked = false;
    if ($checked) return;

    $columnStmt = $pdo->prepare(
        "SELECT COUNT(*) FROM information_schema.COLUMNS
         WHERE TABLE_SCHEMA = DATABASE()
           AND TABLE_NAME = 'mechanics'
           AND COLUMN_NAME = 'position_at'"
    );
    $columnStmt->execute();
    if (!(int) $columnStmt->fetchColumn()) {
        $pdo->exec("ALTER TABLE mechanics ADD COLUMN position_at TIMESTAMP NULL DEFAULT NULL");
    }
    $checked = true;
}