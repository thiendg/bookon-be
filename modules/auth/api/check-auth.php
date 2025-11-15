<?php
/**
 * Check Auth API
 * Checks if user is authenticated via session or persistent login
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

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Initialize database connection
$database = new Database();
$db = $database->getConnection();

// Check if user is authenticated via session
AppSession::start();

if (AppSession::isAuthenticated()) {
    $userId = AppSession::getUserId();
    
    // Get user data
    $user = new User($db);
    if ($user->findById($userId)) {
        Response::success([
            'authenticated' => true,
            'user' => $user->toArray(),
            'auth_method' => 'session'
        ]);
    }
}

// Check persistent login (remember me)
if (isset($_COOKIE['remember_token'])) {
    $persistentLogin = new PersistentLogin($db);
    $userId = $persistentLogin->verify($_COOKIE['remember_token']);
    
    if ($userId) {
        // Valid persistent login - create new session
        $user = new User($db);
        if ($user->findById($userId)) {
            // Regenerate session
            AppSession::regenerate();
            AppSession::setUser($userId, $user->toArray());
            
            // Save session to database
            $sessionModel = new Session($db);
            $sessionModel->session_id = session_id();
            $sessionModel->user_id = $userId;
            $sessionModel->ip_address = AppSession::getClientIp();
            $sessionModel->user_agent = AppSession::getUserAgent();
            $sessionModel->payload = json_encode($_SESSION);
            $sessionModel->last_activity = time();
            $sessionModel->save();
            
            // Refresh persistent login token (rotate for security)
            require_once __DIR__ . '/../../../utils/token_generator.php';
            $parts = TokenGenerator::splitPersistentToken($_COOKIE['remember_token']);
            if ($parts) {
                $newToken = $persistentLogin->refresh($userId, $parts['selector']);
                if ($newToken) {
                    setcookie(
                        'remember_token',
                        $newToken,
                        [
                            'expires' => time() + PersistentLogin::EXPIRY_TIME,
                            'path' => '/',
                            'domain' => '',
                            'secure' => false,
                            'httponly' => true,
                            'samesite' => 'Lax'
                        ]
                    );
                }
            }
            
            Response::success([
                'authenticated' => true,
                'user' => $user->toArray(),
                'auth_method' => 'persistent_login'
            ]);
        }
    } else {
        // Invalid token - clear cookie
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
}

// Not authenticated
Response::success([
    'authenticated' => false,
    'user' => null
]);
?>