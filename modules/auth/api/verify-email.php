<?php
/**
 * Verify Email API
 * Verifies user email using token from email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserToken.php';

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Get token from query parameter
$token = $_GET['token'] ?? null;

if (empty($token)) {
    Response::error('Verification token is required', 400);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$userToken = new UserToken($db);

// Verify token
$userId = $userToken->verifyAndConsume($token, UserToken::TYPE_EMAIL_VERIFICATION);

if (!$userId) {
    Response::error('Invalid or expired verification token', 400);
}

// Get user
if (!$user->findById($userId)) {
    Response::error('User not found', 404);
}

// Check if already verified
if ($user->isEmailVerified()) {
    Response::success(null, 'Email already verified');
}

// Mark email as verified
if ($user->markEmailAsVerified()) {
    Response::success([
        'user' => $user->toArray()
    ], 'Email verified successfully');
} else {
    Response::error('Failed to verify email', 500);
}
?>