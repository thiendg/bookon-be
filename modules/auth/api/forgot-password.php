<?php
/**
 * Forgot Password API
 * Generates password reset token and sends email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserToken.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
if (empty($data->email)) {
    Response::error('Email is required', 400);
}

if (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$userToken = new UserToken($db);

// Find user by email
if (!$user->findByEmail($data->email)) {
    // Don't reveal if email exists - return success anyway
    Response::success(null, 'If your email is registered, you will receive a password reset link');
}

// Generate password reset token
$token = $userToken->generatePasswordResetToken($user->id);

if ($token) {
    // TODO: Send email with reset link
    // For now, return token in response (remove this in production)
    // $resetLink = "http://localhost:3000/reset-password?token=" . $token;
    // sendEmail($user->email, $resetLink);
    
    Response::success([
        'reset_token' => $token // Remove this in production
    ], 'If your email is registered, you will receive a password reset link');
} else {
    Response::error('Failed to generate reset token', 500);
}
?>