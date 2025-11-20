<?php
// Include CORS configuration early
require_once __DIR__ . '/../../../utils/cors.php';

// Set universal headers (excluding CORS which is handled by cors.php)
header("Content-Type: application/json; charset=UTF-8");

// Include dependencies
require_once __DIR__ . '/../controllers/user-token-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Include AuthMiddleware

// Instantiate controller
$tokenController = new UserTokenController();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the ID from the query string, which is set by the main API router
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Apply middleware based on method
switch ($method) {
    case 'GET':
        AuthMiddleware::requirePermission('tokens:read'); // All GET requests for tokens require read permission
        break;
    case 'POST':
        AuthMiddleware::requirePermission('tokens:create'); // Creating tokens requires specific permission
        break;
    case 'DELETE':
        AuthMiddleware::requirePermission('tokens:delete'); // Deleting tokens requires specific permission
        break;
    case 'OPTIONS':
        // Allow preflight requests without authentication
        exit();
    default:
        // Method not allowed
        header('HTTP/1.1 405 Method Not Allowed');
        Response::error('Method Not Allowed', 405);
        exit();
}

// Route the request to the appropriate file
$routePath = __DIR__ . '/routes/' . strtolower($method) . '.php';

if (file_exists($routePath)) {
    require $routePath;
} else {
    // This should ideally not be reached if middleware handles all methods
    header('HTTP/1.1 405 Method Not Allowed');
    Response::error('Method Not Allowed', 405);
}
