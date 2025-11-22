<?php
// Include CORS configuration early
require_once __DIR__ . '/../../../utils/cors.php';

// Set universal headers (excluding CORS which is handled by cors.php)
header("Content-Type: application/json; charset=UTF-8");

// Include dependencies
require_once __DIR__ . '/../controllers/user-controller.php';
require_once __DIR__ . '/../../../utils/response.php';
require_once __DIR__ . '/../../../config/middlewares/auth-middleware.php'; // Include AuthMiddleware

// Instantiate controller
$userController = new UserController();

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Get the ID from the query string, which is set by the main API router
$id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Apply middleware based on method
switch ($method) {
    case 'GET':
        // Check for special 'select' action
        if (isset($_GET['action']) && $_GET['action'] === 'select') {
            AuthMiddleware::requirePermission('users:read'); // Select options also require read permission
            $routePath = __DIR__ . '/routes/select.php';
            require $routePath; // Require the select file and exit
            exit();
        }
        AuthMiddleware::requireLogin(); // All other GET requests for users require login
        break;
    case 'POST':
        AuthMiddleware::requirePermission('users:create'); // Creating users requires specific permission
        break;
    case 'PUT':
        AuthMiddleware::requirePermission('users:update'); // Updating users requires specific permission
        break;
    case 'DELETE':
        AuthMiddleware::requirePermission('users:delete'); // Deleting users requires specific permission
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
