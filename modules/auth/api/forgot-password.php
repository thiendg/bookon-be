<?php
/**
 * Forgot Password API
 * Generates password reset token and sends email
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../users/models/user.php'; // BaseModel-based UserModel
require_once __DIR__ . '/../../user_tokens/models/user-token.php'; // BaseModel-based UserTokenModel
require_once __DIR__ . '/../../../utils/email-sender.php'; // Email sending utility
require_once __DIR__ . '/../../../utils/template-loader.php'; // Template loading utility

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"), true);

// Validate input
if (empty($data['email'])) {
    Response::error('Email is required', 400);
}

if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
    Response::error('Invalid email format', 400);
}

// Initialize models
$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

// Find user by email
$user = $userModel->findOne(['email' => $data['email']]);

if (!$user) {
    // Don't reveal if email exists - return success anyway
    Response::success(null, 'If your email is registered, you will receive a password reset link');
}

// Generate password reset token
$token = $userTokenModel->createToken($user['id'], 'password_reset');

if ($token) {
    // Load APP_BASE_URL from environment
    if (!isset($_ENV['APP_BASE_URL'])) {
        require_once __DIR__ . '/../../../utils/env-loader.php';
        loadEnv(__DIR__ . '/../../../.env');
    }
    $appBaseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost'; // Fallback

    $resetLink = $appBaseUrl . '/reset-password?token=' . $token; // Frontend route for reset
    
    // Prepare data for template
    $templateData = [
        'user_name' => $user['full_name'],
        'action_link' => $resetLink,
        'token' => $token
    ];

    // Load and process email template
    try {
        $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/reset-password-email.html', $templateData);
        $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/reset-password-email.txt', $templateData);
    } catch (Exception $e) {
        error_log("Failed to load reset password email template: " . $e->getMessage());
        Response::error("If your email is registered, you will receive a password reset link (failed to load email template).", 500);
    }

    // Send email
    $emailSentResult = sendEmail($user['email'], 'Password Reset Request', $templateHtml, $templateText);

    if ($emailSentResult === true) {
        Response::success(null, 'If your email is registered, you will receive a password reset link');
    } else {
        // If email sending fails, report the specific error
        error_log("Failed to send password reset email to {$user['email']}. Error: {$emailSentResult}");
        Response::error("If your email is registered, you will receive a password reset link (email sending failed). Error: {$emailSentResult}", 500);
    }
} else {
    Response::error('Failed to generate reset token', 500);
}
?>