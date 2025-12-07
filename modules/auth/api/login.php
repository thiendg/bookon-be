<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../utils/app-session.php';
require_once __DIR__ . '/../../users/models/user.php';
require_once __DIR__ . '/../../sessions/models/session.php';
require_once __DIR__ . '/../models/persistent-login.php';
require_once __DIR__ . '/../../../utils/token_generator.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Method not allowed', 405);
}

$data = json_decode(file_get_contents("php://input"), true);
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

$userModel = new UserModel();
$sessionModel = new SessionModel();
$persistentLoginModel = new PersistentLoginModel();

$user = $userModel->findOne(['email' => $data['email']]);

if (!$user || !password_verify($data['password'], $user['password_hash'])) {
    Response::error('Invalid email or password', 401);
}

if ($user['status'] !== 'active') {
    Response::error('Please verify your email before logging in', 403);
}

unset($user['password_hash']);

AppSession::start();
AppSession::regenerate();
AppSession::setUser($user['id'], [
    'email' => $user['email'],
    'name' => $user['full_name']
]);

$responseData = [
    'user' => $user,
    'session_id' => session_id()
];

if (isset($data['remember_me']) && $data['remember_me'] === true) {
    $token = $persistentLoginModel->generateToken($user['id']);

    if ($token) {
        setcookie(
            'remember_token',
            $token,
            [
                'expires' => time() + PersistentLoginModel::EXPIRY_TIME,
                'path' => '/',
                'domain' => '',
                'secure' => false,
                'httponly' => true,
                'samesite' => 'Lax'
            ]
        );

        $responseData['remember_token'] = $token;
    }
}

Response::success($responseData, 'Login successful');
