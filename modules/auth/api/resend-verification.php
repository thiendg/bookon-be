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
    Response::success(null, 'If your email is registered and unverified, a new verification email will be sent.');
}

if ($user['status'] === 'active') {
    Response::success(null, 'Your email is already verified.');
}

$userTokenModel->deleteWhere(['user_id' => $user['id'], 'type' => 'email_verification']);

$token = $userTokenModel->createToken($user['id'], 'email_verification');

if ($token) {
    if (!isset($_ENV['APP_BASE_URL'])) {
        require_once __DIR__ . '/../../../utils/env-loader.php';
        loadEnv(__DIR__ . '/../../../.env');
    }
    $appBaseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost';

    $verificationLink = $appBaseUrl . '/modules/auth/api/verify-email.php?token=' . $token;

    $templateData = [
        'user_name' => $user['full_name'],
        'action_link' => $verificationLink,
        'token' => $token
    ];

    try {
        $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.html', $templateData);
        $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.txt', $templateData);
    } catch (Exception $e) {
        error_log("Failed to load verification email template: " . $e->getMessage());
        Response::error("Failed to send verification email (template error).", 500);
    }

    $emailSentResult = sendEmail($user['email'], 'Verify Your Email Address', $templateHtml, $templateText);

    if ($emailSentResult === true) {
        Response::success(null, 'A new verification email has been sent to your address.');
    } else {
        error_log("Failed to send verification email to {$user['email']}. Error: {$emailSentResult}");
        Response::error("Failed to send verification email. Error: {$emailSentResult}", 500);
    }
} else {
    Response::error('Failed to generate verification token', 500);
}
