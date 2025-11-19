<?php
/**
 * Check Auth API
 * Checks if user is authenticated via session
 */

header('Content-Type: application/json');

// Include required files
require_once __DIR__ . '/../../../utils/cors.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Use AuthMiddleware

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Method not allowed', 405);
}

// Use AuthMiddleware to check login status
AuthMiddleware::requireLogin(); // This will handle unauthorized responses and exit if not logged in

// If we reach here, the user is authenticated and their data is in $_SERVER['authenticated_user']
$authenticatedUser = $_SERVER['authenticated_user'];

Response::success([
    'authenticated' => true,
    'user' => $authenticatedUser,
    'auth_method' => 'session' // Assuming session is the primary method after middleware
]);