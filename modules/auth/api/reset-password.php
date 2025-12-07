<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php';
require_once __DIR__ . '/../../user_tokens/models/user-token.php';
require_once __DIR__ . '/../../sessions/models/session.php';
require_once __DIR__ . '/../models/persistent-login.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents("php://input"), true);

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

$userModel = new UserModel();
$userTokenModel = new UserTokenModel();
$sessionModel = new SessionModel();
$persistentLoginModel = new PersistentLoginModel();

$tokenRecord = $userTokenModel->findByToken($data['token']);

if (!$tokenRecord || $tokenRecord['type'] !== 'password_reset' || $tokenRecord['expires_at'] < time()) {
    Response::error('Invalid or expired reset token', 400);
}

$userId = $tokenRecord['user_id'];

$userTokenModel->delete($tokenRecord['id']);

$user = $userModel->find($userId);
if (!$user) {
    Response::error('User not found', 404);
}

if ($userModel->update($userId, ['password' => $data['password']])) {
    $sessionModel->deleteWhere(['user_id' => $userId]);
    $persistentLoginModel->deleteByUserId($userId);

    Response::success(null, 'Password reset successfully. Please login with your new password');
} else {
    Response::error('Failed to reset password', 500);
}
