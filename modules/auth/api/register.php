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
$errors = [];

if (empty($data['email'])) {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if (empty($data['password'])) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data['password']) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (empty($data['full_name'])) {
    $errors['full_name'] = 'Name is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

if ($userModel->findOne(['email' => $data['email']])) {
    Response::error('Email already registered', 409);
}

$userData = [
    'email' => $data['email'],
    'password' => $data['password'],
    'full_name' => $data['full_name'],
    'status' => 'unverified',
    'avatar_url' => 'default_avatar.png',
    'role_id' => 2,
    'phone_number' => $data['phone_number'] ?? null,
    'address' => $data['address'] ?? null,
];

if ($userModel->create($userData)) {
    $newUser = $userModel->findOne(['email' => $data['email']]);
    if (!$newUser) {
        Response::error('Failed to retrieve new user data after creation.', 500);
    }
    unset($newUser['password_hash']);

    $token = $userTokenModel->createToken($newUser['id'], 'email_verification');

    if ($token) {
        if (!isset($_ENV['APP_BASE_URL'])) {
            require_once __DIR__ . '/../../../utils/env-loader.php';
            loadEnv(__DIR__ . '/../../../.env');
        }
        $appBaseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost';
        $verificationLink = $appBaseUrl . '/verify-email?token=' . $token;

        $templateData = [
            'user_name' => $newUser['full_name'],
            'action_link' => $verificationLink,
            'token' => $token
        ];

        try {
            $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.html', $templateData);
            $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.txt', $templateData);
        } catch (Exception $e) {
            error_log("Failed to load verification email template: " . $e->getMessage());
            Response::error("Registration successful, but failed to load email template.", 500);
        }

        $emailSentResult = sendEmail($newUser['email'], 'Verify Your Email Address', $templateHtml, $templateText);

        if ($emailSentResult === true) {
            Response::success([
                'user' => $newUser
            ], 'Registration successful. Please check your email to verify your account.', 201);
        } else {
            error_log("Failed to send verification email to {$newUser['email']}. Error: {$emailSentResult}");
            Response::error("Registration successful, but failed to send verification email. Error: {$emailSentResult}", 500);
        }
    } else {
        Response::error('Failed to generate verification token', 500);
    }
} else {
    Response::error('Failed to create user', 500);
}
