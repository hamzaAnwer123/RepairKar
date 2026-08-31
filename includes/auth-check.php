<?php
/**
 * Session/role guard. Include at the TOP of any protected page, before
 * any HTML output. Pass $requiredRole to restrict a page to one role
 * (e.g. 'admin') — omit it to just require any logged-in user.
 *
 * Usage:
 *   $requiredRole = 'admin';
 *   require_once __DIR__ . '/../includes/auth-check.php';
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION['user_id'])) {
    header('Location: /login.html');
    exit;
}

if (isset($requiredRole) && ($_SESSION['role'] ?? null) !== $requiredRole) {
    http_response_code(403);
    die('You do not have permission to view this page.');
}