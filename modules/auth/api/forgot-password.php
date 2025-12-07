<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php';
require_once __DIR__ . '/../../user_tokens/models/user-token.php';
require_once __DIR__ . '/../../../utils/email-sender.php';
require_once __DIR__ . '/../../../utils/template-loader.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents("php://input"), true);

if (empty($data['email'])) {
    Response::error('Email is required', 400);
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

$user = $userModel->findOne(['email' => $data['email']]);

if (!$user) {
    Response::success(null, 'If your email is registered, you will receive a password reset link');
}

$token = $userTokenModel->createToken($user['id'], 'password_reset');

if ($token) {
    if (!isset($_ENV['APP_BASE_URL'])) {
        require_once __DIR__ . '/../../../utils/env-loader.php';
        loadEnv(__DIR__ . '/../../../.env');
    }
    $appBaseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost';

    $resetLink = $appBaseUrl . '/reset-password?token=' . $token;

    $templateData = [
        'user_name' => $user['full_name'],
        'action_link' => $resetLink,
        'token' => $token
    ];

    try {
        $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/reset-password-email.html', $templateData);
        $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/reset-password-email.txt', $templateData);
    } catch (Exception $e) {
        error_log("Failed to load reset password email template: " . $e->getMessage());
        Response::error("If your email is registered, you will receive a password reset link (failed to load email template).", 500);
    }

    $emailSentResult = sendEmail($user['email'], 'Password Reset Request', $templateHtml, $templateText);

    if ($emailSentResult === true) {
        Response::success(null, 'If your email is registered, you will receive a password reset link');
    } else {
        error_log("Failed to send password reset email to {$user['email']}. Error: {$emailSentResult}");
        Response::error("If your email is registered, you will receive a password reset link (email sending failed). Error: {$emailSentResult}", 500);
    }
} else {
    Response::error('Failed to generate reset token', 500);
}
