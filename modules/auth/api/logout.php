<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app-session.php';
require_once __DIR__ . '/../../sessions/models/session.php';
require_once __DIR__ . '/../models/persistent-login.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php';
require_once __DIR__ . '/../../../utils/token_generator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

AuthMiddleware::requireLogin();

$sessionModel = new SessionModel();
$persistentLoginModel = new PersistentLoginModel();

$sessionId = session_id();
$userId = AppSession::getUserId();

$sessionModel->delete($sessionId);

if (isset($_COOKIE['remember_token'])) {
    $parts = TokenGenerator::splitPersistentToken($_COOKIE['remember_token']);
    if ($parts) {
        $persistentLoginModel->deleteBySelector($parts['selector']);
    }

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

AppSession::destroy();

Response::success(null, 'Logout successful');
