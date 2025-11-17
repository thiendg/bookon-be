<?php
/**
 * Logout API
 * Destroys session and removes persistent login
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app-session.php'; // Renamed
require_once __DIR__ . '/../../sessions/models/session.php'; // BaseModel-based SessionModel
require_once __DIR__ . '/../models/persistent-login.php'; // BaseModel-based PersistentLoginModel
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Use AuthMiddleware
require_once __DIR__ . '/../../../utils/token_generator.php'; // For persistent login

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

// Ensure user is logged in before attempting to log out
AuthMiddleware::requireLogin();

// Initialize models
$sessionModel = new SessionModel();
$persistentLoginModel = new PersistentLoginModel();

// Get current session ID and user ID
$sessionId = session_id();
$userId = AppSession::getUserId();

// Delete session from database
$sessionModel->delete($sessionId);

// Delete persistent login if exists
if (isset($_COOKIE['remember_token'])) {
    $parts = TokenGenerator::splitPersistentToken($_COOKIE['remember_token']);
    if ($parts) {
        $persistentLoginModel->deleteBySelector($parts['selector']);
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