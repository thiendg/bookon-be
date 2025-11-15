<?php
/**
 * Reset Password API
 * Resets user password using token from email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserToken.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/PersistentLogin.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
$errors = [];

if (empty($data->token)) {
    $errors['token'] = 'Reset token is required';
}

if (empty($data->password)) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data->password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$userToken = new UserToken($db);

// Verify token
$userId = $userToken->verifyAndConsume($data->token, UserToken::TYPE_PASSWORD_RESET);

if (!$userId) {
    Response::error('Invalid or expired reset token', 400);
}

// Get user
if (!$user->findById($userId)) {
    Response::error('User not found', 404);
}

// Update password
if ($user->updatePassword($data->password)) {
    // Invalidate all sessions and persistent logins for security
    $sessionModel = new Session($db);
    $sessionModel->deleteByUserId($userId);
    
    $persistentLogin = new PersistentLogin($db);
    $persistentLogin->deleteByUserId($userId);
    
    Response::success(null, 'Password reset successfully. Please login with your new password');
} else {
    Response::error('Failed to reset password', 500);
}
?>