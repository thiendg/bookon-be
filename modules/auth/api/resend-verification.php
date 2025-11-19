<?php
/**
 * Resend Verification API
 * Generates a new email verification token and sends it to the user
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
    Response::success(null, 'If your email is registered and unverified, a new verification email will be sent.');
}

// Check if user is already verified
if ($user['status'] === 'active') {
    Response::success(null, 'Your email is already verified.');
}

// Invalidate any existing verification tokens for this user
$userTokenModel->deleteWhere(['user_id' => $user['id'], 'type' => 'email_verification']);

// Generate a new email verification token
$token = $userTokenModel->createToken($user['id'], 'email_verification');

if ($token) {
    // Load APP_BASE_URL from environment
    if (!isset($_ENV['APP_BASE_URL'])) {
        require_once __DIR__ . '/../../../utils/env-loader.php';
        loadEnv(__DIR__ . '/../../../.env');
    }
    $appBaseUrl = $_ENV['APP_BASE_URL'] ?? 'http://localhost'; // Fallback

    $verificationLink = $appBaseUrl . '/modules/auth/api/verify-email.php?token=' . $token;
    
    // Prepare data for template
    $templateData = [
        'user_name' => $user['full_name'],
        'action_link' => $verificationLink,
        'token' => $token
    ];

    // Load and process email templates
    try {
        $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.html', $templateData);
        $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.txt', $templateData);
    } catch (Exception $e) {
        error_log("Failed to load verification email template: " . $e->getMessage());
        Response::error("Failed to send verification email (template error).", 500);
    }

    // Send email
    $emailSentResult = sendEmail($user['email'], 'Verify Your Email Address', $templateHtml, $templateText);

    if ($emailSentResult === true) {
        Response::success(null, 'A new verification email has been sent to your address.');
    } else {
        // If email sending fails, report the specific error
        error_log("Failed to send verification email to {$user['email']}. Error: {$emailSentResult}");
        Response::error("Failed to send verification email. Error: {$emailSentResult}", 500);
    }
} else {
    Response::error('Failed to generate verification token', 500);
}
?>