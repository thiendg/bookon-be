<?php
/**
 * Login API
 * Authenticates user and creates session (and optional persistent login)
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app_session.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/PersistentLogin.php';

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
}

if (empty($data->password)) {
    $errors['password'] = 'Password is required';
}

if (!empty($errors)) {
    Response::validationError($errors);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$user = new User($db);
$sessionModel = new Session($db);
$persistentLogin = new PersistentLogin($db);

// Find user by email
if (!$user->findByEmail($data->email)) {
    Response::error('Invalid email or password', 401);
}

// Verify password
if (!$user->verifyPassword($data->password)) {
    Response::error('Invalid email or password', 401);
}

// Check if email is verified
if (!$user->isEmailVerified()) {
    Response::error('Please verify your email before logging in', 403);
}

// Start session
AppSession::start();
AppSession::regenerate();
AppSession::setUser($user->id, $user->toArray());

// Save session to database
$sessionModel->session_id = session_id();
$sessionModel->user_id = $user->id;
$sessionModel->ip_address = AppSession::getClientIp();
$sessionModel->user_agent = AppSession::getUserAgent();
$sessionModel->payload = json_encode($_SESSION);
$sessionModel->last_activity = time();
$sessionModel->save();

$responseData = [
    'user' => $user->toArray(),
    'session_id' => session_id()
];

// Handle "Remember Me" functionality
if (isset($data->remember_me) && $data->remember_me === true) {
    $token = $persistentLogin->generateToken($user->id);
    
    if ($token) {
        // Set persistent login cookie (30 days)
        setcookie(
            'remember_token',
            $token,
            [
                'expires' => time() + PersistentLogin::EXPIRY_TIME,
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