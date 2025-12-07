<?php
header('Content-Type: application/json');

require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php';

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

AuthMiddleware::requireLogin();

$authenticatedUser = $_SERVER['authenticated_user'];

Response::success([
    'authenticated' => true,
    'user' => $authenticatedUser,
    'auth_method' => 'session'
]);
