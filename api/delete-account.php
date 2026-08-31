<?php
/**
 * Permanently delete authenticated user or mechanic account.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');
requireAuth();

$currentUserId = (int) $_SESSION['user_id'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$password = (string) ($input['password'] ?? '');

if ($password === '') {
    jsonResponse(['message' => 'Please enter your password to confirm account deletion.'], 400);
}

// Verify password before deletion
$stmt = $pdo->prepare("SELECT id, password_hash FROM users WHERE id = :id");
$stmt->execute(['id' => $currentUserId]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    jsonResponse(['message' => 'Incorrect password. Account deletion cancelled.'], 401);
}

// First clean up bookings where this user is mechanic if necessary (due to foreign key RESTRICT on mechanics)
try {
    $pdo->beginTransaction();

    // If mechanic, clean bookings
    $mStmt = $pdo->prepare("SELECT id FROM mechanics WHERE user_id = :uid");
    $mStmt->execute(['uid' => $currentUserId]);
    $mechanicId = $mStmt->fetchColumn();

    if ($mechanicId) {
        $delBookings = $pdo->prepare("DELETE FROM bookings WHERE mechanic_id = :mid");
        $delBookings->execute(['mid' => $mechanicId]);
    }

    // Delete user (cascades to mechanics, gigs, bookings as user, messages, reviews)
    $delUser = $pdo->prepare("DELETE FROM users WHERE id = :uid");
    $delUser->execute(['uid' => $currentUserId]);

    $pdo->commit();
} catch (Exception $e) {
    error_log('delete-account.php failed: ' . $e->getMessage());
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['message' => 'Could not delete account. Please try again later.'], 500);
}

// Destroy session
if (session_status() === PHP_SESSION_ACTIVE) {
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}

jsonResponse(['success' => true, 'message' => 'Account deleted successfully.']);
