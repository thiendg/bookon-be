<?php
/**
 * Register API
 * Creates a new user account and sends email verification token
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserToken.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Get posted data
$data = json_decode(file_get_contents("php://input"));

// Validate input
$errors = [];

if (empty($data->email)) {
    $errors['email'] = 'Email is required';
} elseif (!filter_var($data->email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Invalid email format';
}

if (empty($data->password)) {
    $errors['password'] = 'Password is required';
} elseif (strlen($data->password) < 8) {
    $errors['password'] = 'Password must be at least 8 characters';
}

if (empty($data->name)) {
    $errors['name'] = 'Name is required';
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

// Check if email already exists
if ($user->emailExists($data->email)) {
    Response::error('Email already registered', 409);
}

// Set user properties
$user->email = $data->email;
$user->password_hash = $data->password;
$user->full_name = $data->name;
$user->status = 'unverified'; // Default status
$user->avatar_url = 'default_avatar.png'; // Default avatar
$user->role_id = null; // or set to default role ID
$user->phone_number = null;
$user->address = null;

// Create user
if ($user->create()) {
    // Generate email verification token
    $token = $userToken->generateEmailVerificationToken($user->id);
    
    if ($token) {
        // TODO: Send email with verification link
        // For now, we'll return the token in response (remove this in production)
        // $verificationLink = "http://localhost:3000/verify-email?token=" . $token;
        // sendEmail($user->email, $verificationLink);
        
        Response::success([
            'user' => $user->toArray(),
            'verification_token' => $token  // Remove after send email
        ], 'Registration successful. Please check your email to verify your account.', 201);
    } else {
        Response::error('Failed to generate verification token', 500);
    }
} else {
    Response::error('Failed to create user', 500);
}
?>