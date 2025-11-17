<?php
/**
 * Verify Email API
 * Verifies user email using token from email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php'; // BaseModel-based UserModel
require_once __DIR__ . '/../../user_tokens/models/user-token.php'; // BaseModel-based UserTokenModel

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Get token from query parameter
$token = $_GET['token'] ?? null;

if (empty($token)) {
    Response::error('Verification token is required', 400);
}

// Initialize models
$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

// Verify token
$tokenRecord = $userTokenModel->findByToken($token);

if (!$tokenRecord || $tokenRecord['type'] !== 'email_verification' || $tokenRecord['expires_at'] < time()) {
    Response::error('Invalid or expired verification token', 400);
}

$userId = $tokenRecord['user_id'];

// Consume the token
$userTokenModel->delete($tokenRecord['id']);

// Get user
$user = $userModel->find($userId);
if (!$user) {
    Response::error('User not found', 404);
}

// Check if already verified
if ($user['status'] === 'active') {
    Response::success(null, 'Email already verified');
}

// Mark email as verified
if ($userModel->update($userId, ['status' => 'active'])) {
    // Fetch updated user data for response
    $updatedUser = $userModel->find($userId);
    Response::success([
        'user' => $updatedUser
    ], 'Email verified successfully');
} else {
    Response::error('Failed to verify email', 500);
}
?>