<?php
/**
 * Login API
 * Authenticates user and creates session (and optional persistent login)
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app-session.php'; // Renamed
require_once __DIR__ . '/../../users/models/user.php'; // BaseModel-based UserModel
require_once __DIR__ . '/../../sessions/models/session.php'; // BaseModel-based SessionModel
require_once __DIR__ . '/../models/persistent-login.php'; // BaseModel-based PersistentLoginModel
require_once __DIR__ . '/../../../utils/token_generator.php'; // For persistent login token handling

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
}

if (empty($data['password'])) {
    $errors['password'] = 'Password is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Initialize models
$userModel = new UserModel();
$sessionModel = new SessionModel(); // Not directly used here, but for consistency
$persistentLoginModel = new PersistentLoginModel();

// Find user by email
$user = $userModel->findOne(['email' => $data['email']]);

if (!$user || !password_verify($data['password'], $user['password_hash'])) {
    Response::error('Invalid email or password', 401);
}

// Check if email is verified
if ($user['status'] !== 'active') {
    Response::error('Please verify your email before logging in', 403);
}

// Remove password hash before sending user data in response
unset($user['password_hash']);

// Start session
AppSession::start();
AppSession::regenerate();
AppSession::setUser($user['id'], [
    'email' => $user['email'],
    'name' => $user['full_name']
]);

$responseData = [
    'user' => $user, // User data from UserModel
    'session_id' => session_id()
];

// Handle "Remember Me" functionality
if (isset($data['remember_me']) && $data['remember_me'] === true) {
    $token = $persistentLoginModel->generateToken($user['id']);
    
    if ($token) {
        // Set persistent login cookie (30 days)
        setcookie(
            'remember_token',
            $token,
            [
                'expires' => time() + PersistentLoginModel::EXPIRY_TIME,
                'path' => '/',
                'domain' => '',
                'secure' => false, // Set to true in production with HTTPS
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );
        
        $responseData['remember_token'] = $token; // For testing - remove in production
    }
}

Response::success($responseData, 'Login successful');
?>