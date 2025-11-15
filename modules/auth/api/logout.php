<?php
/**
 * Logout API
 * Destroys session and removes persistent login
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app_session.php';
require_once __DIR__ . '/../../../config/database.php';
require_once __DIR__ . '/../models/Session.php';
require_once __DIR__ . '/../models/PersistentLogin.php';

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Check if user is authenticated
if (!AppSession::isAuthenticated()) {
    Response::error('Not authenticated', 401);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Initialize models
$sessionModel = new Session($db);
$persistentLogin = new PersistentLogin($db);

// Get current session ID and user ID
$sessionId = session_id();
$userId = AppSession::getUserId();

// Delete session from database
$sessionModel->deleteBySessionId($sessionId);

// Delete persistent login if exists
if (isset($_COOKIE['remember_token'])) {
    require_once __DIR__ . '/../../../utils/token_generator.php';
    
    $parts = TokenGenerator::splitPersistentToken($_COOKIE['remember_token']);
    if ($parts) {
        $persistentLogin->deleteBySelector($parts['selector']);
    }
    
    // Delete cookie
    setcookie(
        'remember_token',
        '',
        [
            'expires' => time() - 3600,
            'path' => '/',
            'domain' => '',
            'secure' => false,
            'httponly' => true,
            'samesite' => 'Lax'
        ]
    );
}

// Destroy PHP session
AppSession::destroy();

Response::success(null, 'Logout successful');
?>