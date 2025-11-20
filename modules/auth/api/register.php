<?php
/**
 * Register API
 * Creates a new user account and sends email verification token
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

// Initialize models
$userModel = new UserModel();
$userTokenModel = new UserTokenModel();

// Check if email already exists
if ($userModel->findOne(['email' => $data['email']])) {
    Response::error('Email already registered', 409);
}

// Prepare user data for creation
$userData = [
    'email' => $data['email'],
    'password' => $data['password'], // UserModel will hash this
    'full_name' => $data['full_name'],
    'status' => 'unverified', // Default status
    'avatar_url' => 'default_avatar.png', // Default avatar
    'role_id' => $data['role_id'] ?? null, // Allow setting role_id if provided, otherwise null
    'phone_number' => $data['phone_number'] ?? null,
    'address' => $data['address'] ?? null,
];

// Create user
if ($userModel->create($userData)) {
    // Get the newly created user's ID
    $newUser = $userModel->findOne(['email' => $data['email']]);
    if (!$newUser) {
        Response::error('Failed to retrieve new user data after creation.', 500);
    }

    // Remove password hash before sending user data in response
    unset($newUser['password_hash']);

    // Generate email verification token
    $token = $userTokenModel->createToken($newUser['id'], 'email_verification');
    
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
            'user_name' => $newUser['full_name'],
            'action_link' => $verificationLink,
            'token' => $token
        ];

        // Load and process email template
        try {
            $templateHtml = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.html', $templateData);
            $templateText = loadTemplate(__DIR__ . '/../../../templates/emails/verification-email.txt', $templateData);
        } catch (Exception $e) {
            error_log("Failed to load verification email template: " . $e->getMessage());
            Response::error("Registration successful, but failed to load email template.", 500);
        }
        
        // Send email
        $emailSentResult = sendEmail($newUser['email'], 'Verify Your Email Address', $templateHtml, $templateText);

        if ($emailSentResult === true) {
            Response::success([
                'user' => $newUser
            ], 'Registration successful. Please check your email to verify your account.', 201);
        } else {
            // If email sending fails, report the specific error
            error_log("Failed to send verification email to {$newUser['email']}. Error: {$emailSentResult}");
            Response::error("Registration successful, but failed to send verification email. Error: {$emailSentResult}", 500);
        }
    } else {
        Response::error('Failed to generate verification token', 500);
    }
} else {
    Response::error('Failed to create user', 500);
}
?>