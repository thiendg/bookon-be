<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php';
require_once __DIR__ . '/../../user_tokens/models/user-token.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

$token = $_GET['token'] ?? null;

if (empty($token)) {
    Response::error('Verification token is required', 400);
}

$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

$tokenRecord = $userTokenModel->findByToken($token);

if (!$tokenRecord || $tokenRecord['type'] !== 'email_verification' || $tokenRecord['expires_at'] < time()) {
    Response::error('Invalid or expired verification token', 400);
}

$userId = $tokenRecord['user_id'];

$userTokenModel->delete($tokenRecord['id']);

$user = $userModel->find($userId);
if (!$user) {
    Response::error('User not found', 404);
}

if ($user['status'] === 'active') {
    Response::success(null, 'Email already verified');
}

if ($userModel->update($userId, ['status' => 'active'])) {
    $updatedUser = $userModel->find($userId);
    unset($updatedUser['password_hash']);
    Response::success([
        'user' => $updatedUser
    ], 'Email verified successfully');
} else {
    Response::error('Failed to verify email', 500);
}
