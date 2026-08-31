<?php
/**
 * Store a RepairKar contact/support request.
 * Guests may submit; authenticated users are associated with their account.
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';

requireMethod('POST');

if (session_status() === PHP_SESSION_NONE) session_start();
$input = json_decode(file_get_contents('php://input'), true) ?? [];
$name = trim((string) ($input['name'] ?? ''));
$email = strtolower(trim((string) ($input['email'] ?? '')));
$subject = trim((string) ($input['subject'] ?? ''));
$message = trim((string) ($input['message'] ?? ''));

if (mb_strlen($name) < 2 || mb_strlen($name) > 100) jsonResponse(['message' => 'Please enter your name.'], 400);
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 150) jsonResponse(['message' => 'Please enter a valid email address.'], 400);
if (mb_strlen($subject) < 3 || mb_strlen($subject) > 150) jsonResponse(['message' => 'Please enter a subject.'], 400);
if (mb_strlen($message) < 10 || mb_strlen($message) > 5000) jsonResponse(['message' => 'Message must be between 10 and 5000 characters.'], 400);

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

$userId = !empty($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : null;
$stmt = $pdo->prepare(
    'INSERT INTO contact_messages (user_id, name, email, subject, message)
     VALUES (:user_id, :name, :email, :subject, :message)'
);
$stmt->execute([
    'user_id' => $userId,
    'name' => $name,
    'email' => $email,
    'subject' => $subject,
    'message' => $message,
]);

jsonResponse(['success' => true, 'message' => 'Your message has been sent.'], 201);
