<?php
/**
 * Reset Password API
 * Resets user password using token from email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php'; // BaseModel-based UserModel
require_once __DIR__ . '/../../user_tokens/models/user-token.php'; // BaseModel-based UserTokenModel
require_once __DIR__ . '/../../sessions/models/session.php'; // BaseModel-based SessionModel
require_once __DIR__ . '/../models/persistent-login.php'; // BaseModel-based PersistentLoginModel

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
$errors = [];

if (empty($data['token'])) {
    $errors['token'] = 'Reset token is required';
}

if (empty($data['password'])) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data['password']) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Initialize models
$userModel = new UserModel();
$userTokenModel = new UserTokenModel();
$sessionModel = new SessionModel();
$persistentLoginModel = new PersistentLoginModel();

// Verify token
$tokenRecord = $userTokenModel->findByToken($data['token']);

if (!$tokenRecord || $tokenRecord['type'] !== 'password_reset' || $tokenRecord['expires_at'] < time()) {
    Response::error('Invalid or expired reset token', 400);
}

$userId = $tokenRecord['user_id'];

// Consume the token
$userTokenModel->delete($tokenRecord['id']);

// Get user
$user = $userModel->find($userId);
if (!$user) {
    Response::error('User not found', 404);
}

// Update password
if ($userModel->update($userId, ['password' => $data['password']])) {
    // Invalidate all sessions and persistent logins for security
    $sessionModel->deleteWhere(['user_id' => $userId]);
    $persistentLoginModel->deleteByUserId($userId);
    
    Response::success(null, 'Password reset successfully. Please login with your new password');
} else {
    Response::error('Failed to reset password', 500);
}
?>